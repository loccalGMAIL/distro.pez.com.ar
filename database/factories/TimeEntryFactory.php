<?php

namespace Database\Factories;

use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TimeEntry>
 */
class TimeEntryFactory extends Factory
{
    public function definition(): array
    {
        $startedAt = fake()->dateTimeBetween('-1 month', 'now');

        return [
            'user_id' => User::factory(),
            'started_at' => $startedAt,
            'ended_at' => null,
        ];
    }

    /**
     * Ciclo cerrado, con `ended_at` unas horas después de `started_at`.
     */
    public function closed(): static
    {
        return $this->state(function (): array {
            $startedAt = fake()->dateTimeBetween('-1 month', '-1 day');
            $endedAt = (clone $startedAt)->modify('+'.fake()->numberBetween(1, 8).' hours');

            return [
                'started_at' => $startedAt,
                'ended_at' => $endedAt,
            ];
        });
    }
}
