<?php

namespace App\Services;

use App\Models\Enums\RecurringTransactionSuggestionStatusEnum;
use App\Models\Financial\FixedCost;
use App\Models\Financial\RecurringTransactionSuggestion;
use App\Models\Financial\Transaction;
use App\Settings\FixedCostSettings;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class RecurringTransactionSuggestionService
{
    private ?FixedCostSettings $settings;

    public function __construct(?FixedCostSettings $settings = null)
    {
        $this->settings = $settings;
    }

    private function settings(): FixedCostSettings
    {
        return $this->settings ??= app(FixedCostSettings::class);
    }

    /**
     * @return array{created: int, updated: int, skipped: int}
     */
    public function detectForAllUsers(): array
    {
        $results = ['created' => 0, 'updated' => 0, 'skipped' => 0];

        $userIds = Transaction::query()
            ->whereNull(Transaction::fixed_cost_id)
            ->distinct()
            ->pluck(Transaction::user_id);

        foreach ($userIds as $userId) {
            $partial = $this->detectForUser((int)$userId);
            $results['created'] += $partial['created'];
            $results['updated'] += $partial['updated'];
            $results['skipped'] += $partial['skipped'];
        }

        Log::info('[RecurringTransactionSuggestionService] Erkennung abgeschlossen', $results);

        return $results;
    }

    /**
     * @return array{created: int, updated: int, skipped: int}
     */
    public function detectForUser(int $userId): array
    {
        $results = ['created' => 0, 'updated' => 0, 'skipped' => 0];
        $windowMonths = max(1, $this->settings()->recurring_window_months);
        $minOccurrences = max(2, $this->settings()->recurring_min_occurrences);
        $startDate = now()->subMonths($windowMonths)->startOfDay();

        $transactions = Transaction::query()
            ->where(Transaction::user_id, $userId)
            ->whereNull(Transaction::fixed_cost_id)
            ->whereDate(Transaction::date, '>=', $startDate)
            ->orderBy(Transaction::date)
            ->get();

        if ($transactions->isEmpty()) {
            return $results;
        }

        $groups = [];
        foreach ($transactions as $transaction) {
            $fingerprint = $this->buildFingerprint($transaction);

            if (!isset($groups[$fingerprint])) {
                $groups[$fingerprint] = [];
            }

            $groups[$fingerprint][] = $transaction;
        }

        foreach ($groups as $fingerprint => $group) {
            if (count($group) < $minOccurrences) {
                $results['skipped']++;
                continue;
            }

            $representative = end($group);
            if (!$representative instanceof Transaction) {
                $results['skipped']++;
                continue;
            }

            if ($this->likelyAlreadyCoveredByFixedCost($representative)) {
                $results['skipped']++;
                continue;
            }

            $first = reset($group);
            $last = end($group);

            $values = [
                RecurringTransactionSuggestion::payer => $representative->payer,
                RecurringTransactionSuggestion::purpose => $representative->purpose,
                RecurringTransactionSuggestion::name_hint => $this->buildNameHint($representative),
                RecurringTransactionSuggestion::amount => (float)$representative->amount,
                RecurringTransactionSuggestion::amount_currency => $representative->amount_currency,
                RecurringTransactionSuggestion::amount_sign => $this->sign((float)$representative->amount),
                RecurringTransactionSuggestion::occurrence_count => count($group),
                RecurringTransactionSuggestion::first_seen_at => Carbon::parse($first->date)->toDateString(),
                RecurringTransactionSuggestion::last_seen_at => Carbon::parse($last->date)->toDateString(),
                RecurringTransactionSuggestion::sample_transaction_id => $representative->id,
            ];

            $existing = RecurringTransactionSuggestion::query()->firstOrNew([
                RecurringTransactionSuggestion::user_id => $userId,
                RecurringTransactionSuggestion::fingerprint => $fingerprint,
            ]);

            if (!$existing->exists) {
                $existing->fill($values + [
                        RecurringTransactionSuggestion::status => RecurringTransactionSuggestionStatusEnum::PENDING->name,
                    ])->save();

                $results['created']++;
                continue;
            }

            if ($existing->status === RecurringTransactionSuggestionStatusEnum::DISMISSED->name) {
                $results['skipped']++;
                continue;
            }

            $existing->fill($values)->save();
            $results['updated']++;
        }

        return $results;
    }

    private function buildFingerprint(Transaction $transaction): string
    {
        $payer = $this->normalizeText($transaction->payer);
        $purpose = $this->normalizeText($transaction->purpose);
        $amount = number_format(abs((float)$transaction->amount), 2, '.', '');
        $currency = mb_strtolower((string)$transaction->amount_currency);
        $sign = $this->sign((float)$transaction->amount);

        return sha1(implode('|', [$payer, $purpose, $amount, $currency, (string)$sign]));
    }

    private function normalizeText(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        $value = mb_strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/u', ' ', $value) ?? '';
        $value = preg_replace('/\s+/u', ' ', trim($value)) ?? '';

        return $value;
    }

    private function sign(float $value): int
    {
        return $value <=> 0.0;
    }

    private function likelyAlreadyCoveredByFixedCost(Transaction $transaction): bool
    {
        $amount = (float)$transaction->amount;
        $tolerance = ((float)$this->settings()->recurring_amount_tolerance_percent) / 100;
        $min = abs($amount) * (1 - $tolerance);
        $max = abs($amount) * (1 + $tolerance);

        $token = $this->normalizeText($transaction->purpose ?: $transaction->payer);

        $query = FixedCost::query()
            ->where(FixedCost::user_id, $transaction->user_id)
            ->whereRaw('SIGN(' . FixedCost::amount . ') = ?', [$this->sign($amount)])
            ->whereRaw('ABS(' . FixedCost::amount . ') BETWEEN ? AND ?', [$min, $max]);

        if ($token !== '') {
            $query->where(FixedCost::name, 'like', '%' . $token . '%');
        }

        return $query->exists();
    }

    private function buildNameHint(Transaction $transaction): string
    {
        $candidate = trim((string)($transaction->purpose ?: $transaction->payer ?: 'Wiederkehrende Buchung'));

        return mb_substr($candidate, 0, 120);
    }
}

