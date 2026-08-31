@php
    /** En mobile solo se muestran las primeras 6 cards; el resto se despliega con el botón "Ver más". */
    $mobileVisibleCount = 6;

    $visibleProducts = $products->take($mobileVisibleCount);
    $extraProducts = $products->slice($mobileVisibleCount);
@endphp

<div x-data="{ expanded: false }">
    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4 lg:grid-cols-2">
        @foreach ($visibleProducts as $product)
            @include('filament.sale-form.product-card', [
                'product' => $product,
                'index' => $loop->index,
                'priceListId' => $priceListId,
            ])
        @endforeach

        @if ($extraProducts->isNotEmpty())
            {{-- `display: contents` para que las cards de adentro sigan siendo hijas
                 directas de la grilla. Desde `sm` siempre se ven todas; por debajo
                 arrancan ocultas y `contents!` (important, para ganarle a `hidden`
                 sin depender del orden de las utilidades) las muestra al desplegar. --}}
            <div class="hidden sm:contents" x-bind:class="expanded ? 'contents!' : ''">
                @foreach ($extraProducts as $product)
                    @include('filament.sale-form.product-card', [
                        'product' => $product,
                        'index' => $mobileVisibleCount + $loop->index,
                        'priceListId' => $priceListId,
                    ])
                @endforeach
            </div>
        @endif
    </div>

    @if ($extraProducts->isNotEmpty())
        <button
            type="button"
            x-on:click="expanded = ! expanded"
            class="mt-2 w-full rounded-lg border border-dashed border-gray-300 px-3 py-2 text-base font-medium text-gray-600 transition hover:bg-gray-50 sm:hidden dark:border-gray-600 dark:text-gray-300 dark:hover:bg-white/5"
            x-text="expanded ? 'Ver menos' : 'Ver más ({{ $extraProducts->count() }})'"
        ></button>
    @endif
</div>
