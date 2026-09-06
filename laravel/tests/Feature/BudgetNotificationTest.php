<?php

namespace Tests\Feature;

use App\Models\Enums\BankAccountTypeEnum;
use App\Models\Enums\BudgetStatusEnum;
use App\Models\Financial\BankAccount;
use App\Models\Financial\Budget;
use App\Models\Financial\Transaction;
use App\Models\Financial\TransactionCategory;
use App\Models\User;
use App\Notifications\Financial\BudgetThresholdNotification;
use App\Services\Budget\BudgetNotificationService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class BudgetNotificationTest extends TestCase
{
    use RefreshDatabase;

    private const string NOW = '2026-09-10 12:00:00';

    private User $user;

    private BankAccount $bankAccount;

    private TransactionCategory $category;

    private BudgetNotificationService $service;

    public function test_no_notification_while_the_budget_is_within_its_limits(): void
    {
        $budget = $this->createBudget(100.0);
        $this->createTransaction(-50.0, '2026-09-02');

        self::assertSame(0, $this->service->checkForUser((int)$this->user->id));
        Notification::assertNothingSent();

        self::assertSame(BudgetStatusEnum::OK->name, $budget->refresh()->last_notified_level);
    }

    private function createBudget(float $amount): Budget
    {
        $budget = Budget::query()->create([
            Budget::user_id => $this->user->id,
            Budget::name => 'Lebensmittelbudget',
            Budget::amount => $amount,
        ]);

        $budget->transactionCategories()->sync([$this->category->id]);

        return $budget;
    }

    private function createTransaction(float $amount, string $date): Transaction
    {
        $transaction = Transaction::factory()
            ->forUser((int)$this->user->id)
            ->forBankAccount((int)$this->bankAccount->id)
            ->create([
                Transaction::date => $date,
                Transaction::value_date => $date,
                Transaction::amount => $amount,
                Transaction::amount_currency => 'EUR',
            ]);

        $transaction->transactionCategories()->sync([$this->category->id]);

        return $transaction;
    }

    public function test_warning_is_sent_once_per_period_and_escalates_to_exceeded(): void
    {
        $budget = $this->createBudget(100.0);
        $this->createTransaction(-85.0, '2026-09-02');

        self::assertSame(1, $this->service->checkForUser((int)$this->user->id));
        Notification::assertSentToTimes($this->user, BudgetThresholdNotification::class, 1);
        self::assertSame(BudgetStatusEnum::WARNING->name, $budget->refresh()->last_notified_level);

        // Zweiter Lauf ohne Änderung: keine erneute Benachrichtigung.
        self::assertSame(0, $this->service->checkForUser((int)$this->user->id));
        Notification::assertSentToTimes($this->user, BudgetThresholdNotification::class, 1);

        // Limit überschritten: Eskalation auf die nächste Stufe.
        $this->createTransaction(-20.0, '2026-09-03');

        self::assertSame(1, $this->service->checkForUser((int)$this->user->id));
        Notification::assertSentToTimes($this->user, BudgetThresholdNotification::class, 2);
        self::assertSame(BudgetStatusEnum::EXCEEDED->name, $budget->refresh()->last_notified_level);

        self::assertSame(0, $this->service->checkForUser((int)$this->user->id));
        Notification::assertSentToTimes($this->user, BudgetThresholdNotification::class, 2);
    }

    public function test_the_next_period_notifies_again(): void
    {
        $this->createBudget(100.0);
        $this->createTransaction(-120.0, '2026-09-02');

        self::assertSame(1, $this->service->checkForUser((int)$this->user->id));

        $this->createTransaction(-120.0, '2026-10-02');

        $nextMonth = CarbonImmutable::parse('2026-10-05 12:00:00');
        Carbon::setTestNow(Carbon::parse('2026-10-05 12:00:00'));
        CarbonImmutable::setTestNow($nextMonth);

        self::assertSame(1, $this->service->checkForUser((int)$this->user->id));
        Notification::assertSentToTimes($this->user, BudgetThresholdNotification::class, 2);
    }

    public function test_channels_follow_the_budget_configuration(): void
    {
        $budget = $this->createBudget(100.0);
        $budget->forceFill([
            Budget::send_mail => true,
            Budget::send_notification => false,
        ])->save();

        $this->createTransaction(-120.0, '2026-09-02');

        $this->service->checkForUser((int)$this->user->id);

        Notification::assertSentTo(
            $this->user,
            BudgetThresholdNotification::class,
            static fn(BudgetThresholdNotification $notification, array $channels): bool => $channels === ['mail'],
        );
    }

    public function test_nothing_is_sent_when_both_channels_are_disabled(): void
    {
        $budget = $this->createBudget(100.0);
        $budget->forceFill([
            Budget::send_mail => false,
            Budget::send_notification => false,
        ])->save();

        $this->createTransaction(-120.0, '2026-09-02');

        self::assertSame(0, $this->service->checkForUser((int)$this->user->id));
        Notification::assertNothingSent();

        // Der Zustand wird trotzdem fortgeschrieben, damit ein späteres Aktivieren nicht nachfeuert.
        self::assertSame(BudgetStatusEnum::EXCEEDED->name, $budget->refresh()->last_notified_level);
    }

    public function test_inactive_budgets_are_skipped(): void
    {
        $budget = $this->createBudget(100.0);
        $budget->forceFill([Budget::active => false])->save();

        $this->createTransaction(-120.0, '2026-09-02');

        self::assertSame(0, $this->service->checkForUser((int)$this->user->id));
        Notification::assertNothingSent();
    }

    public function test_notification_payload_contains_the_budget_figures(): void
    {
        $this->createBudget(100.0);
        $this->createTransaction(-120.0, '2026-09-02');

        $this->service->checkForUser((int)$this->user->id);

        Notification::assertSentTo(
            $this->user,
            BudgetThresholdNotification::class,
            function (BudgetThresholdNotification $notification): bool {
                $payload = $notification->toArray($this->user);

                self::assertSame('Lebensmittelbudget', $payload['budget_name']);
                self::assertSame(BudgetStatusEnum::EXCEEDED->name, $payload['level']);
                self::assertSame(120.0, $payload['spent']);
                self::assertSame(100.0, $payload['limit']);
                self::assertSame(-20.0, $payload['remaining']);
                self::assertSame('2026-09-01', $payload['period_start']);

                return true;
            },
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse(self::NOW));
        CarbonImmutable::setTestNow(CarbonImmutable::parse(self::NOW));
        Notification::fake();

        $this->user = User::factory()->create();
        $this->service = app(BudgetNotificationService::class);

        $this->bankAccount = BankAccount::query()->create([
            BankAccount::user_id => $this->user->id,
            BankAccount::name => 'Testkonto',
            BankAccount::type => BankAccountTypeEnum::GIRO->name,
        ]);

        $this->category = TransactionCategory::query()->create([
            TransactionCategory::user_id => $this->user->id,
            TransactionCategory::name => 'Lebensmittel',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }
}
