<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Movie;

class SeedMovies extends Command
{
    protected $signature = 'movies:seed {--count=100 : Number of movies to seed}';
    protected $description = 'Seed movies table with fake data';

    public function handle()
    {
        $count = (int) $this->option('count');
        $this->info("Seeding {$count} movies...");

        $created = 0;
        $tries = 0;
        while ($created < $count && $tries < $count * 3) {
            $data = Movie::factory()->make()->toArray();
            $tries++;
            // Không tạo nếu đã tồn tại imdb_id hoặc không có poster
            if (empty($data['poster'])) continue;
            if (Movie::where('imdb_id', $data['imdb_id'])->exists()) continue;
            Movie::create($data);
            $created++;
        }
        $this->info("Done! Seeded $created movies.");
    }
}
