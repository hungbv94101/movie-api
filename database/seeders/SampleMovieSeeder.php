<?php

namespace Database\Seeders;

use App\Models\Movie;
use Illuminate\Database\Seeder;

class SampleMovieSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🎬 Seeding sample movies...');

        $movies = [
            [
                'imdb_id' => 'tt4154756',
                'title' => 'Avengers: Endgame',
                'year' => 2019,
                'genre' => 'Action, Adventure, Drama',
                'director' => 'Anthony Russo, Joe Russo',
                'plot' => 'After the devastating events of Avengers: Infinity War, the universe is in ruins.',
                'poster' => 'https://m.media-amazon.com/images/M/MV5BMTc5MDE2ODcwNV5BMl5BanBnXkFtZTgwMzI2NzQ2NzM@._V1_SX300.jpg',
                'imdb_rating' => 8.4,
                'runtime' => '181 min'
            ],
            [
                'imdb_id' => 'tt0111161',
                'title' => 'The Shawshank Redemption',
                'year' => 1994,
                'genre' => 'Drama',
                'director' => 'Frank Darabont',
                'plot' => 'Two imprisoned men bond over a number of years, finding solace and eventual redemption.',
                'poster' => 'https://m.media-amazon.com/images/M/MV5BNDE3ODcxYzMtY2YzZC00NmNlLWJiNDMtZDViZWM2MzIxZDYwXkEyXkFqcGdeQXVyNjAwNDUxODI@._V1_SX300.jpg',
                'imdb_rating' => 9.3,
                'runtime' => '142 min'
            ],
            [
                'imdb_id' => 'tt0120737',
                'title' => 'The Lord of the Rings: The Fellowship of the Ring',
                'year' => 2001,
                'genre' => 'Action, Adventure, Drama',
                'director' => 'Peter Jackson',
                'plot' => 'A meek Hobbit from the Shire and eight companions set out on a journey.',
                'poster' => 'https://m.media-amazon.com/images/M/MV5BN2EyZjM3NzUtNWUzMi00MTgxLWI0NTctMzY4M2VlOTdjZWRiXkEyXkFqcGdeQXVyNDUzOTQ5MjY@._V1_SX300.jpg',
                'imdb_rating' => 8.8,
                'runtime' => '178 min'
            ],
            [
                'imdb_id' => 'tt0109830',
                'title' => 'Forrest Gump',
                'year' => 1994,
                'genre' => 'Drama, Romance',
                'director' => 'Robert Zemeckis',
                'plot' => 'The presidencies of Kennedy and Johnson, the events of Vietnam, Watergate and other historical events unfold.',
                'poster' => 'https://m.media-amazon.com/images/M/MV5BNWIwODRlZTUtY2U3ZS00Yzg1LWJhNzYtMmZiYmEyNmU1NjMzXkEyXkFqcGdeQXVyMTQxNzMzNDI@._V1_SX300.jpg',
                'imdb_rating' => 8.8,
                'runtime' => '142 min'
            ],
            [
                'imdb_id' => 'tt0137523',
                'title' => 'Fight Club',
                'year' => 1999,
                'genre' => 'Drama',
                'director' => 'David Fincher',
                'plot' => 'An insomniac office worker and a devil-may-care soapmaker form an underground fight club.',
                'poster' => 'https://m.media-amazon.com/images/M/MV5BMmEzNTkxYjQtZTc0MC00YTVjLTg5ZTEtZWMwOWVlYzY0NWIwXkEyXkFqcGdeQXVyNzkwMjQ5NzM@._V1_SX300.jpg',
                'imdb_rating' => 8.8,
                'runtime' => '139 min'
            ],
            [
                'imdb_id' => 'tt1375666',
                'title' => 'Inception',
                'year' => 2010,
                'genre' => 'Action, Sci-Fi, Thriller',
                'director' => 'Christopher Nolan',
                'plot' => 'A thief who steals corporate secrets through the use of dream-sharing technology.',
                'poster' => 'https://m.media-amazon.com/images/M/MV5BMjAxMzY3NjcxNF5BMl5BanBnXkFtZTcwNTI5OTM0Mw@@._V1_SX300.jpg',
                'imdb_rating' => 8.8,
                'runtime' => '148 min'
            ],
            [
                'imdb_id' => 'tt0816692',
                'title' => 'Interstellar',
                'year' => 2014,
                'genre' => 'Adventure, Drama, Sci-Fi',
                'director' => 'Christopher Nolan',
                'plot' => 'A team of explorers travel through a wormhole in space in an attempt to ensure humanity\'s survival.',
                'poster' => 'https://m.media-amazon.com/images/M/MV5BZjdkOTU3MDktN2IxOS00OGEyLWFmMjktY2FiMmZkNWIyODZiXkEyXkFqcGdeQXVyMTMxODk2OTU@._V1_SX300.jpg',
                'imdb_rating' => 8.6,
                'runtime' => '169 min'
            ],
            [
                'imdb_id' => 'tt0499549',
                'title' => 'Avatar',
                'year' => 2009,
                'genre' => 'Action, Adventure, Fantasy',
                'director' => 'James Cameron',
                'plot' => 'A paraplegic Marine dispatched to the moon Pandora on a unique mission.',
                'poster' => 'https://m.media-amazon.com/images/M/MV5BZDA0OGQxNTItMDZkMC00N2UyLTg3MzMtYTJmNjg3Nzk5MzRiXkEyXkFqcGdeQXVyMjUzOTY1NTc@._V1_SX300.jpg',
                'imdb_rating' => 7.9,
                'runtime' => '162 min'
            ]
        ];

        $added = 0;
        foreach ($movies as $movieData) {
            // Check if movie already exists
            if (!Movie::where('imdb_id', $movieData['imdb_id'])->exists()) {
                Movie::create($movieData);
                $this->command->info("✅ Added: {$movieData['title']} ({$movieData['year']})");
                $added++;
            } else {
                $this->command->info("⏭️  Skipped: {$movieData['title']} (already exists)");
            }
        }

        $this->command->info("🎉 Sample movie seeding completed! Added {$added} movies to database.");
    }
}