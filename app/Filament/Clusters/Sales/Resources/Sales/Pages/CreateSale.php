<?php

namespace App\Filament\Clusters\Sales\Resources\Sales\Pages;

use App\Filament\Clusters\Sales\Resources\Sales\SaleResource;
use App\Filament\Clusters\Sales\Resources\Sales\Schemas\SaleForm;
use App\Models\Product;
use App\Models\Sale;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\Str;

class CreateSale extends CreateRecord
{
    protected static string $resource = SaleResource::class;

    /**
     * El botón "Finalizar" ya vive en la Section "Resumen" de SaleForm
     * (->submit('create') en su footer) — no hace falta la barra
     * Crear/Cancelar que Filament agrega por defecto debajo del form.
     */
    protected function getFormActions(): array
    {
        return [];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if ((float) ($data['total'] ?? 0) <= 0.0) {
            Notification::make()
                ->title('El total de la venta debe ser mayor a $0,00.')
                ->danger()
                ->send();

            throw new Halt;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var Sale $record */
        $record = $this->getRecord();

        if ($record->status === 'confirmada') {
            $record->deducirStock();
            $record->comprobanteNotification()->send();
        }
    }

    /**
     * Tap en una card del grid de productos (filament.sale-form.product-
     * cards): abre un modal a pedir cantidad y, al confirmar, agrega (o
     * suma cantidad, si ya está en el carrito) una línea de venta — sin
     * pasar por ningún Select dentro del Repeater (la tabla de líneas es de
     * solo lectura + botón eliminar). Manipula $this->data (el array crudo
     * del form, bindeado vía statePath('data')) en vez de
     * $this->form->getState(), porque este último valida todo el form y
     * fallaría si hay líneas todavía incompletas.
     */
    public function addProductToSaleAction(): Action
    {
        return Action::make('addProductToSale')
            ->modalHeading(fn (array $arguments): string => Product::query()->whereKey($arguments['product'] ?? null)->first()->nombre ?? 'Agregar producto')
            ->modalSubmitActionLabel('Agregar')
            ->modalWidth(Width::Small)
            ->schema([
                TextInput::make('cantidad')
                    ->label('Cantidad')
                    ->numeric()
                    ->required()
                    ->minValue(0.01)
                    ->default(1)
                    ->autofocus(),
            ])
            ->action(function (array $data, array $arguments): void {
                $product = Product::query()->whereKey($arguments['product'] ?? null)->first();

                if (! $product) {
                    return;
                }

                $cantidadAgregada = (float) ($data['cantidad'] ?? 1);

                $saleData = $this->data ?? [];
                $rawLines = $saleData['lines'] ?? [];
                $lines = collect(is_array($rawLines) ? $rawLines : [])->filter(fn ($line): bool => is_array($line));
                $priceListId = $saleData['price_list_id'] ?? null;

                $existingKey = $lines->search(
                    fn (array $line): bool => (int) ($line['product_id'] ?? 0) === $product->id
                );

                if ($existingKey !== false) {
                    $line = $lines[$existingKey];
                    $line['cantidad'] = (float) $line['cantidad'] + $cantidadAgregada;
                    $line['subtotal'] = number_format(
                        ($line['cantidad'] * (float) $line['precio_unit']) - (float) ($line['descuento'] ?? 0),
                        2,
                        '.',
                        ''
                    );
                    $lines[$existingKey] = $line;
                } else {
                    $precioUnit = $product->precioParaLista($priceListId);

                    $lines[(string) Str::uuid()] = [
                        'product_id' => $product->id,
                        'cantidad' => $cantidadAgregada,
                        'precio_unit' => $precioUnit,
                        'descuento' => 0,
                        'subtotal' => number_format($cantidadAgregada * (float) $precioUnit, 2, '.', ''),
                        'costo_unit' => $product->costo_ultimo,
                    ];
                }

                $saleData['lines'] = $lines->all();

                SaleForm::recalculateSummaryFromRoot(
                    fn (string $key) => $saleData[$key] ?? null,
                    function (string $key, $value) use (&$saleData): void {
                        $saleData[$key] = $value;
                    },
                );

                $this->form->fill($saleData);
            });
    }
}
