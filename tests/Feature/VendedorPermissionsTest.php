<?php

use App\Filament\Clusters\Partners\Resources\Customers\Pages\EditCustomer;
use App\Filament\Clusters\Partners\Resources\Customers\Pages\ListCustomers;
use App\Filament\Clusters\Purchases\Pages\ScanPurchase;
use App\Filament\Clusters\Sales\Resources\Sales\Pages\CreateSale;
use App\Filament\Clusters\Sales\Resources\Sales\Pages\ListSales;
use App\Filament\Clusters\Settings\Pages\General;
use App\Models\Customer;
use App\Models\PriceList;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleLine;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\ShieldSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(ShieldSeeder::class);
    $this->seed(RolePermissionSeeder::class);

    $this->vendedor = User::factory()->vendedor()->create(['activo' => true]);
    $this->actingAs($this->vendedor);
});

test('a vendedor only sees their own sales', function () {
    $own = Sale::factory()->create(['user_id' => $this->vendedor->id]);
    $other = Sale::factory()->create(['user_id' => User::factory()->vendedor()->create()->id]);

    Livewire::test(ListSales::class)
        ->assertCanSeeTableRecords([$own])
        ->assertCanNotSeeTableRecords([$other]);
});

test('an admin still sees every sale', function () {
    $admin = User::factory()->admin()->create(['activo' => true]);
    $own = Sale::factory()->create(['user_id' => $this->vendedor->id]);
    $other = Sale::factory()->create(['user_id' => $admin->id]);

    $this->actingAs($admin);

    Livewire::test(ListSales::class)->assertCanSeeTableRecords([$own, $other]);
});

test('a vendedor can open the comprobante of their own sale but not of someone else', function () {
    $own = Sale::factory()->create(['user_id' => $this->vendedor->id, 'status' => 'borrador']);
    $other = Sale::factory()->create(['user_id' => User::factory()->create()->id, 'status' => 'borrador']);

    foreach ([$own, $other] as $sale) {
        SaleLine::factory()->create(['sale_id' => $sale->id]);
        $sale->confirmar();
    }

    $this->get(route('sales.comprobante', $own))->assertOk();
    $this->get(route('sales.comprobante', $other))->assertForbidden();
});

test('a vendedor can confirm and annul their own sale', function () {
    $draft = Sale::factory()->create(['user_id' => $this->vendedor->id, 'status' => 'borrador']);
    SaleLine::factory()->create(['sale_id' => $draft->id]);

    Livewire::test(ListSales::class)
        ->callTableAction('confirmar', $draft);

    expect($draft->fresh()->status)->toBe('confirmada');

    Livewire::test(ListSales::class)
        ->callTableAction('anular', $draft);

    expect($draft->fresh()->status)->toBe('anulada');
});

test('a vendedor cannot see or run actions on a sale that is not theirs', function () {
    $other = Sale::factory()->create(['user_id' => User::factory()->create()->id, 'status' => 'borrador']);

    expect($this->vendedor->can('update', $other))->toBeFalse()
        ->and($this->vendedor->can('view', $other))->toBeFalse();
});

test('a vendedor can list and edit customers but not delete them', function () {
    $customer = Customer::factory()->create();

    Livewire::test(ListCustomers::class)->assertCanSeeTableRecords([$customer]);
    Livewire::test(EditCustomer::class, ['record' => $customer->getRouteKey()])->assertSuccessful();

    expect($this->vendedor->can('delete', $customer))->toBeFalse();
});

test('a vendedor cannot reach purchases', function () {
    $this->get('/dashboard/purchases/purchases')->assertForbidden();
});

test('a vendedor does not get the purchases or settings pages', function () {
    expect(ScanPurchase::canAccess())->toBeFalse()
        ->and(ScanPurchase::shouldRegisterNavigation())->toBeFalse()
        ->and(General::canAccess())->toBeFalse()
        ->and(General::shouldRegisterNavigation())->toBeFalse();

    $this->get('/dashboard/purchases/scan-purchase')->assertForbidden();
    $this->get('/dashboard/settings/general')->assertForbidden();
});

test('an admin keeps access to the purchases and settings pages', function () {
    $this->actingAs(User::factory()->admin()->create(['activo' => true]));

    expect(ScanPurchase::canAccess())->toBeTrue()
        ->and(General::canAccess())->toBeTrue();
});

test('a vendedor only shares price lists enabled for vendedores', function () {
    $enabled = PriceList::factory()->create(['compartible' => true, 'compartible_vendedores' => true]);
    $adminOnly = PriceList::factory()->create(['compartible' => true, 'compartible_vendedores' => false]);
    $off = PriceList::factory()->create(['compartible' => false, 'compartible_vendedores' => true]);

    expect(PriceList::orderedForSharing($this->vendedor)->pluck('id')->all())->toEqual([$enabled->id]);

    $this->get(route('price-lists.pdf', $enabled))->assertOk();
    $this->get(route('price-lists.pdf', $adminOnly))->assertNotFound();
    $this->get(route('price-lists.pdf', $off))->assertNotFound();
});

test('a sale created by a vendedor is always saved under their own user', function () {
    Customer::factory()->create(['predeterminado' => true]);
    PriceList::factory()->create(['predeterminada' => true]);
    Warehouse::factory()->create(['predeterminado' => true]);
    $product = Product::factory()->create();
    $someoneElse = User::factory()->create();

    Livewire::test(CreateSale::class)
        ->fillForm([
            'user_id' => $someoneElse->id,
            'lines' => ['item1' => [
                'product_id' => $product->id,
                'cantidad' => 1,
                'precio_unit' => 100,
                'descuento' => 0,
                'subtotal' => 100,
                'costo_unit' => 0,
            ]],
            'subtotal' => 100,
            'descuento' => 0,
            'total' => 100,
        ])
        ->call('create');

    expect(Sale::count())->toBe(1)
        ->and(Sale::first()->user_id)->toBe($this->vendedor->id);
});

test('a user with ShareAll:PriceList shares every shareable list', function () {
    $adminOnly = PriceList::factory()->create(['compartible' => true, 'compartible_vendedores' => false]);
    $admin = User::factory()->admin()->create(['activo' => true]);

    expect(PriceList::orderedForSharing($admin)->pluck('id')->all())->toEqual([$adminOnly->id]);
});
