---
paths:
  - 'app/Policies/**'
---

# Policies

## Vendedor acotado: ViewAll:Sale y ShareAll:PriceList
`vendedor` ya NO es rol legado: tiene lista explícita en ShieldSeeder (ventas, clientes sin borrar, clusters Ventas/Socios, widgets NuevaVenta/CompartirLista) y RolePermissionSeeder solo cubre `deposito`/`chofer`.
Dos permisos custom en config/filament-shield.php: `ViewAll:Sale` (sin él SalePolicy/SaleResource::getEloquentQuery limitan a `sales.user_id = auth()->id()`, y CreateSale fuerza user_id propio) y `ShareAll:PriceList` (sin él PriceList::orderedForSharing()/isSharableBy() exigen además `compartible_vendedores`). Al sumar un rol con alcance total, darle ambos. En tests, orderedForSharing() sin usuario devuelve solo listas `compartible_vendedores`: pasar un admin.
