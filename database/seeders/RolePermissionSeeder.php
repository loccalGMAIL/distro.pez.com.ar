<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Antes de sumar roles/permisos granulares no existía ninguna Policy:
     * cualquier usuario activo podía hacer cualquier cosa. Para no romper
     * ese acceso, los roles legados conservan todos los permisos existentes
     * salvo los de Usuarios/Roles/Actividad (sectores nuevos, sensibles,
     * reservados a "admin" que ya tiene todo vía Shield).
     *
     * @var array<int, string>
     */
    private array $legacyRoles = ['vendedor', 'deposito', 'chofer'];

    /**
     * @var array<int, string>
     */
    private array $adminOnlySubjects = ['User', 'Role', 'Activity', 'TimeEntry', 'FichajeWidget', 'TimeEntriesReport'];

    public function run(): void
    {
        $permissions = Permission::query()
            ->get()
            ->reject(fn (Permission $permission): bool => in_array(
                Str::afterLast($permission->name, ':'),
                $this->adminOnlySubjects,
                true,
            ))
            ->pluck('name');

        foreach ($this->legacyRoles as $roleName) {
            Role::findOrCreate($roleName, 'web')->syncPermissions($permissions);
        }
    }
}
