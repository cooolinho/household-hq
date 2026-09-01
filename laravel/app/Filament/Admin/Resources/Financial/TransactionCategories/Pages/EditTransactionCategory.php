<?php

namespace App\Filament\Admin\Resources\Financial\TransactionCategories\Pages;

use App\Filament\Admin\Resources\Financial\TransactionCategories\TransactionCategoryResource;
use App\Models\Financial\TransactionCategory;
use App\Models\Financial\TransactionCategoryCriterion;
use App\Models\Financial\TransactionCategoryRule;
use App\Models\Financial\TransactionCategoryRuleUserSetting;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EditTransactionCategory extends EditRecord
{
    protected static string $resource = TransactionCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn(): bool => !$this->record->isGlobal()),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (!$this->record->isGlobal()) {
            return $data;
        }

        $userId = (int)auth()->id();

        $data['user_extension_rules'] = $this->record->rules()
            ->where(TransactionCategoryRule::user_id, $userId)
            ->with(TransactionCategoryRule::has_many_criteria)
            ->get()
            ->map(function (TransactionCategoryRule $rule): array {
                return [
                    TransactionCategoryRule::operator => $rule->operator,
                    TransactionCategoryRule::active => (bool)$rule->active,
                    TransactionCategoryRule::has_many_criteria => $rule->criteria
                        ->map(function (TransactionCategoryCriterion $criterion): array {
                            return [
                                TransactionCategoryCriterion::field => $criterion->field,
                                TransactionCategoryCriterion::operator => $criterion->operator,
                                TransactionCategoryCriterion::value => $criterion->value,
                                TransactionCategoryCriterion::value_secondary => $criterion->value_secondary,
                                TransactionCategoryCriterion::case_sensitive => (bool)$criterion->case_sensitive,
                            ];
                        })
                        ->toArray(),
                ];
            })
            ->toArray();

        $globalRuleIds = $this->record->rules()
            ->whereNull(TransactionCategoryRule::user_id)
            ->pluck(TransactionCategoryRule::id);

        $data['disabled_global_rule_ids'] = TransactionCategoryRuleUserSetting::query()
            ->where(TransactionCategoryRuleUserSetting::user_id, $userId)
            ->where(TransactionCategoryRuleUserSetting::active, false)
            ->whereIn(TransactionCategoryRuleUserSetting::transaction_category_rule_id, $globalRuleIds)
            ->pluck(TransactionCategoryRuleUserSetting::transaction_category_rule_id)
            ->map(fn(int $id): string => (string)$id)
            ->toArray();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var TransactionCategory $record */
        if (!$record->isGlobal()) {
            unset($data['user_extension_rules'], $data['disabled_global_rule_ids']);

            return parent::handleRecordUpdate($record, $data);
        }

        DB::transaction(function () use ($record, $data): void {
            $this->syncUserExtensionRules($record, $data['user_extension_rules'] ?? []);
            $this->syncGlobalRuleSettings($record, $data['disabled_global_rule_ids'] ?? []);
        });

        return $record->refresh();
    }

    /**
     * @param array<int, array<string, mixed>> $rulesData
     */
    private function syncUserExtensionRules(TransactionCategory $record, array $rulesData): void
    {
        $userId = (int)auth()->id();

        $record->rules()
            ->where(TransactionCategoryRule::user_id, $userId)
            ->delete();

        foreach ($rulesData as $ruleData) {
            $rule = $record->rules()->create([
                TransactionCategoryRule::user_id => $userId,
                TransactionCategoryRule::operator => $ruleData[TransactionCategoryRule::operator] ?? TransactionCategoryRule::OPERATOR_AND,
                TransactionCategoryRule::active => (bool)($ruleData[TransactionCategoryRule::active] ?? true),
            ]);

            foreach (($ruleData[TransactionCategoryRule::has_many_criteria] ?? []) as $criterionData) {
                $rule->criteria()->create([
                    TransactionCategoryCriterion::field => $criterionData[TransactionCategoryCriterion::field] ?? '',
                    TransactionCategoryCriterion::operator => $criterionData[TransactionCategoryCriterion::operator] ?? TransactionCategoryCriterion::OP_EQUALS,
                    TransactionCategoryCriterion::value => $criterionData[TransactionCategoryCriterion::value] ?? '',
                    TransactionCategoryCriterion::value_secondary => $criterionData[TransactionCategoryCriterion::value_secondary] ?? null,
                    TransactionCategoryCriterion::case_sensitive => (bool)($criterionData[TransactionCategoryCriterion::case_sensitive] ?? false),
                ]);
            }
        }
    }

    /**
     * @param array<int, string|int> $disabledGlobalRuleIds
     */
    private function syncGlobalRuleSettings(TransactionCategory $record, array $disabledGlobalRuleIds): void
    {
        $userId = (int)auth()->id();
        $globalRuleIds = $record->rules()
            ->whereNull(TransactionCategoryRule::user_id)
            ->pluck(TransactionCategoryRule::id)
            ->toArray();

        TransactionCategoryRuleUserSetting::query()
            ->where(TransactionCategoryRuleUserSetting::user_id, $userId)
            ->whereIn(TransactionCategoryRuleUserSetting::transaction_category_rule_id, $globalRuleIds)
            ->delete();

        $disabledRuleIds = collect($disabledGlobalRuleIds)
            ->map(fn(string|int $id): int => (int)$id)
            ->filter(fn(int $id): bool => in_array($id, $globalRuleIds, true))
            ->values();

        foreach ($disabledRuleIds as $ruleId) {
            TransactionCategoryRuleUserSetting::query()->create([
                TransactionCategoryRuleUserSetting::user_id => $userId,
                TransactionCategoryRuleUserSetting::transaction_category_rule_id => $ruleId,
                TransactionCategoryRuleUserSetting::active => false,
            ]);
        }
    }
}
