<?php

namespace App\Services;

use App\Models\SupplierProductLink;

class ProductLinkMemory
{
    /**
     * Devuelve, para cada clave de descripción dada, el product_id que el
     * usuario ya vinculó antes para ese proveedor (una sola query). Se usa
     * para pisar la sugerencia de la IA con la decisión humana previa.
     *
     * @param  array<int, string>  $descriptionKeys
     * @return array<string, int> description_key => product_id
     */
    public function recallMany(int $supplierId, array $descriptionKeys): array
    {
        if ($descriptionKeys === []) {
            return [];
        }

        return SupplierProductLink::query()
            ->where('supplier_id', $supplierId)
            ->whereIn('description_key', array_unique($descriptionKeys))
            ->pluck('product_id', 'description_key')
            ->all();
    }

    /**
     * Guarda (o actualiza) la vinculación proveedor+descripción→producto
     * confirmada por el usuario.
     */
    public function remember(int $supplierId, string $descriptionKey, int $productId): void
    {
        SupplierProductLink::query()->updateOrCreate(
            ['supplier_id' => $supplierId, 'description_key' => $descriptionKey],
            ['product_id' => $productId],
        );
    }
}
