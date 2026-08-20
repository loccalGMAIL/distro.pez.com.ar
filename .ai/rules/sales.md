---
paths:
  - 'app/Filament/Clusters/Sales/**'
---

# Sales

## Ventas: sin edición, creación por modal, anular revierte stock y cobros
`SaleResource` solo tiene página `index` (sin `create`/`edit`) — crear una venta abre el formulario de `SaleResource::form()` en un modal automáticamente porque no existe página `create` registrada (patrón Filament: al borrar la página, la `CreateAction` del listado se vuelve modal sola). Las líneas ya no usan un RelationManager: viven como `Repeater::make('lines')->relationship()` embebido directamente en `SaleForm`, porque un RelationManager solo está disponible en páginas Edit/View que este resource no tiene.

Las ventas NUNCA se editan después de creadas. Para corregir un error se usa la acción de tabla "Anular" (`Sale::anular()`), no un form. Las acciones de negocio viven como métodos en `app/Models/Sale.php`:
- `Sale::deducirStock()`: crea un `stock_movement` tipo `venta` (cantidad negativa) por cada línea, usando `costo_unit` de la línea. Idempotente (no duplica si ya existen movimientos `venta` para esa venta).
- `Sale::confirmar()`: pasa status a `confirmada` + `deducirStock()`. Se usa desde la acción de tabla "Confirmar" (solo visible si `status === 'borrador'`).
- `Sale::anular()`: revierte cada `stock_movement` tipo `venta` de la venta generando el inverso (tipo `devolucion`, sin borrar el original — es un libro mayor, no se edita histórico), revierte cualquier `PaymentAllocation` ligada a la venta (repone `sin_imputar` en el `Payment` y borra la asignación), y pasa `status` a `anulada`. Idempotente.

Si el status se manda como `confirmada` directo desde el modal de creación (no pasando por `borrador`), el `afterCreate`-equivalente vive en `ListSales::getHeaderActions()` vía `CreateAction::make()->after(...)`, llamando a `deducirStock()` — no hay página `CreateSale` donde poner un hook de página.

`Sale::stockMovements()` es `morphMany(StockMovement::class, 'source')` — usar SIEMPRE `$sale->stockMovements()->create([...])` (relation-based) en vez de `StockMovement::create([..., 'source_type' => ..., 'source_id' => ...])`, porque `StockMovement::$fillable` no incluye `source_type`/`source_id` a propósito; el create vía relación los setea sin pasar por mass-assignment.

Dentro del `Repeater` de líneas, para leer un campo del form padre (ej. `price_list_id`) desde el `afterStateUpdated` de un campo anidado hay que usar `$get('../../price_list_id')` (dos niveles: uno para salir del item del repeater, otro para salir de `lines`) — el parámetro `isAbsolute: true` de `$get()` NO sirve acá porque resuelve contra la raíz literal del path de Livewire, que cambia según el contexto (página vs. modal de acción), no contra la raíz del propio schema.

`Sale.customer_id` es OBLIGATORIO (NOT NULL, FK `restrictOnDelete()`) — ya no existe la "venta de mostrador sin cliente". `Customer` tiene un flag `predeterminado` (mismo patrón que `Warehouse.predeterminado` y `PriceList.predeterminada`: único activo a la vez, se autodesmarca el anterior en `Customer::booted()`) que `SaleForm` usa como default de `customer_id`.

## Líneas de venta: Repeater en modo tabla, no cards
`SaleForm`'s `lines` Repeater usa `->table([TableColumn::make(...), ...])->compact()` (Filament 5 "table repeater") en vez del modo card por defecto — se ve como una factura: encabezados de columna arriba, una fila por línea, ícono de borrar por fila. El orden de los `TableColumn::make()` tiene que coincidir 1:1 con el orden de los campos VISIBLES del `->schema()` (los `Hidden::make()` no cuentan una columna).

El Select de producto NO usa `->relationship('product', 'nombre')` (que solo busca por `nombre`) — usa `->searchable()->getSearchResultsUsing()->getOptionLabelUsing()` manual, buscando por `nombre` O `barcode` a la vez, para poder tipear el código de barras o el nombre indistintamente. Sigue bindeado a la columna `product_id` igual, ya que el Repeater guarda cada item como `SaleLine` vía `->relationship()` a nivel Repeater.

Todos los campos visibles usan `->hiddenLabel()` (los headers de columna del `table()` ya cumplen ese rol).

Precio, subtotal de línea y subtotal/total del resumen se recalculan en cadena (ver `SaleForm::recalculateLine()` / `recalculateSummaryFromRoot()`): producto → precio (de la lista) → subtotal de línea (cantidad×precio−descuento, disabled/computed) → subtotal del resumen (suma de líneas, disabled/computed) → total (subtotal−descuento, disabled/computed). El único campo de descuento editable a mano es el del Resumen (nivel venta); el descuento POR LÍNEA sigue siendo editable también (afecta el subtotal de esa línea).
