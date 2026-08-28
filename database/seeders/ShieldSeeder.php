<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ShieldSeeder extends Seeder
{
    /**
     * Roles y permisos tal cual los emite `php artisan shield:generate
     * --seeder`. El resto del seeder generado (tenants, usuarios y su pivot)
     * no aplica: esta app no usa tenancy y los usuarios no se seedean acá.
     *
     * El rol "Dueño" es la excepción: se creó a mano en producción desde
     * Configuración → Roles (nunca pasó por `shield:generate`), así que su
     * lista de permisos acá es una transcripción literal de lo que ya tenía
     * asignado en producción al momento de sumarlo a este archivo — no la
     * cambies sin confirmar contra la base real, porque no hay otra fuente
     * de verdad para ese rol.
     */
    private const ROLES_WITH_PERMISSIONS = '[{"name":"admin","guard_name":"web","permissions":["ViewAny:Activity","View:Activity","Create:Activity","Update:Activity","Delete:Activity","DeleteAny:Activity","Restore:Activity","ForceDelete:Activity","ForceDeleteAny:Activity","RestoreAny:Activity","Replicate:Activity","Reorder:Activity","ViewAny:User","View:User","Create:User","Update:User","Delete:User","DeleteAny:User","Restore:User","ForceDelete:User","ForceDeleteAny:User","RestoreAny:User","Replicate:User","Reorder:User","ViewAny:PriceList","View:PriceList","Create:PriceList","Update:PriceList","Delete:PriceList","DeleteAny:PriceList","Restore:PriceList","ForceDelete:PriceList","ForceDeleteAny:PriceList","RestoreAny:PriceList","Replicate:PriceList","Reorder:PriceList","ViewAny:Product","View:Product","Create:Product","Update:Product","Delete:Product","DeleteAny:Product","Restore:Product","ForceDelete:Product","ForceDeleteAny:Product","RestoreAny:Product","Replicate:Product","Reorder:Product","ViewAny:Warehouse","View:Warehouse","Create:Warehouse","Update:Warehouse","Delete:Warehouse","DeleteAny:Warehouse","Restore:Warehouse","ForceDelete:Warehouse","ForceDeleteAny:Warehouse","RestoreAny:Warehouse","Replicate:Warehouse","Reorder:Warehouse","ViewAny:Expense","View:Expense","Create:Expense","Update:Expense","Delete:Expense","DeleteAny:Expense","Restore:Expense","ForceDelete:Expense","ForceDeleteAny:Expense","RestoreAny:Expense","Replicate:Expense","Reorder:Expense","ViewAny:Payment","View:Payment","Create:Payment","Update:Payment","Delete:Payment","DeleteAny:Payment","Restore:Payment","ForceDelete:Payment","ForceDeleteAny:Payment","RestoreAny:Payment","Replicate:Payment","Reorder:Payment","ViewAny:Customer","View:Customer","Create:Customer","Update:Customer","Delete:Customer","DeleteAny:Customer","Restore:Customer","ForceDelete:Customer","ForceDeleteAny:Customer","RestoreAny:Customer","Replicate:Customer","Reorder:Customer","ViewAny:Supplier","View:Supplier","Create:Supplier","Update:Supplier","Delete:Supplier","DeleteAny:Supplier","Restore:Supplier","ForceDelete:Supplier","ForceDeleteAny:Supplier","RestoreAny:Supplier","Replicate:Supplier","Reorder:Supplier","ViewAny:Purchase","View:Purchase","Create:Purchase","Update:Purchase","Delete:Purchase","DeleteAny:Purchase","Restore:Purchase","ForceDelete:Purchase","ForceDeleteAny:Purchase","RestoreAny:Purchase","Replicate:Purchase","Reorder:Purchase","ViewAny:StockMovement","View:StockMovement","Create:StockMovement","Update:StockMovement","Delete:StockMovement","DeleteAny:StockMovement","Restore:StockMovement","ForceDelete:StockMovement","ForceDeleteAny:StockMovement","RestoreAny:StockMovement","Replicate:StockMovement","Reorder:StockMovement","ViewAny:Sale","View:Sale","Create:Sale","Update:Sale","Delete:Sale","DeleteAny:Sale","Restore:Sale","ForceDelete:Sale","ForceDeleteAny:Sale","RestoreAny:Sale","Replicate:Sale","Reorder:Sale","ViewAny:Role","View:Role","Create:Role","Update:Role","Delete:Role","DeleteAny:Role","Restore:Role","ForceDelete:Role","ForceDeleteAny:Role","RestoreAny:Role","Replicate:Role","Reorder:Role","ViewAny:TimeEntry","View:TimeEntry","Create:TimeEntry","Update:TimeEntry","Delete:TimeEntry","DeleteAny:TimeEntry","Restore:TimeEntry","ForceDelete:TimeEntry","ForceDeleteAny:TimeEntry","RestoreAny:TimeEntry","Replicate:TimeEntry","Reorder:TimeEntry","View:CatalogCluster","View:FinanceCluster","View:PartnersCluster","View:ScanPurchase","View:SalesCluster","View:General","View:NuevaCompraWidget","View:NuevaVentaWidget","View:TimeEntriesReport","View:FichajeWidget"]},{"name":"vendedor","guard_name":"web","permissions":[]},{"name":"deposito","guard_name":"web","permissions":[]},{"name":"chofer","guard_name":"web","permissions":[]},{"name":"administrativo","guard_name":"web","permissions":["View:FichajeWidget"]},{"name":"Dueño","guard_name":"web","permissions":["ViewAny:PriceList","View:PriceList","Create:PriceList","Update:PriceList","Delete:PriceList","DeleteAny:PriceList","Restore:PriceList","ForceDelete:PriceList","ForceDeleteAny:PriceList","RestoreAny:PriceList","Replicate:PriceList","Reorder:PriceList","ViewAny:Product","View:Product","Create:Product","Update:Product","Delete:Product","DeleteAny:Product","Restore:Product","ForceDelete:Product","ForceDeleteAny:Product","RestoreAny:Product","Replicate:Product","Reorder:Product","ViewAny:Warehouse","View:Warehouse","Create:Warehouse","Update:Warehouse","Delete:Warehouse","DeleteAny:Warehouse","Restore:Warehouse","ForceDelete:Warehouse","ForceDeleteAny:Warehouse","RestoreAny:Warehouse","Replicate:Warehouse","Reorder:Warehouse","ViewAny:Expense","View:Expense","Create:Expense","Update:Expense","Delete:Expense","DeleteAny:Expense","Restore:Expense","ForceDelete:Expense","ForceDeleteAny:Expense","RestoreAny:Expense","Replicate:Expense","Reorder:Expense","ViewAny:Payment","View:Payment","Create:Payment","Update:Payment","Delete:Payment","DeleteAny:Payment","Restore:Payment","ForceDelete:Payment","ForceDeleteAny:Payment","RestoreAny:Payment","Replicate:Payment","Reorder:Payment","ViewAny:Customer","View:Customer","Create:Customer","Update:Customer","Delete:Customer","DeleteAny:Customer","Restore:Customer","ForceDelete:Customer","ForceDeleteAny:Customer","RestoreAny:Customer","Replicate:Customer","Reorder:Customer","ViewAny:Supplier","View:Supplier","Create:Supplier","Update:Supplier","Delete:Supplier","DeleteAny:Supplier","Restore:Supplier","ForceDelete:Supplier","ForceDeleteAny:Supplier","RestoreAny:Supplier","Replicate:Supplier","Reorder:Supplier","ViewAny:Purchase","View:Purchase","Create:Purchase","Update:Purchase","Delete:Purchase","DeleteAny:Purchase","Restore:Purchase","ForceDelete:Purchase","ForceDeleteAny:Purchase","RestoreAny:Purchase","Replicate:Purchase","Reorder:Purchase","ViewAny:StockMovement","View:StockMovement","Create:StockMovement","Update:StockMovement","Delete:StockMovement","DeleteAny:StockMovement","Restore:StockMovement","ForceDelete:StockMovement","ForceDeleteAny:StockMovement","RestoreAny:StockMovement","Replicate:StockMovement","Reorder:StockMovement","ViewAny:Sale","View:Sale","Create:Sale","Update:Sale","Delete:Sale","DeleteAny:Sale","Restore:Sale","ForceDelete:Sale","ForceDeleteAny:Sale","RestoreAny:Sale","Replicate:Sale","Reorder:Sale","ViewAny:User","View:User","Create:User","Update:User","Delete:User","DeleteAny:User","Restore:User","ForceDelete:User","ForceDeleteAny:User","RestoreAny:User","Replicate:User","Reorder:User","View:CatalogCluster","View:FinanceCluster","View:PartnersCluster","View:ScanPurchase","View:SalesCluster","View:General","View:NuevaCompraWidget","View:NuevaVentaWidget","ViewAny:TimeEntry","View:TimeEntry","Create:TimeEntry","Update:TimeEntry","Delete:TimeEntry","DeleteAny:TimeEntry","Restore:TimeEntry","ForceDelete:TimeEntry","ForceDeleteAny:TimeEntry","RestoreAny:TimeEntry","Replicate:TimeEntry","Reorder:TimeEntry","View:TimeEntriesReport"]}]';

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (json_decode(self::ROLES_WITH_PERMISSIONS, true) as $roleWithPermissions) {
            $guard = $roleWithPermissions['guard_name'];

            $role = Role::firstOrCreate([
                'name' => $roleWithPermissions['name'],
                'guard_name' => $guard,
            ]);

            $permissionNames = $roleWithPermissions['permissions'];

            // Un rol sin permisos en el JSON se deja como está: los legados
            // (vendedor/deposito/chofer) los recibe RolePermissionSeeder.
            if ($permissionNames === []) {
                continue;
            }

            $permissions = collect($permissionNames)
                ->map(fn (string $permission): Permission => Permission::firstOrCreate([
                    'name' => $permission,
                    'guard_name' => $guard,
                ]))
                ->all();

            $role->syncPermissions($permissions);
        }

        $this->command->info('Shield Seeding Completed.');
    }
}
