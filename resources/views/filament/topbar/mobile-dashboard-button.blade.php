<x-filament::icon-button
    tag="a"
    :href="filament()->getHomeUrl()"
    wire:navigate
    color="gray"
    :icon="\Filament\Support\Icons\Heroicon::OutlinedHome"
    icon-size="lg"
    :label="__('Volver al dashboard')"
    class="ms-2 lg:hidden"
/>
