---
paths:
  - 'app/Filament/Clusters/Purchases/**'
  - 'app/Models/{Purchase,PurchaseLine,Supplier}.php'
---

# Purchases

## Compras: sin RelationManager de líneas, alta por modal, pero SÍ hay edición (a diferencia de Ventas)
`PurchaseResource` solo registra páginas `index` y `edit` (sin `create`) — igual que `SaleResource`, crear una compra abre `PurchaseResource::form()` en un modal automáticamente porque no existe página `create` registrada. Las líneas viven como `Repeater::make('lines')->relationship()` embebido en `PurchaseForm` (no un RelationManager), y ese mismo form se reusa tal cual en la página `edit`.

A diferencia de Ventas (que nunca se edita después de creada), las compras SÍ tienen un estado `borrador` editable: toda alta entra como `borrador` (`Hidden::make('status')->default('borrador')` en `PurchaseForm`, no `'confirmada'` como en `SaleForm`). `EditPurchase::mount()` bloquea el acceso si `$record->status !== 'borrador'` (redirige al listado con una notificación) — una vez confirmada o anulada, la única forma de "corregir" es la acción de tabla "Anular", nunca el form.

## Confirmar/anular: ingresa stock y actualiza el costo vigente del producto
Las acciones de negocio viven en `app/Models/Purchase.php`:
- `Purchase::aumentarStock()`: crea un `stock_movement` tipo `compra` (cantidad positiva) por cada línea, usando `costo_unit` de la línea, y además hace `$line->product->update(['costo_ultimo' => $line->costo_unit])` — el costo vigente de cada producto queda igual al último precio de compra pagado. Idempotente (no duplica si ya existen movimientos `compra` para esa compra).
- `Purchase::confirmar()`: pasa `status` a `confirmada` + `aumentarStock()`. Se usa desde la acción de tabla "Confirmar" (solo visible si `status === 'borrador'`).
- `Purchase::anular()`: revierte cada `stock_movement` tipo `compra` generando el inverso (tipo `devolucion_prov`, sin borrar el original — mismo criterio de libro mayor que `Sale::anular()`), revierte cualquier `PaymentAllocation` ligada a la compra, y pasa `status` a `anulada`. Idempotente. **Deliberadamente NO revierte `costo_ultimo`** — el costo del producto no vuelve atrás al anular una compra vieja, porque puede haber compras/costos más recientes pisándolo; revertirlo sería reescribir historia con datos potencialmente desactualizados.

`Purchase::stockMovements()` es `morphMany(StockMovement::class, 'source')` — usar SIEMPRE `$purchase->stockMovements()->create([...])` (relation-based), nunca `StockMovement::create([..., 'source_type' => ...])`, mismo motivo que en Ventas (`StockMovement::$fillable` no incluye `source_type`/`source_id` a propósito).

## `Purchase.numero` es el comprobante del proveedor, no un correlativo interno
A diferencia de `Sale.numero` (auto-generado en `Sale::booted()`), `Purchase.numero` se tipea a mano: es el número de factura/remito que puso el proveedor. Es único por `(supplier_id, tipo_comprobante, numero)` (constraint de la migración), no globalmente. No agregar ninguna auto-generación tipo contador para este campo — no aplica, cada proveedor numera su propio comprobante.

## Tabla: fecha fija primera columna, todo ordenable (desviación intencional de SalesTable)
A pedido explícito: `fecha` es la primera columna y NO lleva `->toggleable()` (siempre visible); el resto de las columnas sí son togglables. Todas las columnas —incluidas las de relación como `supplier.razon_social`, `warehouse.nombre`, `user.name`— llevan `->sortable()`. `SalesTable` NO hace esto (ahí las columnas de relación no son sortable); no asumir que ese patrón aplica acá también si se toca `PurchasesTable` en el futuro.
