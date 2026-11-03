<?php

namespace App\Filament\Resources\Meetups\Tables;

use App\Enums\MeetupStatus;
use App\Filament\Resources\Meetups\MeetupResource;
use App\Models\Meetup;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class MeetupsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('location')
                    ->searchable(),

                TextColumn::make('starts_at')
                    ->dateTime('M j, Y g:i A')
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (MeetupStatus $state): string => $state->color()),

                TextColumn::make('rsvps_count')
                    ->counts('rsvps')
                    ->label('RSVPs')
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                ActionGroup::make([
                    ReplicateAction::make()
                        ->label('Duplicate to next week')
                        ->modalHeading(fn (Meetup $record): string => "Duplicate \"{$record->title}\" to next week")
                        ->modalDescription('Creates a draft copy one week later with the same details, schedule, images, and tags. Review it, then publish.')
                        ->modalSubmitActionLabel('Create draft')
                        ->excludeAttributes(['rsvps_count'])
                        ->beforeReplicaSaved(function (Meetup $replica): void {
                            $replica->starts_at = $replica->starts_at->addWeek();
                            $replica->ends_at = $replica->ends_at?->addWeek();
                            $replica->status = MeetupStatus::Draft;

                            $baseSlug = Str::slug($replica->title).'-'.$replica->starts_at->format('Y-m-d');
                            $replica->slug = $baseSlug;

                            for ($suffix = 2; Meetup::where('slug', $replica->slug)->exists(); $suffix++) {
                                $replica->slug = "{$baseSlug}-{$suffix}";
                            }
                        })
                        ->after(function (Meetup $record, Meetup $replica): void {
                            $replica->tags()->sync($record->tags);
                            $replica->scheduleItems()->createMany(
                                $record->scheduleItems->map->only(['time', 'title', 'note', 'order']),
                            );
                            $replica->images()->createMany(
                                $record->images->map->only(['path', 'alt', 'order']),
                            );
                        })
                        ->successNotificationTitle('Draft created for next week')
                        ->successRedirectUrl(fn (Meetup $replica): string => MeetupResource::getUrl('edit', ['record' => $replica])),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
