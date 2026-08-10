<?php

namespace App\Services;

use App\Models\Financial\FixedCost;
use App\Models\Financial\FixedCostMatchingRule;
use App\Models\Financial\Transaction;
use App\Models\Financial\TransactionMatchingSuggestion;
use App\Settings\FixedCostSettings;
use Illuminate\Support\Collection;

class FixedCostMatchingLearningService
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

    public function learnFromAcceptedSuggestion(TransactionMatchingSuggestion $suggestion): void
    {
        $transaction = $suggestion->transaction;
        $fixedCost = $suggestion->fixedCost;

        if ($transaction === null || $fixedCost === null) {
            return;
        }

        $this->learnPositive(
            $transaction,
            $fixedCost,
            $this->settings()->matching_learning_accepted_positive_weight,
            FixedCostMatchingRule::SOURCE_SUGGESTION_ACCEPT
        );
    }

    private function learnPositive(Transaction $transaction, FixedCost $fixedCost, float $weight, string $source): void
    {
        if ($weight <= 0) {
            return;
        }

        foreach ($this->buildDescriptors($transaction) as $descriptor) {
            $this->upsertRule($transaction, $fixedCost, $descriptor, $weight, 0.0, $source);
        }
    }

    /**
     * @return array<int, array{fingerprint: string, payer_token: ?string, purpose_token: ?string}>
     */
    private function buildDescriptors(Transaction $transaction): array
    {
        $payerToken = $this->normalizeText($transaction->payer);
        $purposeToken = $this->normalizeText($transaction->purpose);
        $descriptors = [];

        if ($payerToken !== '') {
            $descriptors[] = [
                'fingerprint' => sha1('payer|' . $payerToken),
                'payer_token' => $payerToken,
                'purpose_token' => null,
            ];
        }

        if ($purposeToken !== '') {
            $descriptors[] = [
                'fingerprint' => sha1('purpose|' . $purposeToken),
                'payer_token' => null,
                'purpose_token' => $purposeToken,
            ];
        }

        if ($payerToken !== '' && $purposeToken !== '') {
            $descriptors[] = [
                'fingerprint' => sha1('payer-purpose|' . $payerToken . '|' . $purposeToken),
                'payer_token' => $payerToken,
                'purpose_token' => $purposeToken,
            ];
        }

        return $descriptors;
    }

    private function normalizeText(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        $value = mb_strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/u', ' ', $value) ?? '';
        $value = preg_replace('/\s+/u', ' ', trim($value)) ?? '';

        return mb_substr($value, 0, 120);
    }

    /**
     * @param array{fingerprint: string, payer_token: ?string, purpose_token: ?string} $descriptor
     */
    private function upsertRule(
        Transaction $transaction,
        FixedCost   $fixedCost,
        array       $descriptor,
        float       $positiveWeight,
        float       $negativeWeight,
        string      $source,
    ): void
    {
        $absAmount = abs((float)$transaction->amount);
        $tolerance = ((float)$this->settings()->matching_learning_amount_tolerance_percent) / 100;
        $min = round($absAmount * (1 - $tolerance), 2);
        $max = round($absAmount * (1 + $tolerance), 2);

        $rule = FixedCostMatchingRule::query()->firstOrNew([
            FixedCostMatchingRule::user_id => $transaction->user_id,
            FixedCostMatchingRule::fixed_cost_id => $fixedCost->id,
            FixedCostMatchingRule::fingerprint => $descriptor['fingerprint'],
        ]);

        $rule->fill([
            FixedCostMatchingRule::payer_token => $descriptor['payer_token'],
            FixedCostMatchingRule::purpose_token => $descriptor['purpose_token'],
            FixedCostMatchingRule::amount_sign => $this->sign((float)$transaction->amount),
            FixedCostMatchingRule::last_source => $source,
        ]);

        $rule->amount_min = $rule->amount_min === null
            ? $min
            : min((float)$rule->amount_min, $min);

        $rule->amount_max = $rule->amount_max === null
            ? $max
            : max((float)$rule->amount_max, $max);

        $rule->positive_weight = round((float)$rule->positive_weight + $positiveWeight, 2);
        $rule->negative_weight = round((float)$rule->negative_weight + $negativeWeight, 2);

        $rule->save();
    }

    private function sign(float $value): int
    {
        return $value <=> 0.0;
    }

    public function learnFromRejectedSuggestion(TransactionMatchingSuggestion $suggestion): void
    {
        $transaction = $suggestion->transaction;
        $fixedCost = $suggestion->fixedCost;

        if ($transaction === null || $fixedCost === null) {
            return;
        }

        $this->learnNegative(
            $transaction,
            $fixedCost,
            $this->settings()->matching_learning_rejected_negative_weight,
            FixedCostMatchingRule::SOURCE_SUGGESTION_REJECT
        );
    }

    private function learnNegative(Transaction $transaction, FixedCost $fixedCost, float $weight, string $source): void
    {
        if ($weight <= 0) {
            return;
        }

        foreach ($this->buildDescriptors($transaction) as $descriptor) {
            $this->upsertRule($transaction, $fixedCost, $descriptor, 0.0, $weight, $source);
        }
    }

    public function learnFromAutoLink(Transaction $transaction, FixedCost $fixedCost, float $score): void
    {
        if (!$this->settings()->matching_learning_enabled) {
            return;
        }

        $minScore = (float)$this->settings()->matching_learning_auto_learn_min_score;
        if ($score < $minScore) {
            return;
        }

        $this->learnPositive(
            $transaction,
            $fixedCost,
            $this->settings()->matching_learning_auto_positive_weight,
            FixedCostMatchingRule::SOURCE_AUTO_LINK
        );
    }

    /**
     * @param Collection<int, FixedCost> $fixedCosts
     * @return array{
     *   blocked_fixed_cost_ids: array<int>,
     *   confidences: array<int, float>
     * }
     */
    public function getRuleEvidenceForTransaction(Transaction $transaction, Collection $fixedCosts): array
    {
        if (!$this->settings()->matching_learning_enabled) {
            return ['blocked_fixed_cost_ids' => [], 'confidences' => []];
        }

        if ($fixedCosts->isEmpty()) {
            return ['blocked_fixed_cost_ids' => [], 'confidences' => []];
        }

        $descriptors = $this->buildDescriptors($transaction);
        if ($descriptors === []) {
            return ['blocked_fixed_cost_ids' => [], 'confidences' => []];
        }

        $fingerprints = array_values(array_unique(array_column($descriptors, 'fingerprint')));
        $txAbsAmount = abs((float)$transaction->amount);
        $txAmountSign = $this->sign((float)$transaction->amount);
        $blockThreshold = $this->settings()->matching_learning_reject_block_threshold;

        $rules = FixedCostMatchingRule::query()
            ->where(FixedCostMatchingRule::user_id, $transaction->user_id)
            ->whereIn(FixedCostMatchingRule::fixed_cost_id, $fixedCosts->pluck(FixedCost::id))
            ->whereIn(FixedCostMatchingRule::fingerprint, $fingerprints)
            ->get();

        $evidence = [];
        $blocked = [];

        foreach ($rules as $rule) {
            if ((int)$rule->amount_sign !== $txAmountSign) {
                continue;
            }

            if ($rule->amount_min !== null && $txAbsAmount < (float)$rule->amount_min) {
                continue;
            }

            if ($rule->amount_max !== null && $txAbsAmount > (float)$rule->amount_max) {
                continue;
            }

            $fixedCostId = (int)$rule->fixed_cost_id;

            if (!isset($evidence[$fixedCostId])) {
                $evidence[$fixedCostId] = ['pos' => 0.0, 'neg' => 0.0];
            }

            $evidence[$fixedCostId]['pos'] += (float)$rule->positive_weight;
            $evidence[$fixedCostId]['neg'] += (float)$rule->negative_weight;

            if ((float)$rule->negative_weight >= $blockThreshold) {
                $blocked[$fixedCostId] = true;
            }
        }

        $confidences = [];
        foreach ($evidence as $fixedCostId => $values) {
            $pos = (float)$values['pos'];
            $neg = (float)$values['neg'];

            if ($pos <= 0.0) {
                continue;
            }

            $confidences[$fixedCostId] = round(($pos / max(0.01, ($pos + $neg))) * 100, 2);
        }

        return [
            'blocked_fixed_cost_ids' => array_map('intval', array_keys($blocked)),
            'confidences' => $confidences,
        ];
    }
}

