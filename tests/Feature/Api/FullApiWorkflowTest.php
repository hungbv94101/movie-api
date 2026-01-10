<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Movie;
use App\Models\Favorite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FullApiWorkflowTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed some initial movies
        Movie::factory(5)->create();
    }

    public function test_complete_user_workflow(): void
    {
        // 1. User Registration
        $userData = [
            'name' => 'Test User',
            'email' => 'testuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $registerResponse = $this->postJson('/api/auth/register', $userData);
        $registerResponse->assertStatus(201)
            ->assertJsonStructure(['user', 'token']);

        $token = $registerResponse->json('token');
        $user = User::where('email', 'testuser@example.com')->first();

        // 2. User Login
        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'testuser@example.com',
            'password' => 'password123'
        ]);
        $loginResponse->assertStatus(200);

        // 3. Get User Profile
        $profileResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/auth/me');
        
        $profileResponse->assertStatus(200)
            ->assertJson(['name' => 'Test User']);

        // 4. Browse Movies
        $moviesResponse = $this->getJson('/api/movies');
        $moviesResponse->assertStatus(200);
        
        $movies = $moviesResponse->json('data');
        $this->assertCount(5, $movies);

        // 5. View Single Movie
        $singleMovieResponse = $this->getJson('/api/movies/' . $movies[0]['id']);
        $singleMovieResponse->assertStatus(200)
            ->assertJson(['id' => $movies[0]['id']]);

        // 6. Add Movie to Favorites (authenticated)
        $favoriteResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/favorites', [
            'movie_id' => $movies[0]['id']
        ]);
        
        $favoriteResponse->assertStatus(201);

        // 7. View User's Favorites
        $favoritesListResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/favorites');
        
        $favoritesListResponse->assertStatus(200);
        $favorites = $favoritesListResponse->json('data.data');
        $this->assertCount(1, $favorites);
        $this->assertEquals($movies[0]['id'], $favorites[0]['id']);

        // 8. Search Movies via GraphQL
        $searchQuery = '
            query {
                searchMovies(query: "' . substr($movies[1]['title'], 0, 5) . '") {
                    movies {
                        id
                        title
                    }
                    totalCount
                }
            }
        ';

        $searchResponse = $this->postJson('/graphql', ['query' => $searchQuery]);
        $searchResponse->assertStatus(200);
        
        $searchResults = $searchResponse->json('data.searchMovies.movies');
        $this->assertNotEmpty($searchResults);

        // 9. Toggle Favorite Status
        $toggleResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/favorites/toggle', [
            'movie_id' => $movies[1]['id']
        ]);
        
        $toggleResponse->assertStatus(200)
            ->assertJson(['action' => 'added']);

        // 10. Remove from Favorites
        $removeResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson('/api/favorites/' . $movies[0]['id']);
        
        $removeResponse->assertStatus(200);

        // 11. Verify Updated Favorites
        $finalFavoritesResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/favorites');
        
        $finalFavorites = $finalFavoritesResponse->json('data.data');
        $this->assertCount(1, $finalFavorites);
        $this->assertEquals($movies[1]['id'], $finalFavorites[0]['id']);

        // 12. User Logout
        $logoutResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/auth/logout');
        
        $logoutResponse->assertStatus(200);

        // 13. Verify Token is Invalid After Logout
        $invalidTokenResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/auth/me');
        
        $invalidTokenResponse->assertStatus(401);
    }

    public function test_unauthorized_access_protection(): void
    {
        $movie = Movie::first();

        // Test protected endpoints without authentication
        $protectedEndpoints = [
            ['method' => 'GET', 'url' => '/api/auth/me'],
            ['method' => 'POST', 'url' => '/api/auth/logout'],
            ['method' => 'GET', 'url' => '/api/favorites'],
            ['method' => 'POST', 'url' => '/api/favorites', 'data' => ['movie_id' => $movie->id]],
            ['method' => 'DELETE', 'url' => '/api/favorites/' . $movie->id],
            ['method' => 'POST', 'url' => '/api/favorites/toggle', 'data' => ['movie_id' => $movie->id]],
            ['method' => 'POST', 'url' => '/api/movies', 'data' => ['title' => 'Test', 'year' => 2023]],
            ['method' => 'PUT', 'url' => '/api/movies/' . $movie->id, 'data' => ['title' => 'Updated']],
            ['method' => 'DELETE', 'url' => '/api/movies/' . $movie->id],
        ];

        foreach ($protectedEndpoints as $endpoint) {
            $response = match($endpoint['method']) {
                'GET' => $this->getJson($endpoint['url']),
                'POST' => $this->postJson($endpoint['url'], $endpoint['data'] ?? []),
                'PUT' => $this->putJson($endpoint['url'], $endpoint['data'] ?? []),
                'DELETE' => $this->deleteJson($endpoint['url']),
                default => null
            };

            $response->assertStatus(401, "Endpoint {$endpoint['method']} {$endpoint['url']} should require authentication");
        }
    }

    public function test_api_health_check(): void
    {
        $response = $this->getJson('/api/health');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'timestamp',
                'version',
                'features',
                'graphql_endpoint'
            ])
            ->assertJson([
                'status' => 'ok',
                'version' => '2.0.0'
            ]);

        $features = $response->json('features');
        $this->assertContains('GraphQL', $features);
        $this->assertContains('Database Search', $features);
        $this->assertContains('Laravel Sanctum', $features);
    }

    public function test_api_fallback_route(): void
    {
        $response = $this->getJson('/api/nonexistent-endpoint');
        
        $response->assertStatus(404)
            ->assertJson([
                'message' => 'API endpoint not found. Check your URL and method.'
            ]);
    }

    public function test_email_verification_workflow(): void
    {
        $user = User::factory()->create(['email_verified_at' => null]);
        Sanctum::actingAs($user);

        // Send verification email
        $response = $this->postJson('/api/auth/email/verification-notification');
        $response->assertStatus(200)
            ->assertJson(['message' => 'Verification email sent']);

        // Test verification endpoint (basic structure test)
        // Note: In real tests, you'd mock the signed URL generation
        $verifyResponse = $this->getJson('/api/auth/email/verify/999/invalid-hash');
        $verifyResponse->assertStatus(403); // Should fail with invalid signature
    }

    public function test_password_reset_workflow(): void
    {
        $user = User::factory()->create();

        // Request password reset
        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => $user->email
        ]);
        
        $response->assertStatus(200)
            ->assertJson(['message' => 'Password reset link sent to your email']);

        // Test reset with invalid token (should fail)
        $resetResponse = $this->postJson('/api/auth/reset-password', [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123'
        ]);
        
        $resetResponse->assertStatus(400);
    }

    public function test_crud_operations_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Create movie
        $movieData = [
            'title' => 'Test Movie Creation',
            'year' => 2023,
            'genre' => 'Action',
            'director' => 'Test Director'
        ];

        $createResponse = $this->postJson('/api/movies', $movieData);
        $createResponse->assertStatus(201);
        
        $createdMovie = $createResponse->json('movie');
        $this->assertEquals('Test Movie Creation', $createdMovie['title']);

        // Update movie
        $updateResponse = $this->putJson('/api/movies/' . $createdMovie['id'], [
            'title' => 'Updated Movie Title',
            'year' => 2024
        ]);
        
        $updateResponse->assertStatus(200);
        $updatedMovie = $updateResponse->json('movie');
        $this->assertEquals('Updated Movie Title', $updatedMovie['title']);

        // Delete movie
        $deleteResponse = $this->deleteJson('/api/movies/' . $createdMovie['id']);
        $deleteResponse->assertStatus(200);

        // Verify deletion
        $this->assertDatabaseMissing('movies', ['id' => $createdMovie['id']]);
    }

    public function test_data_validation_across_endpoints(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Test invalid movie creation
        $invalidMovieData = [
            'title' => '', // Required field empty
            'year' => 'not-a-number',
            'imdb_rating' => 15 // Out of range
        ];

        $response = $this->postJson('/api/movies', $invalidMovieData);
        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors']);

        // Test invalid favorite addition
        $invalidFavoriteResponse = $this->postJson('/api/favorites', [
            'movie_id' => 99999 // Non-existent movie
        ]);
        
        $invalidFavoriteResponse->assertStatus(422);

        // Test invalid registration
        $invalidUserData = [
            'name' => '',
            'email' => 'not-an-email',
            'password' => '123', // Too short
        ];

        $invalidRegisterResponse = $this->postJson('/api/auth/register', $invalidUserData);
        $invalidRegisterResponse->assertStatus(422);
    }
}