<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin user if not exists
        if (!User::where('email', 'admin@moviedb.com')->exists()) {
            User::factory()->create([
                'name' => 'Admin User',
                'email' => 'admin@moviedb.com',
            ]);
        }

        // Create test user if not exists
        if (!User::where('email', 'test@example.com')->exists()) {
            User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);
        }

        // Seed movies from OMDb API (requires API key)
        // $this->call(MovieSeeder::class);
        
        // Seed sample movies (no API required)
        $this->call(SampleMovieSeeder::class);
    }
}
