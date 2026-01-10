<?php

namespace Tests\Feature\GraphQL;

use App\Models\Movie;
use App\Models\User;
use App\Models\Favorite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GraphQLQueriesTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test movies with different attributes for searching
        Movie::factory()->create([
            'title' => 'The Dark Knight',
            'year' => 2008,
            'genre' => 'Action, Crime, Drama',
            'director' => 'Christopher Nolan',
            'actors' => 'Christian Bale, Heath Ledger',
            'imdb_rating' => 9.0,
            'language' => 'English',
            'country' => 'USA'
        ]);

        Movie::factory()->create([
            'title' => 'Inception',
            'year' => 2010,
            'genre' => 'Action, Sci-Fi, Thriller',
            'director' => 'Christopher Nolan',
            'actors' => 'Leonardo DiCaprio, Marion Cotillard',
            'imdb_rating' => 8.8,
            'language' => 'English',
            'country' => 'USA'
        ]);

        Movie::factory()->create([
            'title' => 'The Matrix',
            'year' => 1999,
            'genre' => 'Action, Sci-Fi',
            'director' => 'The Wachowskis',
            'actors' => 'Keanu Reeves, Laurence Fishburne',
            'imdb_rating' => 8.7,
            'language' => 'English',
            'country' => 'USA'
        ]);

        Movie::factory()->create([
            'title' => 'Spirited Away',
            'year' => 2001,
            'genre' => 'Animation, Family, Supernatural',
            'director' => 'Hayao Miyazaki',
            'actors' => 'Rumi Hiiragi, Miyu Irino',
            'imdb_rating' => 9.3,
            'language' => 'Japanese',
            'country' => 'Japan'
        ]);
    }

    public function test_can_search_movies_by_title(): void
    {
        $query = '
            query SearchMovies($query: String!, $page: Int, $limit: Int) {
                searchMovies(query: $query, page: $page, limit: $limit) {
                    movies {
                        id
                        title
                        year
                        genre
                        director
                    }
                    totalCount
                    currentPage
                    hasMorePages
                }
            }
        ';

        $variables = [
            'query' => 'Dark Knight',
            'page' => 1,
            'limit' => 10
        ];

        $response = $this->postJson('/graphql', [
            'query' => $query,
            'variables' => $variables
        ]);

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
                                'director'
                            ]
                        ],
                        'totalCount',
                        'currentPage',
                        'hasMorePages'
                    ]
                ]
            ]);

        $searchResults = $response->json('data.searchMovies.movies');
        $this->assertNotEmpty($searchResults);
        $this->assertStringContainsString('Dark Knight', $searchResults[0]['title']);
    }

    public function test_can_search_movies_with_genre_filter(): void
    {
        $query = '
            query SearchMovies($query: String, $genre: String, $page: Int, $limit: Int) {
                searchMovies(query: $query, genre: $genre, page: $page, limit: $limit) {
                    movies {
                        id
                        title
                        genre
                    }
                    totalCount
                }
            }
        ';

        $variables = [
            'query' => '',
            'genre' => 'Sci-Fi',
            'page' => 1,
            'limit' => 10
        ];

        $response = $this->postJson('/graphql', [
            'query' => $query,
            'variables' => $variables
        ]);

        $response->assertStatus(200);

        $searchResults = $response->json('data.searchMovies.movies');
        
        foreach ($searchResults as $movie) {
            $this->assertStringContainsString('Sci-Fi', $movie['genre']);
        }
    }

    public function test_can_search_movies_with_year_filter(): void
    {
        $query = '
            query SearchMovies($query: String, $year: String, $page: Int, $limit: Int) {
                searchMovies(query: $query, year: $year, page: $page, limit: $limit) {
                    movies {
                        id
                        title
                        year
                    }
                    totalCount
                }
            }
        ';

        $variables = [
            'query' => '',
            'year' => '2010',
            'page' => 1,
            'limit' => 10
        ];

        $response = $this->postJson('/graphql', [
            'query' => $query,
            'variables' => $variables
        ]);

        $response->assertStatus(200);

        $searchResults = $response->json('data.searchMovies.movies');
        
        if (!empty($searchResults)) {
            foreach ($searchResults as $movie) {
                $this->assertEquals(2010, $movie['year']);
            }
        }
    }

    public function test_can_search_movies_with_sorting(): void
    {
        $query = '
            query SearchMovies($query: String, $sortBy: String, $sortOrder: String) {
                searchMovies(query: $query, sortBy: $sortBy, sortOrder: $sortOrder) {
                    movies {
                        id
                        title
                        year
                        imdbRating
                    }
                    totalCount
                }
            }
        ';

        $variables = [
            'query' => '',
            'sortBy' => 'year',
            'sortOrder' => 'desc'
        ];

        $response = $this->postJson('/graphql', [
            'query' => $query,
            'variables' => $variables
        ]);

        $response->assertStatus(200);

        $searchResults = $response->json('data.searchMovies.movies');
        
        if (count($searchResults) > 1) {
            // Check that results are sorted by year in descending order
            $firstYear = $searchResults[0]['year'];
            $secondYear = $searchResults[1]['year'];
            $this->assertGreaterThanOrEqual($secondYear, $firstYear);
        }
    }

    public function test_can_get_paginated_movies(): void
    {
        $query = '
            query GetMovies($page: Int, $limit: Int) {
                movies(page: $page, limit: $limit) {
                    data {
                        id
                        title
                        year
                    }
                    paginatorInfo {
                        currentPage
                        hasMorePages
                        total
                        perPage
                    }
                }
            }
        ';

        $variables = [
            'page' => 1,
            'limit' => 2
        ];

        $response = $this->postJson('/graphql', [
            'query' => $query,
            'variables' => $variables
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'movies' => [
                        'data' => [
                            '*' => [
                                'id',
                                'title',
                                'year'
                            ]
                        ],
                        'paginatorInfo' => [
                            'currentPage',
                            'hasMorePages',
                            'total',
                            'perPage'
                        ]
                    ]
                ]
            ]);

        $movies = $response->json('data.movies.data');
        $paginatorInfo = $response->json('data.movies.paginatorInfo');

        $this->assertLessThanOrEqual(2, count($movies));
        $this->assertEquals(1, $paginatorInfo['currentPage']);
        $this->assertEquals(2, $paginatorInfo['perPage']);
    }

    public function test_can_get_single_user(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com'
        ]);

        $query = '
            query GetUser($id: ID!) {
                user(id: $id) {
                    id
                    name
                    email
                    createdAt
                    updatedAt
                }
            }
        ';

        $variables = [
            'id' => $user->id
        ];

        $response = $this->postJson('/graphql', [
            'query' => $query,
            'variables' => $variables
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'createdAt',
                        'updatedAt'
                    ]
                ]
            ]);

        $userData = $response->json('data.user');
        $this->assertEquals($user->id, $userData['id']);
        $this->assertEquals('Test User', $userData['name']);
        $this->assertEquals('test@example.com', $userData['email']);
    }

    public function test_can_get_user_by_email(): void
    {
        $user = User::factory()->create([
            'name' => 'Email Test User',
            'email' => 'email-test@example.com'
        ]);

        $query = '
            query GetUser($email: String!) {
                user(email: $email) {
                    id
                    name
                    email
                }
            }
        ';

        $variables = [
            'email' => $user->email
        ];

        $response = $this->postJson('/graphql', [
            'query' => $query,
            'variables' => $variables
        ]);

        $response->assertStatus(200);

        $userData = $response->json('data.user');
        $this->assertEquals($user->id, $userData['id']);
        $this->assertEquals('Email Test User', $userData['name']);
    }

    public function test_can_search_users_by_name(): void
    {
        User::factory()->create(['name' => 'John Doe']);
        User::factory()->create(['name' => 'Jane Smith']);
        User::factory()->create(['name' => 'John Johnson']);

        $query = '
            query SearchUsers($name: String) {
                users(name: $name) {
                    data {
                        id
                        name
                        email
                    }
                }
            }
        ';

        $variables = [
            'name' => 'John%'  // SQL LIKE pattern
        ];

        $response = $this->postJson('/graphql', [
            'query' => $query,
            'variables' => $variables
        ]);

        $response->assertStatus(200);

        $users = $response->json('data.users.data');
        
        foreach ($users as $user) {
            $this->assertStringContainsString('John', $user['name']);
        }
    }

    public function test_search_returns_empty_result_for_no_matches(): void
    {
        $query = '
            query SearchMovies($query: String!) {
                searchMovies(query: $query) {
                    movies {
                        id
                        title
                    }
                    totalCount
                }
            }
        ';

        $variables = [
            'query' => 'Non Existent Movie Title 123456789'
        ];

        $response = $this->postJson('/graphql', [
            'query' => $query,
            'variables' => $variables
        ]);

        $response->assertStatus(200);

        $searchResults = $response->json('data.searchMovies');
        $this->assertEquals(0, $searchResults['totalCount']);
        $this->assertEmpty($searchResults['movies']);
    }

    public function test_graphql_handles_validation_errors(): void
    {
        $query = '
            query SearchMovies($query: String!, $page: Int, $limit: Int) {
                searchMovies(query: $query, page: $page, limit: $limit) {
                    movies {
                        id
                        title
                    }
                }
            }
        ';

        $variables = [
            'query' => '', // Empty query should fail validation
            'page' => 0,   // Invalid page number
            'limit' => 100 // Exceeds max limit
        ];

        $response = $this->postJson('/graphql', [
            'query' => $query,
            'variables' => $variables
        ]);

        // GraphQL should return validation errors
        $response->assertStatus(200); // GraphQL returns 200 with errors in response
        $this->assertArrayHasKey('errors', $response->json());
    }

    public function test_can_handle_complex_search_with_multiple_filters(): void
    {
        $query = '
            query ComplexSearch(
                $query: String,
                $genre: String,
                $year: String,
                $sortBy: String,
                $sortOrder: String,
                $page: Int,
                $limit: Int
            ) {
                searchMovies(
                    query: $query,
                    genre: $genre,
                    year: $year,
                    sortBy: $sortBy,
                    sortOrder: $sortOrder,
                    page: $page,
                    limit: $limit
                ) {
                    movies {
                        id
                        title
                        year
                        genre
                        director
                        imdbRating
                    }
                    totalCount
                    currentPage
                    hasMorePages
                }
            }
        ';

        $variables = [
            'query' => 'Christopher',
            'genre' => 'Action',
            'sortBy' => 'year',
            'sortOrder' => 'desc',
            'page' => 1,
            'limit' => 5
        ];

        $response = $this->postJson('/graphql', [
            'query' => $query,
            'variables' => $variables
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'searchMovies' => [
                        'movies',
                        'totalCount',
                        'currentPage',
                        'hasMorePages'
                    ]
                ]
            ]);
    }
}