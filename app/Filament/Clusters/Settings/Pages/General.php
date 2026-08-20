<?php

namespace App\Filament\Clusters\Settings\Pages;

use App\Filament\Clusters\Settings\SettingsCluster;
use App\Models\CompanySetting;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;

class General extends Page
{
    protected string $view = 'filament.clusters.settings.pages.general';

    protected static ?string $cluster = SettingsCluster::class;

    protected static ?string $navigationLabel = 'General';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill($this->getRecord()?->attributesToArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([
                    TextInput::make('razon_social')
                        ->required(),
                    TextInput::make('cuit')
                        ->required(),
                    Select::make('condicion_iva')
                        ->options([
                            'responsable_inscripto' => 'Responsable Inscripto',
                            'monotributo' => 'Monotributo',
                            'exento' => 'Exento',
                            'consumidor_final' => 'Consumidor Final',
                            'no_responsable' => 'No Responsable',
                        ])
                        ->required(),
                    TextInput::make('domicilio_fiscal')
                        ->label('Domicilio fiscal')
                        ->required(),
                    TextInput::make('telefono')
                        ->label('Teléfono')
                        ->tel(),
                    TextInput::make('email')
                        ->email(),
                    TextInput::make('punto_venta')
                        ->label('Punto de venta'),
                    TextInput::make('proximo_numero_comprobante')
                        ->label('Próximo número de comprobante')
                        ->numeric()
                        ->default(1),
                    FileUpload::make('logo_path')
                        ->label('Logotipo')
                        ->image()
                        ->disk('public')
                        ->directory('company'),
                ])
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make([
                            Action::make('save')
                                ->label('Guardar')
                                ->submit('save'),
                        ]),
                    ]),
            ])
            ->record($this->getRecord())
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $record = $this->getRecord() ?? new CompanySetting;
        $record->fill($data);
        $record->save();

        Notification::make()
            ->success()
            ->title('Guardado')
            ->send();
    }

    public function getRecord(): ?CompanySetting
    {
        return CompanySetting::query()->first();
    }
}
