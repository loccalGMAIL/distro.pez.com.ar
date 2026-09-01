<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property bool $activo
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property string|null $google_id
 * @property string|null $avatar
 * @property string|null $hourly_rate
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password', 'activo', 'google_id', 'avatar', 'hourly_rate'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, LogsActivity, Notifiable;

    public function canAccessPanel(Panel $panel): bool
    {
        return (bool) $this->activo;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->logExcept(['password', 'remember_token']);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
            'hourly_rate' => 'decimal:2',
        ];
    }

    /**
     * @return HasMany<Purchase, $this>
     */
    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    /**
     * @return HasMany<Sale, $this>
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * @return HasMany<Expense, $this>
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * @return HasMany<StockMovement, $this>
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * @return HasMany<TimeEntry, $this>
     */
    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    /**
     * @return HasMany<UserTableColumnPreference, $this>
     */
    public function tableColumnPreferences(): HasMany
    {
        return $this->hasMany(UserTableColumnPreference::class);
    }

    /**
     * Empleados que cobran por hora: los que tienen tarifa cargada, más
     * cualquiera que ya tenga fichajes aunque no la tenga, para que un ciclo
     * registrado nunca quede sin nadie a quien atribuírselo.
     *
     * No filtra por el rol "administrativo": fichar depende del permiso
     * View:FichajeWidget y del gate de super-admin, así que un admin puede
     * fichar sin tener ese rol y quedaba invisible en todos los selectores.
     *
     * @return array<int, string>
     */
    public static function fichajeOptions(): array
    {
        return static::query()
            ->where(fn (Builder $query): Builder => $query
                ->whereNotNull('hourly_rate')
                ->orWhereHas('timeEntries'))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * Solo los que tienen ciclos cerrados sin liquidar, para el selector de
     * "Liquidar honorarios": elegir a alguien sin nada pendiente no lleva a
     * ningún lado.
     *
     * @return array<int, string>
     */
    public static function conFichajesPendientesOptions(): array
    {
        return static::query()
            ->whereHas('timeEntries', fn (Builder $query): Builder => $query
                ->whereNotNull('ended_at')
                ->whereNull('time_entry_settlement_id'))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }
}
