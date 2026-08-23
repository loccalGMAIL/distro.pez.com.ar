<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\OtherDeviceLogout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Arr;

/**
 * Registra los eventos de autenticación (ingresos al sistema, salidas,
 * intentos fallidos) en el mismo registro de actividades que usan los
 * modelos, bajo el log "auth".
 *
 * Los métodos no se llaman `handle*` a propósito: Laravel descubre solo los
 * listeners de `app/Listeners` que tienen métodos con ese prefijo y, sumado
 * al `Event::subscribe()` de `AppServiceProvider`, quedarían registrados dos
 * veces (una actividad duplicada por cada ingreso).
 */
class LogAuthenticationActivity
{
    public const LOG_NAME = 'auth';

    /**
     * Claves de credenciales que se pueden guardar en el log. Todo lo demás
     * (contraseñas, tokens) se descarta.
     *
     * @var array<int, string>
     */
    private const LOGGABLE_CREDENTIALS = ['email', 'username', 'name'];

    /**
     * @return array<class-string, string>
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            Login::class => 'recordLogin',
            Logout::class => 'recordLogout',
            OtherDeviceLogout::class => 'recordOtherDeviceLogout',
            Failed::class => 'recordFailedLogin',
            Lockout::class => 'recordLockout',
            PasswordReset::class => 'recordPasswordReset',
            Registered::class => 'recordRegistered',
        ];
    }

    public function recordLogin(Login $event): void
    {
        self::record('login', 'Inicio de sesión', $event->user, ['guard' => $event->guard]);
    }

    public function recordLogout(Logout $event): void
    {
        self::record('logout', 'Cierre de sesión', $event->user, ['guard' => $event->guard]);
    }

    public function recordOtherDeviceLogout(OtherDeviceLogout $event): void
    {
        self::record('logout', 'Cierre de sesión en otros dispositivos', $event->user, ['guard' => $event->guard]);
    }

    public function recordFailedLogin(Failed $event): void
    {
        self::record(
            'failed_login',
            'Intento de inicio de sesión fallido',
            $event->user,
            Arr::only($event->credentials, self::LOGGABLE_CREDENTIALS) + ['guard' => $event->guard],
            causedByUser: false,
        );
    }

    public function recordLockout(Lockout $event): void
    {
        self::record(
            'lockout',
            'Bloqueo por demasiados intentos de inicio de sesión',
            properties: Arr::only($event->request->all(), self::LOGGABLE_CREDENTIALS),
        );
    }

    public function recordPasswordReset(PasswordReset $event): void
    {
        self::record('password_reset', 'Contraseña restablecida', $event->user);
    }

    public function recordRegistered(Registered $event): void
    {
        self::record('registered', 'Usuario registrado', $event->user);
    }

    /**
     * Escribe una actividad de autenticación. Siempre suma IP y user agent
     * del request (null en consola).
     *
     * @param  array<string, mixed>  $properties
     */
    public static function record(
        string $event,
        string $description,
        Authenticatable|Model|null $user = null,
        array $properties = [],
        bool $causedByUser = true,
    ): void {
        $logger = activity(self::LOG_NAME)
            ->event($event)
            ->withProperties($properties + [
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

        if ($user instanceof Model) {
            $logger->performedOn($user);

            if ($causedByUser) {
                $logger->causedBy($user);
            }
        }

        $logger->log($description);
    }
}
