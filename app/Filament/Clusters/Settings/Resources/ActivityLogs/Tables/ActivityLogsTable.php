<?php

namespace App\Filament\Clusters\Settings\Resources\ActivityLogs\Tables;

use App\Listeners\LogAuthenticationActivity;
use App\Models\User;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Enums\FontFamily;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

class ActivityLogsTable
{
    /**
     * Eventos registrados: los de Eloquent (alta/edición/baja de cualquier
     * modelo con LogsActivity) más los de autenticación que escribe
     * LogAuthenticationActivity.
     *
     * @var array<string, string>
     */
    private const EVENT_LABELS = [
        'created' => 'Creación',
        'updated' => 'Edición',
        'deleted' => 'Eliminación',
        'restored' => 'Restauración',
        'login' => 'Ingreso',
        'logout' => 'Salida',
        'failed_login' => 'Ingreso fallido',
        'lockout' => 'Bloqueo',
        'password_reset' => 'Cambio de contraseña',
        'registered' => 'Alta de usuario',
    ];

    /**
     * @var array<string, string>
     */
    private const EVENT_COLORS = [
        'created' => 'success',
        'updated' => 'info',
        'deleted' => 'danger',
        'restored' => 'warning',
        'login' => 'success',
        'logout' => 'gray',
        'failed_login' => 'danger',
        'lockout' => 'danger',
        'password_reset' => 'warning',
        'registered' => 'success',
    ];

    /**
     * @var array<string, string>
     */
    private const LOG_LABELS = [
        'default' => 'Cambios de datos',
        LogAuthenticationActivity::LOG_NAME => 'Ingresos al sistema',
    ];

    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('causer.name')
                    ->label('Usuario')
                    ->default('Sistema'),
                TextColumn::make('description')
                    ->label('Descripción')
                    ->searchable(),
                TextColumn::make('subject_type')
                    ->label('Modelo')
                    ->formatStateUsing(fn (?string $state): ?string => $state ? Str::afterLast($state, '\\') : null)
                    ->badge(),
                TextColumn::make('event')
                    ->label('Evento')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): ?string => $state ? (self::EVENT_LABELS[$state] ?? $state) : null)
                    ->color(fn (?string $state): string => $state ? (self::EVENT_COLORS[$state] ?? 'gray') : 'gray'),
                TextColumn::make('properties.ip')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('log_name')
                    ->label('Tipo')
                    ->options(self::LOG_LABELS),
                SelectFilter::make('event')
                    ->label('Evento')
                    ->options(self::EVENT_LABELS),
                SelectFilter::make('causer_id')
                    ->label('Usuario')
                    ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all()),
                SelectFilter::make('subject_type')
                    ->label('Modelo')
                    ->options(fn (): array => self::subjectTypeOptions()),
                Filter::make('fecha')
                    ->schema([
                        DatePicker::make('desde')->label('Desde'),
                        DatePicker::make('hasta')->label('Hasta'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['desde'] ?? null, fn (Builder $query, string $desde): Builder => $query->whereDate('created_at', '>=', $desde))
                        ->when($data['hasta'] ?? null, fn (Builder $query, string $hasta): Builder => $query->whereDate('created_at', '<=', $hasta))),
            ])
            ->recordActions([
                ViewAction::make()
                    ->schema([
                        TextEntry::make('causer.name')
                            ->label('Usuario')
                            ->default('Sistema'),
                        TextEntry::make('description')
                            ->label('Descripción'),
                        TextEntry::make('event')
                            ->label('Evento')
                            ->formatStateUsing(fn (?string $state): ?string => $state ? (self::EVENT_LABELS[$state] ?? $state) : null),
                        TextEntry::make('subject_type')
                            ->label('Modelo')
                            ->formatStateUsing(fn (?string $state): ?string => $state ? Str::afterLast($state, '\\') : null),
                        TextEntry::make('created_at')
                            ->label('Fecha')
                            ->dateTime(),
                        TextEntry::make('properties.ip')
                            ->label('IP'),
                        TextEntry::make('properties')
                            ->label('Cambios')
                            ->formatStateUsing(function (array|Collection|null $state): string {
                                $properties = $state instanceof Collection ? $state->all() : ($state ?? []);

                                return filled($properties)
                                    ? json_encode($properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                                    : '—';
                            })
                            ->fontFamily(FontFamily::Mono)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    /**
     * Modelos que efectivamente aparecen en el registro, para no ofrecer
     * filtros vacíos.
     *
     * @return array<string, string>
     */
    private static function subjectTypeOptions(): array
    {
        return Activity::query()
            ->whereNotNull('subject_type')
            ->distinct()
            ->orderBy('subject_type')
            ->pluck('subject_type')
            ->mapWithKeys(fn (string $type): array => [$type => Str::afterLast($type, '\\')])
            ->all();
    }
}
