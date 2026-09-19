<?php

namespace Database\Seeders;

use App\Models\TbrCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'lucascastro121295@gmail.com'],
            [
                'name' => 'Lucas Castro',
                'password' => 'mudar123',
                'role' => 'admin',
            ]
        );

        foreach (['Kid Power', 'Festival de Habilidades'] as $category) {
            TbrCategory::firstOrCreate(['name' => $category]);
        }
    }
}
