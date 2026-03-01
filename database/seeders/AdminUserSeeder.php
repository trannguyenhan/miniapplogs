<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        User::firstOrCreate(
            ['email' => 'admin@miniapplogs.local'],
            [
                'name'     => 'Administrator',
                'password' => Hash::make('Admin@123456'),
                'role'     => 'admin',
            ]
        );

        // Create demo user
        User::firstOrCreate(
            ['email' => 'user@miniapplogs.local'],
            [
                'name'     => 'Demo User',
                'password' => Hash::make('User@123456'),
                'role'     => 'user',
            ]
        );

        $this->command->info('✓ Admin user: admin@miniapplogs.local / Admin@123456');
        $this->command->info('✓ Demo user:  user@miniapplogs.local / User@123456');
    }
}
