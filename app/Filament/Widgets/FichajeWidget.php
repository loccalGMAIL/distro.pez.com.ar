<?php

namespace App\Filament\Widgets;

use App\Models\TimeEntry;
use App\Models\User;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class FichajeWidget extends Widget
{
    use HasWidgetShield;

    protected static ?int $sort = 0;

    protected string $view = 'filament.widgets.fichaje-widget';

    protected int|string|array $columnSpan = [
        'default' => 2,
        'sm' => 1,
        'lg' => 1,
    ];

    public function clockIn(): void
    {
        $user = $this->currentUser();

        abort_unless($user->can('View:FichajeWidget'), 403);

        TimeEntry::clockIn($user);

        Notification::make()->success()->title('Jornada iniciada')->send();
    }

    public function clockOut(): void
    {
        $user = $this->currentUser();

        abort_unless($user->can('View:FichajeWidget'), 403);

        $entry = TimeEntry::openFor($user);

        if ($entry) {
            $entry->update(['ended_at' => now()]);

            Notification::make()->success()->title('Jornada finalizada')->send();
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'openEntry' => TimeEntry::openFor($this->currentUser()),
        ];
    }

    private function currentUser(): User
    {
        return User::query()->whereKey(Auth::id())->firstOrFail();
    }
}
