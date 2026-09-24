<?php

use App\Filament\Clusters\Partners\Resources\Customers\Pages\CreateCustomer;
use App\Filament\Clusters\Partners\Resources\Customers\Pages\EditCustomer;
use App\Filament\Clusters\Partners\Resources\Customers\Pages\ListCustomers;
use App\Models\Customer;
use App\Models\PriceList;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\Testing\TestAction;
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

test('creating a customer assigns its codigo automatically', function () {
    $priceList = PriceList::factory()->create();

    Livewire::test(CreateCustomer::class)
        ->fillForm([
            'razon_social' => 'Cliente Con Codigo SA',
            'price_list_id' => $priceList->id,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $customer = Customer::where('razon_social', 'Cliente Con Codigo SA')->firstOrFail();

    expect($customer->codigo)->toStartWith('CLI-');
});

test('a customer can be soft deleted from the list', function () {
    $customer = Customer::factory()->create();

    Livewire::test(ListCustomers::class)
        ->callAction(TestAction::make(DeleteAction::class)->table($customer));

    expect(Customer::find($customer->id))->toBeNull();
    expect(Customer::withTrashed()->find($customer->id)?->trashed())->toBeTrue();
});

test('a soft deleted customer can be restored from the list', function () {
    $customer = Customer::factory()->create();
    $customer->delete();

    Livewire::test(ListCustomers::class)
        ->filterTable('trashed', true)
        ->callAction(TestAction::make(RestoreAction::class)->table($customer));

    expect($customer->fresh()->trashed())->toBeFalse();
});

test('customers cannot be force deleted from the panel', function () {
    $customer = Customer::factory()->create();
    $customer->delete();

    Livewire::test(EditCustomer::class, ['record' => $customer->getRouteKey()])
        ->assertActionDoesNotExist(ForceDeleteAction::class);

    Livewire::test(ListCustomers::class)
        ->assertActionDoesNotExist(TestAction::make(ForceDeleteBulkAction::class)->table()->bulk());
});
