<?php

namespace App\Filament\Clusters\Purchases\Resources\Purchases\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PurchaseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('supplier_id')
                    ->label('Proveedor')
                    ->relationship('supplier', 'razon_social')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('warehouse_id')
                    ->label('Depósito')
                    ->relationship('warehouse', 'nombre')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('user_id')
                    ->label('Usuario')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
                Select::make('tipo_comprobante')
                    ->options([
                        'factura_a' => 'Factura A',
                        'factura_b' => 'Factura B',
                        'factura_c' => 'Factura C',
                        'remito' => 'Remito',
                        'otro' => 'Otro',
                    ])
                    ->default('factura_a')
                    ->required(),
                TextInput::make('numero'),
                DatePicker::make('fecha')
                    ->required(),
                DatePicker::make('vence_at')
                    ->label('Vence'),
                TextInput::make('subtotal')
                    ->required()
                    ->numeric()
                    ->prefix('$')
                    ->default(0.0),
                TextInput::make('descuento')
                    ->required()
                    ->numeric()
                    ->prefix('$')
                    ->default(0.0),
                TextInput::make('iva')
                    ->label('IVA')
                    ->required()
                    ->numeric()
                    ->prefix('$')
                    ->default(0.0),
                TextInput::make('total')
                    ->required()
                    ->numeric()
                    ->prefix('$')
                    ->default(0.0),
                TextInput::make('saldo')
                    ->required()
                    ->numeric()
                    ->prefix('$')
                    ->default(0.0),
                Select::make('status')
                    ->options(['borrador' => 'Borrador', 'confirmada' => 'Confirmada', 'anulada' => 'Anulada'])
                    ->default('borrador')
                    ->required(),
                TextInput::make('archivo_path')
                    ->label('Archivo'),
                Textarea::make('observaciones')
                    ->columnSpanFull(),
            ]);
    }
}
