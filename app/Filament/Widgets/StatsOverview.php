<?php

namespace App\Filament\Widgets;

use App\Enums\MeetupStatus;
use App\Models\Meetup;
use App\Models\Rsvp;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        return [
            Stat::make('Published events', Meetup::published()->count())
                ->description(Meetup::published()->upcoming()->count().' upcoming')
                ->color('success'),

            Stat::make('Total RSVPs', Rsvp::count())
                ->description('across all events')
                ->color('warning'),

            Stat::make('Pending drafts', Meetup::where('status', MeetupStatus::Draft)->count())
                ->description('awaiting review')
                ->color(Meetup::where('status', MeetupStatus::Draft)->exists() ? 'danger' : 'gray'),
        ];
    }
}
