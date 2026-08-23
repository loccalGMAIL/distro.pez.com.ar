<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            ShieldSeeder::class,
            RolePermissionSeeder::class,
        ]);

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ])->assignRole('admin');

        $this->call([
            ProductCategorySeeder::class,
            WarehouseSeeder::class,
            PriceListSeeder::class,
            ProductSeeder::class,
            SupplierSeeder::class,
            CustomerSeeder::class,
            PurchaseSeeder::class,
            SaleSeeder::class,
        ]);
    }
}
