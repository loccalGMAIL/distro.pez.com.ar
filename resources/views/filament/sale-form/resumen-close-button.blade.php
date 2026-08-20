<x-filament::icon-button
    icon="heroicon-o-x-mark"
    label="Cerrar resumen"
    color="gray"
    x-on:click="window.dispatchEvent(new CustomEvent('resumen-toggle'))"
    class="lg:hidden"
/>
