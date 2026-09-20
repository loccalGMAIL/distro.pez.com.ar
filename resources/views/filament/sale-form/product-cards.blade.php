@php
    /** En mobile solo se muestran las primeras 6 cards del listado completo; el resto se despliega con "Ver más". */
    $mobileVisibleCount = 6;

    $favoritesCount = $favorites->count();
    $visibleOthers = $others->take($mobileVisibleCount);
    $extraOthers = $others->slice($mobileVisibleCount);
@endphp

<div x-data="{ expanded: false }" class="space-y-4">
    @if ($favorites->isNotEmpty())
        <div>
            <h3 class="mb-2 flex items-center gap-1.5 text-sm font-semibold text-gray-700 dark:text-gray-200">
                <x-heroicon-s-star class="h-4 w-4 text-amber-400" />
                Favoritos
            </h3>

            <div class="grid grid-cols-2 gap-2 sm:grid-cols-4 lg:grid-cols-2">
                @foreach ($favorites as $product)
                    @include('filament.sale-form.product-card', [
                        'product' => $product,
                        'index' => $loop->index,
                        'priceListId' => $priceListId,
                    ])
                @endforeach
            </div>
        </div>
    @endif

    <div>
        @if ($favorites->isNotEmpty())
            <h3 class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-200">
                Todos los productos
            </h3>
        @endif

        <div class="grid grid-cols-2 gap-2 sm:grid-cols-4 lg:grid-cols-2">
            @foreach ($visibleOthers as $product)
                @include('filament.sale-form.product-card', [
                    'product' => $product,
                    'index' => $favoritesCount + $loop->index,
                    'priceListId' => $priceListId,
                ])
            @endforeach

            @if ($extraOthers->isNotEmpty())
                {{-- `display: contents` para que las cards de adentro sigan siendo hijas
                     directas de la grilla. Desde `sm` siempre se ven todas; por debajo
                     arrancan ocultas y `contents!` (important, para ganarle a `hidden`
                     sin depender del orden de las utilidades) las muestra al desplegar. --}}
                <div class="hidden sm:contents" x-bind:class="expanded ? 'contents!' : ''">
                    @foreach ($extraOthers as $product)
                        @include('filament.sale-form.product-card', [
                            'product' => $product,
                            'index' => $favoritesCount + $mobileVisibleCount + $loop->index,
                            'priceListId' => $priceListId,
                        ])
                    @endforeach
                </div>
            @endif
        </div>

        @if ($extraOthers->isNotEmpty())
            <button
                type="button"
                x-on:click="expanded = ! expanded"
                class="mt-2 w-full rounded-lg border border-dashed border-gray-300 px-3 py-2 text-base font-medium text-gray-600 transition hover:bg-gray-50 sm:hidden dark:border-gray-600 dark:text-gray-300 dark:hover:bg-white/5"
                x-text="expanded ? 'Ver menos' : 'Ver más ({{ $extraOthers->count() }})'"
            ></button>
        @endif
    </div>
</div>
