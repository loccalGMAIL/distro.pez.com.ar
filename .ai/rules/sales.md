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

## Nested repeater suffixAction: open a modal via modalContent, don't fight the button's default click
A Filament `Action` used as `->suffixAction()` on a field nested inside a `Repeater` item (like `product_id`'s `scanBarcode` action) always renders with Filament's own `wire:click="mountAction(...)"`. You cannot cleanly override that click client-side (Livewire's listener and any extra `x-on:click` you add both fire — there's no reliable way to stop just the default one), so don't try to intercept the click to run custom JS first.

Instead, give the action `->modalContent(view(...))` and NO `->schema()`/form fields. A plain click then opens Filament's own modal (no double-fire, no collision) and the modal's Blade view can run whatever custom JS it needs (e.g. camera access for `SaleForm::scanBarcodeAction()`). To submit a result back without a visible form field, call `$wire.callMountedAction({ key: value })` from JS — it re-runs the action's `action()` closure with `$arguments['key']` populated and closes the modal, same as clicking a submit button would.

Also: `$parentRepeaterItemIndex` and `Livewire $livewire` (the utilities used in a field's `afterStateUpdated`) are NOT injectable inside an `Action::make(...)->action(fn (...))` closure defined via `suffixAction()`, even though `Get $get`/`Set $set` ARE and resolve correctly scoped to that repeater item. Don't rely on `$parentRepeaterItemIndex` there.

## Resumen es un slide-over en mobile: CSS mobile-first, no duplicar el componente
La Section "Resumen" de `SaleForm` (subtotal/total/botón "Finalizar") es el MISMO componente en mobile y desktop — nunca la dupliques en dos schemas distintos por breakpoint. El comportamiento difiere solo por CSS, usando el mismo breakpoint que ya define el layout: `->columns(4)` en el schema raíz mapea internamente a `'lg' => 4` (ver `HasColumns::columns()`), o sea que por debajo de `lg` YA es una sola columna. `resumenSlideOverAttributes()` aprovecha eso: clases sin prefijo (`fixed`, `translate-x-full` vía `:class` de Alpine, etc.) la esconden fuera de pantalla en mobile; las mismas utilidades con prefijo `lg:` (`lg:static`, `lg:translate-x-0`, ...) la devuelven a su lugar en la grilla en desktop SIN transición — porque en el cascade de Tailwind, la variante responsive con el breakpoint activo siempre gana sobre la utilidad sin prefijo, sea cual sea el estado de Alpine. Así, en desktop el resultado es bit-a-bit igual a como era antes de este cambio, pase lo que pase con el estado `open`.

El trigger flotante y el backdrop (`resumen-toggle.blade.php`) y el botón de cerrar (`resumen-close-button.blade.php`, dentro de la Section vía `afterHeader()`) son DOS scopes de Alpine (`x-data`) independientes — no comparten estado directo porque están en subárboles del DOM distintos. Se sincronizan exclusivamente disparando el evento de window `resumen-toggle` (`x-on:resumen-toggle.window="open = ! open"` en ambos) desde CUALQUIER acción de abrir/cerrar (botón flotante, click en backdrop, botón X). Si alguno de esos tres controles alguna vez setea `open` directamente en vez de despachar el evento, se desincroniza del otro scope (bug real que ya pasó una vez: el backdrop cerraba su propio estado pero la Section seguía "abierta"). Todo intento de UI que dependa de un solo `x-data` compartido entre la Section y el trigger externo requeriría un wrapper común que hoy no existe en el schema (los top-level components de `$schema->components([...])` no comparten un contenedor propio) — no lo agregues sin necesidad real, el patrón de evento de window ya cubre el caso.
