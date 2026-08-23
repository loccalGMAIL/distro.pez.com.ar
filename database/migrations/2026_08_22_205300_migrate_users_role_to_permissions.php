<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * @var array<int, string>
     */
    private array $roles = ['admin', 'vendedor', 'deposito', 'chofer'];

    public function up(): void
    {
        foreach ($this->roles as $role) {
            Role::findOrCreate($role, 'web');
        }

        DB::table('users')->select('id', 'role')->orderBy('id')->each(function (object $user): void {
            DB::table('model_has_roles')->insertOrIgnore([
                'role_id' => Role::findByName($user->role, 'web')->id,
                'model_type' => User::class,
                'model_id' => $user->id,
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'vendedor', 'deposito', 'chofer'])
                ->default('vendedor')
                ->after('email');
        });

        DB::table('users')->select('id')->orderBy('id')->each(function (object $user): void {
            $roleId = DB::table('model_has_roles')
                ->where('model_type', User::class)
                ->where('model_id', $user->id)
                ->value('role_id');

            $roleName = $roleId ? DB::table('roles')->where('id', $roleId)->value('name') : null;

            DB::table('users')->where('id', $user->id)->update([
                'role' => $roleName ?? 'vendedor',
            ]);
        });
    }
};
