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
            if (str_contains($shape->value, "SALES INVOICE")) {
                $permissions[] = [
                    'name' => $shape->value,
                    'group' => 'TRANSAKSI',
                    'guard_name' => 'web'
                ];
            }
            if(str_contains($shape->value, "PIUTANG") || str_contains($shape->value,"HUTANG DAGANG")){
                $permissions[] = [
                    'name' => $shape->value,
                    'group' => 'TRANSAKSI',
                    'guard_name' => 'web'
                ];
            }
            if (str_contains($shape->value, "SHIFT")) {
                $permissions[] = [
                    'name' => $shape->value,
                    'group' => 'KASIR',
                    'guard_name' => 'web'
                ];
            }
            if (str_contains($shape->value, "REKAPAN")) {
                $permissions[] = [
                    'name' => $shape->value,
                    'group' => 'LAPORAN',
                    'guard_name' => 'web'
                ];
            }
            if (str_contains($shape->value, "LEASING") || str_contains($shape->value, "METODE PEMBAYARAN") || str_contains($shape->value, "TIPE PEMBAYARAN") || str_contains($shape->value, "OPERATIONAL")) {
                $permissions[] = [
                    'name' => $shape->value,
                    'group' => 'MASTER DATA',
                    'guard_name' => 'web'
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
