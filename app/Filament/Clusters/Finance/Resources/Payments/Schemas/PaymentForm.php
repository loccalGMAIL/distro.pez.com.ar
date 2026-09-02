<?php

namespace App\Filament\Clusters\Finance\Resources\Payments\Schemas;

use App\Models\Customer;
use App\Models\Supplier;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class PaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('party_type')
                    ->label('Tipo')
                    ->options([
                        Supplier::class => 'Proveedor',
                        Customer::class => 'Cliente',
                    ])
                    ->live()
                    ->required(),
                Select::make('party_id')
                    ->label('Proveedor / Cliente')
                    ->options(function (Get $get): array {
                        $partyType = $get('party_type');

                        if (! $partyType || ! class_exists($partyType)) {
                            return [];
                        }

                        return $partyType::query()->pluck('razon_social', 'id')->all();
                    })
                    ->searchable()
                    ->required(),
                Select::make('direccion')
                    ->options(['egreso' => 'Egreso', 'ingreso' => 'Ingreso'])
                    ->default('egreso')
                    ->required(),
                DatePicker::make('fecha')
                    ->required(),
                TextInput::make('monto')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                Select::make('medio_pago')
                    ->options([
                        'efectivo' => 'Efectivo',
                        'transferencia' => 'Transferencia',
                        'cheque' => 'Cheque',
                        'tarjeta' => 'Tarjeta',
                        'mercadopago' => 'Mercado Pago',
                        'otro' => 'Otro',
                    ])
                    ->default('efectivo')
                    ->required(),
                TextInput::make('referencia'),
                TextInput::make('sin_imputar')
                    ->label('Sin imputar')
                    ->helperText('Calculado: monto menos lo imputado a comprobantes. No se edita a mano.')
                    ->numeric()
                    ->prefix('$')
                    ->default(0.0)
                    ->disabled()
                    ->dehydrated(false),
                Select::make('user_id')
                    ->label('Usuario')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
                TextInput::make('comprobante_path')
                    ->label('Comprobante'),
                Textarea::make('observaciones')
                    ->columnSpanFull(),
            ]);
    }
}
