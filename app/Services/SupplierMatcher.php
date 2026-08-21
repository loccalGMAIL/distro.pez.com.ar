<?php

namespace App\Services;

use App\Models\Supplier;

class SupplierMatcher
{
    /**
     * Matchea el texto de proveedor devuelto por la IA contra los
     * proveedores existentes, plegando acentos/mayúsculas y comparando por
     * "contains" en ambas direcciones (el texto de la factura puede traer
     * el nombre completo o abreviado, y viceversa).
     */
    public function match(?string $name): ?Supplier
    {
        $needle = $this->normalize($name ?? '');

        if ($needle === '') {
            return null;
        }

        return Supplier::query()
            ->where('activo', true)
            ->get(['id', 'razon_social'])
            ->first(function (Supplier $supplier) use ($needle): bool {
                $haystack = $this->normalize($supplier->razon_social);

                return str_contains($haystack, $needle) || str_contains($needle, $haystack);
            });
    }

    private function normalize(string $value): string
    {
        $folded = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;

        return trim(preg_replace('/\s+/', ' ', mb_strtolower($folded)));
    }
}
