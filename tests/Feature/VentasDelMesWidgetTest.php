<?php

use App\Filament\Widgets\VentasDelMesWidget;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

test('it sums only confirmed sales from the current month', function () {
    $this->actingAs(User::factory()->create(['activo' => true]));

    Sale::factory()->create([
        'status' => 'confirmada',
        'fecha' => now(),
        'total' => 1000,
    ]);
    Sale::factory()->create([
        'status' => 'confirmada',
        'fecha' => now(),
        'total' => 2500.50,
    ]);

    // Decoys que no deben sumarse.
    Sale::factory()->create([
        'status' => 'borrador',
        'fecha' => now(),
        'total' => 9999,
    ]);
    Sale::factory()->create([
        'status' => 'confirmada',
        'fecha' => now()->startOfMonth()->subDay(),
        'total' => 9999,
    ]);

    Livewire::test(VentasDelMesWidget::class)
        ->assertSeeHtml('$3.500,50');
});

test('it counts sales made on the last day of the month', function () {
    $this->travelTo(Carbon::parse('2026-08-31 20:00:00'));
    $this->actingAs(User::factory()->create(['activo' => true]));

    Sale::factory()->create([
        'status' => 'confirmada',
        'fecha' => now(),
        'total' => 1000,
    ]);

    Livewire::test(VentasDelMesWidget::class)
        ->assertSeeHtml('$1.000,00');
});
