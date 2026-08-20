---
paths:
  - 'app/Http/Controllers/Auth/**,app/Models/User.php,routes/web.php,app/Providers/Filament/DashboardPanelProvider.php'
---

# Filament

## Google login via Socialite is additive, no auto-registration
Login con Google (Socialite, `GoogleAuthController`) es un método adicional, no reemplaza el login por contraseña de Filament: el botón se inyecta vía el render hook `PanelsRenderHook::AUTH_LOGIN_FORM_AFTER` en `DashboardPanelProvider`, no subclaseando `Filament\Auth\Pages\Login`.

No hay auto-registro: en el callback, si el email de Google no matchea un `User` existente, o el usuario existe pero `activo = false`, se rechaza (notificación Filament `->danger()` + redirect a `Filament::getPanel('dashboard')->getLoginUrl()`). Nunca se crea un usuario nuevo desde el flujo de Google.

Guard usado: `web` (default de la app, sin guard custom en el panel). Los IDs/avatar de Google se guardan en las columnas `google_id`/`avatar` de `users`, agregadas de forma aditiva (migración `2026_08_20_000100_add_google_id_to_users_table.php`) — seguir ese patrón de migración aditiva para futuros cambios a la tabla `users`.
