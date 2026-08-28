<?php

namespace App\Filament\Clusters\Catalog\Resources\PriceLists\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PriceListsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')
                    ->searchable(),
                TextColumn::make('codigo')
                    ->searchable(),
                TextColumn::make('basedOn.nombre')
                    ->label('Basado en')
                    ->placeholder('Costo')
                    ->sortable(),
                TextColumn::make('porcentaje')
                    ->label('Recargo')
                    ->suffix('%')
                    ->sortable(),
                IconColumn::make('predeterminada')
                    ->label('Predeterminada')
                    ->boolean(),
                IconColumn::make('compartible')
                    ->label('Compartible')
                    ->boolean(),
                IconColumn::make('activo')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
