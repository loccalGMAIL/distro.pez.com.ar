# Changelog

Todos los cambios notables de este proyecto se documentan en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/),
y este proyecto sigue [Semantic Versioning](https://semver.org/lang/es/) (ver
`.ai/decisions/0002-version-app.md`).

## [Unreleased]

### Added

- Escaneo de código de barras con la cámara del dispositivo en el buscador de
  producto de la línea de venta (`SaleForm`). Visible solo en mobile; usa la
  API nativa `BarcodeDetector` del navegador, sin agregar dependencias JS.

### Fixed

- Migraciones duplicadas de `mcp_tokens` y `mcp_uploads` (publicadas además de
  las que ya carga automáticamente `guava/filament-mcp`), que rompían toda la
  suite de tests basada en `RefreshDatabase`.

## [0.1.0] - 2026-08-20

### Added

- Número de versión de la aplicación (`config('app.version')`, `APP_VERSION`)
  siguiendo SemVer.
- Bitácora de decisiones del proyecto en `.ai/decisions/`.

### Changed

- Reemplazo de la página `Dashboard` por defecto de Filament por un widget de
  acceso rápido a "Nueva venta".
