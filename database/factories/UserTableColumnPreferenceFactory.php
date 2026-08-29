<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserTableColumnPreference;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserTableColumnPreference>
 */
class UserTableColumnPreferenceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'table_key' => fake()->words(3, true),
            'columns' => [],
        ];
    }
}
