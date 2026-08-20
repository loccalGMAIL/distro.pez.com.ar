<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

test('an active user with a matching email logs in via google and is redirected to the dashboard', function () {
    $user = User::factory()->create(['email' => 'admin@example.com']);

    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-123',
        'name' => $user->name,
        'email' => 'admin@example.com',
    ]));

    $this->get('/auth/google/callback')->assertRedirect('/dashboard');

    expect(Auth::check())->toBeTrue();
    expect($user->fresh()->google_id)->toBe('google-123');
});

test('an unknown google email is rejected and redirected back to the login page', function () {
    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-999',
        'name' => 'Unknown',
        'email' => 'unknown@example.com',
    ]));

    $this->get('/auth/google/callback')->assertRedirect('/dashboard/login');

    expect(Auth::check())->toBeFalse();
});

test('an inactive user is rejected even with a matching google email', function () {
    User::factory()->create(['email' => 'inactive@example.com', 'activo' => false]);

    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-456',
        'name' => 'Inactive',
        'email' => 'inactive@example.com',
    ]));

    $this->get('/auth/google/callback')->assertRedirect('/dashboard/login');

    expect(Auth::check())->toBeFalse();
});
