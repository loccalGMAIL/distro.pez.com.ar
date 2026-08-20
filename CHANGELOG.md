# Changelog

Todos los cambios notables de este proyecto se documentan en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/),
y este proyecto sigue [Semantic Versioning](https://semver.org/lang/es/) (ver
`.ai/decisions/0002-version-app.md`).

## [Unreleased]

## [0.3.0] - 2026-08-20

### Added

- Login con Google (Laravel Socialite) en el panel de Filament: botón
  "Continuar con Google" adicional al login por contraseña existente. Sin
  auto-registro: solo pueden loguearse usuarios ya existentes y `activo`;
  el resto es rechazado con una notificación.
- Escaneo de código de barras con la cámara del dispositivo en el buscador de
  producto de la línea de venta (`SaleForm`). Visible solo en mobile; usa la
  API nativa `BarcodeDetector` del navegador, sin agregar dependencias JS.
- La sección "Resumen" del modal de venta ahora es un slide-over en mobile
  (oculto por defecto, se abre con un botón flotante), manteniendo el layout
  de sidebar sin cambios en desktop.
- Bloqueo de la creación de una venta cuando el total es $0 (notificación de
  error en vez de dejar pasar una venta vacía o descontada a cero).

### Changed

- El botón para confirmar la venta en el modal pasó de decir "Crear" a
  "Finalizar".

### Fixed

- Migraciones duplicadas de `mcp_tokens` y `mcp_uploads` (publicadas además de
  las que ya carga automáticamente `guava/filament-mcp`), que rompían toda la
  suite de tests basada en `RefreshDatabase`.
- Mensajes de validación de formularios mostraban la clave cruda sin traducir
  (ej. `"validation.required"`) en vez de un mensaje en español, por falta de
  `lang/es/validation.php`.

## [0.1.0] - 2026-08-20

### Added

- Número de versión de la aplicación (`config('app.version')`, `APP_VERSION`)
  siguiendo SemVer.
- Bitácora de decisiones del proyecto en `.ai/decisions/`.

### Changed

- Reemplazo de la página `Dashboard` por defecto de Filament por un widget de
  acceso rápido a "Nueva venta".
