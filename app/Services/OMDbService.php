<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class OMDbService
{
    private string $apiKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.omdb.api_key');
        $this->baseUrl = config('services.omdb.base_url');
    }

    /**
     * Search movies by title
     */
    public function searchMovies(string $title, int $page = 1): array
    {
        try {
            $response = Http::timeout(10)->get($this->baseUrl, [
                'apikey' => $this->apiKey,
                's' => $title,
                'page' => $page,
                'type' => 'movie'
            ]);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'message' => 'OMDb API request failed'
                ];
            }

            $data = $response->json();

            if (isset($data['Error'])) {
                return [
                    'success' => false,
                    'message' => $data['Error']
                ];
            }

            return [
                'success' => true,
                'data' => $data['Search'] ?? [],
                'total_results' => (int) ($data['totalResults'] ?? 0),
                'current_page' => $page
            ];

        } catch (Exception $e) {
            Log::error('OMDb API Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Service unavailable. Please try again later.'
            ];
        }
    }

    /**
     * Get movie details by IMDB ID
     */
    public function getMovieById(string $imdbId): array
    {
        try {
            $response = Http::timeout(10)->get($this->baseUrl, [
                'apikey' => $this->apiKey,
                'i' => $imdbId,
                'plot' => 'full'
            ]);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'message' => 'OMDb API request failed'
                ];
            }

            $data = $response->json();

            if (isset($data['Error'])) {
                return [
                    'success' => false,
                    'message' => $data['Error']
                ];
            }

            return [
                'success' => true,
                'data' => $data
            ];

        } catch (Exception $e) {
            Log::error('OMDb API Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Service unavailable. Please try again later.'
            ];
        }
    }

    /**
     * Transform OMDb data to our database format
     */
    public function transformMovieData(array $omdbData): array
    {
        return [
            'imdb_id' => $omdbData['imdbID'] ?? null,
            'title' => $omdbData['Title'] ?? null,
            'year' => $this->parseYear($omdbData['Year'] ?? ''),
            'genre' => $omdbData['Genre'] ?? null,
            'director' => $omdbData['Director'] ?? null,
            'actors' => $omdbData['Actors'] ?? null,
            'plot' => $omdbData['Plot'] ?? null,
            'poster' => $this->validatePoster($omdbData['Poster'] ?? ''),
            'runtime' => $omdbData['Runtime'] ?? null,
            'imdb_rating' => $this->parseRating($omdbData['imdbRating'] ?? ''),
            'language' => $omdbData['Language'] ?? null,
            'country' => $omdbData['Country'] ?? null,
        ];
    }

    /**
     * Parse year from OMDb format
     */
    private function parseYear(string $year): ?int
    {
        if (empty($year) || $year === 'N/A') {
            return null;
        }

        // Extract first 4 digits (handles ranges like "2020–2023")
        if (preg_match('/(\d{4})/', $year, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    /**
     * Parse and validate IMDB rating
     */
    private function parseRating(string $rating): ?float
    {
        if (empty($rating) || $rating === 'N/A') {
            return null;
        }

        $parsed = (float) $rating;
        return ($parsed >= 0 && $parsed <= 10) ? $parsed : null;
    }

    /**
     * Validate poster URL and ensure it's an image
     */
    private function validatePoster(string $poster): ?string
    {
        if (empty($poster) || $poster === 'N/A') {
            return null;
        }

        // Validate URL format
        if (!filter_var($poster, FILTER_VALIDATE_URL)) {
            return null;
        }

        // Check if URL seems to be an image (basic check)
        $imageExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $urlPath = parse_url($poster, PHP_URL_PATH);
        $extension = strtolower(pathinfo($urlPath, PATHINFO_EXTENSION));

        // If no extension, check if it contains image indicators
        if (empty($extension)) {
            // Many movie posters from OMDb don't have extensions but are valid
            // so we'll accept URLs from known poster domains
            $allowedDomains = ['m.media-amazon.com', 'ia.media-imdb.com'];
            $domain = parse_url($poster, PHP_URL_HOST);
            
            if (in_array($domain, $allowedDomains)) {
                // Test if URL actually works (with timeout to avoid hanging)
                try {
                    $response = Http::timeout(3)->head($poster);
                    if ($response->successful() && 
                        str_contains($response->header('content-type', ''), 'image')) {
                        return $poster;
                    }
                } catch (Exception $e) {
                    // If request fails, reject this poster
                    return null;
                }
            }
            
            return null;
        }

        // For URLs with extensions, do a quick HEAD request to verify
        if (in_array($extension, $imageExtensions)) {
            try {
                $response = Http::timeout(3)->head($poster);
                return $response->successful() ? $poster : null;
            } catch (Exception $e) {
                // If verification fails, still return the URL if extension looks right
                // This is a fallback to avoid being too strict
                return $poster;
            }
        }

        return null;
    }
}