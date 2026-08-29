---
paths:
  - 'app/Models/{PerceptionType,PurchasePerception,SupplierPerceptionLink}.php,app/Services/PerceptionLinkMemory.php,app/Filament/Clusters/Purchases/**,app/Filament/Clusters/Settings/Resources/PerceptionTypes/**'
---

# Perception Types

## Percepciones: catálogo + memoria de vínculos, mismo patrón que SupplierProductLink
Catálogo de tipos de percepción (`PerceptionType`, campos mínimos `nombre`+`activo`, Settings cluster) + `PurchasePerception` (hijo de `Purchase`, calco de `PurchaseLine`) + `SupplierPerceptionLink`/`PerceptionLinkMemory` (calco exacto de `SupplierProductLink`/`ProductLinkMemory` — memoria de vínculos humanos proveedor+texto→tipo de percepción, gana sobre la sugerencia de la IA).

`Purchase.percepciones` es un decimal cacheado (suma de líneas de percepción, igual criterio que `subtotal`), y la relación se llama `Purchase::perceptions()` (inglés) a propósito — evita choque de nombre con el atributo/columna `percepciones`. `total = subtotal - descuento + iva + percepciones` (antes era sin el término percepciones), recalculado en 3 lugares que hay que mantener sincronizados: `PurchaseForm::recalculateTotal()`, `ScanPurchase::recalculateTotals()`, y `ScanPurchase::confirmar()`.

En `PurchaseForm.php` el repeater de percepciones usa `Select::make('perception_type_id')->relationship('perceptionType', 'nombre')` (válido porque el Repeater padre tiene `->relationship()`, hay contexto de modelo real). En `ScanPurchase.php` el repeater 'perceptions' NO tiene `->relationship()` (es solo estado, como 'lineas'), así que el Select ahí usa `->options()` en vez de `->relationship()` — igual que 'lineas'/`product_id` usa `getSearchResultsUsing` en vez de `->relationship('product', ...)`.

`InvoiceExtractor` inyecta el catálogo de `PerceptionType` activos en el prompt (igual que el catálogo de productos) y pide `percepciones: [{descripcion, monto, matched_perception_type_id}]`; `rawPercepciones()`/`normalize()` tratan la ausencia de la clave `percepciones` en la respuesta de la IA como `[]` (retrocompatible con respuestas viejas).
