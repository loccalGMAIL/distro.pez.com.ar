<?php

use App\Filament\Clusters\Settings\Resources\ActivityLogs\Pages\ListActivityLogs;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create(['activo' => true]));
});

test('creating a product records an activity log entry', function () {
    $product = Product::factory()->create(['nombre' => 'Producto Auditado']);

    $activity = Activity::query()->forSubject($product)->latest('id')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->event)->toBe('created')
        ->and($activity->causer_id)->toBe(auth()->id());
});

test('updating a product records only the dirty attributes', function () {
    $product = Product::factory()->create(['nombre' => 'Original']);

    $product->update(['nombre' => 'Actualizado']);

    $activity = Activity::query()->forSubject($product)->where('event', 'updated')->latest('id')->first();

    expect($activity)->not->toBeNull();
    expect($activity->properties->get('attributes'))->toHaveKey('nombre');
});

test('the activity log page lists recorded activity', function () {
    Product::factory()->create();

    Livewire::test(ListActivityLogs::class)->assertSuccessful();
});
