<?php

use App\Models\TimeEntry;
use App\Models\User;

test('clockIn reuses an already open entry instead of creating a second one', function () {
    $user = User::factory()->create();

    $first = TimeEntry::clockIn($user);
    $second = TimeEntry::clockIn($user);

    expect($second->is($first))->toBeTrue()
        ->and(TimeEntry::query()->where('user_id', $user->id)->count())->toBe(1);
});

test('hours and pay are computed against now() while the entry is still open', function () {
    $user = User::factory()->hourlyRate(1000)->create();

    $entry = TimeEntry::factory()->for($user)->create([
        'started_at' => now()->subHours(2),
        'ended_at' => null,
    ]);

    expect($entry->hours())->toBe(2.0)
        ->and($entry->pay())->toBe(2000.0);
});

test('hours and pay are computed from started_at to ended_at once closed', function () {
    $user = User::factory()->hourlyRate(500)->create();

    $entry = TimeEntry::factory()->for($user)->create([
        'started_at' => now()->subHours(4),
        'ended_at' => now(),
    ]);

    expect($entry->hours())->toBe(4.0)
        ->and($entry->pay())->toBe(2000.0);
});

test('pay is zero when the user has no hourly rate set', function () {
    $user = User::factory()->create(['hourly_rate' => null]);

    $entry = TimeEntry::factory()->for($user)->create([
        'started_at' => now()->subHour(),
        'ended_at' => now(),
    ]);

    expect($entry->pay())->toBe(0.0);
});
