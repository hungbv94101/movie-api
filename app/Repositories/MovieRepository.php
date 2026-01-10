<?php

namespace App\Repositories;

use App\Models\Movie;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class MovieRepository implements MovieRepositoryInterface
{
    public function find(int $id): ?Movie
    {
        return Movie::withCount('favoritedBy')->find($id);
    }

    public function findByImdbId(string $imdbId): ?Movie
    {
        return Movie::where('imdb_id', $imdbId)->first();
    }

    public function findWithFavoritesCount(int $id): ?Movie
    {
        return Movie::withCount('favoritedBy')->find($id);
    }

    public function getAll(): Collection
    {
        return Movie::withCount('favoritedBy')->get();
    }

    public function paginate(int $perPage = 12): LengthAwarePaginator
    {
        return Movie::withCount('favoritedBy')->latest()->paginate($perPage);
    }

    public function create(array $data): Movie
    {
        return Movie::create($data);
    }

    public function update(int $id, array $data): ?Movie
    {
        $movie = Movie::find($id);
        
        if (!$movie) {
            return null;
        }

        $movie->update($data);
        return $movie->fresh();
    }

    public function delete(int $id): bool
    {
        $movie = Movie::find($id);
        
        if (!$movie) {
            return false;
        }

        return $movie->delete();
    }

    public function searchByQuery(string $query): Collection
    {
        $terms = explode(' ', trim($query));
        
        return Movie::where(function ($queryBuilder) use ($terms) {
            foreach ($terms as $term) {
                $queryBuilder->orWhere(function ($termQuery) use ($term) {
                    $termQuery->where('title', 'like', "%{$term}%")
                             ->orWhere('genre', 'like', "%{$term}%")
                             ->orWhere('director', 'like', "%{$term}%")
                             ->orWhere('actors', 'like', "%{$term}%");
                });
            }
        })->withCount('favoritedBy')->get();
    }

    public function getWithFavoritesCount(): Collection
    {
        return Movie::withCount('favoritedBy')->get();
    }
}