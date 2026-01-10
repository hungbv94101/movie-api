<?php

namespace App\Services;

use App\Models\Favorite;
use App\Models\Movie;
use App\Repositories\FavoriteRepositoryInterface;
use App\Repositories\MovieRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class FavoriteService
{
    public function __construct(
        private FavoriteRepositoryInterface $favoriteRepository,
        private MovieRepositoryInterface $movieRepository
    ) {}

    public function getUserFavorites(int $userId, int $perPage = 12): LengthAwarePaginator
    {
        return $this->favoriteRepository->getUserFavorites($userId, $perPage);
    }

    public function addToFavorites(int $userId, int $movieId): Favorite
    {
        return DB::transaction(function () use ($userId, $movieId) {
            // Business logic: Check if movie exists
            $movie = $this->movieRepository->find($movieId);
            if (!$movie) {
                throw new \InvalidArgumentException('Movie not found');
            }

            // Check if already favorited
            if ($this->favoriteRepository->isFavorited($userId, $movieId)) {
                throw new \InvalidArgumentException('Movie is already in favorites');
            }

            // Add to favorites
            $favorite = $this->favoriteRepository->addFavorite($userId, $movieId);
            
            // Update movie favorite count
            $this->updateMovieFavoriteCount($movieId);
            
            return $favorite;
        });
    }

    public function removeFromFavorites(int $userId, int $movieId): bool
    {
        return DB::transaction(function () use ($userId, $movieId) {
            $result = $this->favoriteRepository->removeFavorite($userId, $movieId);
            
            if ($result) {
                // Update movie favorite count
                $this->updateMovieFavoriteCount($movieId);
            }
            
            return $result;
        });
    }

    public function toggleFavorite(int $userId, int $movieId): array
    {
        return DB::transaction(function () use ($userId, $movieId) {
            // For external movie IDs (from OMDB), we'll allow favorites without checking movie existence
            // The favorite count will be managed separately
            
            $result = $this->favoriteRepository->toggleFavorite($userId, $movieId);
            
            // Only update movie count if the movie exists in our database
            $movie = $this->movieRepository->find($movieId);
            if ($movie) {
                $this->updateMovieFavoriteCount($movieId);
                $result['movie_favorite_count'] = $movie->fresh()->favorited_by_count;
            } else {
                // For external movies, get count from favorites table
                $count = $this->favoriteRepository->getFavoriteCount($movieId);
                $result['movie_favorite_count'] = $count;
            }
            
            return $result;
        });
    }

    /**
     * Update the favorited_by_count for a movie
     */
    private function updateMovieFavoriteCount(int $movieId): void
    {
        $count = Favorite::where('movie_id', $movieId)->count();
        Movie::where('id', $movieId)->update(['favorited_by_count' => $count]);
    }

    public function isMovieFavoritedBy(int $movieId, int $userId): bool
    {
        return $this->favoriteRepository->isFavorited($userId, $movieId);
    }

    public function isFavorited(int $userId, int $movieId): bool
    {
        return $this->favoriteRepository->isFavorited($userId, $movieId);
    }

    public function getFavoriteCount(int $movieId): int
    {
        return $this->favoriteRepository->getFavoriteCount($movieId);
    }

    public function getUserFavoriteStats(int $userId): array
    {
        return $this->favoriteRepository->getUserFavoriteStats($userId);
    }
}