<?php

namespace App\Filament\Clusters\Partners\Resources\Customers\Schemas;

use App\Models\Customer;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('codigo')
                    ->suffixAction(
                        Action::make('generateCodigo')
                            ->label('Generar')
                            ->icon(Heroicon::Sparkles)
                            ->action(fn (Set $set) => $set('codigo', self::generateCodigo())),
                    ),
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
                    ->required(),
            ]);
    }

    /**
     * Código correlativo: CLI-000001, CLI-000002, ... a partir del mayor
     * número ya usado (incluye clientes con soft delete, por el unique).
     */
    private static function generateCodigo(): string
    {
        $next = 1 + (Customer::withTrashed()
            ->where('codigo', 'like', 'CLI-%')
            ->pluck('codigo')
            ->map(fn (string $codigo): int => (int) Str::after($codigo, 'CLI-'))
            ->max() ?? 0);

        return 'CLI-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
