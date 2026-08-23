<?php

use App\Filament\Clusters\Partners\Resources\Customers\Pages\CreateCustomer;
use App\Models\Customer;
use App\Models\PriceList;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create(['activo' => true]));
});

test('a new customer is active by default', function () {
    $priceList = PriceList::factory()->create();

    Livewire::test(CreateCustomer::class)
        ->fillForm([
            'razon_social' => 'Cliente Nuevo SA',
            'price_list_id' => $priceList->id,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $customer = Customer::where('razon_social', 'Cliente Nuevo SA')->firstOrFail();

    expect($customer->activo)->toBeTrue();
});
