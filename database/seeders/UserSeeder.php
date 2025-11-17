<?php

namespace Database\Seeders;

use App\Models\User;
use Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $role = Role::findByName('Owner');
        $permissions = Permission::all();

        $createUser = User::create(['name'=>'Owner','username'=>'owner','password'=>Hash::make('owner123'),'email'=>'owner@gmail.com']);

        $role->givePermissionTo($permissions);
        $createUser->assignRole($role);

        $createUser->save();
    }
}
