# 0004 — Traducción manual de `validation.php` al español

**Fecha:** 2026-08-20

## Decisión

Se agrega `lang/es/validation.php` con la traducción completa de los mensajes de validación estándar de Laravel (misma estructura de claves que `vendor/laravel/framework/.../lang/en/validation.php`), escrita a mano en vez de instalar un paquete de traducciones (ej. `laravel-lang/lang`).

## Por qué

`APP_LOCALE` y `APP_FALLBACK_LOCALE` son `es` (ver `.env`), pero el proyecto nunca tuvo `lang/es/validation.php`. Laravel solo trae `en` embebido en el framework — sin un archivo `es` propio ni un fallback a `en`, cualquier regla de validación (`required`, `numeric`, etc.) se mostraba como la clave cruda (`"validation.required"`) en vez de un mensaje. Esto afectaba a CUALQUIER formulario del panel, no solo `SaleForm` (ahí fue donde se detectó, con la línea de venta sin producto).

Se evitó agregar una dependencia nueva (regla de CLAUDE.md: no cambiar dependencias sin aprobación) — la traducción es estática y estándar, así que copiarla a mano es más simple que sumar un paquete.

## Cómo aplicar

- Si aparece un mensaje de validación sin traducir, es porque falta esa clave en `lang/es/validation.php` (revisar `vendor/laravel/framework/src/Illuminate/Translation/lang/en/validation.php` como referencia de qué claves existen en la versión instalada) — agregarla ahí, no parchear el mensaje por campo.
- `lang/es/validation.php` también tiene un array `attributes` con nombres en español para los campos de `SaleForm`; si se agregan campos nuevos con validación en otros formularios, sumar su traducción ahí si el label de Filament no alcanza.
