<?php

namespace App\Filament\Clusters\Finance\Resources\Expenses\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ExpenseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('expense_category_id')
                    ->label('Categoría')
                    ->relationship('category', 'nombre')
                    ->searchable()
                    ->preload()
                    ->required()
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
                Select::make('supplier_id')
                    ->label('Proveedor')
                    ->relationship('supplier', 'razon_social')
                    ->searchable()
                    ->preload(),
                Select::make('user_id')
                    ->label('Usuario')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
                DatePicker::make('fecha')
                    ->required(),
                TextInput::make('descripcion')
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
                TextInput::make('comprobante_numero')
                    ->label('Nº de comprobante'),
                TextInput::make('archivo_path')
                    ->label('Archivo'),
            ]);
    }
}
