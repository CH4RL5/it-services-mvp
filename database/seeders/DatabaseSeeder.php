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

        // Experto 1: El genio de los Servidores
        User::create([
            'name' => 'Experto Linux',
            'email' => 'linux@mimic.com',
            'password' => Hash::make('password'),
            'role' => 'expert',
            'expertise' => 'Servidores', // <--- OJO AQUÍ
            'is_online' => true,
        ]);

        // Experto 2: El mago de los Dominios
        User::create([
            'name' => 'Experto Dominios',
            'email' => 'dns@mimic.com',
            'password' => Hash::make('password'),
            'role' => 'expert',
            'expertise' => 'DNS', // <--- OJO AQUÍ
            'is_online' => true,
        ]);

        // Experto 3: El Todoterreno (Opcional, 'General' o null para ver todo)
        User::create([
            'name' => 'Experto Hosting',
            'email' => 'host@mimic.com',
            'password' => Hash::make('password'),
            'role' => 'expert',
            'expertise' => 'Hosting',
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
