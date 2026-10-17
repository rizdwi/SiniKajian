<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'jamaah@sinikajian.id'],
            [
                'name' => 'Ahmad Rabbani',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );

        $this->call([
            KajianSeeder::class,
        ]);
    }
}
