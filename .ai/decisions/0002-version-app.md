# 0002 — Número de versión de la aplicación

**Fecha:** 2026-08-20

## Decisión

- Se agrega `'version'` en `config/app.php`, leído desde `APP_VERSION` (con default `'0.1.0'`), siguiendo el mismo patrón que `'name'` (`APP_NAME`).
- Esquema: SemVer (`MAJOR.MINOR.PATCH`).
- Punto de partida: `0.1.0` — el proyecto está en desarrollo activo, pre-1.0.
- `APP_VERSION` se agrega a `.env.example` y a `.env` local.

## Por qué

Se necesita poder mostrar/consultar la versión desplegada de la app (soporte, changelog, depuración) sin depender únicamente del hash de git.

## Cómo aplicar

- Bump de versión como parte del mismo commit/PR que introduce el cambio correspondiente:
  - **PATCH** (`0.1.x`): fixes que no cambian comportamiento observable.
  - **MINOR** (`0.x.0`): nuevas funcionalidades compatibles hacia atrás.
  - **MAJOR** (`x.0.0`): cambios incompatibles (a partir de `1.0.0`, cuando el proyecto se considere estable).
- Antes de `1.0.0`, minor puede incluir cambios incompatibles si es necesario (convención estándar de SemVer para versiones `0.y.z`).
- Leer la versión actual en código con `config('app.version')`.
- Al bumpear `APP_VERSION` en `.env` / `.env.example`, actualizar también el
  default de `env('APP_VERSION', '...')` en `config/app.php` para que quede
  igual. Es el fallback si `.env` no define la variable (ej. instalación
  fresca), así que si no se actualiza queda desincronizado del último
  release.
