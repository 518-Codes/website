<?php

namespace App\Filament\Widgets;

use App\Enums\MeetupStatus;
use App\Filament\Resources\Meetups\MeetupResource;
use App\Models\Meetup;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class DraftMeetupsWidget extends TableWidget
{
    protected static ?string $heading = 'Draft events awaiting review';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Meetup::where('status', MeetupStatus::Draft)->orderBy('created_at', 'desc'))
            ->emptyStateHeading('No drafts')
            ->emptyStateDescription('Host submissions and new draft events will appear here.')
            ->emptyStateIcon(Heroicon::CalendarDays)
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('contact_email')
                    ->label('Organiser')
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('location')
                    ->limit(40),

                TextColumn::make('starts_at')
                    ->label('Proposed date')
                    ->dateTime('M j, Y g:i A')
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Submitted')
                    ->since()
                    ->sortable(),
            ])
            ->recordUrl(fn (Meetup $record): string => MeetupResource::getUrl('edit', ['record' => $record]));
    }
}
