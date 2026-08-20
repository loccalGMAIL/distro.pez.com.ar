<div
    x-data="{ open: false }"
    x-on:resumen-toggle.window="open = ! open"
    class="lg:hidden"
>
    <div
        x-show="open"
        x-on:click="window.dispatchEvent(new CustomEvent('resumen-toggle'))"
        x-transition.opacity
        class="fixed inset-0 z-30 bg-gray-950/50"
        x-cloak
    ></div>

    <button
        type="button"
        x-on:click="window.dispatchEvent(new CustomEvent('resumen-toggle'))"
        x-show="! open"
        class="fixed bottom-4 right-4 z-30 inline-flex items-center gap-2 rounded-full bg-primary-600 px-4 py-3 text-sm font-semibold text-white shadow-lg"
    >
        <x-filament::icon
            icon="heroicon-o-receipt-percent"
            class="h-5 w-5"
        />
        Resumen
    </button>
</div>
