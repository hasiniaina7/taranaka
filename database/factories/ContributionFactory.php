<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Contribution;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contribution>
 */
final class ContributionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'author_id'     => User::factory(),
            'target_type'   => Contribution::TARGET_PERSON,
            'target_id'     => null,
            'field'         => 'surname',
            'old_value'     => $this->faker->lastName(),
            'new_value'     => $this->faker->lastName(),
            'justification' => $this->faker->optional()->sentence(),
            'status'        => Contribution::STATUS_PENDING,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status'           => Contribution::STATUS_PENDING,
            'reviewer_id'      => null,
            'reviewed_at'      => null,
            'rejection_reason' => null,
        ]);
    }

    public function accepted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status'           => Contribution::STATUS_ACCEPTED,
            'reviewer_id'      => User::factory(),
            'reviewed_at'      => now(),
            'rejection_reason' => null,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status'           => Contribution::STATUS_REJECTED,
            'reviewer_id'      => User::factory(),
            'reviewed_at'      => now(),
            'rejection_reason' => $this->faker->sentence(),
        ]);
    }
}
