<?php

namespace Tests\Feature;

use App\Filament\App\Resources\Financial\BookingDateSuggestions\Pages\ListBookingDateSuggestions;
use App\Models\Enums\FixedCostIntervalEnum;
use App\Models\Enums\MatchingSuggestionStatusEnum;
use App\Models\Financial\FixedCost;
use App\Models\Financial\FixedCostBookingDateSuggestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BookingDateSuggestionResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_only_the_current_users_pending_suggestions(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $ownFixedCost = $this->makeFixedCost($user, 'Miete');
        $foreignFixedCost = $this->makeFixedCost($otherUser, 'Fremde Miete');

        $ownSuggestion = $this->makeSuggestion($ownFixedCost, 12);
        $this->makeSuggestion($foreignFixedCost, 12);

        Livewire::actingAs($user)
            ->test(ListBookingDateSuggestions::class)
            ->assertCanSeeTableRecords([$ownSuggestion])
            ->assertCanNotSeeTableRecords([FixedCostBookingDateSuggestion::query()
                ->where(FixedCostBookingDateSuggestion::fixed_cost_id, $foreignFixedCost->id)
                ->sole()]);
    }

    private function makeFixedCost(User $user, string $name, string $nextBookingDate = '2026-09-15'): FixedCost
    {
        return FixedCost::query()->create([
            FixedCost::user_id => $user->id,
            FixedCost::name => $name,
            FixedCost::amount => -800,
            FixedCost::interval => FixedCostIntervalEnum::MONTHLY->name,
            FixedCost::next_booking_date => $nextBookingDate,
        ]);
    }

    private function makeSuggestion(
        FixedCost $fixedCost,
        int       $suggestedDay,
        string    $suggestedDate = '2026-09-12',
        string    $status = 'PENDING',
    ): FixedCostBookingDateSuggestion
    {
        return FixedCostBookingDateSuggestion::query()->create([
            FixedCostBookingDateSuggestion::fixed_cost_id => $fixedCost->id,
            FixedCostBookingDateSuggestion::suggested_day => $suggestedDay,
            FixedCostBookingDateSuggestion::suggested_date => $suggestedDate,
            FixedCostBookingDateSuggestion::current_date => $fixedCost->next_booking_date,
            FixedCostBookingDateSuggestion::deviation_days => 3,
            FixedCostBookingDateSuggestion::sample_count => 3,
            FixedCostBookingDateSuggestion::analyzed_from => '2026-06-01',
            FixedCostBookingDateSuggestion::analyzed_to => '2026-09-01',
            FixedCostBookingDateSuggestion::status => $status,
        ]);
    }

    public function test_accepting_a_suggestion_updates_the_fixed_cost_and_rejects_siblings(): void
    {
        $user = User::factory()->create();
        $fixedCost = $this->makeFixedCost($user, 'Miete', '2026-09-15');

        $accepted = $this->makeSuggestion($fixedCost, 12, '2026-09-12');
        $sibling = $this->makeSuggestion($fixedCost, 18, '2026-09-18');

        Livewire::actingAs($user)
            ->test(ListBookingDateSuggestions::class)
            ->callTableAction('accept', $accepted);

        $this->assertSame('2026-09-12', $fixedCost->fresh()->next_booking_date->toDateString());
        $this->assertSame(MatchingSuggestionStatusEnum::ACCEPTED->name, $accepted->fresh()->status);
        $this->assertSame(MatchingSuggestionStatusEnum::REJECTED->name, $sibling->fresh()->status);
    }

    public function test_rejecting_a_suggestion_only_changes_its_own_status(): void
    {
        $user = User::factory()->create();
        $fixedCost = $this->makeFixedCost($user, 'Miete', '2026-09-15');

        $suggestion = $this->makeSuggestion($fixedCost, 12, '2026-09-12');

        Livewire::actingAs($user)
            ->test(ListBookingDateSuggestions::class)
            ->callTableAction('reject', $suggestion);

        $this->assertSame(MatchingSuggestionStatusEnum::REJECTED->name, $suggestion->fresh()->status);
        $this->assertSame('2026-09-15', $fixedCost->fresh()->next_booking_date->toDateString());
    }

    public function test_accept_and_reject_actions_are_hidden_for_already_decided_suggestions(): void
    {
        $user = User::factory()->create();
        $fixedCost = $this->makeFixedCost($user, 'Miete', '2026-09-15');

        $suggestion = $this->makeSuggestion($fixedCost, 12, '2026-09-12', MatchingSuggestionStatusEnum::REJECTED->name);

        $component = Livewire::actingAs($user)
            ->test(ListBookingDateSuggestions::class)
            ->filterTable(FixedCostBookingDateSuggestion::status, null);

        $component->assertTableActionHidden('accept', $suggestion);
        $component->assertTableActionHidden('reject', $suggestion);
    }
}
