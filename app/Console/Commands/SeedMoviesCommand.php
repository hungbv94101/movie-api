<?php

namespace App\Console\Commands;

use App\Models\Movie;
use App\Services\OMDbService;
use Illuminate\Console\Command;

class SeedMoviesCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'movies:seed {--count=50 : Number of movies to seed}';

    /**
     * The console command description.
     */
    protected $description = 'Seed movies from OMDb API into database';

    private OMDbService $omdbService;

    public function __construct(OMDbService $omdbService)
    {
        parent::__construct();
        $this->omdbService = $omdbService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = $this->option('count');
        $this->info("Starting to seed {$count} movies from OMDb API...");

        // Popular search terms for movies
        $searchTerms = [
            'action', 'comedy', 'drama', 'thriller', 'horror', 'romance', 
            'adventure', 'fantasy', 'superman', 'batman', 'spider',
            'marvel', 'disney', 'love', 'war', 'space', 'robot',
            'zombie', 'alien', 'magic', 'hero', 'princess', 'king'
        ];

        $seeded = 0;
        $duplicates = 0;
        $errors = 0;

        foreach ($searchTerms as $term) {
            if ($seeded >= $count) break;

            $this->info("Searching for: {$term}");
            
            for ($page = 1; $page <= 5; $page++) {
                if ($seeded >= $count) break;

                $result = $this->omdbService->searchMovies($term, $page);

                if (!$result['success'] || empty($result['data'])) {
                    continue;
                }

                foreach ($result['data'] as $movieData) {
                    if ($seeded >= $count) break;

                    try {
                        // Check if movie already exists
                        $existingMovie = Movie::where('imdb_id', $movieData['imdbID'])->first();
                        if ($existingMovie) {
                            $duplicates++;
                            continue;
                        }

                        // Get detailed movie information
                        $detailResult = $this->omdbService->getMovieById($movieData['imdbID']);
                        
                        if (!$detailResult['success']) {
                            $errors++;
                            continue;
                        }

                        // Transform and validate movie data
                        $transformedData = $this->omdbService->transformMovieData($detailResult['data']);
                        
                        // Skip movies without poster to avoid incomplete displays
                        if (empty($transformedData['poster'])) {
                            $this->warn("⚠️  Skipped: {$transformedData['title']} - No poster available");
                            $errors++;
                            continue;
                        }
                        
                        // Skip movies with missing essential data
                        if (empty($transformedData['title']) || empty($transformedData['year'])) {
                            $this->warn("⚠️  Skipped: Movie with missing title or year");
                            $errors++;
                            continue;
                        }
                        
                        $movie = Movie::create($transformedData);

                        $this->line("✓ Saved: {$movie->title} ({$movie->year})");
                        $seeded++;

                        // Small delay to avoid rate limiting
                        usleep(200000); // 0.2 seconds

                    } catch (\Exception $e) {
                        $this->error("Error saving movie {$movieData['Title']}: " . $e->getMessage());
                        $errors++;
                    }
                }
            }
        }

        $this->newLine();
        $this->info("=== Seeding Summary ===");
        $this->info("✅ Movies seeded: {$seeded}");
        $this->info("⏭️ Duplicates skipped: {$duplicates}");
        $this->info("❌ Errors/Skipped (no poster/incomplete): {$errors}");
        $this->info("🎬 Total movies in database: " . Movie::count());
        
        if ($seeded > 0) {
            $this->info("🎉 Database populated successfully with movies containing posters!");
        }

        return Command::SUCCESS;
    }
}