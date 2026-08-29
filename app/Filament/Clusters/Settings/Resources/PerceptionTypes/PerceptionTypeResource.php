<?php

namespace App\Filament\Clusters\Settings\Resources\PerceptionTypes;

use App\Filament\Clusters\Settings\Resources\PerceptionTypes\Pages\CreatePerceptionType;
use App\Filament\Clusters\Settings\Resources\PerceptionTypes\Pages\EditPerceptionType;
use App\Filament\Clusters\Settings\Resources\PerceptionTypes\Pages\ListPerceptionTypes;
use App\Filament\Clusters\Settings\Resources\PerceptionTypes\Schemas\PerceptionTypeForm;
use App\Filament\Clusters\Settings\Resources\PerceptionTypes\Tables\PerceptionTypesTable;
use App\Filament\Clusters\Settings\SettingsCluster;
use App\Models\PerceptionType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PerceptionTypeResource extends Resource
{
    protected static ?string $model = PerceptionType::class;

    protected static ?string $recordTitleAttribute = 'nombre';

    protected static ?string $modelLabel = 'Tipo de percepción';

    protected static ?string $pluralModelLabel = 'Tipos de percepción';

    protected static ?string $cluster = SettingsCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return PerceptionTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PerceptionTypesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    /**
     * @return array<string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['nombre'];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPerceptionTypes::route('/'),
            'create' => CreatePerceptionType::route('/create'),
            'edit' => EditPerceptionType::route('/{record}/edit'),
        ];
    }
}
