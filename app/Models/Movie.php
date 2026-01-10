<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Movie extends Model
{
    use HasFactory;
    protected $fillable = [
        'title',
        'imdb_id',
        'year',
        'rated',
        'released',
        'runtime',
        'genre',
        'director',
        'writer',
        'actors',
        'plot',
        'language',
        'country',
        'awards',
        'poster',
        'metascore',
        'imdb_rating',
        'imdb_votes',
        'type',
        'dvd',
        'box_office',
        'production',
        'website',
        'response',
        'ratings',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'ratings' => 'array',
        'year' => 'integer',
        'imdb_rating' => 'decimal:1',
    ];

    /**
     * Users who favorited this movie
     */
    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites');
    }

    /**
     * Check if movie is favorited by a specific user
     */
    public function isFavoritedBy($userId): bool
    {
        return $this->favoritedBy()->where('user_id', $userId)->exists();
    }
}
