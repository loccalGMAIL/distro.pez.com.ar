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
