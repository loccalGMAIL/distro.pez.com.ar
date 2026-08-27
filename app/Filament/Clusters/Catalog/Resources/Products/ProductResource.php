<?php

namespace App\Filament\Clusters\Catalog\Resources\Products;

use App\Filament\Clusters\Catalog\CatalogCluster;
use App\Filament\Clusters\Catalog\Resources\Products\Pages\CreateProduct;
use App\Filament\Clusters\Catalog\Resources\Products\Pages\EditProduct;
use App\Filament\Clusters\Catalog\Resources\Products\Pages\ListProducts;
use App\Filament\Clusters\Catalog\Resources\Products\Schemas\ProductForm;
use App\Filament\Clusters\Catalog\Resources\Products\Tables\ProductsTable;
use App\Models\PriceList;
use App\Models\Product;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $recordTitleAttribute = 'nombre';

    protected static ?string $modelLabel = 'Producto';

    protected static ?string $pluralModelLabel = 'Productos';

    protected static ?string $cluster = CatalogCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return ProductForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductsTable::configure($table);
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
        return ['nombre', 'sku', 'barcode'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        if (! $record instanceof Product) {
            return 'Producto';
        }

        $priceListId = PriceList::where('predeterminada', true)->value('id');
        $precio = number_format((float) $record->precioParaLista($priceListId), 2, ',', '.');

        return "{$record->nombre} — \${$precio}";
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
