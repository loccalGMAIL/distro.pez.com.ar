<?php

use App\Models\Warehouse;

test('marking a warehouse as default unmarks the previous default', function () {
    $a = Warehouse::factory()->create(['predeterminado' => true]);
    $b = Warehouse::factory()->create(['predeterminado' => true]);

    expect($a->fresh()->predeterminado)->toBeFalse();
    expect($b->fresh()->predeterminado)->toBeTrue();
});
