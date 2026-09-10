<?php

namespace App\Services\FixedCost;

use App\Models\Enums\MatchingSuggestionStatusEnum;
use App\Models\Financial\FixedCost;
use App\Models\Financial\FixedCostBookingDateSuggestion;
use App\Models\Financial\Transaction;
use App\Services\FixedCostNextBookingDateCalculator;
use App\Settings\FixedCostSettings;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Vergleicht den statistisch aus zugeordneten Transaktionen ermittelten Buchungstag mit dem
 * hinterlegten `next_booking_date` jeder Fixkost und pflegt daraus Vorschläge, die der Benutzer
 * annehmen oder ablehnen kann (siehe FixedCostBookingDateSuggestionResource).
 */
class FixedCostBookingDateSuggestionService
{
    private const int MAX_CATCH_UP_ITERATIONS = 24;

    private ?FixedCostSettings $settings;

    public function __construct(
        private readonly FixedCostBookingDayAnalyzer        $analyzer,
        private readonly FixedCostNextBookingDateCalculator $bookingDates,
        ?FixedCostSettings                                  $settings = null,
    )
    {
        $this->settings = $settings;
    }

    /**
     * @return array{created: int, updated: int, skipped: int}
     */
    public function detectForAllUsers(): array
    {
        $results = ['created' => 0, 'updated' => 0, 'skipped' => 0];

        $userIds = FixedCost::query()
            ->whereNotNull(FixedCost::next_booking_date)
            ->distinct()
            ->pluck(FixedCost::user_id);

        foreach ($userIds as $userId) {
            $partial = $this->detectForUser((int)$userId);
            $results['created'] += $partial['created'];
            $results['updated'] += $partial['updated'];
            $results['skipped'] += $partial['skipped'];
        }

        Log::info('[FixedCostBookingDateSuggestionService] Erkennung abgeschlossen', $results);

        return $results;
    }

    /**
     * @return array{created: int, updated: int, skipped: int}
     */
    public function detectForUser(int $userId): array
    {
        $results = ['created' => 0, 'updated' => 0, 'skipped' => 0];

        $windowMonths = max(1, $this->settings()->booking_date_window_months);
        $minOccurrences = max(1, $this->settings()->booking_date_min_occurrences);
        $minDeviationDays = max(1, $this->settings()->booking_date_min_deviation_days);

        $today = CarbonImmutable::today();
        $analyzedFrom = $today->subMonths($windowMonths)->startOfDay();

        $fixedCosts = FixedCost::query()
            ->where(FixedCost::user_id, $userId)
            ->whereNotNull(FixedCost::next_booking_date)
            ->with(FixedCost::has_many_transactions)
            ->get();

        foreach ($fixedCosts as $fixedCost) {
            $transactions = $fixedCost->transactions
                ->filter(fn(Transaction $transaction): bool => CarbonImmutable::parse($transaction->{Transaction::date})
                    ->greaterThanOrEqualTo($analyzedFrom));

            $analysis = $this->analyzer->analyze(
                $fixedCost,
                $transactions,
                $minOccurrences,
                $analyzedFrom,
                $today,
            );

            if ($analysis === null || !$analysis->hasDeviation($minDeviationDays)) {
                // Keine (mehr) relevante Abweichung -> offene Vorschläge dieser Fixkost verwerfen.
                FixedCostBookingDateSuggestion::query()
                    ->where(FixedCostBookingDateSuggestion::fixed_cost_id, $fixedCost->id)
                    ->where(FixedCostBookingDateSuggestion::status, MatchingSuggestionStatusEnum::PENDING->name)
                    ->delete();

                $results['skipped']++;
                continue;
            }

            $alreadyRejected = FixedCostBookingDateSuggestion::query()
                ->where(FixedCostBookingDateSuggestion::fixed_cost_id, $fixedCost->id)
                ->where(FixedCostBookingDateSuggestion::suggested_day, $analysis->suggestedDay)
                ->where(FixedCostBookingDateSuggestion::status, MatchingSuggestionStatusEnum::REJECTED->name)
                ->exists();

            if ($alreadyRejected) {
                $results['skipped']++;
                continue;
            }

            $suggestedDate = $this->resolveSuggestedDate($fixedCost, $analysis->suggestedDay, $today);

            $existing = FixedCostBookingDateSuggestion::query()
                ->where(FixedCostBookingDateSuggestion::fixed_cost_id, $fixedCost->id)
                ->where(FixedCostBookingDateSuggestion::suggested_day, $analysis->suggestedDay)
                ->first();

            $values = [
                FixedCostBookingDateSuggestion::suggested_date => $suggestedDate->toDateString(),
                FixedCostBookingDateSuggestion::current_date => $fixedCost->{FixedCost::next_booking_date}?->toDateString(),
                FixedCostBookingDateSuggestion::deviation_days => $analysis->deviationDays,
                FixedCostBookingDateSuggestion::sample_count => $analysis->sampleCount,
                FixedCostBookingDateSuggestion::analyzed_from => $analysis->analyzedFrom->toDateString(),
                FixedCostBookingDateSuggestion::analyzed_to => $analysis->analyzedTo->toDateString(),
            ];

            if ($existing !== null) {
                $existing->fill($values)->save();
                $results['updated']++;
                continue;
            }

            FixedCostBookingDateSuggestion::query()->create($values + [
                    FixedCostBookingDateSuggestion::fixed_cost_id => $fixedCost->id,
                    FixedCostBookingDateSuggestion::suggested_day => $analysis->suggestedDay,
                    FixedCostBookingDateSuggestion::status => MatchingSuggestionStatusEnum::PENDING->name,
                ]);

            $results['created']++;
        }

        return $results;
    }

    private function settings(): FixedCostSettings
    {
        return $this->settings ??= app(FixedCostSettings::class);
    }

    /**
     * Verschiebt nur den Tag im aktuellen Buchungsmonat, ohne den Intervall-Rhythmus anzutasten.
     * Liegt das Ergebnis nicht mehr in der Zukunft, wird mit dem Intervall-Rechner so oft
     * weitergeschoben, bis wieder ein zukünftiger Termin mit dem vorgeschlagenen Tag erreicht ist.
     */
    private function resolveSuggestedDate(FixedCost $fixedCost, int $suggestedDay, CarbonImmutable $today): CarbonImmutable
    {
        $current = $fixedCost->{FixedCost::next_booking_date}->toImmutable()->startOfDay();
        $candidate = $current->setDay(min($suggestedDay, $current->daysInMonth));

        $iterations = 0;
        while ($candidate->lessThanOrEqualTo($today) && $iterations++ < self::MAX_CATCH_UP_ITERATIONS) {
            try {
                $next = $this->bookingDates->addInterval($fixedCost, $candidate);
            } catch (InvalidArgumentException) {
                break;
            }

            if ($next->lessThanOrEqualTo($candidate)) {
                break;
            }

            $candidate = $next;
        }

        return $candidate;
    }
}
