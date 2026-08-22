<?php

use App\Filament\Clusters\Sales\Resources\Sales\Pages\ListSales;
use App\Models\Customer;
use App\Models\PriceList;
use App\Models\User;
use App\Models\Warehouse;
use Filament\Actions\CreateAction;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create(['role' => 'admin', 'activo' => true]));
    Customer::factory()->create(['predeterminado' => true]);
    PriceList::factory()->create(['predeterminada' => true]);
    Warehouse::factory()->create(['predeterminado' => true]);
});

test('creating a customer from the sale form selects it as the sale customer', function () {
    $priceList = PriceList::factory()->create();

    Livewire::test(ListSales::class)
        ->mountAction(CreateAction::class)
        ->callAction(
            TestAction::make('createOption')->schemaComponent('customer_id'),
            data: [
                'razon_social' => 'Cliente Nuevo SA',
                'price_list_id' => $priceList->id,
            ],
        )
        ->assertHasNoActionErrors()
        ->assertSchemaStateSet(function (array $state) {
            $customer = Customer::where('razon_social', 'Cliente Nuevo SA')->firstOrFail();

            expect((int) $state['customer_id'])->toBe($customer->id);

            return [];
        });

    expect(Customer::where('razon_social', 'Cliente Nuevo SA')->count())->toBe(1);
});
