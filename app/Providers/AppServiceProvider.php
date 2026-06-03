<?php

namespace App\Providers;

use App\Services\DiscordWebhook;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(DiscordWebhook::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // This app serves flat JSON API resources (no "data" envelope).
        JsonResource::withoutWrapping();
    }
}
