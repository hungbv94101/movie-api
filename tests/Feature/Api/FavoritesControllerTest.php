<?php

namespace Tests\Feature\Api;

use App\Models\Favorite;
use App\Models\Movie;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FavoritesControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
        
        // Create some test movies
        Movie::factory(10)->create();
    }

    public function test_authenticated_user_can_get_their_favorites(): void
    {
        Sanctum::actingAs($this->user);

        // Add some movies to favorites
        $movies = Movie::take(3)->get();
        foreach ($movies as $movie) {
            Favorite::create([
                'user_id' => $this->user->id,
                'movie_id' => $movie->id
            ]);
        }

        $response = $this->getJson('/api/favorites');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
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
                ]
            ])
            ->assertJson([
                'success' => true
            ]);

        // Check that we get exactly 3 favorites
        $responseData = $response->json('data.data');
        $this->assertCount(3, $responseData);
    }

    public function test_unauthenticated_user_cannot_get_favorites(): void
    {
        $response = $this->getJson('/api/favorites');

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_add_movie_to_favorites(): void
    {
        Sanctum::actingAs($this->user);
        $movie = Movie::first();

        $response = $this->postJson('/api/favorites', [
            'movie_id' => $movie->id
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'favorite' => [
                    'id',
                    'user_id',
                    'movie_id',
                    'created_at'
                ]
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Movie added to favorites'
            ]);

        $this->assertDatabaseHas('favorites', [
            'user_id' => $this->user->id,
            'movie_id' => $movie->id
        ]);
    }

    public function test_cannot_add_non_existent_movie_to_favorites(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/favorites', [
            'movie_id' => 999999
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'movie_id'
                ]
            ]);
    }

    public function test_cannot_add_same_movie_to_favorites_twice(): void
    {
        Sanctum::actingAs($this->user);
        $movie = Movie::first();

        // Add to favorites first time
        Favorite::create([
            'user_id' => $this->user->id,
            'movie_id' => $movie->id
        ]);

        // Try to add again
        $response = $this->postJson('/api/favorites', [
            'movie_id' => $movie->id
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'message' => 'Movie is already in favorites'
            ]);

        // Should still have only one favorite record
        $this->assertEquals(1, Favorite::where('user_id', $this->user->id)->where('movie_id', $movie->id)->count());
    }

    public function test_authenticated_user_can_remove_movie_from_favorites(): void
    {
        Sanctum::actingAs($this->user);
        $movie = Movie::first();

        // Add to favorites first
        Favorite::create([
            'user_id' => $this->user->id,
            'movie_id' => $movie->id
        ]);

        $response = $this->deleteJson("/api/favorites/{$movie->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Movie removed from favorites'
            ]);

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $this->user->id,
            'movie_id' => $movie->id
        ]);
    }

    public function test_cannot_remove_movie_not_in_favorites(): void
    {
        Sanctum::actingAs($this->user);
        $movie = Movie::first();

        $response = $this->deleteJson("/api/favorites/{$movie->id}");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Movie not found in favorites'
            ]);
    }

    public function test_can_toggle_favorite_status(): void
    {
        Sanctum::actingAs($this->user);
        $movie = Movie::first();

        // Toggle to add (movie not in favorites initially)
        $response = $this->postJson('/api/favorites/toggle', [
            'movie_id' => $movie->id
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'action',
                'favorite'
            ])
            ->assertJson([
                'success' => true,
                'action' => 'added'
            ]);

        $this->assertDatabaseHas('favorites', [
            'user_id' => $this->user->id,
            'movie_id' => $movie->id
        ]);

        // Toggle again to remove
        $response = $this->postJson('/api/favorites/toggle', [
            'movie_id' => $movie->id
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'action' => 'removed'
            ]);

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $this->user->id,
            'movie_id' => $movie->id
        ]);
    }

    public function test_toggle_favorite_validates_movie_id(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/favorites/toggle', [
            'movie_id' => 999999
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'movie_id'
                ]
            ]);
    }

    public function test_user_can_only_see_their_own_favorites(): void
    {
        // Add favorites for both users
        $movie1 = Movie::skip(0)->first();
        $movie2 = Movie::skip(1)->first();
        $movie3 = Movie::skip(2)->first();

        // User 1 favorites
        Favorite::create(['user_id' => $this->user->id, 'movie_id' => $movie1->id]);
        Favorite::create(['user_id' => $this->user->id, 'movie_id' => $movie2->id]);

        // User 2 favorites
        Favorite::create(['user_id' => $this->otherUser->id, 'movie_id' => $movie3->id]);

        // Check user 1 only sees their favorites
        Sanctum::actingAs($this->user);
        $response = $this->getJson('/api/favorites');
        
        $responseData = $response->json('data.data');
        $this->assertCount(2, $responseData);

        // Check user 2 only sees their favorites
        Sanctum::actingAs($this->otherUser);
        $response = $this->getJson('/api/favorites');
        
        $responseData = $response->json('data.data');
        $this->assertCount(1, $responseData);
    }

    public function test_user_cannot_remove_other_users_favorites(): void
    {
        $movie = Movie::first();

        // Other user adds to favorites
        Favorite::create([
            'user_id' => $this->otherUser->id,
            'movie_id' => $movie->id
        ]);

        // Current user tries to remove it
        Sanctum::actingAs($this->user);
        $response = $this->deleteJson("/api/favorites/{$movie->id}");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Movie not found in favorites'
            ]);

        // Other user's favorite should still exist
        $this->assertDatabaseHas('favorites', [
            'user_id' => $this->otherUser->id,
            'movie_id' => $movie->id
        ]);
    }

    public function test_favorites_include_movie_details(): void
    {
        Sanctum::actingAs($this->user);
        $movie = Movie::first();

        Favorite::create([
            'user_id' => $this->user->id,
            'movie_id' => $movie->id
        ]);

        $response = $this->getJson('/api/favorites');

        $responseData = $response->json('data.data.0');
        
        $this->assertEquals($movie->id, $responseData['id']);
        $this->assertEquals($movie->title, $responseData['title']);
        $this->assertEquals($movie->year, $responseData['year']);
        $this->assertArrayHasKey('favorited_by_count', $responseData);
    }

    public function test_unauthenticated_users_cannot_manage_favorites(): void
    {
        $movie = Movie::first();

        // Cannot add to favorites
        $response = $this->postJson('/api/favorites', ['movie_id' => $movie->id]);
        $response->assertStatus(401);

        // Cannot remove from favorites
        $response = $this->deleteJson("/api/favorites/{$movie->id}");
        $response->assertStatus(401);

        // Cannot toggle favorites
        $response = $this->postJson('/api/favorites/toggle', ['movie_id' => $movie->id]);
        $response->assertStatus(401);
    }
}