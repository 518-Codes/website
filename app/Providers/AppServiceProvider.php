<?php

namespace App\Providers;

use App\Events\HostEventSubmitted;
use App\Listeners\SendHostEventDiscordAlert;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Event;
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

        Event::listen(HostEventSubmitted::class, SendHostEventDiscordAlert::class);
    }
}
