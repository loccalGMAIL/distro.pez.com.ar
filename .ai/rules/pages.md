---
paths:
  - 'database/seeders/ShieldSeeder.php,app/Filament/**/Pages/**'
---

# Pages

## ShieldSeeder recortado y $this->form en páginas de Filament
- `database/seeders/ShieldSeeder.php` NO es el archivo tal cual lo genera `php artisan shield:generate --seeder`: se le sacaron las ramas de tenants/usuarios/pivot (esta app no usa tenancy y no seedea usuarios ahí) porque eran código muerto que rompía PHPStan. Si hay que regenerarlo, volver a recortarlo: dejar solo el JSON de roles/permisos y el loop que hace `Role::firstOrCreate` + `Permission::firstOrCreate` + `syncPermissions`, respetando que un rol con `permissions: []` se saltea (los legados los llena `RolePermissionSeeder`).
- En páginas de Filament (`Filament\Pages\Page`) `$this->form` existe solo por el `__get` de `ResolvesDynamicLivewireProperties`. Para que el análisis estático lo vea, la clase lleva `@property-read Schema $form` en su docblock (ver `ScanPurchase`, `Settings\Pages\General`).
