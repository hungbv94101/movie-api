<?php

namespace App\Repositories;

use App\Models\Movie;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface MovieRepositoryInterface
{
    public function find(int $id): ?Movie;
    public function findByImdbId(string $imdbId): ?Movie;
    public function findWithFavoritesCount(int $id): ?Movie;
    public function getAll(): Collection;
    public function paginate(int $perPage = 12): LengthAwarePaginator;
    public function create(array $data): Movie;
    public function update(int $id, array $data): ?Movie;
    public function delete(int $id): bool;
    public function searchByQuery(string $query): Collection;
    public function getWithFavoritesCount(): Collection;
}