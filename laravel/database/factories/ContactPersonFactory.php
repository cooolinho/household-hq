<?php

namespace Database\Factories;

use App\Models\ContactPerson;
use App\Models\Enums\ContactPersonTypeEnum;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactPerson>
 */
class ContactPersonFactory extends Factory
{
    protected $model = ContactPerson::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            ContactPerson::title => fake()->titleMale(),
            ContactPerson::firstname => fake()->firstNameMale(),
            ContactPerson::lastname => fake()->lastName(),
            ContactPerson::email => fake()->unique()->safeEmail(),
            ContactPerson::phone_private => fake()->phoneNumber(),
            ContactPerson::phone_business => fake()->phoneNumber(),
            ContactPerson::notes => fake()->paragraph(),
            ContactPerson::role => fake()->jobTitle(),
            ContactPerson::type => fake()->randomElement(ContactPersonTypeEnum::allNames()),
        ];
    }

    public function forUser(int $userId): static
    {
        return $this->state(fn(array $attributes) => [
            ContactPerson::user_id => $userId,
        ]);
    }
}
