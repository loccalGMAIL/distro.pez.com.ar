<?php

use App\Filament\Widgets\FichajeWidget;
use App\Models\TimeEntry;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    Role::findOrCreate('administrativo', 'web')
        ->givePermissionTo(Permission::findOrCreate('View:FichajeWidget', 'web'));
});

test('an administrativo user can clock in and out', function () {
    $user = User::factory()->administrativo()->create();
    $this->actingAs($user);

    Livewire::test(FichajeWidget::class)->call('clockIn');
    expect(TimeEntry::openFor($user))->not->toBeNull();

    Livewire::test(FichajeWidget::class)->call('clockOut');
    expect(TimeEntry::openFor($user))->toBeNull();
});

test('a second clockIn does not duplicate the open entry', function () {
    $user = User::factory()->administrativo()->create();
    $this->actingAs($user);

    Livewire::test(FichajeWidget::class)->call('clockIn');
    Livewire::test(FichajeWidget::class)->call('clockIn');

    expect(TimeEntry::query()->where('user_id', $user->id)->count())->toBe(1);
});

test('a user without the fichaje permission cannot clock in', function () {
    $vendedor = User::factory()->vendedor()->create();
    $this->actingAs($vendedor);

    // Se instancia el widget directamente (sin Livewire::test) porque el
    // harness de test de Livewire convierte una HttpException lanzada
    // dentro de un método en una respuesta HTTP renderizada en vez de
    // dejarla propagar como excepción de PHP.
    expect(fn () => (new FichajeWidget)->clockIn())->toThrow(HttpException::class);

    expect(TimeEntry::query()->where('user_id', $vendedor->id)->exists())->toBeFalse();
});
