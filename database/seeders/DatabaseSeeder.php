<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Crear un Admin
        User::create([
            'name' => 'Admin Principal',
            'email' => 'admin@mimic.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'phone' => '5555555555',
            'is_online' => true,
        ]);

        // 2. Crear un Experto (El que recibirá los tickets)
        User::create([
            'name' => 'Experto IT',
            'email' => 'experto@mimic.com',
            'password' => Hash::make('password'),
            'role' => 'expert',
            'phone' => '1234567890',
            'is_online' => true,
        ]);

        // 3. Crear un Cliente de prueba
        User::create([
            'name' => 'Cliente Feliz',
            'email' => 'cliente@mimic.com',
            'password' => Hash::make('password'),
            'role' => 'client',
            'phone' => '9876543210',
        ]);
    }
}
