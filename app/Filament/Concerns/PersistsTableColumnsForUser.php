<?php

namespace App\Filament\Concerns;

/**
 * Reemplaza la persistencia en sesión del column manager de Filament
 * (`Filament\Tables\Concerns\HasColumnManager::loadTableColumnsFromSession()`
 * / `persistTableColumns()`) por una tabla por usuario, para que las columnas
 * ocultas sobrevivan al logout (la sesión se invalida en cada logout).
 *
 * Declarar estos métodos acá y hacer `use PersistsTableColumnsForUser;` en la
 * página List* los pisa: un método llega a la clase vía un trait propio, lo
 * que gana sobre el mismo método heredado de ListRecords (que lo trae por su
 * propio `use InteractsWithTable`).
 */
trait PersistsTableColumnsForUser
{
    /**
     * @return array<int, array<string, mixed>>
     */
    protected function loadTableColumnsFromSession(): array
    {
        $stored = auth()->user()
            ?->tableColumnPreferences()
            ->where('table_key', static::class)
            ->value('columns');

        return $stored ?? $this->getDefaultTableColumnState();
    }

    protected function persistTableColumns(): void
    {
        if (! $this->getTable()->persistsColumnsInSession()) {
            return;
        }

        auth()->user()?->tableColumnPreferences()->updateOrCreate(
            ['table_key' => static::class],
            ['columns' => $this->tableColumns],
        );
    }
}
