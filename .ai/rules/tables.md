---
paths:
  - 'app/Filament/**/Tables/**'
  - 'app/Filament/**/RelationManagers/**'
---

# Tables

## Acciones de fila siempre como ícono (`->iconButton()`)
A pedido explícito: toda acción dentro de `->recordActions([...])` —incluidas las nativas `EditAction`, `ViewAction`, `DeleteAction`, `RestoreAction`— lleva `->iconButton()`, para que la columna de acciones no se ensanche con el texto. El `->label()` se sigue definiendo (Filament lo usa como tooltip). Las `Action::make()` custom necesitan además un `->icon(Heroicon::...)` explícito, porque sin ícono un `iconButton` queda vacío. Esto aplica a tablas de Resources y RelationManagers; no a las acciones de una `Notification`.

## Links a otro listado ya filtrado: `getUrl('index', ['filters' => [...]])`
`ListRecords` persiste los filtros en la query string como `filters` (`#[Url(as: 'filters')]`), así que para abrir un listado pre-filtrado se pasa `Resource::getUrl('index', ['filters' => ['<filtro>' => ['value' => $id]]])`. Ejemplos: la columna Stock de `ProductsTable` (→ movimientos de stock por `product_id`) y la acción "Ver compras" (→ compras por `product_id`).

## Filtros que agregan columnas (`withSum`, `withCount`, `addSelect`): usar `->baseQuery()`, no `->query()`
Filament aplica el `->query()` de cada filtro dentro de un `$query->where(function ($query) {...})` anidado (`HasFilters::applyFiltersToTableQuery()`), así que cualquier select que se agregue ahí (`withSum`, `withCount`, `addSelect`) se pierde y la columna queda vacía, aunque el filtrado en sí funcione. El `where`/`whereHas` va en `->query()`; lo que agrega columnas va en `->baseQuery()`, que recibe la consulta principal. Ejemplo: el filtro "Producto" de `PurchasesTable` (`filterByProduct()` + `sumProductUnits()`).

## Cards en mobile: `stackedOnMobile()` global, no `Split`/`Stack`
`AppServiceProvider::configureTables()` aplica `Table::configureUsing(fn (Table $table) => $table->stackedOnMobile())` a todas las tablas: debajo de `sm` (640px) cada fila se ve como una card (etiqueta: valor) con un selector de orden, y en pantallas más anchas queda la tabla normal. Es el mismo HTML para los dos tamaños; solo cambia el CSS de Filament (ya compilado, no hace falta `npm run build`). No usar `Split`/`Stack` para esto: con un componente de layout Filament saca los encabezados también en desktop. Si alguna tabla puntual no tiene que apilarse, encadenar `->stackedOnMobile(false)`.
