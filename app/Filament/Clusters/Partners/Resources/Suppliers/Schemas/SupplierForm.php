<?php

namespace App\Filament\Clusters\Partners\Resources\Suppliers\Schemas;

use App\Models\Supplier;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

class SupplierForm
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
                Select::make('condicion_pago')
                    ->options(['contado' => 'Contado', 'cuenta_corriente' => 'Cuenta corriente'])
                    ->default('cuenta_corriente')
                    ->required(),
                TextInput::make('dias_pago')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('balance')
                    ->required()
                    ->numeric()
                    ->prefix('$')
                    ->default(0.0),
                Textarea::make('observaciones')
                    ->columnSpanFull(),
                Toggle::make('activo')
                    ->required()
                    ->default(true),
            ]);
    }

    /**
     * Código correlativo: PROV-000001, PROV-000002, ... a partir del mayor
     * número ya usado (incluye proveedores con soft delete, por el unique).
     */
    private static function generateCodigo(): string
    {
        $next = 1 + (Supplier::withTrashed()
            ->where('codigo', 'like', 'PROV-%')
            ->pluck('codigo')
            ->map(fn (string $codigo): int => (int) Str::after($codigo, 'PROV-'))
            ->max() ?? 0);

        return 'PROV-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
