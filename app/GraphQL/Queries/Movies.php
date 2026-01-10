<?php

namespace App\GraphQL\Queries;

use App\Models\Movie;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class Movies
{
    /**
     * Return a value for the field.
     */
    public function __invoke($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $page = $args['page'] ?? 1;
        $limit = $args['limit'] ?? 12;
        $genre = $args['genre'] ?? null;
        $year = $args['year'] ?? null;
        $sortBy = $args['sortBy'] ?? 'created_at';
        $sortOrder = $args['sortOrder'] ?? 'desc';

        $moviesQuery = Movie::withCount('favoritedBy');

        // Apply filters
        if ($genre) {
            $moviesQuery->where('genre', 'like', "%{$genre}%");
        }

        if ($year) {
            $moviesQuery->where('year', $year);
        }

        // Apply sorting
        switch ($sortBy) {
            case 'title':
                $moviesQuery->orderBy('title', $sortOrder);
                break;
            case 'year':
                $moviesQuery->orderBy('year', $sortOrder);
                break;
            case 'rating':
                $moviesQuery->orderBy('imdbRating', $sortOrder);
                break;
            case 'favorites':
                $moviesQuery->orderBy('favorited_by_count', $sortOrder);
                break;
            case 'created_at':
            default:
                $moviesQuery->orderBy('created_at', $sortOrder);
                break;
        }

        // Get paginated results
        $paginatedMovies = $moviesQuery->paginate(perPage: $limit, page: $page);

        // Transform movies to include proper format
        $movies = $paginatedMovies->items();
        $transformedMovies = collect($movies)->map(function ($movie) {
            // Properly handle ratings JSON field
            $ratings = [];
            if ($movie->ratings) {
                $decodedRatings = is_string($movie->ratings) ? json_decode($movie->ratings, true) : $movie->ratings;
                if (is_array($decodedRatings)) {
                    $ratings = array_filter($decodedRatings, function($rating) {
                        return isset($rating['Source']) && isset($rating['Value']) && 
                               !empty($rating['Source']) && !empty($rating['Value']);
                    });
                }
            }
            
            return array_merge($movie->toArray(), [
                'is_favorited' => false, // TODO: Check if user has favorited this movie
                'ratings' => $ratings,
            ]);
        });

        return [
            'data' => $transformedMovies->toArray(),
            'pagination' => [
                'current_page' => $paginatedMovies->currentPage(),
                'last_page' => $paginatedMovies->lastPage(),
                'per_page' => $paginatedMovies->perPage(),
                'total' => $paginatedMovies->total(),
                'has_more_pages' => $paginatedMovies->hasMorePages(),
            ],
            'filters' => [
                'query' => null,
                'genre' => $genre,
                'year' => $year,
                'rating' => null,
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder,
            ],
        ];
    }
}