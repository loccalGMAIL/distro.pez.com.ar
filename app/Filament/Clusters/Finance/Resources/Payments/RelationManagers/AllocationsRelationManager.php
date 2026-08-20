<?php

namespace App\Filament\Clusters\Finance\Resources\Payments\RelationManagers;

use App\Models\Purchase;
use App\Models\Sale;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AllocationsRelationManager extends RelationManager
{
    protected static string $relationship = 'allocations';

    protected static ?string $title = 'Imputaciones';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('allocatable_type')
                    ->label('Comprobante')
                    ->options([
                        Purchase::class => 'Compra',
                        Sale::class => 'Venta',
                    ])
                    ->live()
                    ->required(),
                Select::make('allocatable_id')
                    ->label('Número')
                    ->options(function (Get $get): array {
                        $type = $get('allocatable_type');

                        if (! $type || ! class_exists($type)) {
                            return [];
                        }

                        return $type::query()->pluck('numero', 'id')->all();
                    })
                    ->searchable()
                    ->required(),
                TextInput::make('monto')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('allocatable_type')
                    ->label('Comprobante')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        Purchase::class => 'Compra',
                        Sale::class => 'Venta',
                        default => (string) $state,
                    }),
                TextColumn::make('allocatable.numero')
                    ->label('Número'),
                TextColumn::make('monto')
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
