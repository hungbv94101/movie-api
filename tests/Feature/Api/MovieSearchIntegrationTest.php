<?php

namespace Tests\Feature\Api;

use App\Models\Movie;
use App\Models\User;
use App\Models\Favorite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MovieSearchIntegrationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create sample movies for testing search functionality
        Movie::factory()->create([
            'title' => 'The Avengers',
            'year' => 2012,
            'genre' => 'Action, Adventure, Sci-Fi',
            'director' => 'Joss Whedon',
            'actors' => 'Robert Downey Jr., Chris Evans, Mark Ruffalo',
            'imdb_rating' => 8.0
        ]);

        Movie::factory()->create([
            'title' => 'Iron Man',
            'year' => 2008,
            'genre' => 'Action, Adventure, Sci-Fi',
            'director' => 'Jon Favreau',
            'actors' => 'Robert Downey Jr., Terrence Howard, Jeff Bridges',
            'imdb_rating' => 7.9
        ]);

        Movie::factory()->create([
            'title' => 'The Dark Knight',
            'year' => 2008,
            'genre' => 'Action, Crime, Drama',
            'director' => 'Christopher Nolan',
            'actors' => 'Christian Bale, Heath Ledger, Aaron Eckhart',
            'imdb_rating' => 9.0
        ]);
    }

    public function test_search_movies_via_rest_api(): void
    {
        // Test basic movie listing through REST API
        $response = $this->getJson('/api/movies');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'year',
                        'genre',
                        'favorited_by_count'
                    ]
                ]
            ]);

        $this->assertCount(3, $response->json('data'));
    }

    public function test_search_movies_via_graphql(): void
    {
        $query = '
            query {
                searchMovies(query: "Iron Man", limit: 5) {
                    movies {
                        id
                        title
                        year
                        genre
                        director
                        imdbRating
                    }
                    totalCount
                }
            }
        ';

        $response = $this->postJson('/graphql', ['query' => $query]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'searchMovies' => [
                        'movies' => [
                            '*' => [
                                'id',
                                'title',
                                'year',
                                'genre',
                                'director',
                                'imdbRating'
                            ]
                        ],
                        'totalCount'
                    ]
                ]
            ]);

        $movies = $response->json('data.searchMovies.movies');
        $this->assertNotEmpty($movies);
        
        foreach ($movies as $movie) {
            $this->assertStringContainsString('Iron Man', $movie['title']);
        }
    }

    public function test_favorites_integration_with_search(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $movie = Movie::where('title', 'The Dark Knight')->first();

        // Add movie to favorites
        $this->postJson('/api/favorites', ['movie_id' => $movie->id])
            ->assertStatus(201);

        // Check favorites list
        $favoritesResponse = $this->getJson('/api/favorites');
        $favoritesResponse->assertStatus(200);

        $favorites = $favoritesResponse->json('data.data');
        $this->assertCount(1, $favorites);
        $this->assertEquals($movie->id, $favorites[0]['id']);

        // Check that movie shows favorite count
        $movieResponse = $this->getJson("/api/movies/{$movie->id}");
        $movieResponse->assertStatus(200);
        
        $movieData = $movieResponse->json();
        $this->assertEquals(1, $movieData['favorited_by_count']);
    }

    public function test_search_with_favorites_count(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $movie = Movie::where('title', 'The Avengers')->first();

        // Two users add the same movie to favorites
        Favorite::create(['user_id' => $user1->id, 'movie_id' => $movie->id]);
        Favorite::create(['user_id' => $user2->id, 'movie_id' => $movie->id]);

        // Search via GraphQL should show favorites count
        $query = '
            query {
                searchMovies(query: "Avengers", limit: 1) {
                    movies {
                        id
                        title
                        favoritedByCount
                    }
                }
            }
        ';

        $response = $this->postJson('/graphql', ['query' => $query]);
        $response->assertStatus(200);

        $movies = $response->json('data.searchMovies.movies');
        $this->assertNotEmpty($movies);
        $this->assertEquals(2, $movies[0]['favoritedByCount']);
    }

    public function test_search_by_director(): void
    {
        $query = '
            query {
                searchMovies(query: "Christopher Nolan") {
                    movies {
                        id
                        title
                        director
                    }
                    totalCount
                }
            }
        ';

        $response = $this->postJson('/graphql', ['query' => $query]);
        $response->assertStatus(200);

        $movies = $response->json('data.searchMovies.movies');
        $this->assertNotEmpty($movies);
        
        foreach ($movies as $movie) {
            $this->assertStringContainsString('Christopher Nolan', $movie['director']);
        }
    }

    public function test_search_by_actor(): void
    {
        $query = '
            query {
                searchMovies(query: "Robert Downey Jr") {
                    movies {
                        id
                        title
                        actors
                    }
                }
            }
        ';

        $response = $this->postJson('/graphql', ['query' => $query]);
        $response->assertStatus(200);

        $movies = $response->json('data.searchMovies.movies');
        $this->assertNotEmpty($movies);
        
        foreach ($movies as $movie) {
            $this->assertStringContainsString('Robert Downey Jr.', $movie['actors']);
        }
    }

    public function test_pagination_works_correctly(): void
    {
        // Add more movies to test pagination
        Movie::factory(10)->create();

        $query = '
            query {
                searchMovies(query: "", page: 1, limit: 5) {
                    movies {
                        id
                        title
                    }
                    totalCount
                    currentPage
                    hasMorePages
                }
            }
        ';

        $response = $this->postJson('/graphql', ['query' => $query]);
        $response->assertStatus(200);

        $searchResult = $response->json('data.searchMovies');
        
        $this->assertLessThanOrEqual(5, count($searchResult['movies']));
        $this->assertEquals(1, $searchResult['currentPage']);
        $this->assertGreaterThan(5, $searchResult['totalCount']);
        $this->assertTrue($searchResult['hasMorePages']);
    }

    public function test_empty_search_returns_all_movies(): void
    {
        $query = '
            query {
                searchMovies(query: "") {
                    movies {
                        id
                        title
                    }
                    totalCount
                }
            }
        ';

        $response = $this->postJson('/graphql', ['query' => $query]);
        $response->assertStatus(200);

        $searchResult = $response->json('data.searchMovies');
        
        // Should return all movies when query is empty
        $this->assertEquals(3, $searchResult['totalCount']);
        $this->assertCount(3, $searchResult['movies']);
    }
}