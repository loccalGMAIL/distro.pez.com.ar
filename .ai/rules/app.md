---
paths:
  - 'app/**,database/**,phpstan.neon'
---

# App

## PHPStan nivel 7 tiene que quedar en 0 errores
`composer ci:check` corre `types:check` (PHPStan 7 + larastan) ANTES de los tests: si hay un error de tipos, los tests del CI ni se ejecutan. Convenciones que hacen falta para mantenerlo en 0:

- Modelos: `/** @use HasFactory<XFactory> */` sobre el `use`, y `@return BelongsTo<Related, $this>` / `HasMany<Related, $this>` / `MorphTo<Model, $this>` en cada relación. Sin eso larastan devuelve `Model` y todo acceso a atributos falla.
- `Model::find($x)` con un valor `mixed` (ej. `$get('product_id')` de un form de Filament) se infiere `Model|Collection|null`; usar `Model::query()->whereKey($x)->first()` (mismo comportamiento, tipo `?Model`).
- `$a?->b ?? $c` se marca como error (`nullsafe.neverNull`): dentro de `??` PHP ya tolera leer una propiedad de null, así que va `$a->b ?? $c`.
- `collect($mixed)` no resuelve sus genéricos. Narrowear antes (`is_array()`) o pasar por un helper con `@return array<int, array<string, mixed>>` (ver `lineRows()` en SaleForm/PurchaseForm/ScanPurchase).
- Formas de array que cruzan métodos: declararlas con `@phpstan-type` (ver `InvoiceExtractor`). `Collection` es invariante en TValue: pasar la misma forma de array entre métodos falla, con array plano no.
- Nada de baselines, `@phpstan-ignore` ni `@var` inline para tapar errores.

## Rangos de fechas: comparar contra Carbon, nunca contra `->toDateString()`
Las columnas `date` de Eloquent (ej. `Sale.fecha`) se guardan con el formato de la conexión (`Y-m-d H:i:s`), o sea `'2026-08-31 00:00:00'` — MySQL lo trunca al leerlo por ser una columna DATE, pero SQLite (el driver de los tests) guarda y compara el string tal cual. Por eso `whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])` **deja afuera todo el último día del rango**: `'2026-08-31 00:00:00' > '2026-08-31'`. Es un bug que solo aparece el último día del mes y solo en tests (rompió el CI un 31), no en producción, así que no se ve hasta que se ve.

Pasar SIEMPRE los Carbon crudos (`whereBetween('fecha', [now()->startOfMonth(), now()->endOfMonth()])`): `endOfMonth()` es `23:59:59` y cubre el día entero en los dos motores. Si un test depende de un borde de mes/día, fijar el reloj con `$this->travelTo(Carbon::parse('...'))` en vez de esperar que el CI no corra ese día (ver `VentasDelMesWidgetTest` / `ProductoMasVendidoWidgetTest`).
