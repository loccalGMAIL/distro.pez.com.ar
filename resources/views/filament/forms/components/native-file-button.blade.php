@php
    $id = $getId();
@endphp

<div {{ $attributes->merge($getExtraAttributes())->class(['fi-native-file-button']) }}>
    <x-filament::button
        tag="label"
        for="{{ $id }}"
        color="gray"
        icon="heroicon-o-paper-clip"
        :disabled="$isDisabled()"
    >
        Subir archivo
    </x-filament::button>

    <input
        type="file"
        id="{{ $id }}"
        wire:model="{{ $getStatePath() }}"
        @if ($getAcceptedFileTypesAttribute()) accept="{{ $getAcceptedFileTypesAttribute() }}" @endif
        @disabled($isDisabled())
        class="fi-hidden"
        style="position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0;"
    />
</div>
