<?php

namespace App\Models;

use Database\Factories\CompanySettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CompanySetting extends Model
{
    /** @use HasFactory<CompanySettingFactory> */
    use HasFactory, LogsActivity;

    protected $fillable = [
        'razon_social',
        'cuit',
        'condicion_iva',
        'domicilio_fiscal',
        'telefono',
        'email',
        'punto_venta',
        'logo_path',
    ];

    /**
     * Nombre con el que se identifica la app hacia afuera (manifest de la PWA,
     * título de la pantalla de inicio del celular). Cae en `APP_NAME` mientras
     * no se haya cargado la razón social.
     */
    public static function appName(): string
    {
        $razonSocial = static::query()->value('razon_social');

        return filled($razonSocial)
            ? (string) $razonSocial
            : (string) config('app.name');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll()->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    /**
     * @return array<string, string>
     */
    public static function condicionIvaOptions(): array
    {
        return [
            'responsable_inscripto' => 'Responsable Inscripto',
            'monotributo' => 'Monotributo',
            'exento' => 'Exento',
            'consumidor_final' => 'Consumidor Final',
            'no_responsable' => 'No Responsable',
        ];
    }
}
