<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\Financial\TransactionCategories\Pages\ListTransactionCategories;
use App\Filament\Admin\Resources\Financial\TransactionCategories\TransactionCategoryResource;
use App\Models\Financial\TransactionCategory;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TransactionCategoryMoveActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_move_action_lists_hierarchy_and_can_move_or_detach_a_category(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $sourceRoot = $this->createCategory('Quellkategorie', $user->id);
        $sourceChild = $this->createCategory('Quell-Unterkategorie', $user->id, $sourceRoot->id);
        $sourceGrandchild = $this->createCategory('Quell-Enkelkategorie', $user->id, $sourceChild->id);
        $targetRoot = $this->createCategory('Zielkategorie', null);
        $targetChild = $this->createCategory('Ziel-Unterkategorie', null, $targetRoot->id);

        self::assertSame(
            [$sourceChild->id, $sourceGrandchild->id],
            $sourceRoot->getDescendantIds(),
        );

        $component = Livewire::actingAs($user)->test(ListTransactionCategories::class);
        $component
            ->assertTableActionExists(
                'moveCategory',
                function (Action $action): bool {
                    $footerLabels = collect($action->getExtraModalFooterActions())
                        ->map(fn(Action $footerAction): string => $footerAction->getLabel())
                        ->values()
                        ->all();

                    return $action->getModalSubmitActionLabel() === 'Verschieben'
                        && $footerLabels === ['Verschieben & Bearbeiten'];
                },
                $sourceRoot,
            )
            ->assertTableActionVisible('moveCategory', $sourceRoot)
            ->assertTableActionHidden('moveCategory', $targetRoot)
            ->mountTableAction('moveCategory', $sourceRoot);

        $schema = $component->instance()->getSchema('mountedActionSchema0');
        $select = $schema?->getComponentByStatePath(TransactionCategory::parent_id);

        self::assertInstanceOf(Select::class, $select);
        self::assertTrue($select->isPreloaded());
        self::assertTrue($select->isSearchable());

        $options = collect($select->getOptionsForJs())->pluck('label', 'value')->all();

        self::assertSame(
            ['Zielkategorie', 'Zielkategorie > Ziel-Unterkategorie'],
            array_values($options),
        );
        self::assertSame(
            'Zielkategorie > Ziel-Unterkategorie',
            $options[(string)$targetChild->id],
        );
        self::assertArrayNotHasKey((string)$sourceRoot->id, $options);
        self::assertArrayNotHasKey((string)$sourceChild->id, $options);
        self::assertArrayNotHasKey((string)$sourceGrandchild->id, $options);

        $component->unmountTableAction();
        $component->callTableAction(
            'moveCategory',
            $sourceRoot,
            ['detach_parent' => true],
        );

        $this->assertDatabaseHas(TransactionCategory::TABLE, [
            TransactionCategory::id => $sourceRoot->id,
            TransactionCategory::parent_id => null,
        ]);

        $component->callTableAction(
            'moveCategory',
            $sourceRoot,
            [TransactionCategory::parent_id => $targetChild->id],
        );

        $this->assertDatabaseHas(TransactionCategory::TABLE, [
            TransactionCategory::id => $sourceRoot->id,
            TransactionCategory::parent_id => $targetChild->id,
        ]);
    }

    private function createCategory(string $name, ?int $userId, ?int $parentId = null): TransactionCategory
    {
        return TransactionCategory::query()->create([
            TransactionCategory::user_id => $userId,
            TransactionCategory::name => $name,
            TransactionCategory::parent_id => $parentId,
            TransactionCategory::active => true,
        ]);
    }

    public function test_move_action_rejects_descendants_and_can_redirect_to_edit(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $source = $this->createCategory('Quelle', $user->id);
        $descendant = $this->createCategory('Nachkomme', $user->id, $source->id);
        $target = $this->createCategory('Ziel', null);

        $component = Livewire::actingAs($user)->test(ListTransactionCategories::class);

        $component->callTableAction(
            'moveCategory',
            $source,
            [TransactionCategory::parent_id => $descendant->id],
        );
        $component->assertHasTableActionErrors([TransactionCategory::parent_id]);
        $component->unmountTableAction();

        $this->assertDatabaseHas(TransactionCategory::TABLE, [
            TransactionCategory::id => $source->id,
            TransactionCategory::parent_id => null,
        ]);

        $component->callTableAction(
            'moveCategory',
            $source,
            [TransactionCategory::parent_id => $target->id],
            ['mode' => 'edit'],
        );

        $this->assertDatabaseHas(TransactionCategory::TABLE, [
            TransactionCategory::id => $source->id,
            TransactionCategory::parent_id => $target->id,
        ]);
        $component->assertRedirect(TransactionCategoryResource::getUrl('edit', [
            'record' => $source,
        ]));
    }
}
