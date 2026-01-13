<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\FavoritesController;
use App\Http\Controllers\Api\MoviesController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
    
    // Email verification routes
    Route::get('email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->middleware(['signed'])
        ->name('verification.verify');
});

// Public movie routes (basic CRUD only - search handled by GraphQL)
Route::prefix('movies')->group(function () {
    Route::get('/', [MoviesController::class, 'index']); // List saved movies
    Route::get('/{id}', [MoviesController::class, 'show']); // Get single movie
});

// Protected routes
Route::middleware(['auth:sanctum', \App\Http\Middleware\RequirePasswordChange::class])->group(function () {
    
    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        Route::post('email/verification-notification', [AuthController::class, 'resendVerification']);
        Route::post('change-password', [AuthController::class, 'changePassword']);
        Route::put('profile', [AuthController::class, 'updateProfile']);
    });

    // User profile routes
    Route::prefix('user')->group(function () {
        Route::get('/', function (Request $request) {
            return response()->json(['user' => $request->user()]);
        });
    });

    // Movie management routes (authenticated users only)
    Route::prefix('movies')->group(function () {
        Route::post('/', [MoviesController::class, 'store']); // Create movie
        Route::put('/{id}', [MoviesController::class, 'update']); // Update movie
        Route::delete('/{id}', [MoviesController::class, 'destroy']); // Delete movie
    });

    // Favorites routes
    Route::prefix('favorites')->group(function () {
        Route::get('/', [FavoritesController::class, 'index']); // Get user's favorites
        Route::post('/', [FavoritesController::class, 'store']); // Add to favorites
        Route::delete('/{movieId}', [FavoritesController::class, 'destroy']); // Remove from favorites
        Route::post('toggle', [FavoritesController::class, 'toggle']); // Toggle favorite
        Route::get('check/{movieId}', [FavoritesController::class, 'check']); // Check favorite status
        Route::get('stats', [FavoritesController::class, 'stats']); // Get user favorite stats
    });

});

// Health check route
Route::get('health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now(),
        'version' => '2.0.0',
        'features' => ['GraphQL', 'Database Search', 'Laravel Sanctum'],
        'graphql_endpoint' => url('/graphql'),
    ]);
});

// Fallback route for API
Route::fallback(function () {
    return response()->json([
        'message' => 'API endpoint not found. Check your URL and method.',
    ], 404);
});