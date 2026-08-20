<?php

namespace App\Models;

use Database\Factories\CompanySettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    /** @use HasFactory<CompanySettingFactory> */
    use HasFactory;

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
