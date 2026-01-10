<?php

namespace App\GraphQL\Queries;

use App\Models\Movie;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class SearchMovies
{
    /**
     * Return a value for the field.
     */
    public function __invoke($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $query = $args['query'];
        $page = $args['page'] ?? 1;
        $limit = $args['limit'] ?? 12;
        $genre = $args['genre'] ?? null;
        $year = $args['year'] ?? null;
        $rating = $args['rating'] ?? null;
        $sortBy = $args['sortBy'] ?? 'relevance';
        $sortOrder = $args['sortOrder'] ?? 'desc';

        // Only search local database - return items with ID only
        $localResults = $this->searchLocalMovies($query, $genre, $year, $rating, $sortBy, $sortOrder);
        
        // Apply additional filtering
        if ($genre) {
            $localResults = $localResults->filter(function ($movie) use ($genre) {
                return stripos($movie['genre'] ?? '', $genre) !== false;
            });
        }

        if ($year) {
            $localResults = $localResults->filter(function ($movie) use ($year) {
                return ($movie['year'] ?? '') === $year;
            });
        }

        if ($rating) {
            $localResults = $localResults->filter(function ($movie) use ($rating) {
                return ($movie['rated'] ?? '') === $rating;
            });
        }

        // Apply sorting
        $sortedResults = $this->applySorting($localResults, $sortBy, $sortOrder);

        // Apply pagination
        $total = $sortedResults->count();
        $totalPages = max(1, ceil($total / $limit));
        $offset = ($page - 1) * $limit;
        $paginatedResults = $sortedResults->slice($offset, $limit)->values();

        return [
            'data' => $paginatedResults->toArray(),
            'pagination' => [
                'current_page' => $page,
                'last_page' => $totalPages,
                'per_page' => $limit,
                'total' => $total,
                'has_more_pages' => $page < $totalPages,
            ],
            'filters' => [
                'query' => $query,
                'genre' => $genre,
                'year' => $year,
                'rating' => $rating,
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder,
            ],
        ];
    }

    /**
     * Search movies in local database
     */
    private function searchLocalMovies(string $query, ?string $genre = null, ?string $year = null, ?string $rating = null, string $sortBy = 'relevance', string $sortOrder = 'desc')
    {
        $moviesQuery = Movie::withCount('favoritedBy');

        // Only add search conditions if query is meaningful
        if (!empty($query) && $query !== '*' && strlen(trim($query)) > 0) {
            $searchTerms = preg_split('/\s+/', strtolower(trim($query)));
            
            $moviesQuery->where(function ($queryBuilder) use ($searchTerms) {
                // OR logic: any term can match any field
                foreach ($searchTerms as $term) {
                    $queryBuilder->orWhere(function ($termQuery) use ($term) {
                        $termQuery->where('title', 'like', "%{$term}%")
                                 ->orWhere('genre', 'like', "%{$term}%")
                                 ->orWhere('director', 'like', "%{$term}%")
                                 ->orWhere('plot', 'like', "%{$term}%")
                                 ->orWhere('language', 'like', "%{$term}%")
                                 ->orWhere('country', 'like', "%{$term}%")
                                 ->orWhere('year', 'like', "%{$term}%")
                                 ->orWhere('actors', 'like', "%{$term}%");
                    });
                }
            });
        }
        // If query is empty or '*', return all movies

        // Apply additional filters
        if ($genre) {
            $moviesQuery->where('genre', 'like', "%{$genre}%");
        }

        if ($year) {
            $moviesQuery->where('year', $year);
        }

        if ($rating) {
            $moviesQuery->where('rated', $rating);
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
            case 'relevance':
            default:
                // For relevance, we'll use the default order (created_at desc)
                $moviesQuery->orderBy('created_at', 'desc');
                break;
        }

        return $moviesQuery->limit(20)->get()->map(function ($movie) {
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
    }

    /**
     * Apply sorting to collection
     */
    private function applySorting($collection, string $sortBy, string $sortOrder)
    {
        $isDesc = strtolower($sortOrder) === 'desc';

        switch ($sortBy) {
            case 'title':
                return $isDesc ? $collection->sortByDesc('title') : $collection->sortBy('title');
            case 'year':
                return $isDesc ? $collection->sortByDesc('year') : $collection->sortBy('year');
            case 'rating':
                return $isDesc ? 
                    $collection->sortByDesc(function ($movie) {
                        return floatval($movie['imdbRating'] ?? 0);
                    }) :
                    $collection->sortBy(function ($movie) {
                        return floatval($movie['imdbRating'] ?? 0);
                    });
            case 'favorites':
                return $isDesc ? $collection->sortByDesc('favorited_by_count') : $collection->sortBy('favorited_by_count');
            case 'relevance':
            default:
                return $collection; // Keep original order for relevance
        }
    }
}