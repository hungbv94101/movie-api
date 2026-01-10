<?php

namespace App\Services;

use App\Models\Movie;
use App\Repositories\MovieRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class MovieService
{
    public function __construct(
        private MovieRepositoryInterface $movieRepository
    ) {}

    public function getAllMovies(): Collection
    {
        return $this->movieRepository->getAll();
    }

    public function getMoviesPaginated(int $perPage = 12): LengthAwarePaginator
    {
        return $this->movieRepository->paginate($perPage);
    }

    public function findMovie(int $id): ?Movie
    {
        return $this->movieRepository->find($id);
    }

    public function findByImdbId(string $imdbId): ?Movie
    {
        return $this->movieRepository->findByImdbId($imdbId);
    }

    public function createMovie(array $data): Movie
    {
        // Business logic validation
        $this->validateMovieData($data);

        return $this->movieRepository->create($data);
    }

    public function updateMovie(int $id, array $data): ?Movie
    {
        $this->validateMovieData($data, true);

        return $this->movieRepository->update($id, $data);
    }

    public function deleteMovie(int $id): bool
    {
        return $this->movieRepository->delete($id);
    }

    public function searchMovies(string $query): Collection
    {
        if (empty(trim($query))) {
            return $this->movieRepository->getWithFavoritesCount();
        }

        return $this->movieRepository->searchByQuery($query);
    }

    public function movieExists(int $id): bool
    {
        return $this->movieRepository->find($id) !== null;
    }

    public function getMovieById(int $id): ?Movie
    {
        return $this->movieRepository->find($id);
    }

    public function getMovieWithDetails(int $id): ?Movie
    {
        return $this->movieRepository->findWithFavoritesCount($id);
    }

    /**
     * Validate movie data
     */
    private function validateMovieData(array $data, bool $isUpdate = false): void
    {
        // Additional business logic validation can go here
        // For example: checking duplicate IMDB IDs, rating ranges, etc.
        
        if (!$isUpdate && isset($data['imdb_id'])) {
            $existing = $this->movieRepository->findByImdbId($data['imdb_id']);
            if ($existing) {
                throw new \InvalidArgumentException('Movie with this IMDB ID already exists');
            }
        }

        if (isset($data['imdb_rating']) && ($data['imdb_rating'] < 0 || $data['imdb_rating'] > 10)) {
            throw new \InvalidArgumentException('IMDB rating must be between 0 and 10');
        }
    }
}