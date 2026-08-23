<?php

namespace App\Filament\Clusters\Purchases\Pages;

use App\Filament\Clusters\Catalog\Resources\Products\Schemas\ProductForm;
use App\Filament\Clusters\Purchases\PurchasesCluster;
use App\Filament\Clusters\Purchases\Resources\Purchases\PurchaseResource;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\InvoiceExtractor;
use App\Services\InvoiceImagePreparer;
use App\Services\ProductLinkMemory;
use App\Services\SupplierMatcher;
use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use RuntimeException;
use Throwable;

/**
 * @property-read Schema $form Resuelto por el __get de Filament (ResolvesDynamicLivewireProperties).
 */
class ScanPurchase extends Page
{
    protected string $view = 'filament.clusters.purchases.pages.scan-purchase';

    protected static ?string $cluster = PurchasesCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCamera;

    protected static ?string $navigationLabel = 'Escanear factura';

    protected static ?string $title = 'Escanear factura';

    protected static ?int $navigationSort = 2;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    /**
     * Respuesta cruda (ya normalizada) de la última extracción, para
     * auditar en Purchase.ocr_data. No es parte del form state porque no
     * hace falta que el usuario la edite.
     *
     * @var array<string, mixed>
     */
    public array $ocrData = [];

    public function mount(): void
    {
        $this->form->fill([
            'fecha' => now()->toDateString(),
            'tipo_comprobante' => 'factura_a',
            'warehouse_id' => Warehouse::where('predeterminado', true)->value('id'),
            'descuento' => 0,
            'iva' => 0,
            'lineas' => [],
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    Step::make('Capturar')
                        ->description('Subí una foto o el PDF de la factura del proveedor.')
                        ->schema($this->captureSchema())
                        ->afterValidation(fn (Get $get, Set $set) => $this->runExtraction($get, $set)),
                    Step::make('Revisar')
                        ->description('Corregí lo que haga falta antes de confirmar.')
                        ->schema($this->reviewSchema()),
                ]),
            ])
            ->model(Purchase::class)
            ->statePath('data');
    }

    /**
     * @return array<int, Component>
     */
    private function captureSchema(): array
    {
        return [
            FileUpload::make('upload')
                ->label('Factura')
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'application/pdf'])
                ->maxSize(10240)
                ->imageEditor()
                ->dehydrated(false)
                ->live()
                ->afterStateUpdated(function ($state, Set $set) {
                    if (! $state instanceof TemporaryUploadedFile) {
                        return;
                    }

                    $path = $state->store('purchases', 'local');

                    if (! is_string($path)) {
                        Notification::make()
                            ->danger()
                            ->title('No pudimos guardar el archivo de la factura')
                            ->body('Probá de nuevo o subí otro archivo.')
                            ->send();

                        return;
                    }

                    app(InvoiceImagePreparer::class)->prepare(
                        Storage::disk('local')->path($path)
                    );

                    $set('archivo_path', $path);
                    $set('mime_type', $state->getMimeType());
                })
                ->required(),
            Hidden::make('archivo_path')
                ->required(),
            Hidden::make('mime_type'),
        ];
    }

    /**
     * @return array<int, Component>
     */
    private function reviewSchema(): array
    {
        return [
            Select::make('supplier_id')
                ->label('Proveedor')
                ->relationship('supplier', 'razon_social')
                ->searchable()
                ->preload()
                ->required()
                ->createOptionForm([
                    TextInput::make('razon_social')->label('Razón social')->required()
                        ->default(fn (): ?string => $this->ocrData['proveedor'] ?? null),
                    TextInput::make('cuit')->label('CUIT')
                        ->default(fn (): ?string => $this->ocrData['cuit'] ?? null),
                ])
                ->createOptionUsing(fn (array $data): int => Supplier::create([
                    ...$data,
                    'activo' => true,
                    'condicion_pago' => 'cuenta_corriente',
                    'dias_pago' => 0,
                    'balance' => 0,
                ])->getKey())
                ->columnSpan(2),
            Select::make('tipo_comprobante')
                ->label('Tipo de comprobante')
                ->options([
                    'factura_a' => 'Factura A',
                    'factura_b' => 'Factura B',
                    'factura_c' => 'Factura C',
                    'remito' => 'Remito',
                    'otro' => 'Otro',
                ])
                ->required(),
            TextInput::make('numero')
                ->label('Número'),
            DatePicker::make('fecha')
                ->required(),
            DatePicker::make('vence_at')
                ->label('Vence'),
            Select::make('warehouse_id')
                ->label('Depósito')
                ->relationship('warehouse', 'nombre')
                ->searchable()
                ->preload()
                ->required(),

            Repeater::make('lineas')
                ->label('Líneas')
                ->live()
                ->afterStateUpdated(fn (Get $get, Set $set) => $this->recalculateTotals($get, $set))
                ->table([
                    TableColumn::make('Detectado por la IA'),
                    TableColumn::make('Producto'),
                    TableColumn::make('Cantidad')->width('90px'),
                    TableColumn::make('Costo unit.')->width('120px')->alignment(Alignment::End),
                    TableColumn::make('Subtotal')->width('120px')->alignment(Alignment::End),
                ])
                ->compact()
                ->schema([
                    Hidden::make('description_key'),
                    TextInput::make('descripcion')
                        ->hiddenLabel()
                        ->disabled()
                        ->dehydrated()
                        ->helperText(fn (Get $get): ?string => $get('unidad') ? "Unidad detectada: {$get('unidad')}" : null)
                        ->extraInputAttributes(['style' => 'font-size: 0.75rem;']),
                    Hidden::make('unidad'),
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
                        ->placeholder('Sin match — elegí uno o borrá la línea')
                        ->createOptionForm(ProductForm::quickCreateFields())
                        ->createOptionUsing(fn (array $data): int => Product::create([
                            ...$data,
                            'activo' => true,
                            'tracks_lot' => false,
                        ])->getKey())
                        ->createOptionAction(fn (Action $action, Get $get): Action => $action->fillForm([
                            'sku' => ProductForm::generateSku(),
                            'nombre' => $get('descripcion'),
                            'base_unit' => $get('unidad'),
                            'costo_ultimo' => (float) ($get('costo_unit') ?? 0),
                        ]))
                        ->extraAttributes(['style' => 'min-width: 12rem;'])
                        ->live()
                        ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                            $product = $state ? Product::find($state) : null;

                            if ($product && (float) ($get('costo_unit') ?? 0) === 0.0) {
                                $set('costo_unit', $product->costo_ultimo);
                                $this->recalculateLine($get, $set);
                            }
                        }),
                    TextInput::make('cantidad')
                        ->hiddenLabel()
                        ->required()
                        ->numeric()
                        ->live()
                        ->afterStateUpdated(fn (Get $get, Set $set) => $this->recalculateLine($get, $set)),
                    TextInput::make('costo_unit')
                        ->hiddenLabel()
                        ->required()
                        ->numeric()
                        ->prefix('$')
                        ->live()
                        ->afterStateUpdated(fn (Get $get, Set $set) => $this->recalculateLine($get, $set)),
                    Hidden::make('subtotal')
                        ->default(0),
                ])
                ->addActionLabel('Agregar línea')
                ->required()
                ->minItems(1)
                ->columnSpanFull(),

            Hidden::make('subtotal')
                ->default(0),
            TextEntry::make('subtotal_display')
                ->label('Subtotal')
                ->state(fn (Get $get) => (float) ($get('subtotal') ?? 0))
                ->numeric(decimalPlaces: 2, decimalSeparator: ',', thousandsSeparator: '.')
                ->prefix('$'),
            TextInput::make('descuento')
                ->label('Descuento')
                ->required()
                ->numeric()
                ->prefix('$')
                ->live()
                ->afterStateUpdated(fn (Get $get, Set $set) => $this->recalculateTotals($get, $set)),
            TextInput::make('iva')
                ->label('IVA')
                ->required()
                ->numeric()
                ->prefix('$')
                ->live()
                ->afterStateUpdated(fn (Get $get, Set $set) => $this->recalculateTotals($get, $set)),
            Hidden::make('total')
                ->default(0),
            TextEntry::make('total_display')
                ->label('Total')
                ->state(fn (Get $get) => (float) ($get('total') ?? 0))
                ->numeric(decimalPlaces: 2, decimalSeparator: ',', thousandsSeparator: '.')
                ->prefix('$'),

            Actions::make([
                Action::make('confirmar')
                    ->label('Confirmar y crear compra')
                    ->color('success')
                    ->icon(Heroicon::Check)
                    ->action(fn () => $this->confirmar()),
            ])
                ->columnSpanFull(),
        ];
    }

    /**
     * Corre al validar el paso "Capturar" (justo antes de avanzar a
     * "Revisar"): sube la extracción a Claude y precompleta el paso 2 con lo
     * que devolvió, pisado por lo que el usuario ya vinculó antes para ese
     * proveedor (ProductLinkMemory > sugerencia de la IA).
     */
    private function runExtraction(Get $get, Set $set): void
    {
        $path = $get('archivo_path');
        $mime = $get('mime_type');

        if (blank($path)) {
            return;
        }

        try {
            $extraction = app(InvoiceExtractor::class)->extract(
                Storage::disk('local')->path($path),
                $mime ?: 'image/jpeg',
            );
        } catch (Throwable $e) {
            Notification::make()
                ->title('No se pudo leer la factura')
                ->body($e instanceof RuntimeException ? $e->getMessage() : 'Ocurrió un error inesperado. Probá de nuevo.')
                ->danger()
                ->send();

            throw new Halt;
        }

        $this->ocrData = $extraction;

        $supplier = app(SupplierMatcher::class)->match($extraction['proveedor']);

        $descriptionKeys = collect($extraction['lineas'])->pluck('description_key')->all();
        $remembered = $supplier
            ? app(ProductLinkMemory::class)->recallMany($supplier->id, $descriptionKeys)
            : [];

        if (! $supplier && filled($extraction['proveedor'])) {
            Notification::make()
                ->title("No encontramos al proveedor \"{$extraction['proveedor']}\"")
                ->body('Podés crearlo con el botón "+" junto al campo Proveedor — ya viene precargado con lo que leímos de la factura.')
                ->warning()
                ->send();
        }

        $lineasSinProducto = collect($extraction['lineas'])
            ->filter(fn (array $linea): bool => blank($remembered[$linea['description_key']] ?? $linea['matched_product_id']))
            ->count();

        if ($lineasSinProducto > 0) {
            Notification::make()
                ->title($lineasSinProducto === 1
                    ? 'Hay 1 línea sin producto detectado'
                    : "Hay {$lineasSinProducto} líneas sin producto detectado")
                ->body('Elegí uno existente o creá uno nuevo con el botón "+" en esa línea.')
                ->warning()
                ->send();
        }

        $tiposValidos = ['factura_a', 'factura_b', 'factura_c', 'remito', 'otro'];

        $set('supplier_id', $supplier?->id);
        $set('tipo_comprobante', in_array($extraction['tipo_comprobante'], $tiposValidos, true) ? $extraction['tipo_comprobante'] : 'factura_a');
        $set('numero', $extraction['numero']);
        $set('fecha', $extraction['fecha'] ?? now()->toDateString());
        $set('vence_at', $extraction['vencimiento']);
        $set('descuento', 0);
        $set('iva', $extraction['iva'] ?? 0);

        $set('lineas', collect($extraction['lineas'])->map(fn (array $linea): array => [
            'description_key' => $linea['description_key'],
            'descripcion' => $linea['descripcion'],
            'unidad' => $linea['unidad'],
            'product_id' => $remembered[$linea['description_key']] ?? $linea['matched_product_id'],
            'cantidad' => $linea['cantidad'],
            'costo_unit' => $linea['precio_unitario'],
            'subtotal' => $linea['subtotal'],
        ])->all());

        $this->recalculateTotals($get, $set);
    }

    private function recalculateLine(Get $get, Set $set): void
    {
        $cantidad = (float) ($get('cantidad') ?? 0);
        $costoUnit = (float) ($get('costo_unit') ?? 0);

        $set('subtotal', round($cantidad * $costoUnit, 2));

        $this->recalculateTotals(
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
    private function lineRows(mixed $state): array
    {
        return is_array($state)
            ? array_values(array_filter($state, is_array(...)))
            : [];
    }

    /**
     * Recalcula subtotal (suma de líneas) y total (subtotal − descuento +
     * iva) para el resumen del paso "Revisar". El total final "real" se
     * vuelve a calcular en confirmar() a partir de lo que haya en la base en
     * ese momento; esto es solo para que el usuario vea el número mientras
     * corrige.
     */
    private function recalculateTotals(Get|Closure $get, Set|Closure $set): void
    {
        $subtotal = collect($this->lineRows($get('lineas')))
            ->sum(fn (array $linea): float => (float) ($linea['subtotal'] ?? 0));

        $set('subtotal', round($subtotal, 2));

        $descuento = (float) ($get('descuento') ?? 0);
        $iva = (float) ($get('iva') ?? 0);

        $set('total', round($subtotal - $descuento + $iva, 2));
    }

    /**
     * Crea la Purchase (borrador) + sus líneas a partir de lo revisado, y
     * guarda en supplier_product_links cada vinculación producto confirmada
     * para que el próximo escaneo de este proveedor ya la traiga.
     */
    public function confirmar(): void
    {
        $data = $this->form->getState();

        $lineas = collect($this->lineRows($data['lineas'] ?? null))
            ->filter(fn (array $linea): bool => filled($linea['product_id']) && (float) ($linea['cantidad'] ?? 0) > 0)
            ->values();

        if ($lineas->isEmpty()) {
            Notification::make()
                ->title('Agregá al menos una línea con producto y cantidad antes de confirmar.')
                ->danger()
                ->send();

            return;
        }

        $purchase = DB::transaction(function () use ($data, $lineas): Purchase {
            $purchase = Purchase::create([
                'supplier_id' => $data['supplier_id'],
                'warehouse_id' => $data['warehouse_id'],
                'user_id' => auth()->id(),
                'tipo_comprobante' => $data['tipo_comprobante'],
                'numero' => $data['numero'] ?? null,
                'fecha' => $data['fecha'],
                'vence_at' => $data['vence_at'] ?? null,
                'descuento' => $data['descuento'] ?? 0,
                'iva' => $data['iva'] ?? 0,
                'archivo_path' => $data['archivo_path'] ?? null,
                'ocr_data' => $this->ocrData ?: null,
                'status' => 'borrador',
            ]);

            $subtotal = 0.0;

            foreach ($lineas as $linea) {
                $lineSubtotal = round((float) $linea['cantidad'] * (float) $linea['costo_unit'], 2);
                $subtotal += $lineSubtotal;

                $purchase->lines()->create([
                    'product_id' => $linea['product_id'],
                    'cantidad' => $linea['cantidad'],
                    'costo_unit' => $linea['costo_unit'],
                    'subtotal' => $lineSubtotal,
                ]);

                if (filled($linea['description_key'] ?? null)) {
                    app(ProductLinkMemory::class)->remember(
                        (int) $purchase->supplier_id,
                        $linea['description_key'],
                        (int) $linea['product_id'],
                    );
                }
            }

            $purchase->update([
                'subtotal' => $subtotal,
                'total' => $subtotal - (float) $purchase->descuento + (float) $purchase->iva,
            ]);

            return $purchase;
        });

        Notification::make()
            ->title("Compra creada en borrador ({$purchase->numero})")
            ->success()
            ->send();

        $this->redirect(PurchaseResource::getUrl('edit', ['record' => $purchase]));
    }
}
