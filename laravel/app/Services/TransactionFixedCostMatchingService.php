<?php

namespace App\Services;

use App\Models\Enums\MatchingSuggestionStatusEnum;
use App\Models\Financial\FixedCost;
use App\Models\Financial\Transaction;
use App\Models\Financial\TransactionMatchingSuggestion;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Zentrale Service-Klasse für das Matching von Transaktionen zu Fixkosten.
 *
 * Score-Aufbau (Option A – betrag-schwer):
 *   Betrag:  max. 60 Punkte  – exakte oder nahe Übereinstimmung, gleiche Vorzeichen
 *   Text:    max. 30 Punkte  – Ähnlichkeit von fixedCost.name ↔ payer/purpose
 *   Datum:   max. 10 Punkte  – Nähe zum nächsten Buchungsdatum
 *
 * Verhalten:
 *   - Score >= threshold UND nur EIN Kandidat    → automatisch verknüpfen
 *   - Score >= threshold UND mehrere Gleichstände → Vorschlag erzeugen (manuell entscheiden)
 *   - Score < threshold                           → nichts tun (skipped)
 */
readonly class TransactionFixedCostMatchingService
{
    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Matcht alle unverknüpften Transaktionen für alle User.
     *
     * @return array{linked: int, suggestion_created: int, skipped: int}
     */
    public function matchAllUnmatchedForAllUsers(): array
    {
        $results = ['linked' => 0, 'suggestion_created' => 0, 'skipped' => 0];

        $userIds = Transaction::query()
            ->whereNull(Transaction::fixed_cost_id)
            ->distinct()
            ->pluck(Transaction::user_id);

        foreach ($userIds as $userId) {
            $partial = $this->matchAllUnmatched((int)$userId);
            $results['linked'] += $partial['linked'];
            $results['suggestion_created'] += $partial['suggestion_created'];
            $results['skipped'] += $partial['skipped'];
        }

        Log::info('[TransactionFixedCostMatchingService] Matching abgeschlossen', $results);

        return $results;
    }

    /**
     * Matcht alle unverknüpften Transaktionen eines Users.
     *
     * @return array{linked: int, suggestion_created: int, skipped: int}
     */
    public function matchAllUnmatched(int $userId): array
    {
        $results = ['linked' => 0, 'suggestion_created' => 0, 'skipped' => 0];

        Transaction::query()
            ->where(Transaction::user_id, $userId)
            ->whereNull(Transaction::fixed_cost_id)
            ->chunkById(100, function (Collection $transactions) use (&$results) {
                foreach ($transactions as $transaction) {
                    $outcome = $this->matchTransaction($transaction);
                    $results[$outcome]++;
                }
            });

        return $results;
    }

    /**
     * Matcht eine einzelne Transaktion gegen alle Fixkosten desselben Users.
     *
     * @return 'linked'|'suggestion_created'|'skipped'
     */
    public function matchTransaction(Transaction $transaction): string
    {
        if ($transaction->fixed_cost_id !== null) {
            return 'skipped';
        }

        $fixedCosts = FixedCost::query()
            ->where(FixedCost::user_id, $transaction->user_id)
            ->get();

        if ($fixedCosts->isEmpty()) {
            return 'skipped';
        }

        // Scores berechnen und absteigend sortieren
        $scored = $fixedCosts
            ->map(fn(FixedCost $fc) => [
                'fixedCost' => $fc,
                'score' => $this->calculateScore($transaction, $fc),
            ])
            ->sortByDesc('score')
            ->values();

        $topScore = (float)$scored->first()['score'];
        $threshold = (float)config('fixed_costs.matching.threshold', 70);

        if ($topScore < $threshold) {
            return 'skipped';
        }

        // Alle Kandidaten mit dem Top-Score (Gleichstand-Prüfung)
        $topMatches = $scored->filter(fn($s) => abs((float)$s['score'] - $topScore) < 0.001)->values();

        if ($topMatches->count() === 1) {
            // Eindeutiger Treffer → automatisch verknüpfen
            $transaction->update([Transaction::fixed_cost_id => $topMatches->first()['fixedCost']->id]);

            return 'linked';
        }

        // Gleichstand → Vorschläge für manuelle Entscheidung erzeugen
        $this->createSuggestions($transaction, $topMatches);

        return 'suggestion_created';
    }

    /**
     * Berechnet den kombinierten Score (0–100) für ein Transaction/FixedCost-Paar.
     */
    public function calculateScore(Transaction $transaction, FixedCost $fixedCost): float
    {
        return $this->calculateAmountScore($transaction, $fixedCost)
            + $this->calculateTextScore($transaction, $fixedCost)
            + $this->calculateDateScore($transaction, $fixedCost);
    }

    // -------------------------------------------------------------------------
    // Score-Teilberechnungen
    // -------------------------------------------------------------------------

    /**
     * Betrag-Score: max. 60 Punkte (Option A – betrag-schwer).
     * Vorzeichen muss übereinstimmen, dann relative Abweichung messen.
     */
    private function calculateAmountScore(Transaction $transaction, FixedCost $fixedCost): float
    {
        $fcAmount = (float)$fixedCost->amount;

        if ($fcAmount == 0) {
            return 0.0;
        }

        $txAmount = (float)$transaction->amount;

        // Vorzeichen muss übereinstimmen
        if (($txAmount <=> 0) !== ($fcAmount <=> 0)) {
            return 0.0;
        }

        $deviation = abs($txAmount - $fcAmount) / abs($fcAmount);

        return match (true) {
            $deviation <= 0.001 => 60.0,
            $deviation <= 0.05 => 45.0,
            $deviation <= 0.10 => 25.0,
            $deviation <= 0.20 => 10.0,
            default => 0.0,
        };
    }

    /**
     * Text-Score: max. 30 Punkte.
     * Vergleicht den Fixkosten-Namen mit payer und purpose der Transaktion.
     */
    private function calculateTextScore(Transaction $transaction, FixedCost $fixedCost): float
    {
        $name = mb_strtolower(trim($fixedCost->name));
        $payer = mb_strtolower(trim($transaction->payer ?? ''));
        $purpose = mb_strtolower(trim($transaction->purpose ?? ''));

        if ($name === '') {
            return 0.0;
        }

        // Direkte Enthaltensein-Prüfung → maximaler Text-Score
        if (str_contains($payer, $name) || str_contains($purpose, $name)) {
            return 30.0;
        }

        // Fuzzy-Vergleich via similar_text
        similar_text($name, $payer, $payerPercent);
        similar_text($name, $purpose, $purposePercent);

        $bestPercent = max($payerPercent, $purposePercent);

        return round($bestPercent / 100 * 30, 2);
    }

    /**
     * Datum-Score: max. 10 Punkte.
     * Bonus wenn Transaktionsdatum nahe am next_booking_date liegt.
     */
    private function calculateDateScore(Transaction $transaction, FixedCost $fixedCost): float
    {
        if ($fixedCost->next_booking_date === null) {
            return 0.0;
        }

        $txDate = Carbon::parse($transaction->date)->startOfDay();
        $bookingDate = $fixedCost->next_booking_date->startOfDay();
        $diffDays = abs($txDate->diffInDays($bookingDate));

        return match (true) {
            $diffDays <= 3 => 10.0,
            $diffDays <= 7 => 7.0,
            $diffDays <= 15 => 4.0,
            $diffDays <= 31 => 1.0,
            default => 0.0,
        };
    }

    // -------------------------------------------------------------------------
    // Interne Hilfsmethoden
    // -------------------------------------------------------------------------

    /**
     * Erzeugt Matching-Vorschläge für Gleichstands-Kandidaten.
     * Bereits vorhandene PENDING-Vorschläge für dieselbe Kombination werden nicht dupliziert.
     *
     * @param \Illuminate\Support\Collection<int, array{fixedCost: FixedCost, score: float}> $topMatches
     */
    private function createSuggestions(Transaction $transaction, \Illuminate\Support\Collection $topMatches): void
    {
        $existingFixedCostIds = TransactionMatchingSuggestion::query()
            ->where(TransactionMatchingSuggestion::transaction_id, $transaction->id)
            ->where(TransactionMatchingSuggestion::status, MatchingSuggestionStatusEnum::PENDING->name)
            ->pluck(TransactionMatchingSuggestion::fixed_cost_id)
            ->toArray();

        foreach ($topMatches as $match) {
            if (in_array($match['fixedCost']->id, $existingFixedCostIds, true)) {
                continue;
            }

            TransactionMatchingSuggestion::create([
                TransactionMatchingSuggestion::transaction_id => $transaction->id,
                TransactionMatchingSuggestion::fixed_cost_id => $match['fixedCost']->id,
                TransactionMatchingSuggestion::score => $match['score'],
                TransactionMatchingSuggestion::status => MatchingSuggestionStatusEnum::PENDING->name,
            ]);
        }
    }
}

