---
paths:
  - 'app/Models/{Product,PriceList,Sale,Customer}.php'
---

# Models

## Precio de producto siempre sale de listas de precios (sin precio_venta)
`products.precio_venta` ya NO existe (eliminado). Productos solo cargan `costo_ultimo`; el precio de venta sale siempre de `PriceList::precioPara($product)` = `costo_ultimo * PriceList::multiplicador()`.

Cada `PriceList` tiene un `porcentaje` (markup, o descuento si es negativo) y un `based_on_price_list_id` opcional (self-referencing FK). Si `based_on_price_list_id` es null, el % se aplica sobre `costo_ultimo`. Si está seteado, el % se aplica sobre el precio ya calculado de esa otra lista (cadenas, ej. "Minorista" = "Mayorista" − 5%). No hay override por producto — el mismo % aplica a todos los productos de esa lista.

`multiplicador()` es independiente del producto (compone el % de toda la cadena de `based_on`), así que se puede precalcular una vez por lista y reusar en muchas filas — ver `ProductsTable::priceListColumns()`, que arma una columna por cada `PriceList` activa sin recorrer la cadena por cada producto. `based_on_price_list_id` usa `restrictOnDelete()`: no se puede borrar una lista de la que otras dependen sin reasignarlas antes. Las referencias circulares se rechazan con `PriceList::wouldCreateCycle()`, validado en `PriceListForm`.

`Customer.price_list_id` es OBLIGATORIO (NOT NULL, FK `restrictOnDelete()`) — todo cliente tiene que tener una lista asignada.

`Sale` tiene su propio `price_list_id` nullable (no el del cliente): se autocompleta desde `customer_id` vía `afterStateUpdated` en `SaleForm`, pero es pisable a mano — cubre venta de mostrador sin cliente. `LinesRelationManager` de Sales usa `$sale->price_list_id` (no `$sale->customer->price_list_id`) para resolver `Product::precioParaLista()` al elegir producto en una línea.

`Product::precioParaLista(?int $priceListId)`: sin lista (null o no encontrada) devuelve `costo_ultimo` formateado sin recargo — ya no hay fallback a un precio manual.
