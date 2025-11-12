<?php

namespace Database\Seeders;

use App\Models\User;
use Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin-ruang@secret.com'], // Cek apakah email sudah ada
            [
                'email' => 'admin-ruang@secret.com',
                'name' => 'admin-ruang',
                'password' => Hash::make(env('ADMIN_DEFAULT_PASSWORD', 'password')),
            ]
        );
    }
}
