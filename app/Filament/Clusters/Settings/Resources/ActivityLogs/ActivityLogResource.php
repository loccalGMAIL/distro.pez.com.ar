<?php

namespace App\Filament\Clusters\Settings\Resources\ActivityLogs;

use App\Filament\Clusters\Settings\Resources\ActivityLogs\Pages\ListActivityLogs;
use App\Filament\Clusters\Settings\Resources\ActivityLogs\Tables\ActivityLogsTable;
use App\Filament\Clusters\Settings\SettingsCluster;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity;

class ActivityLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $modelLabel = 'Actividad';

    protected static ?string $pluralModelLabel = 'Registro de actividades';

    protected static ?string $navigationLabel = 'Actividad';

    protected static ?string $cluster = SettingsCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?int $navigationSort = 2;

    public static function table(Table $table): Table
    {
        return ActivityLogsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivityLogs::route('/'),
        ];
    }
}
