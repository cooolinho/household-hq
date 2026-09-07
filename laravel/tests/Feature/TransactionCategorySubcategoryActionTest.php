<?php

namespace Tests\Feature;

use App\Filament\App\Resources\Financial\TransactionCategories\Pages\ListTransactionCategories;
use App\Filament\App\Resources\Financial\TransactionCategories\TransactionCategoryResource;
use App\Models\Financial\TransactionCategory;
use App\Models\User;
use Filament\Actions\Action;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TransactionCategorySubcategoryActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_subcategory_action_supports_all_create_modes(): void
    {
        $user = User::factory()->create();
        $parent = TransactionCategory::query()->create([
            TransactionCategory::user_id => null,
            TransactionCategory::name => 'Globale Hauptkategorie',
            TransactionCategory::parent_id => null,
            TransactionCategory::active => true,
        ]);

        $component = Livewire::actingAs($user)->test(ListTransactionCategories::class);

        $component->assertTableActionExists(
            'createSubcategory',
            function (Action $action): bool {
                $footerLabels = collect($action->getExtraModalFooterActions())
                    ->map(fn(Action $footerAction): string => $footerAction->getLabel())
                    ->values()
                    ->all();

                return $action->getModalSubmitActionLabel() === 'Erstellen'
                    && $footerLabels === ['Erstellen & Neu', 'Erstellen und Bearbeiten'];
            },
            $parent,
        );

        $component->callTableAction(
            'createSubcategory',
            $parent,
            [TransactionCategory::name => 'Erste Unterkategorie'],
        );

        $this->assertDatabaseHas(TransactionCategory::TABLE, [
            TransactionCategory::user_id => $user->id,
            TransactionCategory::name => 'Erste Unterkategorie',
            TransactionCategory::parent_id => $parent->id,
            TransactionCategory::active => true,
        ]);

        $component->callTableAction(
            'createSubcategory',
            $parent,
            [TransactionCategory::name => 'Zweite Unterkategorie'],
            ['mode' => 'new'],
        );

        self::assertNotEmpty($component->instance()->mountedActions);
        $component->assertTableActionDataSet([
            TransactionCategory::name => null,
        ]);

        $this->assertDatabaseHas(TransactionCategory::TABLE, [
            TransactionCategory::user_id => $user->id,
            TransactionCategory::name => 'Zweite Unterkategorie',
            TransactionCategory::parent_id => $parent->id,
        ]);

        $component->unmountTableAction();

        $component->callTableAction(
            'createSubcategory',
            $parent,
            [TransactionCategory::name => 'Dritte Unterkategorie'],
            ['mode' => 'edit'],
        );

        $createdCategory = TransactionCategory::query()
            ->where(TransactionCategory::name, 'Dritte Unterkategorie')
            ->firstOrFail();

        $component->assertRedirect(TransactionCategoryResource::getUrl('edit', [
            'record' => $createdCategory,
        ]));
    }

    public function test_header_create_actions_follow_the_current_parent_filter(): void
    {
        $user = User::factory()->create();
        $parent = TransactionCategory::query()->create([
            TransactionCategory::user_id => null,
            TransactionCategory::name => 'Globale Hauptkategorie',
            TransactionCategory::parent_id => null,
            TransactionCategory::active => true,
        ]);

        $component = Livewire::actingAs($user)->test(ListTransactionCategories::class);

        $component
            ->assertActionVisible('create')
            ->assertActionHidden('createSubcategory');

        $component->set('tableFilters', [
            TransactionCategory::parent_id => [
                'value' => $parent->id,
            ],
        ]);

        $component
            ->assertActionHidden('create')
            ->assertActionVisible('createSubcategory')
            ->assertActionExists('edit')
            ->assertActionExists('delete')
            ->assertActionExists('viewCategoryTransactions')
            ->assertActionExists('moveCategory')
            ->callAction('createSubcategory', [
                TransactionCategory::name => 'Header-Unterkategorie',
            ]);

        $this->assertDatabaseHas(TransactionCategory::TABLE, [
            TransactionCategory::user_id => $user->id,
            TransactionCategory::name => 'Header-Unterkategorie',
            TransactionCategory::parent_id => $parent->id,
            TransactionCategory::active => true,
        ]);
    }
}
