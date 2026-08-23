---
paths:
  - 'app/Filament/Clusters/Settings/Resources/**,app/Policies/**,config/filament-shield.php,config/permission.php,app/Models/*.php'
---

# Policies Models

## Roles/permisos con filament-shield + spatie/permission
Autorización via bezhansalleh/filament-shield (v4, Filament 5) + spatie/laravel-permission. Puntos no obvios:

- `config/filament-shield.php`: `super_admin.name = 'admin'` y `super_admin.define_via_gate = true` (NO el default `false`). Con `define_via_gate=false` el "admin" solo tiene acceso porque cada permiso quedó asignado como fila explícita en la DB (lo hace `shield:generate` al crear cada permiso) — en tests con `RefreshDatabase` (migran pero no seedean) eso deja a admin sin ningún permiso real y todo devuelve 403. Con `define_via_gate=true` hay un `Gate::before` que bypassea todo con solo tener el rol `admin`, sin depender de que existan filas de permisos.
- Formato de permisos: `Accion:Recurso` en PascalCase (ej. `ViewAny:Product`, `View:User`), no snake_case.
- `php artisan shield:generate --resource=X` espera el **basename** de la clase (`UserResource`), no el FQCN completo — pasar el FQCN se acepta sin error pero procesa 0 entidades.
- Policies para modelos fuera de `app/Models` (ej. `Spatie\Activitylog\Models\Activity`, `Spatie\Permission\Models\Role`) quedan "requires registration": Laravel no las descubre por convención. Hay que registrarlas a mano con `Gate::policy(Modelo::class, Policy::class)` en `AppServiceProvider::boot()` (ver `ActivityPolicy`/`RolePolicy`).
- Roles legados `admin/vendedor/deposito/chofer` vienen de una migración de datos (`migrate_users_role_to_permissions`) que reemplazó el enum `users.role`. `vendedor/deposito/chofer` reciben, vía `database/seeders/RolePermissionSeeder.php`, TODOS los permisos existentes excepto los de User/Role/Activity (para no romper el acceso que ya tenían cuando no existía ninguna Policy). Al agregar un resource nuevo, correr `shield:generate` y luego re-ejecutar ese seeder si corresponde que los roles legados lo vean.
- Los eventos de autenticación (login/logout/fallidos/lockout/reset) se registran en el mismo `activity_log` bajo el log `auth` desde `App\Listeners\LogAuthenticationActivity`, registrado con `Event::subscribe()` en `AppServiceProvider::boot()`. Sus métodos NO se llaman `handle*` a propósito: Laravel autodescubre los listeners de `app/Listeners` cuyos métodos empiezan con `handle`, y con el `subscribe()` quedarían registrados dos veces (dos actividades por cada ingreso). El log de modelos usa `default`, así que filtrar por `log_name` separa "cambios de datos" de "ingresos al sistema".
- `LogsActivity` (spatie/laravel-activitylog) en un modelo requiere `logAll()` (o `logOnly`/`logFillable`) en `getActivitylogOptions()` — `LogOptions::defaults()->logOnlyDirty()` solo, sin especificar atributos, no registra nada (el array de atributos a loguear queda vacío).
