---
paths:
  - 'app/Models/{Payment,PaymentAllocation}.php,app/Services/PaymentAllocator.php,app/Filament/Clusters/Finance/**'
---

# Finance

## Imputación de pagos: automática al guardar, vía PaymentAllocator, no un model hook
Un `Payment` creado directo (factory, tinker, seeder) NO se auto-imputa: el reparto automático (FIFO por `fecha` entre las compras `confirmada` con `saldo` del proveedor) vive en `app/Services/PaymentAllocator::allocate()` y se dispara solo desde las páginas Filament (`CreatePayment::afterCreate()`, `EditPayment::afterSave()`/acción "Imputar automáticamente"), no desde un hook del modelo `Payment`. Si se crea un pago por otro camino (import, comando, otro seeder) hay que llamar `app(PaymentAllocator::class)->allocate($payment)` a mano, o correr `php artisan app:recalculate-supplier-balances` después.

`Payment.sin_imputar` y `Purchase.saldo`/`Supplier.balance` son columnas derivadas (`monto − imputado`, `total − imputado`, `sum(saldo) − sum(sin_imputar de egresos)`), nunca se tipean ni se incrementan a mano — ver `Payment::recalcularSinImputar()`, `Purchase::recalcularSaldo()`, `Supplier::recalcularBalance()`. El único punto de recálculo automático es `PaymentAllocation::booted()` (`saved`/`deleted`), que en `sync()` usa `$this->payment?->fresh()` y `$this->allocatable?->fresh()` a propósito: la misma instancia de `PaymentAllocation` puede disparar el hook varias veces (alta y edición) y Eloquent cachea la relación una vez cargada, así que sin `fresh()` un cambio hecho por *otra* instancia en memoria del mismo `Payment`/`Purchase` (p. ej. subir el monto del pago y después tocar la allocation) se pierde silenciosamente y el recálculo usa datos viejos.

Borrar un `Payment` (soft o forzado) dispara `Payment::booted()`'s `deleted`, que borra sus `allocations` una por una (nunca en bloque) para que el hook de cada `PaymentAllocation` corra — el `cascadeOnDelete` de la FK no sirve para esto: solo actúa en un force delete y de todos modos se saltea los hooks de Eloquent.
