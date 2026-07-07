<?php

namespace Database\Factories\Financial;

use App\Models\Financial\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $date = $this->faker->date();

        return [
            Transaction::date => $date,
            Transaction::value_date => $date,
            Transaction::payer => $this->faker->name(),
            Transaction::description => $this->faker->sentence(),
            Transaction::purpose => $this->faker->sentence(),
            Transaction::balance => $this->faker->randomFloat(2, -1000, 1000),
            Transaction::balance_currency => 'EUR',
            Transaction::amount => $this->faker->randomFloat(2, -1000, 1000),
            Transaction::amount_currency => 'EUR',
            Transaction::hash => $this->faker->sha256(),
        ];
    }

    public function forUser(int $userId): static
    {
        return $this->state(fn(array $attributes) => [
            Transaction::user_id => $userId,
        ]);
    }

    public function forBankAccount(int $bankAccountId): static
    {
        return $this->state(fn(array $attributes) => [
            Transaction::bank_account_id => $bankAccountId,
        ]);
    }
}
