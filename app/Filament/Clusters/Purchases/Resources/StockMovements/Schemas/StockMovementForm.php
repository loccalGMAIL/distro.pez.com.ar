<?php

namespace App\Filament\Clusters\Purchases\Resources\StockMovements\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class StockMovementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_id')
                    ->label('Producto')
                    ->relationship('product', 'nombre')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('warehouse_id')
                    ->label('Depósito')
                    ->relationship('warehouse', 'nombre')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('quantity')
                    ->label('Cantidad')
                    ->helperText('Positivo = entrada, negativo = salida.')
                    ->required()
                    ->numeric(),
                TextInput::make('unit_cost')
                    ->label('Costo unitario')
                    ->required()
                    ->numeric()
                    ->default(0.0)
                    ->prefix('$'),
                Select::make('type')
                    ->label('Tipo')
                    ->options([
                        'compra' => 'Compra',
                        'venta' => 'Venta',
                        'ajuste' => 'Ajuste',
                        'devolucion' => 'Devolución',
                        'devolucion_prov' => 'Devolución a proveedor',
                        'merma' => 'Merma',
                        'transferencia' => 'Transferencia',
                    ])
                    ->required(),
                Select::make('user_id')
                    ->label('Usuario')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
                Textarea::make('motivo')
                    ->columnSpanFull(),
            ]);
    }
}
