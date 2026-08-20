<?php

namespace App\Filament\Clusters\Partners\Resources\Customers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('codigo')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('razon_social')
                    ->searchable(),
                TextColumn::make('cuit')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('telefono')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('domicilio')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('localidad')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('condicion_pago')
                    ->badge(),
                TextColumn::make('priceList.nombre')
                    ->label('Lista de precios')
                    ->sortable(),
                TextColumn::make('balance')
                    ->money('ARS')
                    ->sortable(),
                IconColumn::make('predeterminado')
                    ->label('Predeterminado')
                    ->boolean()
                    ->toggleable(),
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
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
