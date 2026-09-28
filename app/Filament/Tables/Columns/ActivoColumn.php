<?php

namespace App\Filament\Tables\Columns;

use Closure;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Illuminate\Database\Eloquent\Model;

/**
 * IconColumn ✓/✗ estándar para el flag `activo` de un modelo, con el ícono
 * también como botón: un clic alterna el valor (con confirmación), en vez de
 * obligar a entrar a Editar para desactivar/reactivar. Reemplaza
 * `IconColumn::make('activo')->boolean()` en cualquier tabla que tenga esa
 * columna.
 *
 * La autorización usa la Policy del recurso vía `authorize('update')`
 * (mismo criterio que Filament usa para `EditAction`) — quien no puede
 * editar el registro no puede alternar su estado.
 */
class ActivoColumn extends IconColumn
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Activo')
            ->boolean()
            ->tooltip(fn (Model $record): string => self::isActivo($record) ? 'Clic para desactivar' : 'Clic para activar')
            ->action(
                Action::make('toggleActivo')
                    ->requiresConfirmation()
                    ->authorize('update')
                    ->modalHeading(fn (Model $record): string => self::isActivo($record) ? 'Desactivar registro' : 'Activar registro')
                    ->modalDescription(fn (Model $record): string => self::isActivo($record)
                        ? '¿Seguro que querés desactivar este registro?'
                        : '¿Seguro que querés activar este registro?')
                    ->action(function (Model $record): void {
                        $activo = ! self::isActivo($record);
                        $record->update(['activo' => $activo]);

                        Notification::make()
                            ->title($activo ? 'Registro activado' : 'Registro desactivado')
                            ->success()
                            ->send();
                    }),
            );
    }

    private static function isActivo(Model $record): bool
    {
        return (bool) $record->getAttribute('activo');
    }

    public static function getDefaultName(): ?string
    {
        return 'activo';
    }

    /**
     * Deshabilita el botón de alternar para los registros que cumplan la
     * condición (ej. que un usuario no pueda desactivarse a sí mismo).
     */
    public function disableToggleWhen(Closure $condition): static
    {
        $action = $this->getAction();

        if ($action instanceof Action) {
            $action->disabled($condition);
        }

        return $this;
    }
}
