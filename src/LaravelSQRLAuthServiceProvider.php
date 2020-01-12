<?php

namespace DestruidorPT\LaravelSQRLAuth;

use Illuminate\Support\ServiceProvider;

class LaravelSQRLAuthServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        $this->publishes([
            __DIR__.'/../exemple/app/Http/Controllers' => app_path('Http/Controllers'),
            __DIR__.'/../exemple/resources/views' => resource_path('views'),
        ]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        
    }
}