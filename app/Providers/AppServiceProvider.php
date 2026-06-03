<?php

namespace App\Providers;

use App\Models\Rsvp;
use App\Observers\RsvpObserver;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void {}

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // This app serves flat JSON API resources (no "data" envelope).
        JsonResource::withoutWrapping();
        Rsvp::observe(RsvpObserver::class);
    }
}
