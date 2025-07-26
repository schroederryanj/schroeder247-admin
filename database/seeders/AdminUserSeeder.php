<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'ryan@schroeder247.com'],
            [
                'name' => 'Ryan Schroeder',
                'email' => 'ryan@schroeder247.com',
                'password' => Hash::make('Changeme01!!!'),
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('Admin user created: ryan@schroeder247.com');
    }
}
