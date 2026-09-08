<?php

namespace App\Filament\Clusters\Settings\Resources\PerceptionTypes\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PerceptionTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nombre')
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('porcentaje')
                    ->label('Porcentaje')
                    ->helperText('Opcional: autocompleta el monto al cargar una compra a mano.')
                    ->numeric()
                    ->suffix('%'),
                Toggle::make('afecta_costo')
                    ->label('Afecta el costo del producto')
                    ->helperText('Si está prendido, esta percepción se prorratea entre las líneas de la compra y sube el costo (y el precio de venta) de los productos. Apagalo para impuestos recuperables, como crédito fiscal de IVA.')
                    ->required()
                    ->default(true),
                Toggle::make('activo')
                    ->required()
                    ->default(true),
            ]);
    }
}
