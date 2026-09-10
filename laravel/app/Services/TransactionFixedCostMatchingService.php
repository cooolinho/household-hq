<?php

namespace App\Services;

use App\Models\Enums\MatchingSuggestionStatusEnum;
use App\Models\Financial\FixedCost;
use App\Models\Financial\Transaction;
use App\Models\Financial\TransactionMatchingSuggestion;
use App\Settings\FixedCostSettings;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Zentrale Service-Klasse für das Matching von Transaktionen zu Fixkosten.
 *
 * Score-Aufbau (Option A – betrag-schwer):
 *   Betrag:    max. 60 Punkte              – exakte oder nahe Übereinstimmung, gleiche Vorzeichen
 *   Text:      max. 30 Punkte              – Ähnlichkeit von fixedCost.name ↔ payer/purpose
 *   Datum:     max. 10 Punkte              – Nähe zum nächsten Buchungsdatum
 *   Kategorie: max. matching_category_weight Punkte – nur aktiv, wenn Fixkosten UND Transaktion
 *              Transaktions-Kategorien haben; Betrag/Text/Datum werden dafür anteilig herunterskaliert
 *              (siehe calculateScore()), damit Fixkosten ohne Kategorie-Verknüpfung unverändert bewertet werden.
 *
 * Verhalten:
 *   - Score >= threshold UND nur EIN Kandidat    → automatisch verknüpfen
 *   - Score >= threshold UND mehrere Gleichstände → Vorschlag erzeugen (manuell entscheiden), außer ein
 *     einzelner Kandidat hat eine direkt übereinstimmende Kategorie → dieser gewinnt den Gleichstand
 *   - Score < threshold                           → nichts tun (skipped)
 *   - Kategorie-Mismatch (matching_category_mismatch_blocks_auto_link) verhindert den Auto-Link eines sonst
 *     eindeutigen Treffers → Vorschlag statt automatischer Verknüpfung
 */
readonly class TransactionFixedCostMatchingService
{
    public function __construct(
        private FixedCostMatchingLearningService $learningService,
        private FixedCostSettings                $settings,
    )
    {
    }

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
            ->with(Transaction::belongs_to_many_transaction_categories)
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
            ->with(FixedCost::belongs_to_many_transaction_categories)
            ->get();

        if ($fixedCosts->isEmpty()) {
            return 'skipped';
        }

        $ruleEvidence = $this->learningService->getRuleEvidenceForTransaction($transaction, $fixedCosts);
        $blockedIds = $ruleEvidence['blocked_fixed_cost_ids'];

        $eligibleFixedCosts = [];
        foreach ($fixedCosts as $fixedCost) {
            if (in_array($fixedCost->id, $blockedIds, true)) {
                continue;
            }

            $eligibleFixedCosts[] = $fixedCost;
        }

        if ($eligibleFixedCosts === []) {
            return 'skipped';
        }

        $ruleOutcome = $this->resolveRuleOutcome($transaction, $eligibleFixedCosts, $ruleEvidence['confidences']);
        if ($ruleOutcome !== null) {
            return $ruleOutcome;
        }

        // Scores berechnen und absteigend sortieren
        $scored = collect($eligibleFixedCosts)
            ->map(fn(FixedCost $fc) => [
                'fixedCost' => $fc,
                'score' => $this->calculateScore($transaction, $fc),
            ])
            ->sortByDesc('score')
            ->values();

        $topScore = (float)$scored->first()['score'];
        $threshold = (float)$this->settings->matching_threshold;

        if ($topScore < $threshold) {
            return 'skipped';
        }

        // Alle Kandidaten mit dem Top-Score (Gleichstand-Prüfung)
        $topMatches = $scored->filter(fn($s) => abs((float)$s['score'] - $topScore) < 0.001)->values();

        if ($topMatches->count() > 1) {
            // Bei Gleichstand gewinnt ein einzelner Kandidat mit direkter Kategorie-Übereinstimmung
            $categoryPreferred = $topMatches
                ->filter(fn(array $match) => $this->hasDirectCategoryMatch($transaction, $match['fixedCost']))
                ->values();

            if ($categoryPreferred->count() === 1) {
                $topMatches = $categoryPreferred;
            }
        }

        if ($topMatches->count() === 1) {
            /** @var FixedCost $fixedCost */
            $fixedCost = $topMatches->first()['fixedCost'];

            // Kategorie-Mismatch verhindert den Auto-Link eines sonst eindeutigen Treffers
            if ($this->settings->matching_category_mismatch_blocks_auto_link
                && $this->hasCategoryMismatch($transaction, $fixedCost)) {
                $this->createSuggestions($transaction, $topMatches);

                return 'suggestion_created';
            }

            // Eindeutiger Treffer → automatisch verknüpfen
            $transaction->update([Transaction::fixed_cost_id => $fixedCost->id]);
            $this->learningService->learnFromAutoLink($transaction, $fixedCost, $topScore);

            return 'linked';
        }

        // Gleichstand → Vorschläge für manuelle Entscheidung erzeugen
        $this->createSuggestions($transaction, $topMatches);

        return 'suggestion_created';
    }

    /**
     * Berechnet den kombinierten Score (0–100) für ein Transaction/FixedCost-Paar.
     *
     * Ist die Kategorie-Komponente aktiv (Fixkosten UND Transaktion haben Kategorien), wird der
     * Betrag/Text/Datum-Anteil auf (100 - matching_category_weight) heruntergerechnet, damit der
     * Gesamtscore weiterhin maximal 100 erreicht. Ohne Kategorie-Verknüpfung bleibt der Score unverändert.
     */
    public function calculateScore(Transaction $transaction, FixedCost $fixedCost): float
    {
        $baseScore = $this->calculateAmountScore($transaction, $fixedCost)
            + $this->calculateTextScore($transaction, $fixedCost)
            + $this->calculateDateScore($transaction, $fixedCost);

        $categoryScore = $this->calculateCategoryScore($transaction, $fixedCost);

        if ($categoryScore === null) {
            return $baseScore;
        }

        $weight = (float)$this->settings->matching_category_weight;

        return round($baseScore * (100 - $weight) / 100 + $categoryScore, 2);
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
     * @param array<int, FixedCost> $eligibleFixedCosts
     * @param array<int, float> $confidences
     * @return 'linked'|'suggestion_created'|null
     */
    private function resolveRuleOutcome(Transaction $transaction, array $eligibleFixedCosts, array $confidences): ?string
    {
        $ruleMinConfidence = (float)$this->settings->matching_learning_rule_confidence_min;

        $scoredByRule = collect($eligibleFixedCosts)
            ->map(function (FixedCost $fixedCost) use ($confidences): array {
                return [
                    'fixedCost' => $fixedCost,
                    'score' => (float)($confidences[$fixedCost->id] ?? 0.0),
                ];
            })
            ->filter(fn(array $entry) => (float)$entry['score'] > 0)
            ->sortByDesc('score')
            ->values();

        if ($scoredByRule->isEmpty()) {
            return null;
        }

        $topScore = (float)$scoredByRule->first()['score'];
        if ($topScore < $ruleMinConfidence) {
            return null;
        }

        $topMatches = $scoredByRule
            ->filter(fn(array $entry) => abs((float)$entry['score'] - $topScore) < 0.001)
            ->values();

        if ($topMatches->count() === 1) {
            /** @var FixedCost $fixedCost */
            $fixedCost = $topMatches->first()['fixedCost'];
            $transaction->update([Transaction::fixed_cost_id => $fixedCost->id]);

            return 'linked';
        }

        $this->createSuggestions($transaction, $topMatches);

        return 'suggestion_created';
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

    /**
     * Kategorie-Score: max. matching_category_weight Punkte.
     * Nur aktiv, wenn sowohl die Fixkosten-Position als auch die Transaktion Transaktions-Kategorien
     * haben – sonst null (Komponente inaktiv, Score bleibt wie ohne Kategorie-Verknüpfung).
     */
    private function calculateCategoryScore(Transaction $transaction, FixedCost $fixedCost): ?float
    {
        $transactionCategoryIds = $this->getTransactionCategoryIds($transaction);
        if ($transactionCategoryIds === []) {
            return null;
        }

        $directCategoryIds = $fixedCost->getDirectCategoryIds();
        if ($directCategoryIds === []) {
            return null;
        }

        $weight = (float)$this->settings->matching_category_weight;

        // Direkter Treffer → volles Gewicht
        if (array_intersect($transactionCategoryIds, $directCategoryIds) !== []) {
            return $weight;
        }

        // Treffer nur über eine Unterkategorie → reduziertes Gewicht
        if ($fixedCost->include_subcategories
            && array_intersect($transactionCategoryIds, $fixedCost->getMatchingCategoryIds()) !== []) {
            return round($weight * 0.7, 2);
        }

        return 0.0;
    }

    // -------------------------------------------------------------------------
    // Interne Hilfsmethoden
    // -------------------------------------------------------------------------

    /**
     * @return array<int>
     */
    private function getTransactionCategoryIds(Transaction $transaction): array
    {
        $categories = $transaction->relationLoaded(Transaction::belongs_to_many_transaction_categories)
            ? $transaction->transactionCategories
            : $transaction->transactionCategories()->get();

        return $categories
            ->map(static fn($category): int => (int)$category->getKey())
            ->values()
            ->all();
    }

    /**
     * True, wenn Fixkosten UND Transaktion Kategorien haben, sich diese aber nicht überschneiden.
     */
    private function hasCategoryMismatch(Transaction $transaction, FixedCost $fixedCost): bool
    {
        $transactionCategoryIds = $this->getTransactionCategoryIds($transaction);
        if ($transactionCategoryIds === []) {
            return false;
        }

        $fixedCostCategoryIds = $fixedCost->getMatchingCategoryIds();
        if ($fixedCostCategoryIds === []) {
            return false;
        }

        return array_intersect($transactionCategoryIds, $fixedCostCategoryIds) === [];
    }

    /**
     * True, wenn die Transaktion eine der direkt (ohne Unterkategorien) verknüpften Kategorien der
     * Fixkosten-Position trägt.
     */
    private function hasDirectCategoryMatch(Transaction $transaction, FixedCost $fixedCost): bool
    {
        $transactionCategoryIds = $this->getTransactionCategoryIds($transaction);
        if ($transactionCategoryIds === []) {
            return false;
        }

        return array_intersect($transactionCategoryIds, $fixedCost->getDirectCategoryIds()) !== [];
    }

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

