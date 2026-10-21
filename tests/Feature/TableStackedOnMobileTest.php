<?php

use App\Filament\Clusters\Sales\Resources\Sales\Pages\ListSales;
use App\Models\Sale;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create(['activo' => true]));
});

test('tables are stacked as cards on mobile by default', function () {
    Sale::factory()->create();

    Livewire::test(ListSales::class)
        ->assertSeeHtml('fi-ta-table-stacked-on-mobile')
        ->assertSeeHtml('<div class="fi-ta-cell-label">Cliente</div>');
});
