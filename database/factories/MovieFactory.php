<?php

namespace Database\Factories;

use App\Models\Movie;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Movie>
 */
class MovieFactory extends Factory
{
    protected $model = Movie::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $genres = [
            'Action', 'Adventure', 'Animation', 'Biography', 'Comedy', 'Crime',
            'Documentary', 'Drama', 'Family', 'Fantasy', 'Film-Noir', 'History',
            'Horror', 'Music', 'Musical', 'Mystery', 'Romance', 'Sci-Fi',
            'Sport', 'Thriller', 'War', 'Western'
        ];

        $ratings = ['G', 'PG', 'PG-13', 'R', 'NC-17', 'Not Rated'];
        
        $countries = ['USA', 'UK', 'Canada', 'France', 'Germany', 'Japan', 'Australia'];
        
        $languages = ['English', 'Spanish', 'French', 'German', 'Japanese', 'Italian'];

            $poster = $this->faker->imageUrl(300, 400, 'movie', true);
            return [
                'title' => $this->faker->sentence(rand(2, 4)),
                'year' => $this->faker->numberBetween(1950, 2024),
                'rated' => $this->faker->randomElement($ratings),
                'released' => $this->faker->date('Y-m-d', '2024-12-31'),
                'runtime' => $this->faker->numberBetween(80, 180) . ' min',
                'genre' => implode(', ', $this->faker->randomElements($genres, rand(1, 3))),
                'director' => $this->faker->name(),
                'writer' => $this->faker->name() . ', ' . $this->faker->name(),
                'actors' => $this->faker->name() . ', ' . $this->faker->name() . ', ' . $this->faker->name(),
                'plot' => $this->faker->paragraph(3),
                'language' => $this->faker->randomElement($languages),
                'country' => $this->faker->randomElement($countries),
                'awards' => $this->faker->sentence(),
                'poster' => $poster,
                'metascore' => $this->faker->numberBetween(1, 100),
                'imdb_rating' => $this->faker->randomFloat(1, 1.0, 10.0),
                'imdb_votes' => $this->faker->numberBetween(1000, 2000000),
                'imdb_id' => 'tt' . $this->faker->unique()->numberBetween(1000000, 9999999),
                'type' => 'movie',
                'dvd' => $this->faker->date('Y-m-d'),
                'box_office' => '$' . number_format($this->faker->numberBetween(1000000, 500000000)),
                'production' => $this->faker->company(),
                'website' => $this->faker->url(),
                'response' => 'True',
                'created_at' => now(),
                'updated_at' => now(),
            ];
    }

    /**
     * Configure the model factory to use array for genre field.
     */
    public function configure()
    {
        return $this->afterMaking(function (Movie $movie) {
            if (is_array($movie->genre)) {
                $movie->genre = implode(', ', $movie->genre);
            }
        });
    }

    /**
     * Indicate that the movie is highly rated.
     */
    public function highlyRated(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'imdb_rating' => $this->faker->randomFloat(1, 8.0, 10.0),
                'metascore' => $this->faker->numberBetween(80, 100),
            ];
        });
    }

    /**
     * Indicate that the movie is from a specific year.
     */
    public function fromYear(int $year): Factory
    {
        return $this->state(function (array $attributes) use ($year) {
            return [
                'year' => $year,
                'released' => $this->faker->dateTimeBetween("{$year}-01-01", "{$year}-12-31")->format('Y-m-d'),
            ];
        });
    }

    /**
     * Indicate that the movie belongs to a specific genre.
     */
    public function genre(string $genre): Factory
    {
        return $this->state(function (array $attributes) use ($genre) {
            return [
                'genre' => $genre,
            ];
        });
    }

    /**
     * Indicate that the movie is a blockbuster.
     */
    public function blockbuster(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'box_office' => '$' . number_format($this->faker->numberBetween(100000000, 2000000000)),
                'imdb_votes' => $this->faker->numberBetween(500000, 2000000),
                'imdb_rating' => $this->faker->randomFloat(1, 7.0, 9.5),
            ];
        });
    }
}