---
paths:
  - 'app/Services/PurchaseCostAllocator.php,app/Models/{Purchase,PurchaseLine,PerceptionType,PurchasePerception}.php'
---

# Services Models

## Costo de producto = costo final prorrateado, no el costo_unit de la línea
`products.costo_ultimo` es el costo CON impuestos: costo de factura (`purchase_lines.costo_unit`) + los impuestos de esa compra cuyo `PerceptionType.afecta_costo` es true, prorrateados por neto de línea. `products.costo_neto_ultimo` guarda el costo de factura sin impuestos, solo como referencia/auditoría.

La única fórmula vive en `app/Services/PurchaseCostAllocator.php` (`distribuir()` puro sobre arrays, `aplicar()` persiste `purchase_lines.costo_final`). No la reimplementes: los formularios de compra (`PurchaseForm`, `ScanPurchase`) solo la usan para previsualizar mientras se carga, y `Purchase::aumentarStock()` es el único lugar que la corre de verdad y actualiza `costo_ultimo`/`costo_neto_ultimo` del producto — al confirmar la compra, no al guardar el form.

Prorrateo proporcional al neto de cada línea (no "% de la percepción por línea"): da el mismo resultado si la percepción aplica a toda la factura (caso normal), y hoy no se modela alícuota mezclada por producto en un mismo comprobante.

`PerceptionType.afecta_costo` (default true) decide qué percepciones entran a esa cuenta — el IVA como crédito fiscal recuperable, por ejemplo, se apaga desde el catálogo. `PerceptionType.porcentaje` y `PurchasePerception.porcentaje` son solo para autocompletar/verificar el monto tipeado; la distribución real es siempre por neto, no por ese %.

`Purchase::anular()` deliberadamente NO revierte `costo_ultimo` ni `costo_neto_ultimo` (mismo criterio ya documentado para el resto de los costos).

Comando `php artisan app:recalculate-product-costs [--dry-run]` para recalcular el histórico si cambia el catálogo de `afecta_costo`.
