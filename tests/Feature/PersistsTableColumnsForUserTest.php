<?php

use App\Filament\Clusters\Catalog\Resources\Products\Pages\ListProducts;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->admin()->create(['activo' => true]);
    $this->actingAs($this->user);
});

function hideSkuColumn(array $state): array
{
    return collect($state)
        ->map(function (array $item): array {
            if ($item['type'] === 'column' && $item['name'] === 'sku') {
                $item['isToggled'] = false;
            }

            return $item;
        })
        ->all();
}

test('a new user with no stored preference sees the default column state', function () {
    $component = Livewire::test(ListProducts::class);

    expect($component->instance()->isTableColumnToggledHidden('sku'))->toBeFalse();
});

test('toggling a column off persists the preference for the user', function () {
    $component = Livewire::test(ListProducts::class);
    $modified = hideSkuColumn($component->instance()->getDefaultTableColumnState());

    $component->call('applyTableColumnManager', $modified);

    $this->assertDatabaseHas('user_table_column_preferences', [
        'user_id' => $this->user->id,
        'table_key' => ListProducts::class,
    ]);

    $stored = $this->user->tableColumnPreferences()->where('table_key', ListProducts::class)->first();
    $skuState = collect($stored->columns)->firstWhere('name', 'sku');

    expect($skuState['isToggled'])->toBeFalse();
});

test('a hidden column stays hidden after logging out and back in', function () {
    $component = Livewire::test(ListProducts::class);
    $modified = hideSkuColumn($component->instance()->getDefaultTableColumnState());
    $component->call('applyTableColumnManager', $modified);

    auth()->logout();
    $this->actingAs($this->user);

    $reloaded = Livewire::test(ListProducts::class);

    expect($reloaded->instance()->isTableColumnToggledHidden('sku'))->toBeTrue();
});

test('a different user is not affected by another user\'s hidden column', function () {
    $component = Livewire::test(ListProducts::class);
    $modified = hideSkuColumn($component->instance()->getDefaultTableColumnState());
    $component->call('applyTableColumnManager', $modified);

    $otherUser = User::factory()->admin()->create(['activo' => true]);
    $this->actingAs($otherUser);

    $otherComponent = Livewire::test(ListProducts::class);

    expect($otherComponent->instance()->isTableColumnToggledHidden('sku'))->toBeFalse();
});
