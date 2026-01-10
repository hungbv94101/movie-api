<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMovieRequest;
use App\Http\Requests\UpdateMovieRequest;
use App\Services\MovieService;
use App\Services\FavoriteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MoviesController extends Controller
{
    public function __construct(
        private MovieService $movieService,
        private FavoriteService $favoriteService
    ) {}

    /**
     * Display a listing of saved movies
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 12);
        $movies = $this->movieService->getMoviesPaginated($perPage);

        return response()->json($movies);
    }

    /**
     * Store a new movie
     */
    public function store(StoreMovieRequest $request): JsonResponse
    {
        try {
            $movie = $this->movieService->createMovie($request->validated());

            return response()->json([
                'message' => 'Movie created successfully',
                'movie' => $movie
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 409);
        }
    }

    /**
     * Display the specified movie
     */
    public function show(string $id): JsonResponse
    {
        try {
            // Convert string ID to integer for MovieService
            $movieId = (int) $id;
            $movie = $this->movieService->getMovieById($movieId);

            if (!$movie) {
                return response()->json([
                    'success' => false,
                    'message' => 'Movie not found'
                ], 404);
            }

            // Add favorite status for authenticated user
            if (auth()->check()) {
                $movie->is_favorited = $this->favoriteService->isMovieFavoritedBy($movieId, auth()->id());
            }

            return response()->json([
                'success' => true,
                'data' => $movie
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving movie'
            ], 500);
        }
    }

    /**
     * Update the specified movie
     */
    public function update(UpdateMovieRequest $request, string $id): JsonResponse
    {
        try {
            $movie = $this->movieService->getMovieById($id);

            if (!$movie) {
                return response()->json([
                    'message' => 'Movie not found'
                ], 404);
            }

            $updatedMovie = $this->movieService->updateMovie($id, $request->validated());

            return response()->json([
                'message' => 'Movie updated successfully',
                'data' => $updatedMovie
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 409);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error updating movie'
            ], 500);
        }
    }

    /**
     * Remove the specified movie
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $movie = $this->movieService->getMovieById($id);

            if (!$movie) {
                return response()->json([
                    'message' => 'Movie not found'
                ], 404);
            }

            $this->movieService->deleteMovie($id);

            return response()->json([
                'message' => 'Movie deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error deleting movie'
            ], 500);
        }
    }
}
