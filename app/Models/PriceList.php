<?php

namespace App\Models;

use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfDocument;
use Database\Factories\PriceListFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PriceList extends Model
{
    /** @use HasFactory<PriceListFactory> */
    use HasFactory, LogsActivity;

    protected $fillable = [
        'nombre',
        'codigo',
        'based_on_price_list_id',
        'porcentaje',
        'predeterminada',
        'compartible',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'porcentaje' => 'decimal:2',
            'predeterminada' => 'boolean',
            'compartible' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll()->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    protected static function booted(): void
    {
        static::saved(function (PriceList $priceList) {
            if ($priceList->predeterminada) {
                static::where('id', '!=', $priceList->id)->update(['predeterminada' => false]);
            }
        });
    }

    /**
     * @return HasMany<Customer, $this>
     */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * @return BelongsTo<PriceList, $this>
     */
    public function basedOn(): BelongsTo
    {
        return $this->belongsTo(PriceList::class, 'based_on_price_list_id');
    }

    /**
     * @return HasMany<PriceList, $this>
     */
    public function dependents(): HasMany
    {
        return $this->hasMany(PriceList::class, 'based_on_price_list_id');
    }

    /**
     * Cuánto multiplica esta lista al costo de cualquier producto, resolviendo
     * la cadena de "basada en" (independiente del producto, así se puede
     * calcular una sola vez por lista en vez de una vez por producto×lista).
     *
     * @param  array<int, int>  $visited  IDs ya recorridos de la cadena.
     */
    public function multiplicador(array $visited = []): float
    {
        if (in_array($this->id, $visited, true)) {
            return 1.0;
        }

        $visited[] = $this->id;

        $factor = 1 + ((float) $this->porcentaje / 100);

        if (! $this->based_on_price_list_id) {
            return $factor;
        }

        return $factor * ($this->basedOn?->multiplicador($visited) ?? 1.0);
    }

    /**
     * Precio de un producto en esta lista: costo_ultimo × multiplicador().
     */
    public function precioPara(Product $product): string
    {
        return number_format((float) $product->costo_ultimo * $this->multiplicador(), 2, '.', '');
    }

    /**
     * Listas activas en el orden de negocio (minorista, mayorista, VIP), en
     * vez del orden alfabético. Usado por las columnas de precio de la tabla
     * de Productos.
     *
     * @return Collection<int, static>
     */
    public static function orderedForDisplay(): Collection
    {
        return static::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get()
            ->sortBy(fn (self $priceList): int => self::displaySortOrder($priceList->nombre))
            ->values();
    }

    /**
     * Listas que se pueden compartir con clientes: activas y con el switch
     * `compartible` prendido. Usado por el botón de compartir lista de precios
     * (topbar, widget y tabla de Productos).
     *
     * @return Collection<int, static>
     */
    public static function orderedForSharing(): Collection
    {
        return static::orderedForDisplay()
            ->filter(fn (self $priceList): bool => $priceList->compartible)
            ->values();
    }

    private static function displaySortOrder(string $nombre): int
    {
        return match (true) {
            str_contains(mb_strtolower($nombre), 'minorista') => 0,
            str_contains(mb_strtolower($nombre), 'mayorista') => 1,
            str_contains(mb_strtolower($nombre), 'vip') => 2,
            default => 3,
        };
    }

    /**
     * PDF para compartir esta lista de precios: código de barra, nombre,
     * unidad de presentación y precio de cada producto activo.
     */
    public function productsPdf(): PdfDocument
    {
        return Pdf::loadView('pdf.price-lists.productos', [
            'priceList' => $this,
            'products' => Product::query()->where('activo', true)->orderBy('nombre')->get(),
            'company' => CompanySetting::query()->first(),
        ]);
    }

    /**
     * True si asignar $candidateBasedOnId como based_on_price_list_id de esta
     * lista formaría una referencia circular (directa o a través de la cadena).
     */
    public function wouldCreateCycle(?int $candidateBasedOnId): bool
    {
        $currentId = $candidateBasedOnId;
        $visited = [];

        while ($currentId !== null) {
            if ($currentId === $this->id || in_array($currentId, $visited, true)) {
                return true;
            }

            $visited[] = $currentId;
            $currentId = static::where('id', $currentId)->value('based_on_price_list_id');
        }

        return false;
    }
}
