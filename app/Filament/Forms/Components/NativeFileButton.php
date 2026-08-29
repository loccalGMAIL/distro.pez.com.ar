<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Field;
use Filament\Support\Components\Contracts\HasEmbeddedView;

/**
 * Botón que dispara el selector de archivos nativo del sistema operativo
 * (un <input type="file"> real, sin el panel de arrastrar-y-soltar de
 * FilePond que usa Filament\Forms\Components\FileUpload). Solo maneja la
 * subida a almacenamiento temporal de Livewire; guardar el archivo en disco
 * final y setear el campo persistido correspondiente es responsabilidad de
 * quien lo declare (vía ->afterStateUpdated()), igual que el patrón ya usado
 * en ScanPurchase::captureSchema().
 *
 * Implementa HasEmbeddedView (como TextInput, FileUpload, etc.) para poder
 * envolver el contenido con wrapEmbeddedHtml() y así heredar el mismo
 * label/título por encima del control que tienen el resto de los campos del
 * form. Deliberadamente NO tiene una propiedad $view: `ViewComponent::toHtml()`
 * chequea `hasView()` ANTES que `instanceof HasEmbeddedView` (ver
 * vendor/filament/support/src/Components/ViewComponent.php) — si hubiera una
 * $view seteada, Filament renderizaría esa vista a secas y jamás llegaría a
 * llamar a toEmbeddedHtml(), dejando el campo sin el wrapper de label/error.
 * La vista Blade se renderiza a mano dentro de toEmbeddedHtml() con
 * renderView(), que es lo que ya inyecta $getId()/$getLabel()/etc. como
 * closures utilizables en el .blade.php.
 */
class NativeFileButton extends Field implements HasEmbeddedView
{
    protected const VIEW = 'filament.forms.components.native-file-button';

    /**
     * @var array<int, string>
     */
    protected array $acceptedFileTypes = [];

    /**
     * @param  array<int, string>  $types
     */
    public function acceptedFileTypes(array $types): static
    {
        $this->acceptedFileTypes = $types;

        return $this;
    }

    public function getAcceptedFileTypesAttribute(): ?string
    {
        return $this->acceptedFileTypes === [] ? null : implode(',', $this->acceptedFileTypes);
    }

    public function toEmbeddedHtml(): string
    {
        return $this->wrapEmbeddedHtml($this->renderView(self::VIEW)->render());
    }
}
