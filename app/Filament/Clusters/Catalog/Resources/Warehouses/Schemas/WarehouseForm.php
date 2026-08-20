<?php

namespace App\Filament\Clusters\Catalog\Resources\Warehouses\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class WarehouseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nombre')
                    ->required(),
                Select::make('tipo')
                    ->options(['deposito' => 'Deposito', 'camion' => 'Camion', 'sucursal' => 'Sucursal'])
                    ->default('deposito')
                    ->required(),
                Toggle::make('predeterminado')
                    ->label('Predeterminado para ventas')
                    ->helperText('Se usa como depósito por defecto al crear una venta. Solo puede haber uno.')
                    ->default(false),
                Toggle::make('activo')
                    ->required(),
            ]);
    }
}
