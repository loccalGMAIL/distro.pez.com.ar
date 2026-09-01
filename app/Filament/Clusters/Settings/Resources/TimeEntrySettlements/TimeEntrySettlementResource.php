<?php

namespace App\Filament\Clusters\Settings\Resources\TimeEntrySettlements;

use App\Filament\Clusters\Settings\Resources\TimeEntrySettlements\Pages\ListTimeEntrySettlements;
use App\Filament\Clusters\Settings\Resources\TimeEntrySettlements\Tables\TimeEntrySettlementsTable;
use App\Filament\Clusters\Settings\SettingsCluster;
use App\Models\TimeEntrySettlement;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TimeEntrySettlementResource extends Resource
{
    protected static ?string $model = TimeEntrySettlement::class;

    protected static ?string $modelLabel = 'Liquidación';

    protected static ?string $pluralModelLabel = 'Liquidaciones';

    protected static ?string $cluster = SettingsCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?int $navigationSort = 4;

    public static function table(Table $table): Table
    {
        return TimeEntrySettlementsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    /**
     * Sólo listado: las liquidaciones se generan desde el Reporte de fichajes
     * (que es donde está el contador de lo pendiente) y no se editan a mano —
     * si hay un error, se anulan y se vuelven a liquidar.
     */
    public static function getPages(): array
    {
        return [
            'index' => ListTimeEntrySettlements::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
