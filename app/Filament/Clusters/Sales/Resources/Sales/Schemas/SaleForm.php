<?php

namespace App\Filament\Clusters\Sales\Resources\Sales\Schemas;

use App\Filament\Clusters\Partners\Resources\Customers\Schemas\CustomerForm;
use App\Models\Customer;
use App\Models\PriceList;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Warehouse;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Support\RawJs;
use Livewire\Component as Livewire;

class SaleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(4)
            ->components([
                Section::make()
                    ->columnSpan(3)
                    ->columns(4)
                    ->extraAttributes([
                        // Cualquier campo de una sola línea (fecha, cliente, lista,
                        // el botón del producto, etc.) dispara
                        // el submit implícito del navegador al presionar Enter dentro
                        // del <form> del modal, y Livewire lo procesa vía `wire:submit`
                        // en el propio <form> (por encima de este wrapper), así que
                        // frenarlo recién en el evento "submit" llega tarde. Lo cortamos
                        // acá, en el keydown, antes de que el navegador dispare el
                        // submit implícito. Si en ese momento no hay ninguna línea
                        // cargada todavía, ese mismo Enter agrega la primera (mismo
                        // botón "Agregar producto" que usa "descuento" para las
                        // siguientes líneas).
                        'x-on:keydown.enter.prevent' => <<<'JS'
                            if (! $el.querySelector('.fi-fo-table-repeater tbody > tr')) {
                                $el.querySelector('.fi-fo-table-repeater-add button')?.click()
                            }
                            JS,
                    ])
                    ->schema(self::mainFields())
                    ->belowContent(fn () => view('filament.sale-form.resumen-toggle')),
                Section::make('Resumen')
                    ->columnSpan(1)
                    ->dense()
                    ->inlineLabel()
                    ->extraAttributes(self::resumenSlideOverAttributes())
                    ->afterHeader(fn () => view('filament.sale-form.resumen-close-button'))
                    ->schema(self::summaryFields())
                    ->footer(fn (?Sale $record) => $record ? [] : [
                        Action::make('crear')
                            ->label('Finalizar')
                            ->color('primary')
                            ->submit('callMountedAction')
                            ->extraAttributes(['class' => 'w-full']),
                    ]),
            ]);
    }

    /**
     * @return array<int, Component>
     */
    private static function mainFields(): array
    {
        return [
            // TextInput::make('numero')
            //     ->disabled()
            //     ->dehydrated(false)
            //     ->placeholder('Automático'),
            DatePicker::make('fecha')
                ->required()
                ->default(now()),
            Select::make('customer_id')
                ->label('Cliente')
                ->relationship('customer', 'razon_social')
                ->searchable()
                ->preload()
                ->required()
                ->default(fn () => Customer::where('predeterminado', true)->value('id'))
                ->live()
                ->afterStateUpdated(function (Set $set, ?string $state) {
                    if ($state) {
                        $set('price_list_id', Customer::find($state)?->price_list_id);
                    }
                })
                ->createOptionForm(CustomerForm::quickCreateFields())
                ->createOptionModalHeading('Nuevo cliente')
                ->columnSpan(2),
            Select::make('price_list_id')
                ->label('Lista')
                ->relationship('priceList', 'nombre')
                ->searchable()
                ->preload()
                ->default(fn () => PriceList::where('predeterminada', true)->value('id')),

            Repeater::make('lines')
                ->label('Líneas')
                ->relationship()
                ->live()
                ->default([])
                ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculateSummaryFromRoot($get, $set))
                ->extraAttributes([
                    'style' => 'font-size: 0.75rem;',
                    'x-on:sale-line-product-picked.window' => <<<'JS'
                        setTimeout(() => {
                            $el.querySelectorAll('tbody > tr')[$event.detail.index]
                                ?.querySelector('[data-repeater-field=cantidad]')
                                ?.focus();
                        }, 300)
                        JS,
                ])
                ->table([
                    TableColumn::make('Código')->width('130px'),
                    TableColumn::make('Producto'),
                    TableColumn::make('Cantidad')->width('70px'),
                    TableColumn::make('Precio')->width('110px')->alignment(Alignment::End),
                    TableColumn::make('Descuento')->width('110px')->alignment(Alignment::End),
                    TableColumn::make('Subtotal')->width('110px')->alignment(Alignment::End),
                ])
                ->compact()
                ->schema([
                    TextEntry::make('barcode')
                        ->hiddenLabel()
                        ->state(fn (Get $get) => Product::find($get('product_id'))?->barcode ?? '—')
                        ->wrap(false)
                        ->extraAttributes(['style' => 'font-size: 0.75rem;']),
                    Select::make('product_id')
                        ->label('Producto')
                        ->hiddenLabel()
                        ->searchable()
                        ->autofocus()
                        ->getSearchResultsUsing(fn (string $search): array => Product::query()
                            ->where('activo', true)
                            ->where(fn ($query) => $query
                                ->where('nombre', 'like', "%{$search}%")
                                ->orWhere('barcode', 'like', "%{$search}%"))
                            ->limit(50)
                            ->pluck('nombre', 'id')
                            ->all())
                        ->getOptionLabelUsing(fn ($value): ?string => Product::find($value)?->nombre)
                        ->placeholder('Nombre o código')
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (Set $set, Get $get, ?string $state, Livewire $livewire, int $parentRepeaterItemIndex) {
                            $product = $state ? Product::find($state) : null;

                            if (! $product) {
                                return;
                            }

                            $set('precio_unit', $product->precioParaLista($get('../../price_list_id')));
                            $set('costo_unit', $product->costo_ultimo);
                            self::recalculateLine($get, $set);

                            $livewire->dispatch('sale-line-product-picked', index: $parentRepeaterItemIndex);
                        })
                        ->suffixAction(self::scanBarcodeAction()),
                    TextInput::make('cantidad')
                        ->hiddenLabel()
                        ->required()
                        ->numeric()
                        ->default(1)
                        ->live()
                        ->extraInputAttributes([
                            'data-repeater-field' => 'cantidad',
                            'style' => 'font-size: 0.75rem;',
                            'x-on:keydown.enter.prevent.stop' => <<<'JS'
                                $el.closest('tr').querySelector('[data-repeater-field=descuento]')?.focus()
                                JS,
                        ])
                        ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculateLine($get, $set)),
                    Hidden::make('precio_unit')
                        ->default(0),
                    TextEntry::make('precio_unit_display')
                        ->hiddenLabel()
                        ->state(fn (Get $get) => (float) ($get('precio_unit') ?? 0))
                        ->numeric(decimalPlaces: 2, decimalSeparator: ',', thousandsSeparator: '.')
                        ->prefix('$')
                        ->extraAttributes(['style' => 'display: block; text-align: right; font-size: 0.75rem;']),
                    TextInput::make('descuento')
                        ->hiddenLabel()
                        ->required()
                        ->prefix('$')
                        ->default(0.0)
                        ->live()
                        ->mask(RawJs::make("\$money(\$input, ',')"))
                        ->dehydrateStateUsing(fn ($state) => self::parseAmount($state))
                        ->extraInputAttributes([
                            'data-repeater-field' => 'descuento',
                            'style' => 'text-align: right; font-size: 0.75rem;',
                            'x-on:keydown.enter.prevent.stop' => <<<'JS'
                                $el.closest('.fi-fo-table-repeater').querySelector('.fi-fo-table-repeater-add button')?.click()
                                JS,
                        ])
                        ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculateLine($get, $set)),
                    Hidden::make('subtotal')
                        ->default(0),
                    TextEntry::make('subtotal_display')
                        ->hiddenLabel()
                        ->state(fn (Get $get) => (float) ($get('subtotal') ?? 0))
                        ->numeric(decimalPlaces: 2, decimalSeparator: ',', thousandsSeparator: '.')
                        ->prefix('$')
                        ->extraAttributes(['style' => 'display: block; text-align: right; font-size: 0.75rem;']),
                    Hidden::make('costo_unit')
                        ->default(0),
                ])
                ->addActionLabel('Agregar producto')
                ->required()
                ->minItems(1)
                ->columnSpanFull(),
            Section::make()
                ->collapsible()
                ->collapsed()
                ->compact()
                ->columns(2)
                ->columnSpanFull()
                ->extraAttributes(self::compactAccordionAttributes())
                ->schema([
                    Select::make('warehouse_id')
                        ->label('Depósito')
                        ->relationship('warehouse', 'nombre')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->default(fn () => Warehouse::where('predeterminado', true)->value('id')),
                    Select::make('user_id')
                        ->label('Usuario')
                        ->relationship('user', 'name')
                        ->searchable()
                        ->preload()
                        ->default(fn () => auth()->id()),
                    Textarea::make('observaciones')
                        ->columnSpanFull(),
                ]),
            Hidden::make('status')
                ->default('confirmada'),
        ];
    }

    /**
     * @return array<int, Component>
     */
    private static function summaryFields(): array
    {
        return [
            Hidden::make('subtotal')
                ->default(0),
            TextEntry::make('subtotal_display')
                ->label('Subtotal')
                ->state(fn (Get $get) => (float) ($get('subtotal') ?? 0))
                ->numeric(decimalPlaces: 2, decimalSeparator: ',', thousandsSeparator: '.')
                ->prefix('$')
                ->extraAttributes(['style' => 'display: block; text-align: right;']),
            TextInput::make('descuento')
                ->label('Descuento')
                // ->helperText('Único campo editable a mano del resumen.')
                ->required()
                ->prefix('$')
                ->default(0.0)
                ->live()
                ->mask(RawJs::make("\$money(\$input, ',')"))
                ->dehydrateStateUsing(fn ($state) => self::parseAmount($state))
                ->extraInputAttributes(['style' => 'text-align: right;'])
                ->afterStateUpdated(function (Get $get, Set $set) {
                    $subtotal = (float) ($get('subtotal') ?? 0);
                    $descuento = self::parseAmount($get('descuento'));

                    $set('total', number_format($subtotal - $descuento, 2, '.', ''));
                }),
            Hidden::make('total')
                ->default(0),
            TextEntry::make('total_display')
                ->label('Total')
                ->state(fn (Get $get) => (float) ($get('total') ?? 0))
                ->numeric(decimalPlaces: 2, decimalSeparator: ',', thousandsSeparator: '.')
                ->prefix('$')
                ->extraAttributes(['style' => 'display: block; text-align: right;']),
            Section::make()
                ->collapsible()
                ->collapsed()
                ->compact()
                ->inlineLabel(false)
                ->extraAttributes(self::compactAccordionAttributes())
                ->schema([
                    Select::make('condicion_pago')
                        ->label('Condición de pago')
                        ->options(['contado' => 'Contado', 'cuenta_corriente' => 'Cuenta corriente'])
                        ->default('contado')
                        ->required()
                        ->extraFieldWrapperAttributes(['class' => '[&_label]:text-xs'])
                        ->extraInputAttributes(['style' => 'font-size: 0.75rem; line-height: 1.25rem; padding-top: 0.125rem; padding-bottom: 0.125rem;']),
                    Select::make('medio_pago')
                        ->label('Medio de pago')
                        ->options([
                            'efectivo' => 'Efectivo',
                            'transferencia' => 'Transferencia',
                            'tarjeta' => 'Tarjeta',
                            'cheque' => 'Cheque',
                            'mercadopago' => 'Mercado Pago',
                            'otro' => 'Otro',
                        ])
                        ->default('efectivo')
                        ->extraFieldWrapperAttributes(['class' => '[&_label]:text-xs'])
                        ->extraInputAttributes(['style' => 'font-size: 0.75rem; line-height: 1.25rem; padding-top: 0.125rem; padding-bottom: 0.125rem;']),
                ]),
        ];
    }

    /**
     * Header más chico para los Section sin título usados como acordeón
     * (Depósito/Usuario/Observaciones y Forma de pago): botón de plegar y
     * padding reducidos, para que el control ocupe lo mínimo cuando está
     * colapsado.
     *
     * @return array<string, string>
     */
    private static function compactAccordionAttributes(): array
    {
        return [
            'class' => '[&_.fi-section-header]:px-2! [&_.fi-section-header]:py-1! [&_.fi-section-collapse-btn]:size-5! [&_.fi-section-collapse-btn_.fi-icon]:size-3!',
        ];
    }

    /**
     * Convierte la Section "Resumen" en un panel deslizable (slide-over) por
     * debajo del breakpoint `lg` — el mismo en el que `columns(4)` pasa a
     * mostrarla como sidebar (ver `HasColumns::columns()`: un entero se
     * mapea a `'lg' => $columns`, con `1` para todo lo anterior). Es CSS
     * puro sobre el mismo componente (nada de markup duplicado): clases sin
     * prefijo lo sacan de flujo y lo esconden fuera de pantalla; las
     * variantes `lg:` de esas mismas clases lo devuelven a su lugar en la
     * grilla sin transición, así que en desktop el resultado es idéntico al
     * de antes. El toggle es puro Alpine local (`open`), sincronizado por el
     * evento de window `resumen-toggle` con el botón flotante y el botón de
     * cerrar (ver resumen-toggle.blade.php / resumen-close-button.blade.php).
     *
     * @return array<string, string>
     */
    private static function resumenSlideOverAttributes(): array
    {
        return [
            'x-data' => '{ open: false }',
            'x-on:resumen-toggle.window' => 'open = ! open',
            ':class' => "open ? 'translate-x-0' : 'translate-x-full'",
            'class' => 'fixed inset-y-0 right-0 z-40 flex w-5/6 max-w-sm flex-col overflow-y-auto shadow-2xl transition-transform duration-300 ease-in-out '
                .'lg:static lg:inset-auto lg:z-auto lg:flex lg:w-auto lg:max-w-none lg:translate-x-0 lg:overflow-visible lg:shadow-sm lg:transition-none',
        ];
    }

    /**
     * Botón de escaneo de código de barras con la cámara del dispositivo,
     * visible solo en mobile (`sm:hidden`) porque es donde tiene sentido usar
     * la cámara trasera. Abre un modal sin formulario propio: el contenido
     * (resources/views/filament/sale-form/barcode-scanner.blade.php) corre
     * la cámara vía `BarcodeDetector` nativo del navegador y, al detectar un
     * código, llama a `$wire.callMountedAction({ barcode })` — que ejecuta
     * este mismo `action()` con `$arguments['barcode']` poblado, sin volver a
     * pasar por el click del botón (evita el doble disparo de mountAction).
     */
    private static function scanBarcodeAction(): Action
    {
        return Action::make('scanBarcode')
            ->label('Escanear código de barras')
            ->icon(Heroicon::OutlinedCamera)
            ->color('gray')
            ->extraAttributes(['class' => 'sm:hidden'])
            ->modalHeading('Escanear código de barras')
            ->modalWidth(Width::Small)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Cerrar')
            ->modalContent(fn () => view('filament.sale-form.barcode-scanner'))
            ->action(function (array $arguments, Set $set, Get $get) {
                $barcode = trim((string) ($arguments['barcode'] ?? ''));

                if ($barcode === '') {
                    return;
                }

                $product = Product::query()
                    ->where('activo', true)
                    ->where('barcode', $barcode)
                    ->first();

                if (! $product) {
                    Notification::make()
                        ->title("No se encontró ningún producto con el código {$barcode}")
                        ->danger()
                        ->send();

                    return;
                }

                $set('product_id', $product->id);
                $set('precio_unit', $product->precioParaLista($get('../../price_list_id')));
                $set('costo_unit', $product->costo_ultimo);
                self::recalculateLine($get, $set);
            });
    }

    /**
     * Recalcula el subtotal de una línea (cantidad × precio − descuento).
     * Se llama desde dentro de un item del repeater, así que $get/$set están
     * a nivel de esa línea.
     */
    private static function recalculateLine(Get $get, Set $set): void
    {
        $cantidad = (float) ($get('cantidad') ?? 0);
        $precioUnit = (float) ($get('precio_unit') ?? 0);
        $descuento = self::parseAmount($get('descuento'));

        $set('subtotal', number_format(($cantidad * $precioUnit) - $descuento, 2, '.', ''));

        self::recalculateSummaryFromRoot(
            fn (string $key) => $get("../../{$key}"),
            fn (string $key, $value) => $set("../../{$key}", $value),
        );
    }

    /**
     * Suma el subtotal de todas las líneas y recalcula subtotal/total del
     * resumen (el descuento del resumen es manual, no se toca acá). Recibe
     * $get/$set ya resueltos a nivel raíz (o closures que lo simulan).
     */
    private static function recalculateSummaryFromRoot(Get|Closure $get, Set|Closure $set): void
    {
        $lines = $get('lines') ?? [];

        $subtotal = collect($lines)->sum(fn (array $line): float => (float) ($line['subtotal'] ?? 0));

        $set('subtotal', number_format($subtotal, 2, '.', ''));

        $descuento = self::parseAmount($get('descuento'));

        $set('total', number_format($subtotal - $descuento, 2, '.', ''));
    }

    /**
     * Convierte un monto tipeado con el mask "$money($input, ',')" (punto de
     * miles, coma decimal) a un float plano. Los valores que ya llegan
     * numéricos (ej. el 0.0 por defecto, sin tocar) se devuelven tal cual.
     */
    private static function parseAmount(mixed $value): float
    {
        if (blank($value)) {
            return 0.0;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        return (float) str_replace(['.', ','], ['', '.'], (string) $value);
    }
}
