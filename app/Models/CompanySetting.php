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
        'proximo_numero_comprobante',
        'logo_path',
    ];

    protected function casts(): array
    {
        return [
            'proximo_numero_comprobante' => 'integer',
        ];
    }
}
