# 0001 — Inicio del versionado del proyecto

**Fecha:** 2026-08-20

## Decisión

A partir de ahora el proyecto se versiona formalmente:

- El historial de Git es la fuente de verdad del código. Cada cambio funcional relevante debe quedar en un commit propio con mensaje descriptivo (evitar mezclar features no relacionadas en un mismo commit).
- Se crea `.ai/decisions/` como bitácora de decisiones del proyecto (este archivo es la primera entrada). Es un log cronológico, no se edita retroactivamente: si una decisión cambia, se agrega una entrada nueva que reemplaza a la anterior y se referencia mutuamente.
- Se agrega un número de versión de la aplicación en `config/app.php` (ver [0002-version-app.md](0002-version-app.md)).

## Por qué

El proyecto venía acumulando cambios sin un registro explícito de por qué se tomaron ciertas decisiones (estructura, convenciones, dependencias). `.ai/rules/` ya cubre convenciones de código hacia adelante, pero faltaba un lugar para el histórico de decisiones puntuales.

## Cómo aplicar

- Antes de tomar una decisión estructural (nueva carpeta base, cambio de dependencia, cambio de convención de versionado, etc.), revisar si ya hay una entrada relacionada en este índice.
- Al tomar una decisión de ese tipo, agregar una entrada nueva numerada secuencialmente y sumarla a [index.md](index.md).
