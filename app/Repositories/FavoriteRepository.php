<?php

namespace App\Repositories;

use App\Models\Favorite;
use App\Models\Movie;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class FavoriteRepository implements FavoriteRepositoryInterface
{
    public function findByUserAndMovie(int $userId, int $movieId): ?Favorite
    {
        return Favorite::where('user_id', $userId)
                      ->where('movie_id', $movieId)
                      ->first();
    }

    public function getUserFavorites(int $userId, int $perPage = 12): LengthAwarePaginator
    {
        return Movie::whereHas('favoritedBy', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })
        ->withCount('favoritedBy')
        ->latest('favorites.created_at')
        ->paginate($perPage);
    }

    public function addFavorite(int $userId, int $movieId): Favorite
    {
        return Favorite::create([
            'user_id' => $userId,
            'movie_id' => $movieId
        ]);
    }

    public function removeFavorite(int $userId, int $movieId): bool
    {
        $favorite = $this->findByUserAndMovie($userId, $movieId);
        
        if (!$favorite) {
            return false;
        }

        return $favorite->delete();
    }

    public function isFavorited(int $userId, int $movieId): bool
    {
        return $this->findByUserAndMovie($userId, $movieId) !== null;
    }

    /**
     * Get total favorite count for a movie
     */
    public function getFavoriteCount(int $movieId): int
    {
        return Favorite::where('movie_id', $movieId)->count();
    }

    public function toggleFavorite(int $userId, int $movieId): array
    {
        $favorite = $this->findByUserAndMovie($userId, $movieId);

        if ($favorite) {
            $favorite->delete();
            return [
                'added' => false,
                'favorite' => null
            ];
        }

        $newFavorite = $this->addFavorite($userId, $movieId);
        
        return [
            'added' => true,
            'favorite' => $newFavorite->load('movie')
        ];
    }

    public function getUserFavoriteStats(int $userId): array
    {
        $totalFavorites = Favorite::where('user_id', $userId)->count();
        
        // Get favorites by genre
        $favoritesByGenre = Favorite::query()
            ->where('user_id', $userId)
            ->join('movies', 'favorites.movie_id', '=', 'movies.id')
            ->selectRaw('movies.genre, COUNT(*) as count')
            ->whereNotNull('movies.genre')
            ->where('movies.genre', '!=', '')
            ->groupBy('movies.genre')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        // Get recent favorites
        $recentFavorites = Favorite::where('user_id', $userId)
            ->with(['movie' => function ($query) {
                $query->select(['id', 'title', 'year', 'poster', 'genre', 'imdb_rating']);
            }])
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(function ($favorite) {
                return $favorite->movie;
            });

        return [
            'total_favorites' => $totalFavorites,
            'favorites_by_genre' => $favoritesByGenre,
            'recent_favorites' => $recentFavorites
        ];
    }
}