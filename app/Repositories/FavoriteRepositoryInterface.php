<?php

namespace App\Repositories;

use App\Models\Favorite;
use App\Models\Movie;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface FavoriteRepositoryInterface
{
    public function findByUserAndMovie(int $userId, int $movieId): ?Favorite;
    public function getUserFavorites(int $userId, int $perPage = 12): LengthAwarePaginator;
    public function addFavorite(int $userId, int $movieId): Favorite;
    public function removeFavorite(int $userId, int $movieId): bool;
    public function isFavorited(int $userId, int $movieId): bool;
    public function toggleFavorite(int $userId, int $movieId): array;
    public function getFavoriteCount(int $movieId): int;
    public function getUserFavoriteStats(int $userId): array;
}