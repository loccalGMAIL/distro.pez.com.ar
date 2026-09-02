<?php

namespace App\Filament\Clusters\Finance\Resources\Payments\RelationManagers;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Supplier;
use Closure;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AllocationsRelationManager extends RelationManager
{
    protected static string $relationship = 'allocations';

    protected static ?string $title = 'Imputaciones';

    public function form(Schema $schema): Schema
    {
        /** @var Payment $payment */
        $payment = $this->getOwnerRecord();

        return $schema
            ->components([
                Select::make('allocatable_type')
                    ->label('Comprobante')
                    ->options(match ($payment->party_type) {
                        Supplier::class => [Purchase::class => 'Compra'],
                        Customer::class => [Sale::class => 'Venta'],
                        default => [Purchase::class => 'Compra', Sale::class => 'Venta'],
                    })
                    ->default($payment->party_type === Customer::class ? Sale::class : Purchase::class)
                    ->live()
                    ->required(),
                Select::make('allocatable_id')
                    ->label('Número')
                    ->options(function (Get $get) use ($payment): array {
                        $type = $get('allocatable_type');

                        if (! $type || ! class_exists($type)) {
                            return [];
                        }

                        // Solo comprobantes confirmados de la misma
                        // contraparte del pago -de lo contrario el select
                        // lista toda la cartera de compras/ventas del
                        // sistema, de cualquier proveedor o cliente,
                        // incluidas las anuladas (con numero = null).
                        $partyColumn = $type === Purchase::class ? 'supplier_id' : 'customer_id';

                        return $type::query()
                            ->where($partyColumn, $payment->party_id)
                            ->where('status', 'confirmada')
                            ->whereNotNull('numero')
                            ->pluck('numero', 'id')
                            ->all();
                    })
                    ->searchable()
                    ->required(),
                TextInput::make('monto')
                    ->required()
                    ->numeric()
                    ->minValue(0.01)
                    ->prefix('$')
                    ->rules([
                        fn (Get $get, ?PaymentAllocation $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get, $record): void {
                            $type = $get('allocatable_type');
                            $id = $get('allocatable_id');

                            if (! $type || ! class_exists($type) || ! $id) {
                                return;
                            }

                            $allocatable = $type::query()->find($id);

                            if (! $allocatable) {
                                return;
                            }

                            $montoAnterior = $record !== null ? (float) $record->monto : 0.0;
                            $saldoDisponible = (float) $allocatable->saldo + $montoAnterior;

                            if ((float) $value > $saldoDisponible) {
                                $fail("El monto no puede superar el saldo pendiente del comprobante (\${$saldoDisponible}).");
                            }
                        },
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('allocatable_type')
                    ->label('Comprobante')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        Purchase::class => 'Compra',
                        Sale::class => 'Venta',
                        default => (string) $state,
                    }),
                TextColumn::make('allocatable.numero')
                    ->label('Número'),
                TextColumn::make('monto')
                    ->money('ARS', locale: 'es_AR'),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
