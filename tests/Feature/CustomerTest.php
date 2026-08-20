<?php

use App\Models\Customer;

test('marking a customer as default unmarks the previous default', function () {
    $a = Customer::factory()->create(['predeterminado' => true]);
    $b = Customer::factory()->create(['predeterminado' => true]);

    expect($a->fresh()->predeterminado)->toBeFalse();
    expect($b->fresh()->predeterminado)->toBeTrue();
});
