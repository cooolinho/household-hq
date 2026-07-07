<?php

namespace Database\Factories;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tag>
 */
class TagFactory extends Factory
{
    protected $model = Tag::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            Tag::name => fake()->word(),
        ];
    }

    public function forUser(int $userId): static
    {
        return $this->state(fn(array $attributes) => [
            Tag::user_id => $userId,
        ]);
    }
}
