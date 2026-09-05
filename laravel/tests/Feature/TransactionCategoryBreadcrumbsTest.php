<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\Financial\TransactionCategories\Support\TransactionCategoryBreadcrumbs;
use App\Filament\Admin\Resources\Financial\TransactionCategories\TransactionCategoryResource;
use App\Models\Financial\TransactionCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionCategoryBreadcrumbsTest extends TestCase
{
    use RefreshDatabase;

    public function test_breadcrumbs_build_root_nested_and_edit_paths(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $root = TransactionCategory::query()->create([
            TransactionCategory::user_id => null,
            TransactionCategory::name => 'Hauptkategorie',
            TransactionCategory::parent_id => null,
            TransactionCategory::active => true,
        ]);
        $child = TransactionCategory::query()->create([
            TransactionCategory::user_id => $user->id,
            TransactionCategory::name => 'Unterkategorie',
            TransactionCategory::parent_id => $root->id,
            TransactionCategory::active => true,
        ]);
        $grandchild = TransactionCategory::query()->create([
            TransactionCategory::user_id => $user->id,
            TransactionCategory::name => 'Enkelkategorie',
            TransactionCategory::parent_id => $child->id,
            TransactionCategory::active => true,
        ]);

        self::assertSame(
            ['Hauptkategorien'],
            array_values(TransactionCategoryBreadcrumbs::forList(null)),
        );
        self::assertSame(
            ['Hauptkategorien', 'Hauptkategorie'],
            array_values(TransactionCategoryBreadcrumbs::forList($root->id)),
        );

        $nestedBreadcrumbs = TransactionCategoryBreadcrumbs::forList($grandchild->id);

        self::assertSame(
            ['Hauptkategorien', 'Hauptkategorie', 'Unterkategorie', 'Enkelkategorie'],
            array_values($nestedBreadcrumbs),
        );
        self::assertArrayHasKey(
            TransactionCategoryResource::getUrl('index', [
                'filters' => [
                    TransactionCategory::parent_id => [
                        'value' => $root->id,
                    ],
                ],
            ]),
            $nestedBreadcrumbs,
        );

        self::assertSame(
            ['Hauptkategorien', 'Hauptkategorie', 'Unterkategorie', 'Enkelkategorie', 'Bearbeiten'],
            array_values(TransactionCategoryBreadcrumbs::forEdit($grandchild)),
        );
    }
}
