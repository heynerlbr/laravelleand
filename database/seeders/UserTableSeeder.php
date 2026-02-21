<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $role_admin = Role::where('name', 'admin')->first();
        $role_user = Role::where('name', 'user')->first();
        $role_proveedor = Role::where('name', 'proveedor')->first();

        // 1. Admin / Lender (Prestamista)
        $admin = User::updateOrCreate(
            ['email' => 'admin@midominio.com'],
            [
                'name' => 'Administrador Prestamista',
                'password' => Hash::make('password'),
                'identificacion' => '123456789',
                'celular' => '3001234567'
            ]
        );
        $admin->roles()->sync([$role_admin->id, $role_proveedor->id]);

        // 2. Sample Borrower (Prestatario / Cliente)
        $user = User::updateOrCreate(
            ['email' => 'cliente@test.com'],
            [
                'name' => 'Juan Perez Cliente',
                'password' => Hash::make('password'),
                'identificacion' => '987654321',
                'celular' => '3109876543'
            ]
        );
        $user->roles()->sync([$role_user->id]);

        // 3. Optional: More users for stats testing
        for ($i = 1; $i <= 5; $i++) {
            $u = User::create([
                'name' => "Usuario de Prueba $i",
                'email' => "user$i@test.com",
                'password' => Hash::make('password'),
                'identificacion' => "1000$i",
                'celular' => "320000000$i"
            ]);
            $u->roles()->attach($role_user);
        }
    }
}
