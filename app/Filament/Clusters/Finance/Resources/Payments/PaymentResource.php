<?php

namespace App\Filament\Clusters\Finance\Resources\Payments;

use App\Filament\Clusters\Finance\FinanceCluster;
use App\Filament\Clusters\Finance\Resources\Payments\Pages\CreatePayment;
use App\Filament\Clusters\Finance\Resources\Payments\Pages\EditPayment;
use App\Filament\Clusters\Finance\Resources\Payments\Pages\ListPayments;
use App\Filament\Clusters\Finance\Resources\Payments\RelationManagers\AllocationsRelationManager;
use App\Filament\Clusters\Finance\Resources\Payments\Schemas\PaymentForm;
use App\Filament\Clusters\Finance\Resources\Payments\Tables\PaymentsTable;
use App\Models\Payment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $modelLabel = 'Pago';

    protected static ?string $pluralModelLabel = 'Pagos';

    protected static ?string $cluster = FinanceCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return PaymentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PaymentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            AllocationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPayments::route('/'),
            'create' => CreatePayment::route('/create'),
            'edit' => EditPayment::route('/{record}/edit'),
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
