<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Listeners\LogAuthenticationActivity;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

class GoogleAuthController extends Controller
{
    public function redirect(): SymfonyRedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(): RedirectResponse
    {
        $googleUser = Socialite::driver('google')->user();

        $user = User::where('email', $googleUser->getEmail())->first();

        if (! $user || ! $user->activo) {
            LogAuthenticationActivity::record(
                'failed_login',
                'Intento de inicio de sesión con Google rechazado',
                $user,
                [
                    'email' => $googleUser->getEmail(),
                    'motivo' => $user ? 'Usuario inactivo' : 'El email no corresponde a ningún usuario',
                ],
                causedByUser: false,
            );

            Notification::make()
                ->danger()
                ->title('No se pudo iniciar sesión con Google')
                ->body('Tu cuenta de Google no está autorizada para acceder. Contactá a un administrador.')
                ->send();

            return redirect()->to(Filament::getPanel('dashboard')->getLoginUrl());
        }

        $user->fill([
            'google_id' => $user->google_id ?? $googleUser->getId(),
            'avatar' => $user->avatar ?? $googleUser->getAvatar(),
        ])->save();

        Auth::guard('web')->login($user);

        return redirect()->to(Filament::getPanel('dashboard')->getUrl());
    }
}
