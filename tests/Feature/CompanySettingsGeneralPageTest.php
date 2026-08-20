<?php

use App\Filament\Clusters\Settings\Pages\General;
use App\Models\CompanySetting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create(['activo' => true]));
});

test('the general settings page can be rendered', function () {
    Livewire::test(General::class)->assertSuccessful();
});

test('saving the form persists company settings', function () {
    Livewire::test(General::class)
        ->fillForm([
            'razon_social' => 'Comercio Demo SRL',
            'cuit' => '30-12345678-9',
            'condicion_iva' => 'responsable_inscripto',
            'domicilio_fiscal' => 'Av. Siempre Viva 123',
            'telefono' => '1234-5678',
            'email' => 'contacto@comerciodemo.test',
            'punto_venta' => '0001',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('company_settings', [
        'razon_social' => 'Comercio Demo SRL',
        'cuit' => '30-12345678-9',
        'condicion_iva' => 'responsable_inscripto',
        'domicilio_fiscal' => 'Av. Siempre Viva 123',
        'punto_venta' => '0001',
    ]);
});

test('saving twice updates the same row instead of creating a second one', function () {
    Livewire::test(General::class)
        ->fillForm(['razon_social' => 'Primero', 'cuit' => '1', 'condicion_iva' => 'monotributo', 'domicilio_fiscal' => 'A'])
        ->call('save');

    Livewire::test(General::class)
        ->fillForm(['razon_social' => 'Segundo', 'cuit' => '2', 'condicion_iva' => 'monotributo', 'domicilio_fiscal' => 'B'])
        ->call('save');

    $this->assertDatabaseCount('company_settings', 1);
    expect(CompanySetting::first()->razon_social)->toBe('Segundo');
});

test('uploading a logo persists its path', function () {
    Livewire::test(General::class)
        ->fillForm([
            'razon_social' => 'Comercio Demo SRL',
            'cuit' => '1',
            'condicion_iva' => 'monotributo',
            'domicilio_fiscal' => 'A',
            'logo_path' => UploadedFile::fake()->image('logo.png'),
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(CompanySetting::first()->logo_path)->not->toBeNull();
});
