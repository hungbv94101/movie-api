<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFavoriteRequest;
use App\Services\FavoriteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FavoritesController extends Controller
{
    public function __construct(
        private FavoriteService $favoriteService
    ) {}

    /**
     * Display user's favorite movies
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 12);
        $userId = $request->user()->id;
        
        $favorites = $this->favoriteService->getUserFavorites($userId, $perPage);

        return response()->json([
            'success' => true,
            'data' => $favorites
        ]);
    }

    /**
     * Add movie to favorites
     */
    public function store(StoreFavoriteRequest $request): JsonResponse
    {
        try {
            $movieId = $request->validated()['movie_id'];
            $userId = $request->user()->id;

            $favorite = $this->favoriteService->addToFavorites($userId, $movieId);

            return response()->json([
                'success' => true,
                'message' => 'Movie added to favorites successfully',
                'data' => $favorite
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => true,
                'message' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error adding movie to favorites'
            ], 500);
        }
    }

    /**
     * Remove movie from favorites
     */
    public function destroy(Request $request, string $movieId): JsonResponse
    {
        try {
            $userId = $request->user()->id;

            $removed = $this->favoriteService->removeFromFavorites($userId, $movieId);

            if (!$removed) {
                return response()->json([
                    'success' => false,
                    'message' => 'Movie not found in favorites'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Movie removed from favorites'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error removing movie from favorites'
            ], 500);
        }
    }

    /**
     * Toggle favorite status
     */
    public function toggle(StoreFavoriteRequest $request): JsonResponse
    {
        try {
            $movieId = $request->validated()['movie_id'];
            $userId = $request->user()->id;

            $result = $this->favoriteService->toggleFavorite($userId, $movieId);

            return response()->json([
                'success' => true,
                'message' => $result['added'] ? 'Movie added to favorites' : 'Movie removed from favorites',
                'is_favorited' => $result['added'],
                'movie_favorite_count' => $result['movie_favorite_count'] ?? null,
                'data' => $result['favorite'] ?? null
            ], $result['added'] ? 201 : 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error toggling favorite status'
            ], 500);
        }
    }

    /**
     * Check if movie is favorited
     */
    public function check(Request $request, string $movieId): JsonResponse
    {
        try {
            $userId = $request->user()->id;
            
            $isFavorited = $this->favoriteService->isMovieFavoritedBy($movieId, $userId);

            return response()->json([
                'success' => true,
                'is_favorited' => $isFavorited
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error checking favorite status'
            ], 500);
        }
    }

    /**
     * Get favorite statistics
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            $userId = $request->user()->id;
            
            $stats = $this->favoriteService->getUserFavoriteStats($userId);

            return response()->json([
                'success' => true,
                'total_favorites' => $stats['total_favorites'],
                'favorites_by_genre' => $stats['favorites_by_genre'],
                'recent_favorites' => $stats['recent_favorites']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error retrieving favorite statistics'
            ], 500);
        }
    }
}
