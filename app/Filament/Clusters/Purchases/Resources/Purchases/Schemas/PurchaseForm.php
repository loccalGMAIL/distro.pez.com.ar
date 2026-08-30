<?php

namespace App\Filament\Clusters\Purchases\Resources\Purchases\Schemas;

use App\Filament\Forms\Components\NativeFileButton;
use App\Models\PerceptionType;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Warehouse;
use Carbon\Carbon;
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
use Filament\Support\Icons\Heroicon;
use Filament\Support\RawJs;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class PurchaseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make()
                    ->columnSpan(2)
                    ->columns(4)
                    ->schema(self::headerFields()),
                Section::make('Resumen')
                    ->columnSpan(1)
                    ->dense()
                    ->inlineLabel()
                    ->schema(self::summaryFields()),
                self::linesRepeater(),
                self::perceptionsRepeater(),
            ]);
    }

    /**
     * @return array<int, Component>
     */
    private static function headerFields(): array
    {
        return [
            Select::make('supplier_id')
                ->label('Proveedor')
                ->relationship('supplier', 'razon_social')
                ->searchable()
                ->preload()
                ->required()
                ->live()
                ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                    if (! $state || $get('vence_at')) {
                        return;
                    }

                    $supplier = Supplier::find($state);
                    $fecha = $get('fecha');

                    if ($supplier?->dias_pago && $fecha) {
                        $set('vence_at', Carbon::parse($fecha)->addDays($supplier->dias_pago)->toDateString());
                    }
                })
                ->columnSpan(2),
            Select::make('tipo_comprobante')
                ->label('Tipo')
                ->options([
                    'factura_a' => 'Factura A',
                    'factura_b' => 'Factura B',
                    'factura_c' => 'Factura C',
                    'remito' => 'Remito',
                    'otro' => 'Otro',
                ])
                ->default('factura_a')
                ->required(),
            TextInput::make('numero')
                ->label('Número'),
            DatePicker::make('fecha')
                ->required()
                ->default(now()),
            DatePicker::make('vence_at')
                ->label('Vence'),

            Hidden::make('archivo_path'),
            NativeFileButton::make('archivo_upload')
                ->label('Comprobante')
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'application/pdf'])
                ->dehydrated(false)
                ->live()
                ->afterStateUpdated(function ($state, Set $set) {
                    if (! $state instanceof TemporaryUploadedFile) {
                        return;
                    }

                    $path = $state->store('purchases', 'local');

                    if (is_string($path)) {
                        $set('archivo_path', $path);
                    }
                }),
            TextEntry::make('archivo_path_display')
                ->hiddenLabel()
                ->state('Comprobante cargado ✓')
                ->visible(fn (Get $get): bool => filled($get('archivo_path'))),

            Section::make()
                ->collapsible()
                ->collapsed()
                ->compact()
                ->columns(2)
                ->columnSpanFull()
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
                ->default('borrador'),
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
                ->required()
                ->prefix('$')
                ->default(0.0)
                ->live()
                ->mask(RawJs::make("\$money(\$input, ',')"))
                ->formatStateUsing(fn ($state) => self::formatAmountForMask($state))
                ->dehydrateStateUsing(fn ($state) => self::parseAmount($state))
                ->extraInputAttributes(['style' => 'text-align: right;'])
                ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculateTotal($get, $set)),
            Hidden::make('percepciones')
                ->default(0),
            TextEntry::make('percepciones_display')
                ->label('Percepciones')
                ->state(fn (Get $get) => (float) ($get('percepciones') ?? 0))
                ->numeric(decimalPlaces: 2, decimalSeparator: ',', thousandsSeparator: '.')
                ->prefix('$')
                ->extraAttributes(['style' => 'display: block; text-align: right;']),
            Hidden::make('total')
                ->default(0),
            TextEntry::make('total_display')
                ->label('Total')
                ->state(fn (Get $get) => (float) ($get('total') ?? 0))
                ->numeric(decimalPlaces: 2, decimalSeparator: ',', thousandsSeparator: '.')
                ->prefix('$')
                ->extraAttributes(['style' => 'display: block; text-align: right;']),
        ];
    }

    /**
     * Fuera de la Section de 2/3 a propósito: con 5 columnas (Código,
     * Producto, Cantidad, Costo unit., Subtotal) necesita todo el ancho de
     * la página, no solo el de la columna que comparte con "Resumen" — ahí
     * el nombre del producto quedaba tan apretado que el navegador lo
     * partía letra por letra (`word-break: break-word` del Select de
     * Filament sobre una columna de tabla sin ancho propio).
     */
    private static function linesRepeater(): Repeater
    {
        return Repeater::make('lines')
            ->label('Líneas')
            ->relationship()
            ->live()
            ->default([])
            ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculateSummaryFromRoot($get, $set))
            ->table([
                TableColumn::make('Código')->width('130px'),
                TableColumn::make('Producto'),
                TableColumn::make('Cantidad')->width('90px'),
                TableColumn::make('Costo unit.')->width('120px')->alignment(Alignment::End),
                TableColumn::make('Subtotal')->width('120px')->alignment(Alignment::End),
            ])
            ->compact()
            ->schema([
                TextEntry::make('barcode')
                    ->hiddenLabel()
                    ->state(fn (Get $get): string => Product::query()->whereKey($get('product_id'))->first()->barcode ?? '—')
                    ->wrap(false)
                    ->extraAttributes(['style' => 'font-size: 0.75rem;']),
                Select::make('product_id')
                    ->label('Producto')
                    ->hiddenLabel()
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search): array => Product::query()
                        ->where('activo', true)
                        ->where(fn ($query) => $query
                            ->where('nombre', 'like', "%{$search}%")
                            ->orWhere('barcode', 'like', "%{$search}%"))
                        ->limit(50)
                        ->pluck('nombre', 'id')
                        ->all())
                    ->getOptionLabelUsing(fn (mixed $value): ?string => Product::query()->whereKey($value)->first()?->nombre)
                    ->placeholder('Nombre o código')
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (Set $set, Get $get, ?string $state) {
                        $product = $state ? Product::find($state) : null;

                        if (! $product) {
                            return;
                        }

                        $set('costo_unit', $product->costo_ultimo);
                        self::recalculateLine($get, $set);
                    })
                    ->suffixAction(self::scanBarcodeAction())
                    ->extraAttributes(['style' => 'min-width: 12rem;']),
                TextInput::make('cantidad')
                    ->hiddenLabel()
                    ->required()
                    ->numeric()
                    ->default(1)
                    ->live()
                    ->extraInputAttributes([
                        'style' => 'font-size: 0.75rem;',
                        'x-on:keydown.enter.prevent' => self::addLineOnEnterJs(),
                    ])
                    ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculateLine($get, $set)),
                TextInput::make('costo_unit')
                    ->hiddenLabel()
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->live()
                    ->prefix('$')
                    ->extraInputAttributes([
                        'style' => 'text-align: right; font-size: 0.75rem;',
                        'x-on:keydown.enter.prevent' => self::addLineOnEnterJs(),
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
            ])
            ->addActionLabel('Agregar producto')
            ->required()
            ->minItems(1)
            ->columnSpanFull();
    }

    /**
     * Percepciones cargadas por el proveedor en la factura (IIBB, IVA RG,
     * etc.), cada una vinculada a un `PerceptionType` del catálogo. A
     * diferencia de las líneas de producto, el monto se tipea directo (no
     * hay cantidad × costo que calcular).
     */
    private static function perceptionsRepeater(): Repeater
    {
        return Repeater::make('perceptions')
            ->label('Percepciones')
            ->relationship()
            ->live()
            ->default([])
            ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculateSummaryFromRoot($get, $set))
            ->table([
                TableColumn::make('Tipo'),
                TableColumn::make('Monto')->width('120px')->alignment(Alignment::End),
            ])
            ->compact()
            ->schema([
                Select::make('perception_type_id')
                    ->label('Tipo')
                    ->hiddenLabel()
                    ->relationship('perceptionType', 'nombre')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->createOptionForm([
                        TextInput::make('nombre')->required(),
                    ])
                    ->createOptionUsing(fn (array $data): int => PerceptionType::create([
                        ...$data,
                        'activo' => true,
                    ])->getKey())
                    ->extraAttributes(['style' => 'min-width: 12rem;']),
                TextInput::make('monto')
                    ->hiddenLabel()
                    ->required()
                    ->prefix('$')
                    ->default(0.0)
                    ->live()
                    ->mask(RawJs::make("\$money(\$input, ',')"))
                    ->formatStateUsing(fn ($state) => self::formatAmountForMask($state))
                    ->dehydrateStateUsing(fn ($state) => self::parseAmount($state))
                    ->extraInputAttributes(['style' => 'text-align: right; font-size: 0.75rem;'])
                    ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculateSummaryFromRoot(
                        fn (string $key) => $get("../../{$key}"),
                        fn (string $key, $value) => $set("../../{$key}", $value),
                    )),
            ])
            ->addActionLabel('Agregar percepción')
            ->columnSpanFull();
    }

    /**
     * JS del atajo "Enter agrega producto": dispara un click nativo sobre el
     * botón "Agregar producto" del table-repeater (`.fi-fo-table-repeater-add
     * button`, ver `Filament\Forms\Components\Repeater::tableRepeaterEmbeddedHtml()`
     * en el vendor) en vez de invocar el método de Livewire a mano — así no
     * depende de nombres de acción/mount internos de Filament y sigue
     * funcionando si esos cambian entre versiones. Después de que Livewire
     * termina el request (`$wire.$nextTick`, ya esperado por Alpine dentro
     * del morph de Livewire), enfoca el buscador de producto de la ÚLTIMA
     * fila para poder seguir tipeando sin usar el mouse.
     */
    private static function addLineOnEnterJs(): string
    {
        return <<<'JS'
            (() => {
                const repeater = $el.closest('.fi-fo-table-repeater');
                if (! repeater) { return; }
                repeater.querySelector('.fi-fo-table-repeater-add button')?.click();
                $wire.$nextTick(() => {
                    const rows = repeater.querySelectorAll('tbody tr');
                    rows[rows.length - 1]?.querySelector('input')?.focus();
                });
            })()
            JS;
    }

    /**
     * Botón de escaneo de código de barras con la cámara del dispositivo.
     * Reusa la misma vista que Ventas (resources/views/filament/sale-form/barcode-scanner.blade.php),
     * que es genérica y no depende de nada específico de Ventas.
     */
    private static function scanBarcodeAction(): Action
    {
        return Action::make('scanBarcode')
            ->label('Escanear código de barras')
            ->icon(Heroicon::OutlinedCamera)
            ->color('gray')
            ->extraAttributes(['class' => 'sm:hidden'])
            ->modalHeading('Escanear código de barras')
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
                $set('costo_unit', $product->costo_ultimo);
                self::recalculateLine($get, $set);
            });
    }

    /**
     * Recalcula el subtotal de una línea (cantidad × costo unitario). Se
     * llama desde dentro de un item del repeater, así que $get/$set están a
     * nivel de esa línea.
     */
    private static function recalculateLine(Get $get, Set $set): void
    {
        $cantidad = (float) ($get('cantidad') ?? 0);
        $costoUnit = (float) ($get('costo_unit') ?? 0);

        $set('subtotal', number_format($cantidad * $costoUnit, 2, '.', ''));

        self::recalculateSummaryFromRoot(
            fn (string $key) => $get("../../{$key}"),
            fn (string $key, $value) => $set("../../{$key}", $value),
        );
    }

    /**
     * Las filas del repeater tal como vienen del estado del formulario,
     * descartando cualquier cosa que no sea una fila.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function lineRows(mixed $state): array
    {
        return is_array($state)
            ? array_values(array_filter($state, is_array(...)))
            : [];
    }

    /**
     * Suma el subtotal de todas las líneas y recalcula subtotal/total del
     * resumen. Recibe $get/$set ya resueltos a nivel raíz (o closures que lo
     * simulan).
     */
    private static function recalculateSummaryFromRoot(Get|Closure $get, Set|Closure $set): void
    {
        $subtotal = collect(self::lineRows($get('lines')))
            ->sum(fn (array $line): float => (float) ($line['subtotal'] ?? 0));

        $set('subtotal', number_format($subtotal, 2, '.', ''));

        $percepciones = collect(self::lineRows($get('perceptions')))
            ->sum(fn (array $perception): float => self::parseAmount($perception['monto'] ?? 0));

        $set('percepciones', number_format($percepciones, 2, '.', ''));

        self::recalculateTotal($get, $set);
    }

    /**
     * Total = subtotal - descuento + percepciones. El IVA ya no es un
     * término aparte: se carga como una percepción más.
     */
    private static function recalculateTotal(Get|Closure $get, Set|Closure $set): void
    {
        $subtotal = (float) ($get('subtotal') ?? 0);
        $descuento = self::parseAmount($get('descuento'));
        $percepciones = (float) ($get('percepciones') ?? 0);

        $set('total', number_format($subtotal - $descuento + $percepciones, 2, '.', ''));
    }

    /**
     * Convierte un monto tipeado con el mask "$money($input, ',')" (punto de
     * miles, coma decimal) a un float plano. Los valores que ya llegan
     * numéricos y NO como string (ej. el 0.0 por defecto, sin tocar) se
     * castean directo.
     *
     * A propósito NO usa `is_numeric()` para decidir el atajo: un string
     * como "18.060" (lo que deja el mask al tipear "18060" sin coma, sin
     * agregar centavos) es un float PHP válido (18.06) y con ese atajo se
     * interpretaría mal — hay que asumir que todo string que llega acá es
     * texto del mask (punto de miles) y parsearlo siempre como tal.
     */
    private static function parseAmount(mixed $value): float
    {
        if (blank($value)) {
            return 0.0;
        }

        if (! is_string($value)) {
            return (float) $value;
        }

        return (float) str_replace(['.', ','], ['', '.'], $value);
    }

    /**
     * Formatea un monto crudo (decimal de la base, separador "." como en
     * PHP) al formato que espera el mask "$money($input, ',')" (punto de
     * miles, coma decimal) antes de mostrarlo. Sin esto, al abrir una
     * compra ya guardada el mask no reconoce el "." como decimal (espera
     * ",", y descarta cualquier otro carácter) y reinterpreta el número
     * entero como si fueran miles — comprobado en vivo: "18.06" (dieciocho
     * con seis) el mask lo deja en "1.806" (mil ochocientos seis).
     *
     * `formatStateUsing` corre en cada hidratación, no solo la primera — así
     * que además de la carga inicial, también se dispara sobre lo que el
     * usuario ya tipeó (ej. "18.060" sin coma, el propio mask no agrega
     * centavos si no se los tipea). Por eso NO alcanza con `is_numeric()`:
     * "18.060" también es un float válido en PHP (18.06) y se
     * reformatearía mal una segunda vez. Un decimal crudo de `decimal:2`
     * siempre tiene EXACTAMENTE 2 dígitos después del único punto (ej.
     * "18060.00"); un texto ya agrupado por el mask sin coma escrita queda
     * con 3 (el último grupo de miles, ej. "18.060"). Ese patrón exacto es
     * lo único que distingue con certeza "todavía sin tocar por el mask" de
     * "ya lo procesó el mask" — cualquier otro string se deja intacto.
     */
    private static function formatAmountForMask(mixed $state): mixed
    {
        if (! is_string($state)) {
            return is_numeric($state) ? number_format((float) $state, 2, ',', '.') : $state;
        }

        return preg_match('/^-?\d+\.\d{2}$/', $state)
            ? number_format((float) $state, 2, ',', '.')
            : $state;
    }
}
