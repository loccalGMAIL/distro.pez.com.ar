<?php

namespace App\Filament\Clusters\Catalog\Resources\StockMovements;

use App\Filament\Clusters\Catalog\CatalogCluster;
use App\Filament\Clusters\Catalog\Resources\StockMovements\Pages\CreateStockMovement;
use App\Filament\Clusters\Catalog\Resources\StockMovements\Pages\EditStockMovement;
use App\Filament\Clusters\Catalog\Resources\StockMovements\Pages\ListStockMovements;
use App\Filament\Clusters\Catalog\Resources\StockMovements\Schemas\StockMovementForm;
use App\Filament\Clusters\Catalog\Resources\StockMovements\Tables\StockMovementsTable;
use App\Models\StockMovement;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class StockMovementResource extends Resource
{
    protected static ?string $model = StockMovement::class;

    protected static ?string $modelLabel = 'Movimiento de stock';

    protected static ?string $pluralModelLabel = 'Movimientos de stock';

    protected static ?string $cluster = CatalogCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return StockMovementForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StockMovementsTable::configure($table);
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
            'index' => ListStockMovements::route('/'),
            'create' => CreateStockMovement::route('/create'),
            'edit' => EditStockMovement::route('/{record}/edit'),
        ];
    }
}
