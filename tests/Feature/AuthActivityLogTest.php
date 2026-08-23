<?php

use App\Models\User;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

/**
 * Actividades del log "auth" (ingresos, salidas e intentos fallidos), en
 * orden cronológico. Los cambios de modelos van al log "default", así que
 * quedan afuera.
 */
function authActivities(): Collection
{
    return Activity::query()->inLog('auth')->orderBy('id')->get();
}

test('logging in records an activity', function () {
    $user = User::factory()->create();

    Auth::login($user);

    $activities = authActivities();

    expect($activities)->toHaveCount(1);
    expect($activities->first()->event)->toBe('login');
    expect($activities->first()->description)->toBe('Inicio de sesión');
    expect($activities->first()->causer_id)->toBe($user->id);
    expect($activities->first()->subject_id)->toBe($user->id);
});

test('logging out records an activity', function () {
    $user = User::factory()->create();

    Auth::login($user);
    Auth::logout();

    $events = authActivities()->pluck('event')->all();

    expect($events)->toBe(['login', 'logout']);
});

test('a failed login attempt is recorded without the password', function () {
    $user = User::factory()->create(['email' => 'vendedor@example.com']);

    expect(Auth::attempt(['email' => $user->email, 'password' => 'incorrecta']))->toBeFalse();

    $activity = authActivities()->last();

    expect($activity->event)->toBe('failed_login');
    expect($activity->description)->toBe('Intento de inicio de sesión fallido');
    expect($activity->properties->get('email'))->toBe('vendedor@example.com');
    expect($activity->properties->has('password'))->toBeFalse();
    expect($activity->causer_id)->toBeNull();
    expect($activity->subject_id)->toBe($user->id);
});

test('a login through the panel records a single activity with the request ip', function () {
    $user = User::factory()->admin()->create(['email' => 'admin@example.com']);

    Filament::setCurrentPanel('dashboard');

    Livewire::test(Login::class)
        ->fillForm([
            'email' => $user->email,
            'password' => 'password',
        ])
        ->call('authenticate')
        ->assertHasNoFormErrors();

    $activities = authActivities();

    expect($activities)->toHaveCount(1);
    expect($activities->first()->event)->toBe('login');
    expect($activities->first()->properties->get('ip'))->not->toBeNull();
});

test('a rejected google login is recorded', function () {
    User::factory()->create(['email' => 'inactive@example.com', 'activo' => false]);

    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-456',
        'name' => 'Inactive',
        'email' => 'inactive@example.com',
    ]));

    $this->get('/auth/google/callback');

    $activity = authActivities()->last();

    expect($activity->event)->toBe('failed_login');
    expect($activity->description)->toBe('Intento de inicio de sesión con Google rechazado');
    expect($activity->properties->get('email'))->toBe('inactive@example.com');
    expect($activity->properties->get('motivo'))->toBe('Usuario inactivo');
});

test('a successful google login records the login activity', function () {
    $user = User::factory()->create(['email' => 'admin@example.com']);

    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-123',
        'name' => $user->name,
        'email' => 'admin@example.com',
    ]));

    $this->get('/auth/google/callback');

    $activity = authActivities()->last();

    expect($activity->event)->toBe('login');
    expect($activity->causer_id)->toBe($user->id);
});
