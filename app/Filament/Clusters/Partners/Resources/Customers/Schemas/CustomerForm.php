<?php

namespace App\Filament\Clusters\Partners\Resources\Customers\Schemas;

use App\Models\PriceList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components(self::fields());
    }

    /**
     * @return array<int, Component>
     */
    public static function fields(): array
    {
        return [
            TextInput::make('codigo')
                ->label('Código')
                ->disabled()
                ->dehydrated(false)
                ->placeholder('Se asigna automáticamente al guardar'),
            TextInput::make('razon_social')
                ->required(),
            TextInput::make('cuit'),
            TextInput::make('telefono')
                ->tel(),
            TextInput::make('email')
                ->label('Email address')
                ->email(),
            TextInput::make('domicilio'),
            TextInput::make('localidad'),
            Select::make('condicion_pago')
                ->options(['contado' => 'Contado', 'cuenta_corriente' => 'Cuenta corriente'])
                ->default('contado')
                ->required(),
            Select::make('price_list_id')
                ->label('Lista de precios')
                ->relationship('priceList', 'nombre')
                ->searchable()
                ->preload()
                ->required(),
            TextInput::make('balance')
                ->required()
                ->numeric()
                ->prefix('$')
                ->default(0.0),
            Toggle::make('predeterminado')
                ->label('Cliente predeterminado para ventas')
                ->helperText('Se usa como cliente por defecto al crear una venta. Solo puede haber uno.')
                ->default(false),
            Textarea::make('observaciones')
                ->columnSpanFull(),
            Toggle::make('activo')
                ->required()
                ->default(true),
        ];
    }

    /**
     * Campos mínimos para dar de alta un cliente al vuelo (ej. desde el
     * modal de creación de venta): solo lo que la tabla exige sin default
     * (`razon_social`, `price_list_id` — ver migración
     * `2026_08_18_000300_make_price_list_id_required_on_customers`). El
     * código lo asigna `Customer` al crear; el resto (contacto, condición
     * de pago, saldo, etc.) tiene default en la tabla y se completa
     * después desde el apartado Clientes.
     *
     * @return array<int, Component>
     */
    public static function quickCreateFields(): array
    {
        return [
            TextInput::make('razon_social')
                ->label('Razón social')
                ->required(),
            Select::make('price_list_id')
                ->label('Lista de precios')
                ->relationship('priceList', 'nombre')
                ->searchable()
                ->preload()
                ->default(fn () => PriceList::where('predeterminada', true)->value('id'))
                ->required(),
        ];
    }
}
