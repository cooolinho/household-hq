<?php

namespace App\Filament\Admin\Resources\Financial\TransactionCategories\Pages;

use App\Filament\Admin\Resources\Financial\TransactionCategories\Support\TransactionCategoryBreadcrumbs;
use App\Filament\Admin\Resources\Financial\TransactionCategories\TransactionCategoryResource;
use App\Models\Financial\TransactionCategory;
use App\Models\Financial\TransactionCategoryCriterion;
use App\Models\Financial\TransactionCategoryRule;
use App\Models\Financial\TransactionCategoryRuleUserSetting;
use App\Services\TransactionCategoryRulePreviewService;
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

        $globalRules = $this->record->rules()
            ->whereNull(TransactionCategoryRule::user_id)
            ->with(TransactionCategoryRule::has_many_criteria)
            ->orderBy(TransactionCategoryRule::id)
            ->get();

        $disabledGlobalRuleIds = TransactionCategoryRuleUserSetting::query()
            ->where(TransactionCategoryRuleUserSetting::user_id, $userId)
            ->where(TransactionCategoryRuleUserSetting::active, false)
            ->whereIn(
                TransactionCategoryRuleUserSetting::transaction_category_rule_id,
                $globalRules->modelKeys(),
            )
            ->pluck(TransactionCategoryRuleUserSetting::transaction_category_rule_id)
            ->map(fn(int $id): int => (int)$id);

        $previewService = app(TransactionCategoryRulePreviewService::class);

        $data['global_rule_settings'] = $globalRules
            ->map(fn(TransactionCategoryRule $rule): array => [
                'rule_id' => (string)$rule->getKey(),
                'disabled' => $disabledGlobalRuleIds->containsStrict((int)$rule->getKey()),
                'preview' => $previewService->build($rule),
            ])
            ->toArray();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var TransactionCategory $record */
        if (!$record->isGlobal()) {
            unset($data['user_extension_rules'], $data['global_rule_settings']);

            return parent::handleRecordUpdate($record, $data);
        }

        DB::transaction(function () use ($record, $data): void {
            $this->syncUserExtensionRules($record, $data['user_extension_rules'] ?? []);
            $this->syncGlobalRuleSettings($record, $data['global_rule_settings'] ?? []);
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
     * @param array<int, array<string, mixed>> $globalRuleSettings
     */
    private function syncGlobalRuleSettings(TransactionCategory $record, array $globalRuleSettings): void
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

        $disabledRuleIds = collect($globalRuleSettings)
            ->filter(fn(mixed $setting): bool => is_array($setting) && (bool)($setting['disabled'] ?? false))
            ->map(fn(mixed $setting): int => (int)($setting['rule_id'] ?? 0))
            ->filter(fn(int $id): bool => in_array($id, $globalRuleIds, true))
            ->unique()
            ->values();

        foreach ($disabledRuleIds as $ruleId) {
            TransactionCategoryRuleUserSetting::query()->create([
                TransactionCategoryRuleUserSetting::user_id => $userId,
                TransactionCategoryRuleUserSetting::transaction_category_rule_id => $ruleId,
                TransactionCategoryRuleUserSetting::active => false,
            ]);
        }
    }

    public function getBreadcrumbs(): array
    {
        /** @var TransactionCategory $record */
        $record = $this->getRecord();

        return TransactionCategoryBreadcrumbs::forEdit($record);
    }
}
