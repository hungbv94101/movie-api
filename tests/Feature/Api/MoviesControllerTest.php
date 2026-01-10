<?php

namespace Tests\Feature\Api;

use App\Models\Movie;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MoviesControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        // Create some test movies
        Movie::factory(5)->create();
    }

    public function test_can_list_movies(): void
    {
        $response = $this->getJson('/api/movies');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'year',
                        'genre',
                        'director',
                        'favorited_by_count'
                    ]
                ],
                'current_page',
                'per_page',
                'total'
            ]);
    }

    public function test_can_show_single_movie(): void
    {
        $movie = Movie::first();

        $response = $this->getJson("/api/movies/{$movie->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'title',
                'year',
                'genre',
                'director',
                'actors',
                'plot',
                'poster',
                'runtime',
                'imdb_rating',
                'imdb_id',
                'language',
                'country',
                'favorited_by_count'
            ])
            ->assertJson([
                'id' => $movie->id,
                'title' => $movie->title,
            ]);
    }

    public function test_returns_404_for_non_existent_movie(): void
    {
        $response = $this->getJson('/api/movies/999999');

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Movie not found'
            ]);
    }

    public function test_authenticated_user_can_create_movie(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $movieData = [
            'title' => 'Test Movie',
            'year' => 2023,
            'genre' => 'Action',
            'director' => 'Test Director',
            'actors' => 'Test Actor 1, Test Actor 2',
            'plot' => 'This is a test movie plot',
            'poster' => 'https://example.com/poster.jpg',
            'runtime' => '120 min',
            'imdb_rating' => 8.5,
            'imdb_id' => 'tt1234567',
            'language' => 'English',
            'country' => 'USA'
        ];

        $response = $this->postJson('/api/movies', $movieData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'movie' => [
                    'id',
                    'title',
                    'year',
                    'genre',
                    'director'
                ]
            ]);

        $this->assertDatabaseHas('movies', [
            'title' => 'Test Movie',
            'year' => 2023,
            'imdb_id' => 'tt1234567'
        ]);
    }

    public function test_unauthenticated_user_cannot_create_movie(): void
    {
        $movieData = [
            'title' => 'Test Movie',
            'year' => 2023,
        ];

        $response = $this->postJson('/api/movies', $movieData);

        $response->assertStatus(401);
    }

    public function test_create_movie_validation_fails_with_invalid_data(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $invalidData = [
            'title' => '', // Required field empty
            'year' => 'invalid_year', // Should be integer
            'imdb_rating' => 15, // Should be between 0-10
        ];

        $response = $this->postJson('/api/movies', $invalidData);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'title',
                    'year',
                    'imdb_rating'
                ]
            ]);
    }

    public function test_authenticated_user_can_update_movie(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $movie = Movie::first();
        $updateData = [
            'title' => 'Updated Movie Title',
            'year' => 2024,
            'genre' => 'Updated Genre'
        ];

        $response = $this->putJson("/api/movies/{$movie->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'movie' => [
                    'id',
                    'title',
                    'year',
                    'genre'
                ]
            ]);

        $this->assertDatabaseHas('movies', [
            'id' => $movie->id,
            'title' => 'Updated Movie Title',
            'year' => 2024
        ]);
    }

    public function test_authenticated_user_can_delete_movie(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $movie = Movie::first();

        $response = $this->deleteJson("/api/movies/{$movie->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Movie deleted successfully'
            ]);

        $this->assertDatabaseMissing('movies', [
            'id' => $movie->id
        ]);
    }

    public function test_cannot_delete_non_existent_movie(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/movies/999999');

        $response->assertStatus(404);
    }

    public function test_unauthenticated_user_cannot_update_movie(): void
    {
        $movie = Movie::first();
        $updateData = ['title' => 'Updated Title'];

        $response = $this->putJson("/api/movies/{$movie->id}", $updateData);

        $response->assertStatus(401);
    }

    public function test_unauthenticated_user_cannot_delete_movie(): void
    {
        $movie = Movie::first();

        $response = $this->deleteJson("/api/movies/{$movie->id}");

        $response->assertStatus(401);
    }

    public function test_create_movie_prevents_duplicate_imdb_id(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $existingMovie = Movie::first();
        
        $movieData = [
            'title' => 'Different Movie',
            'year' => 2023,
            'imdb_id' => $existingMovie->imdb_id // Use same IMDB ID
        ];

        $response = $this->postJson('/api/movies', $movieData);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'imdb_id'
                ]
            ]);
    }
}