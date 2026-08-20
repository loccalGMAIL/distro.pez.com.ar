# 0003 — Escaneo de código de barras por cámara sin dependencias JS nuevas

**Fecha:** 2026-08-20

## Decisión

El buscador de producto de `SaleForm` (línea de venta) suma un botón de cámara (`SaleForm::scanBarcodeAction()`) que abre un modal con la cámara trasera del dispositivo y decodifica el código de barras usando la API nativa `BarcodeDetector` del navegador — sin agregar ninguna librería JS (zxing, quagga, html5-qrcode, etc.) al proyecto.

El botón solo se muestra en mobile (`sm:hidden`), porque es donde tiene sentido usar la cámara trasera para esto.

## Por qué

- `BarcodeDetector` ya viene soportado en Chrome/Android (el navegador más probable para uso en piso de venta) sin instalar nada.
- Evita tocar `package.json` / agregar una dependencia nueva, que según las reglas del proyecto (CLAUDE.md) requiere aprobación explícita.
- Contras aceptados: no funciona en Safari/iOS (sin soporte de `BarcodeDetector` a la fecha). Se muestra un mensaje de fallback ("Probá desde Chrome en Android") en vez de fallar en silencio.

## Cómo aplicar

- Si en el futuro se necesita soporte iOS/Safari, evaluar agregar una librería JS (ej. `zxing-js` o `html5-qrcode`) — eso sí requiere aprobación explícita por ser una dependencia nueva.
- El patrón técnico usado (Action con `modalContent()` + `$wire.callMountedAction()` para evitar el doble disparo de un `suffixAction` dentro de un Repeater) queda documentado como regla en `.ai/rules/sales.md`.
