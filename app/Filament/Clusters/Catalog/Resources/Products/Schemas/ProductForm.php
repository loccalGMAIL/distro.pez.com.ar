<?php

namespace App\Filament\Clusters\Catalog\Resources\Products\Schemas;

use App\Models\Product;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('sku')
                    ->label('SKU')
                    ->required()
                    ->suffixAction(
                        Action::make('generateSku')
                            ->label('Generar')
                            ->icon(Heroicon::Sparkles)
                            ->action(fn (Set $set) => $set('sku', self::generateSku())),
                    ),
                TextInput::make('barcode'),
                TextInput::make('nombre')
                    ->required(),
                Select::make('product_category_id')
                    ->label('Categoría')
                    ->relationship('category', 'nombre')
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        TextInput::make('nombre')
                            ->required(),
                        Toggle::make('activo')
                            ->default(true),
                    ])
                    ->editOptionForm([
                        TextInput::make('nombre')
                            ->required(),
                        Toggle::make('activo')
                            ->default(true),
                    ]),
                Select::make('base_unit')
                    ->label('Unidad base')
                    ->options(Product::baseUnitOptions())
                    ->required()
                    ->default('unidad'),
                TextInput::make('costo_ultimo')
                    ->label('Costo')
                    ->required()
                    ->numeric()
                    ->prefix('$')
                    ->default(0.0),
                TextInput::make('min_stock')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                Toggle::make('tracks_lot')
                    ->required(),
                Toggle::make('activo')
                    ->required()
                    ->default(true),
            ]);
    }

    /**
     * Campos mínimos para dar de alta un producto al vuelo (ej. desde el
     * escaneo de facturas): solo lo que la tabla exige sin default (`sku`)
     * más lo que conviene precargar con lo detectado por la IA (`nombre`,
     * `base_unit`, `costo_ultimo`). El resto (barcode, categoría, stock
     * mínimo, etc.) tiene default en la tabla y se completa después desde
     * el catálogo.
     *
     * @return array<int, Component>
     */
    public static function quickCreateFields(): array
    {
        return [
            TextInput::make('sku')
                ->label('SKU')
                ->required()
                ->default(fn () => self::generateSku())
                ->suffixAction(
                    Action::make('generateSku')
                        ->label('Generar')
                        ->icon(Heroicon::Sparkles)
                        ->action(fn (Set $set) => $set('sku', self::generateSku())),
                ),
            TextInput::make('nombre')
                ->required(),
            Select::make('base_unit')
                ->label('Unidad base')
                ->options(Product::baseUnitOptions())
                ->required()
                ->default('unidad'),
            TextInput::make('costo_ultimo')
                ->label('Costo')
                ->required()
                ->numeric()
                ->prefix('$')
                ->default(0.0),
        ];
    }

    /**
     * SKU correlativo: SKU-000001, SKU-000002, ... a partir del mayor
     * número ya usado (incluye productos con soft delete, por el unique).
     */
    public static function generateSku(): string
    {
        $next = 1 + (Product::withTrashed()
            ->where('sku', 'like', 'SKU-%')
            ->pluck('sku')
            ->map(fn (string $sku): int => (int) Str::after($sku, 'SKU-'))
            ->max() ?? 0);

        return 'SKU-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
