---
paths:
  - 'app/Filament/Tables/Columns/**'
---

# Columns

## Usar ActivoColumn para el flag `activo` en tablas, no IconColumn crudo
`App\Filament\Tables\Columns\ActivoColumn` (paralela a `App\Filament\Forms\Components\NativeFileButton`) reemplaza `IconColumn::make('activo')->boolean()` en toda tabla con ese flag. Además del ✓/✗, el ícono es clickeable: dispara una `Action` con `requiresConfirmation()` que alterna `activo` vía `$record->update(['activo' => ! $record->activo])`, autorizada con `->authorize('update')` (misma Policy que `EditAction`). Usa `$record->getAttribute('activo')` en vez de `$record->activo` porque el `$record` inyectado es `Model` genérico (la columna se reusa entre modelos distintos) y PHPStan no puede resolver esa property dinámica.

Para impedir que un caso puntual se autodesactive (ej. `UsersTable` con el usuario logueado, porque `User::canAccessPanel()` depende de `activo`), encadenar `->disableToggleWhen(fn ($record) => ...)`.

Ya migradas: Customers, Suppliers, Products, PriceLists, Warehouses, PerceptionTypes, Users. Si el modelo también expone `IconColumn` para otro campo (`predeterminado`, `tracks_lot`, etc.), el `use IconColumn` se mantiene para ese otro campo.
