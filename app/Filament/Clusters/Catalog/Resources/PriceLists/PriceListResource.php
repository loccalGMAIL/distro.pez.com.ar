<?php

namespace App\Filament\Clusters\Catalog\Resources\PriceLists;

use App\Filament\Clusters\Catalog\CatalogCluster;
use App\Filament\Clusters\Catalog\Resources\PriceLists\Pages\CreatePriceList;
use App\Filament\Clusters\Catalog\Resources\PriceLists\Pages\EditPriceList;
use App\Filament\Clusters\Catalog\Resources\PriceLists\Pages\ListPriceLists;
use App\Filament\Clusters\Catalog\Resources\PriceLists\Schemas\PriceListForm;
use App\Filament\Clusters\Catalog\Resources\PriceLists\Tables\PriceListsTable;
use App\Models\PriceList;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PriceListResource extends Resource
{
    protected static ?string $model = PriceList::class;

    protected static ?string $recordTitleAttribute = 'nombre';

    protected static ?string $modelLabel = 'Lista de precios';

    protected static ?string $pluralModelLabel = 'Listas de precios';

    protected static ?string $cluster = CatalogCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return PriceListForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PriceListsTable::configure($table);
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
        return ['nombre', 'codigo'];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPriceLists::route('/'),
            'create' => CreatePriceList::route('/create'),
            'edit' => EditPriceList::route('/{record}/edit'),
        ];
    }
}
