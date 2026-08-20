<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(): RedirectResponse
    {
        $googleUser = Socialite::driver('google')->user();

        $user = User::where('email', $googleUser->getEmail())->first();

        if (! $user || ! $user->activo) {
            Notification::make()
                ->danger()
                ->title('No se pudo iniciar sesión con Google')
                ->body('Tu cuenta de Google no está autorizada para acceder. Contactá a un administrador.')
                ->send();

            return redirect(Filament::getPanel('dashboard')->getLoginUrl());
        }

        $user->fill([
            'google_id' => $user->google_id ?? $googleUser->getId(),
            'avatar' => $user->avatar ?? $googleUser->getAvatar(),
        ])->save();

        Auth::guard('web')->login($user);

        return redirect(Filament::getPanel('dashboard')->getUrl());
    }
}
