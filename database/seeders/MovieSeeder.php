<?php

namespace Database\Seeders;

use App\Models\Movie;
use App\Services\OMDbService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class MovieSeeder extends Seeder
{
    private OMDbService $omdbService;

    public function __construct()
    {
        $this->omdbService = new OMDbService();
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🎬 Seeding movies from OMDb API...');

        // Popular movie searches to get diverse content
        $searches = [
            'batman', 'spider', 'action', 'comedy', 'drama', 'thriller',
            'adventure', 'horror', 'romance', 'sci-fi', 'fantasy',
            'avengers', 'star', 'iron', 'captain', 'wonder'
        ];

        $moviesAdded = 0;
        $targetCount = 30;

        foreach ($searches as $search) {
            if ($moviesAdded >= $targetCount) break;

            try {
                $this->command->info("Searching for: {$search}");
                
                $searchResults = $this->omdbService->searchMovies($search);
                
                if ($searchResults['success'] && !empty($searchResults['data'])) {
                    foreach ($searchResults['data'] as $movieData) {
                        if ($moviesAdded >= $targetCount) break;

                        // Check if movie already exists
                        $existingMovie = Movie::where('imdb_id', $movieData['imdbID'])->first();
                        
                        if (!$existingMovie) {
                            try {
                                // Get detailed movie info
                                $detailResult = $this->omdbService->getMovieById($movieData['imdbID']);
                                
                                if ($detailResult['success'] && !empty($detailResult['data'])) {
                                    $detailedMovie = $detailResult['data'];
                                    
                                    // Create movie record
                                    $movie = Movie::create([
                                        'title' => $detailedMovie['Title'] ?? $movieData['Title'],
                                        'imdb_id' => $movieData['imdbID'],
                                        'year' => (int) ($detailedMovie['Year'] ?? $movieData['Year']),
                                        'genre' => $detailedMovie['Genre'] ?? 'N/A',
                                        'director' => $detailedMovie['Director'] ?? 'N/A',
                                        'plot' => $detailedMovie['Plot'] ?? 'N/A',
                                        'poster' => $detailedMovie['Poster'] ?? $movieData['Poster'] ?? null,
                                        'runtime' => $detailedMovie['Runtime'] ?? 'N/A',
                                        'imdb_rating' => $detailedMovie['imdbRating'] !== 'N/A' 
                                            ? (float) $detailedMovie['imdbRating'] 
                                            : null,
                                        'language' => $detailedMovie['Language'] ?? 'English',
                                        'country' => $detailedMovie['Country'] ?? 'N/A',
                                        'ratings' => isset($detailedMovie['Ratings']) 
                                            ? $this->formatRatings($detailedMovie['Ratings']) 
                                            : null,
                                    ]);

                                    $moviesAdded++;
                                    $this->command->info("✅ Added: {$movie->title} ({$movie->year})");
                                    
                                    // Add small delay to avoid rate limiting
                                    sleep(1);
                                }
                            } catch (\Exception $e) {
                                $this->command->warn("❌ Failed to add movie {$movieData['Title']}: " . $e->getMessage());
                                Log::error('Movie seeding error: ' . $e->getMessage());
                            }
                        } else {
                            $this->command->info("⏭️  Skipped: {$movieData['Title']} (already exists)");
                        }
                    }
                }
                
                // Add delay between searches
                sleep(2);
                
            } catch (\Exception $e) {
                $this->command->warn("❌ Search failed for '{$search}': " . $e->getMessage());
                Log::error('Movie search error: ' . $e->getMessage());
            }
        }

        $this->command->info("🎉 Movie seeding completed! Added {$moviesAdded} movies to database.");
    }

    /**
     * Format ratings array
     */
    private function formatRatings(array $ratings): array
    {
        $formatted = [];
        foreach ($ratings as $rating) {
            $formatted[$rating['Source']] = $rating['Value'];
        }
        return $formatted;
    }
}