<?php

namespace App\Filament\Clusters\Settings\Pages;

use App\Filament\Clusters\Settings\SettingsCluster;
use App\Models\TimeEntry;
use App\Models\TimeEntrySettlement;
use App\Models\User;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class TimeEntriesReport extends Page implements HasTable
{
    use HasPageShield, InteractsWithTable;

    protected string $view = 'filament.clusters.settings.pages.time-entries-report';

    protected static ?string $cluster = SettingsCluster::class;

    protected static ?string $navigationLabel = 'Reporte de fichajes';

    protected static ?string $title = 'Reporte de fichajes';

    /**
     * Estados de liquidación del filtro. "Pendientes" es el default: es lo que
     * hace que el contador se vea en cero apenas se liquida un período.
     */
    private const ESTADOS_LIQUIDACION = [
        'pendientes' => 'Pendientes de liquidar',
        'liquidados' => 'Liquidados',
    ];

    public function table(Table $table): Table
    {
        return $table
            ->query(TimeEntry::query()->whereNotNull('ended_at'))
            ->defaultSort('started_at', 'desc')
            ->columns([
                TextColumn::make('user.name')
                    ->label('Empleado')
                    ->searchable(),
                TextColumn::make('started_at')
                    ->label('Inicio')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('ended_at')
                    ->label('Fin')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('hours')
                    ->label('Horas')
                    ->state(fn (TimeEntry $record): float => $record->hours()),
                TextColumn::make('pay')
                    ->label('A cobrar')
                    ->state(fn (TimeEntry $record): string => '$'.number_format($record->pay(), 2, ',', '.')),
                TextColumn::make('settlement.id')
                    ->label('Liquidación')
                    ->badge()
                    ->color(fn (TimeEntry $record): string => $record->isSettled() ? 'success' : 'warning')
                    ->state(fn (TimeEntry $record): string => $record->settlement?->numero() ?? 'Pendiente'),
            ])
            ->filters([
                SelectFilter::make('user_id')
                    ->label('Empleado')
                    ->options(fn (): array => User::administrativoOptions()),
                SelectFilter::make('liquidacion')
                    ->label('Estado')
                    ->options(self::ESTADOS_LIQUIDACION)
                    ->default('pendientes')
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(($data['value'] ?? null) === 'pendientes', fn (Builder $query): Builder => $query->whereNull('time_entry_settlement_id'))
                        ->when(($data['value'] ?? null) === 'liquidados', fn (Builder $query): Builder => $query->whereNotNull('time_entry_settlement_id'))),
                Filter::make('periodo')
                    ->schema([
                        DatePicker::make('desde')->label('Desde'),
                        DatePicker::make('hasta')->label('Hasta'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['desde'] ?? null, fn (Builder $query, string $desde): Builder => $query->whereDate('started_at', '>=', $desde))
                        ->when($data['hasta'] ?? null, fn (Builder $query, string $hasta): Builder => $query->whereDate('started_at', '<=', $hasta))),
            ]);
    }

    /**
     * Totales por empleado (horas y a cobrar), acorde a los filtros
     * actualmente aplicados a la tabla.
     *
     * @return Collection<int, array{user: User, hours: float, pay: float}>
     */
    public function summaryRows(): Collection
    {
        $filters = $this->currentFilters();

        return TimeEntry::summarize(
            TimeEntry::closedQuery($filters['userId'], $filters['desde'], $filters['hasta'], $filters['liquidacion'])
                ->with('user')
                ->get()
        );
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        $filters = $this->currentFilters();

        return [
            $this->liquidarAction(),
            Action::make('descargarPdf')
                ->label('Descargar PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->url(fn (): string => route('time-entries.report.pdf', [
                    'user_id' => $filters['userId'],
                    'desde' => $filters['desde'],
                    'hasta' => $filters['hasta'],
                    'liquidacion' => $filters['liquidacion'],
                ]), shouldOpenInNewTab: true),
        ];
    }

    /**
     * Liquida todo lo pendiente de un empleado hasta una fecha de corte: deja
     * asentado el pago y saca esos ciclos del contador.
     */
    private function liquidarAction(): Action
    {
        return Action::make('liquidar')
            ->label('Liquidar honorarios')
            ->icon('heroicon-o-banknotes')
            ->color('success')
            ->visible(fn (): bool => Auth::user()?->can('Create:TimeEntrySettlement') ?? false)
            ->schema([
                Select::make('user_id')
                    ->label('Empleado')
                    ->options(fn (): array => User::administrativoOptions())
                    ->required()
                    ->live(),
                DatePicker::make('hasta')
                    ->label('Liquidar fichajes hasta')
                    ->default(now())
                    ->required()
                    ->live(),
                Placeholder::make('resumen')
                    ->label('A liquidar')
                    ->content(function (Get $get): string {
                        $userId = $get('user_id');

                        if (blank($userId)) {
                            return 'Elegí un empleado para ver el detalle.';
                        }

                        $hasta = $get('hasta');
                        $resumen = TimeEntrySettlement::previsualizar((int) $userId, filled($hasta) ? (string) $hasta : null);

                        if ($resumen['ciclos'] === 0) {
                            return 'Sin fichajes pendientes en ese período.';
                        }

                        return sprintf(
                            '%d ciclos · %s horas × $%s/h = $%s',
                            $resumen['ciclos'],
                            number_format($resumen['horas'], 2, ',', '.'),
                            number_format($resumen['tarifa'], 2, ',', '.'),
                            number_format($resumen['total'], 2, ',', '.'),
                        );
                    }),
                DatePicker::make('fecha_pago')
                    ->label('Fecha de pago')
                    ->default(now())
                    ->required(),
                Select::make('medio_pago')
                    ->label('Medio de pago')
                    ->options([
                        'efectivo' => 'Efectivo',
                        'transferencia' => 'Transferencia',
                        'cheque' => 'Cheque',
                        'tarjeta' => 'Tarjeta',
                        'mercadopago' => 'Mercado Pago',
                        'otro' => 'Otro',
                    ])
                    ->default('efectivo')
                    ->required(),
                TextInput::make('referencia')
                    ->label('Referencia')
                    ->helperText('Nº de transferencia, cheque o comprobante.')
                    ->maxLength(80),
                Textarea::make('observaciones')
                    ->label('Observaciones')
                    ->rows(2),
            ])
            ->modalHeading('Liquidar honorarios de fichaje')
            ->modalDescription('Se asienta el pago y esos fichajes dejan de contar como pendientes. Se puede anular después.')
            ->modalSubmitActionLabel('Liquidar')
            ->action(function (array $data): void {
                $employee = User::query()->whereKey($data['user_id'])->first();

                if (! $employee instanceof User) {
                    Notification::make()->danger()->title('No se encontró el empleado')->send();

                    return;
                }

                $actor = User::query()->whereKey(Auth::id())->first();

                try {
                    $settlement = TimeEntrySettlement::liquidar($employee, (string) $data['hasta'], [
                        'fecha_pago' => isset($data['fecha_pago']) ? (string) $data['fecha_pago'] : null,
                        'medio_pago' => isset($data['medio_pago']) ? (string) $data['medio_pago'] : null,
                        'referencia' => isset($data['referencia']) ? (string) $data['referencia'] : null,
                        'observaciones' => isset($data['observaciones']) ? (string) $data['observaciones'] : null,
                        'liquidated_by_user_id' => $actor?->id,
                    ]);
                } catch (RuntimeException $exception) {
                    Notification::make()->danger()->title('No se pudo liquidar')->body($exception->getMessage())->send();

                    return;
                }

                Notification::make()
                    ->success()
                    ->title("Liquidación {$settlement->numero()} registrada")
                    ->body('$'.number_format((float) $settlement->total, 2, ',', '.').' por '.number_format((float) $settlement->horas, 2, ',', '.').' horas.')
                    ->actions([
                        Action::make('recibo')
                            ->label('Ver recibo')
                            ->url(route('time-entry-settlements.receipt.pdf', $settlement), shouldOpenInNewTab: true),
                    ])
                    ->send();
            });
    }

    /**
     * Filtros actualmente aplicados a la tabla (empleado, rango de fechas y
     * estado de liquidación), usados tanto para el resumen como para armar la
     * URL del PDF.
     *
     * @return array{userId: ?int, desde: ?string, hasta: ?string, liquidacion: ?string}
     */
    private function currentFilters(): array
    {
        $userId = $this->tableFilters['user_id']['value'] ?? null;
        $desde = $this->tableFilters['periodo']['desde'] ?? null;
        $hasta = $this->tableFilters['periodo']['hasta'] ?? null;
        $liquidacion = $this->tableFilters['liquidacion']['value'] ?? null;

        return [
            'userId' => filled($userId) ? (int) $userId : null,
            'desde' => filled($desde) ? (string) $desde : null,
            'hasta' => filled($hasta) ? (string) $hasta : null,
            'liquidacion' => filled($liquidacion) ? (string) $liquidacion : null,
        ];
    }
}
