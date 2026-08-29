<?php

namespace App\Filament\Clusters\Partners\Resources\Suppliers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class SuppliersTable
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
                TextColumn::make('condicion_pago')
                    ->badge()
                    ->toggleable(),
                TextColumn::make('dias_pago')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('balance')
                    ->money('ARS', locale: 'es_AR')
                    ->sortable()
                    ->toggleable(),
                IconColumn::make('activo')
                    ->boolean()
                    ->toggleable(),
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
