<?php

namespace App\Filament\Clusters\Settings\Pages;

use App\Filament\Clusters\Settings\SettingsCluster;
use App\Models\TimeEntry;
use App\Models\User;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class TimeEntriesReport extends Page implements HasTable
{
    use HasPageShield, InteractsWithTable;

    protected string $view = 'filament.clusters.settings.pages.time-entries-report';

    protected static ?string $cluster = SettingsCluster::class;

    protected static ?string $navigationLabel = 'Reporte de fichajes';

    protected static ?string $title = 'Reporte de fichajes';

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
            ])
            ->filters([
                SelectFilter::make('user_id')
                    ->label('Empleado')
                    ->options(fn (): array => User::administrativoOptions()),
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
            TimeEntry::closedQuery($filters['userId'], $filters['desde'], $filters['hasta'])
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
            Action::make('descargarPdf')
                ->label('Descargar PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->url(fn (): string => route('time-entries.report.pdf', [
                    'user_id' => $filters['userId'],
                    'desde' => $filters['desde'],
                    'hasta' => $filters['hasta'],
                ]), shouldOpenInNewTab: true),
        ];
    }

    /**
     * Filtros actualmente aplicados a la tabla (empleado y rango de
     * fechas), usados tanto para el resumen como para armar la URL del PDF.
     *
     * @return array{userId: ?int, desde: ?string, hasta: ?string}
     */
    private function currentFilters(): array
    {
        $userId = $this->tableFilters['user_id']['value'] ?? null;
        $desde = $this->tableFilters['periodo']['desde'] ?? null;
        $hasta = $this->tableFilters['periodo']['hasta'] ?? null;

        return [
            'userId' => filled($userId) ? (int) $userId : null,
            'desde' => filled($desde) ? (string) $desde : null,
            'hasta' => filled($hasta) ? (string) $hasta : null,
        ];
    }
}
