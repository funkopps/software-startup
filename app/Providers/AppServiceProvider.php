<?php

namespace App\Providers;

use App\Services\Api\Identify\IdentifyService;
use App\Services\Api\Identify\IdentifyServiceInterface;
use App\Services\Ffmpeg;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            IdentifyServiceInterface::class,
            IdentifyService::class,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
