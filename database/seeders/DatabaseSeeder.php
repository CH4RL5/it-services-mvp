<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Admin Principal (VERIFICADO)
        User::create([
            'name' => 'Admin Principal',
            'email' => 'admin@mimic.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'phone' => '5215555555555', // Teléfono dummy
            'is_online' => true,
            'email_verified_at' => now(), // <--- Esto evita que pida verificar correo
        ]);

        // 2. Experto en Servidores (VERIFICADO)
        User::create([
            'name' => 'Experto Linux',
            'email' => 'linux@mimic.com',
            'password' => Hash::make('password'),
            'role' => 'expert',
            'expertise' => 'Servidores', // Solo verá tickets de Servidores
            'phone' => '5215551111111',
            'is_online' => true,
            'email_verified_at' => now(),
        ]);

        // 3. Experto en DNS (VERIFICADO)
        User::create([
            'name' => 'Experto Dominios',
            'email' => 'dns@mimic.com',
            'password' => Hash::make('password'),
            'role' => 'expert',
            'expertise' => 'DNS', // Solo verá tickets de DNS
            'phone' => '5215552222222',
            'is_online' => true,
            'email_verified_at' => now(),
        ]);

        // 4. Experto General / Todoterreno (VERIFICADO)
        User::create([
            'name' => 'Experto General',
            'email' => 'experto@mimic.com',
            'password' => Hash::make('password'),
            'role' => 'expert',
            'expertise' => null, // null = Ve TODOS los tickets (incluyendo "Otros")
            'phone' => '5215553333333',
            'is_online' => true,
            'email_verified_at' => now(),
        ]);

        // 5. Cliente de Prueba (VERIFICADO)
        User::create([
            'name' => 'Cliente Feliz',
            'email' => 'cliente@mimic.com',
            'password' => Hash::make('password'),
            'role' => 'client',
            'phone' => '5215554444444',
            'email_verified_at' => now(),
        ]);
    }
}
