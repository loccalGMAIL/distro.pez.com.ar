{{--
    Ítem permanente para volver a instalar la app después de cerrar el aviso.
    Se inyecta con `PanelsRenderHook::USER_MENU_PROFILE_AFTER`, que con
    `->profile()` habilitado en el panel renderiza dentro de un
    `<x-filament::dropdown.list>` ya abierto (ver `user-menu.blade.php` de
    Filament) — por eso acá va solo el item, sin envolverlo en su propia lista.
    Si el panel alguna vez pierde `->profile()`, este hook no se renderiza y el
    ítem desaparece sin error.

    Arranca oculto por `style.display` (mismo motivo que el banner: compite en
    especificidad con el `display` propio de `fi-dropdown-list-item`) porque si
    se puede instalar depende de condiciones que solo conoce el cliente.
--}}
<x-filament::dropdown.list.item
    :icon="\Filament\Support\Icons\Heroicon::OutlinedArrowDownTray"
    data-pwa-install
    style="display: none;"
>
    Instalar app
</x-filament::dropdown.list.item>
