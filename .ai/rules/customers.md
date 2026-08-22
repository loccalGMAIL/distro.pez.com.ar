---
paths:
  - 'app/Filament/Clusters/Sales/**, app/Filament/Clusters/Partners/Resources/Customers/**'
---

# Customers

## Cliente inline en SaleForm: createOptionForm(), no modal custom
El Select `customer_id` de `SaleForm` (`->relationship('customer', 'razon_social')`) usa `->createOptionForm(CustomerForm::quickCreateFields())` para el botón "+" que crea un cliente en modal sin salir de la venta — es el mecanismo nativo de Filament para esto, no un Action/modal custom. Por defecto Filament crea el registro vía `$relationship->getRelated()::create($data)` y selecciona el nuevo id solo — no hace falta `createOptionUsing()` a menos que el alta necesite lógica especial.

`CustomerForm::fields()` (usado por `CustomerResource`) tiene TODOS los campos; `CustomerForm::quickCreateFields()` es un subconjunto deliberadamente mínimo para el alta rápida desde la venta — a propósito, no un descuido: solo los campos que la tabla `customers` exige sin default (`razon_social` y `price_list_id`, éste último NOT NULL desde la migración `2026_08_18_000300_make_price_list_id_required_on_customers`, aunque la migración original lo creó nullable). El resto (código, contacto, condición de pago, saldo, activo, observaciones) tiene default en la tabla y se completa después desde el apartado Clientes. Si se agrega una columna NOT NULL sin default a `customers`, hay que sumarla también acá o el alta rápida rompe.

Test: `tests/Feature/SaleCreateCustomerTest.php`, patrón `TestAction::make('createOption')->schemaComponent('customer_id')` con `callAction(..., data: [...])`, igual que `TestAction::make('scanBarcode')->schemaComponent('lines.item1.product_id')` en `SaleFormBarcodeScanTest.php` para actions anidadas dentro del modal de `CreateAction`.
