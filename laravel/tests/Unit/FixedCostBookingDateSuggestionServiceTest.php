<?php

namespace Tests\Unit;

use App\Models\Enums\BankAccountTypeEnum;
use App\Models\Enums\FixedCostIntervalEnum;
use App\Models\Enums\MatchingSuggestionStatusEnum;
use App\Models\Financial\BankAccount;
use App\Models\Financial\FixedCost;
use App\Models\Financial\FixedCostBookingDateSuggestion;
use App\Models\Financial\Transaction;
use App\Models\User;
use App\Services\FixedCost\FixedCostBookingDateSuggestionService;
use App\Settings\FixedCostSettings;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FixedCostBookingDateSuggestionServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private BankAccount $bankAccount;

    public function test_it_creates_a_suggestion_when_deviation_is_at_least_two_days(): void
    {
        $fixedCost = $this->makeFixedCost('2026-09-15');
        $this->addTransactions($fixedCost, ['2026-06-12', '2026-07-11', '2026-08-13']);

        $results = app(FixedCostBookingDateSuggestionService::class)->detectForUser($this->user->id);

        $this->assertSame(1, $results['created']);

        $suggestion = FixedCostBookingDateSuggestion::query()->sole();
        $this->assertSame($fixedCost->id, $suggestion->fixed_cost_id);
        $this->assertSame(12, $suggestion->suggested_day);
        $this->assertSame('2026-09-12', $suggestion->suggested_date->toDateString());
        $this->assertSame(MatchingSuggestionStatusEnum::PENDING->name, $suggestion->status);
    }

    private function makeFixedCost(string $nextBookingDate): FixedCost
    {
        return FixedCost::query()->create([
            FixedCost::user_id => $this->user->id,
            FixedCost::name => 'Miete',
            FixedCost::amount => -800,
            FixedCost::interval => FixedCostIntervalEnum::MONTHLY->name,
            FixedCost::next_booking_date => $nextBookingDate,
        ]);
    }

    /**
     * @param list<string> $dates
     */
    private function addTransactions(FixedCost $fixedCost, array $dates): void
    {
        foreach ($dates as $date) {
            Transaction::factory()
                ->forUser($this->user->id)
                ->forBankAccount($this->bankAccount->id)
                ->create([
                    Transaction::date => $date,
                    Transaction::amount => -800,
                    Transaction::fixed_cost_id => $fixedCost->id,
                ]);
        }
    }

    public function test_it_does_not_create_a_suggestion_for_a_single_day_deviation(): void
    {
        $fixedCost = $this->makeFixedCost('2026-09-15');
        $this->addTransactions($fixedCost, ['2026-06-14', '2026-07-14', '2026-08-14']);

        $results = app(FixedCostBookingDateSuggestionService::class)->detectForUser($this->user->id);

        $this->assertSame(0, $results['created']);
        $this->assertSame(0, FixedCostBookingDateSuggestion::query()->count());
    }

    public function test_it_removes_a_stale_pending_suggestion_once_the_deviation_disappears(): void
    {
        $fixedCost = $this->makeFixedCost('2026-09-15');

        FixedCostBookingDateSuggestion::query()->create([
            FixedCostBookingDateSuggestion::fixed_cost_id => $fixedCost->id,
            FixedCostBookingDateSuggestion::suggested_day => 12,
            FixedCostBookingDateSuggestion::suggested_date => '2026-09-12',
            FixedCostBookingDateSuggestion::current_date => '2026-09-15',
            FixedCostBookingDateSuggestion::deviation_days => 3,
            FixedCostBookingDateSuggestion::sample_count => 3,
            FixedCostBookingDateSuggestion::analyzed_from => '2026-06-01',
            FixedCostBookingDateSuggestion::analyzed_to => '2026-09-01',
            FixedCostBookingDateSuggestion::status => MatchingSuggestionStatusEnum::PENDING->name,
        ]);

        // Nutzer bucht jetzt konsistent am 15. -> keine Abweichung mehr.
        $this->addTransactions($fixedCost, ['2026-06-15', '2026-07-15', '2026-08-15']);

        $results = app(FixedCostBookingDateSuggestionService::class)->detectForUser($this->user->id);

        $this->assertSame(1, $results['skipped']);
        $this->assertSame(0, FixedCostBookingDateSuggestion::query()->count());
    }

    public function test_it_does_not_recreate_a_rejected_suggestion_for_the_same_day(): void
    {
        $fixedCost = $this->makeFixedCost('2026-09-15');

        FixedCostBookingDateSuggestion::query()->create([
            FixedCostBookingDateSuggestion::fixed_cost_id => $fixedCost->id,
            FixedCostBookingDateSuggestion::suggested_day => 12,
            FixedCostBookingDateSuggestion::suggested_date => '2026-08-12',
            FixedCostBookingDateSuggestion::current_date => '2026-08-15',
            FixedCostBookingDateSuggestion::deviation_days => 3,
            FixedCostBookingDateSuggestion::sample_count => 3,
            FixedCostBookingDateSuggestion::analyzed_from => '2026-05-01',
            FixedCostBookingDateSuggestion::analyzed_to => '2026-08-01',
            FixedCostBookingDateSuggestion::status => MatchingSuggestionStatusEnum::REJECTED->name,
        ]);

        $this->addTransactions($fixedCost, ['2026-06-12', '2026-07-11', '2026-08-13']);

        $results = app(FixedCostBookingDateSuggestionService::class)->detectForUser($this->user->id);

        $this->assertSame(0, $results['created']);
        $this->assertSame(0, $results['updated']);
        $this->assertSame(
            1,
            FixedCostBookingDateSuggestion::query()
                ->where(FixedCostBookingDateSuggestion::status, MatchingSuggestionStatusEnum::REJECTED->name)
                ->count(),
        );
    }

    public function test_suggested_date_keeps_the_current_booking_month_when_still_in_the_future(): void
    {
        // "Heute" ist auf den 10.09.2026 fixiert (siehe setUp) - der vorgeschlagene Tag 20 liegt
        // noch in der Zukunft, darf also nicht in den Folgemonat verschoben werden.
        $fixedCost = $this->makeFixedCost('2026-09-28');
        $this->addTransactions($fixedCost, ['2026-06-19', '2026-07-21', '2026-08-20']);

        app(FixedCostBookingDateSuggestionService::class)->detectForUser($this->user->id);

        $suggestion = FixedCostBookingDateSuggestion::query()->sole();
        $this->assertSame(20, $suggestion->suggested_day);
        $this->assertSame('2026-09-20', $suggestion->suggested_date->toDateString());
    }

    public function test_suggested_date_rolls_over_to_next_month_once_the_day_has_already_passed(): void
    {
        // "Heute" ist der 10.09.2026 - der vorgeschlagene Tag 2 liegt bereits in der Vergangenheit,
        // der nächste tatsächliche Termin ist also der 2. Oktober statt der 2. September.
        $fixedCost = $this->makeFixedCost('2026-09-28');
        $this->addTransactions($fixedCost, ['2026-06-02', '2026-07-01', '2026-08-03']);

        app(FixedCostBookingDateSuggestionService::class)->detectForUser($this->user->id);

        $suggestion = FixedCostBookingDateSuggestion::query()->sole();
        $this->assertSame(2, $suggestion->suggested_day);
        $this->assertSame('2026-10-02', $suggestion->suggested_date->toDateString());
    }

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-10'));

        $this->user = User::factory()->create();
        $this->bankAccount = BankAccount::query()->create([
            BankAccount::user_id => $this->user->id,
            BankAccount::name => 'Testkonto',
            BankAccount::type => BankAccountTypeEnum::GIRO->name,
        ]);

        $settings = app(FixedCostSettings::class);
        $settings->booking_date_min_deviation_days = 2;
        $settings->booking_date_window_months = 12;
        $settings->booking_date_min_occurrences = 3;
        $settings->save();
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }
}
