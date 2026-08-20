<?php

namespace App\Filament\Clusters\Catalog\Resources\PriceLists\Schemas;

use App\Models\PriceList;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

class PriceListForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nombre')
                    ->required(),
                TextInput::make('codigo')
                    ->suffixAction(
                        Action::make('generateCodigo')
                            ->label('Generar')
                            ->icon(Heroicon::Sparkles)
                            ->action(fn (Set $set) => $set('codigo', self::generateCodigo())),
                    ),
                Select::make('based_on_price_list_id')
                    ->label('Basado en')
                    ->helperText('Opcional. Si se elige otra lista, el % se aplica sobre el precio ya calculado de esa lista en vez de sobre el costo.')
                    ->relationship(name: 'basedOn', titleAttribute: 'nombre', ignoreRecord: true)
                    ->searchable()
                    ->preload()
                    ->live()
                    ->rules([
                        fn (?PriceList $record): Closure => function (string $attribute, $value, Closure $fail) use ($record) {
                            if ($value === null) {
                                return;
                            }

                            if (($record ?? new PriceList)->wouldCreateCycle((int) $value)) {
                                $fail('Esa lista generaría una referencia circular entre listas de precios.');
                            }
                        },
                    ]),
                TextInput::make('porcentaje')
                    ->label(fn (Get $get): string => $get('based_on_price_list_id')
                        ? 'Recargo / descuento sobre esa lista'
                        : 'Recargo sobre costo')
                    ->helperText('Usá un valor negativo para aplicar un descuento.')
                    ->required()
                    ->numeric()
                    ->suffix('%')
                    ->default(0),
                Toggle::make('predeterminada')
                    ->label('Predeterminada para ventas')
                    ->helperText('Se usa como lista de precios por defecto al crear una venta. Solo puede haber una.')
                    ->default(false),
                Toggle::make('activo')
                    ->required()
                    ->default(true),
            ]);
    }

    /**
     * Código correlativo: LP-001, LP-002, ... a partir del mayor número
     * ya usado.
     */
    private static function generateCodigo(): string
    {
        $next = 1 + (PriceList::query()
            ->where('codigo', 'like', 'LP-%')
            ->pluck('codigo')
            ->map(fn (string $codigo): int => (int) Str::after($codigo, 'LP-'))
            ->max() ?? 0);

        return 'LP-'.str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }
}
