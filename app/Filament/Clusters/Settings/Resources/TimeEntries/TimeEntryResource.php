<?php

namespace App\Filament\Clusters\Settings\Resources\TimeEntries;

use App\Filament\Clusters\Settings\Resources\TimeEntries\Pages\CreateTimeEntry;
use App\Filament\Clusters\Settings\Resources\TimeEntries\Pages\EditTimeEntry;
use App\Filament\Clusters\Settings\Resources\TimeEntries\Pages\ListTimeEntries;
use App\Filament\Clusters\Settings\Resources\TimeEntries\Schemas\TimeEntryForm;
use App\Filament\Clusters\Settings\Resources\TimeEntries\Tables\TimeEntriesTable;
use App\Filament\Clusters\Settings\SettingsCluster;
use App\Models\TimeEntry;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TimeEntryResource extends Resource
{
    protected static ?string $model = TimeEntry::class;

    protected static ?string $modelLabel = 'Fichaje';

    protected static ?string $pluralModelLabel = 'Fichajes';

    protected static ?string $cluster = SettingsCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return TimeEntryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TimeEntriesTable::configure($table);
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
            'index' => ListTimeEntries::route('/'),
            'create' => CreateTimeEntry::route('/create'),
            'edit' => EditTimeEntry::route('/{record}/edit'),
        ];
    }
}
