<?php

namespace App\Filament\Clusters\Purchases\Resources\Purchases\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LinesRelationManager extends RelationManager
{
    protected static string $relationship = 'lines';

    protected static ?string $title = 'Líneas';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_id')
                    ->label('Producto')
                    ->relationship('product', 'nombre')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('cantidad')
                    ->required()
                    ->numeric(),
                TextInput::make('costo_unit')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('subtotal')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('producto')
            ->columns([
                TextColumn::make('product.nombre')
                    ->label('Producto')
                    ->searchable(),
                TextColumn::make('cantidad')
                    ->numeric(),
                TextColumn::make('costo_unit')
                    ->money('ARS'),
                TextColumn::make('subtotal')
                    ->money('ARS'),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
