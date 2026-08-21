<?php

namespace App\Filament\Clusters\Purchases\Resources\Purchases;

use App\Filament\Clusters\Purchases\PurchasesCluster;
use App\Filament\Clusters\Purchases\Resources\Purchases\Pages\EditPurchase;
use App\Filament\Clusters\Purchases\Resources\Purchases\Pages\ListPurchases;
use App\Filament\Clusters\Purchases\Resources\Purchases\Schemas\PurchaseForm;
use App\Filament\Clusters\Purchases\Resources\Purchases\Tables\PurchasesTable;
use App\Models\Purchase;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PurchaseResource extends Resource
{
    protected static ?string $model = Purchase::class;

    protected static ?string $modelLabel = 'Compra';

    protected static ?string $pluralModelLabel = 'Compras';

    protected static ?string $cluster = PurchasesCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return PurchaseForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PurchasesTable::configure($table);
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
            'index' => ListPurchases::route('/'),
            'edit' => EditPurchase::route('/{record}/edit'),
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
