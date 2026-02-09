<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin Sekolah',
            'email' => 'admin@sekolah.id',
            'password' => Hash::make('password'), // Password admin
            'role' => 'admin',
        ]);
        
        // Buat jaga-jaga kalau mau tes operator
        User::create([
            'name' => 'Petugas Piket',
            'email' => 'piket@sekolah.id',
            'password' => Hash::make('password'),
            'role' => 'operator',
        ]);
    }
}