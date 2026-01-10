<?php

namespace App\Providers;

use App\Repositories\MovieRepositoryInterface;
use App\Repositories\MovieRepository;
use App\Repositories\FavoriteRepositoryInterface;
use App\Repositories\FavoriteRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Repository bindings
        $this->app->bind(MovieRepositoryInterface::class, MovieRepository::class);
        $this->app->bind(FavoriteRepositoryInterface::class, FavoriteRepository::class);

        // Service bindings
        $this->app->singleton(\App\Services\MovieService::class);
        $this->app->singleton(\App\Services\FavoriteService::class);
        $this->app->singleton(\App\Services\AuthService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
