<?php

use App\Models\User;

test('the dashboard page loads for an admin', function () {
    $admin = User::factory()->admin()->create(['activo' => true]);

    $this->actingAs($admin)
        ->get('/dashboard')
        ->assertSuccessful();
});
