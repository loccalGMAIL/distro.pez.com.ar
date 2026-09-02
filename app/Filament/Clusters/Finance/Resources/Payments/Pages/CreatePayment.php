<?php

namespace App\Filament\Clusters\Finance\Resources\Payments\Pages;

use App\Filament\Clusters\Finance\Resources\Payments\PaymentResource;
use App\Models\Payment;
use App\Services\PaymentAllocator;
use Filament\Resources\Pages\CreateRecord;

class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;

    /**
     * Al crear un pago a proveedor, imputarlo solo a sus compras
     * confirmadas con saldo (ver PaymentAllocator). El usuario puede
     * ajustar la imputación después desde la pestaña "Imputaciones".
     */
    protected function afterCreate(): void
    {
        /** @var Payment $record */
        $record = $this->record;

        app(PaymentAllocator::class)->allocate($record);
    }
}
