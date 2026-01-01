<?php

namespace Database\Seeders;

use App\Enums\PermissionEnum;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [];

        foreach (PermissionEnum::cases() as $shape) {
            if(str_contains($shape->value, "DASHBOARD")){
                $permissions[] = [
                    'name'=>$shape->value,
                    'group'=>'HOME',
                    'guard_name'=>'web'
                ];
            }
            if (str_contains($shape->value, "PENGUNA")) {
                $permissions[] = [
                    'name' => $shape->value,
                    'group' => 'PENGUNA',
                    'guard_name' => 'web'
                ];
            }
            if (str_contains($shape->value, "ROLE")) {
                $permissions[] = [
                    'name' => $shape->value,
                    'group' => 'PENGUNA',
                    'guard_name' => 'web'
                ];
            }
            if(str_contains($shape->value, "PRODUCT") || str_contains($shape->value, "PRODUCT CATEGORY") || str_contains($shape->value, "SUPPLIER") || str_contains($shape->value, "WAREHOUSE") || str_contains($shape->value, "CUSTOMER")){
                  $permissions[] = [
                    'name' => $shape->value,
                    'group' => 'MASTER DATA',
                    'guard_name' => 'web'
                ];
            }
            if (str_contains($shape->value, "INBOUND") || str_contains($shape->value, "OUTBOUND")) {
                $permissions[] = [
                    'name' => $shape->value,
                    'group' => 'TRANSAKSI',
                    'guard_name' => 'web'
                ];
            }
            if(str_contains($shape->value, "INVENTORY") || str_contains($shape->value, "PENYESUAIAN STOK") || str_contains($shape->value, "MUTATION IN") || str_contains($shape->value , "MUTATION OUT")){
                $permissions[] = [
                    'name'=>$shape->value,
                    'group'=>'INVENTORY',
                    'guard_name'=>'web'
                ];
            }
            if(str_contains($shape->value,"SETTING")){
                $permissions[]=[
                    'name'=>$shape->value,
                    'group'=>'SETTING',
                    'guard_name'=>'web'
                ];
            }

        }
        Permission::insert($permissions);
    }
}
