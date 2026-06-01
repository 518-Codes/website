<?php

namespace App\Filament\Resources\Meetups\Schemas;

use App\Enums\MeetupStatus;
use App\Services\GeocodingService;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class MeetupForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Details')->schema([
                TextInput::make('title')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Set $set, ?string $state) => $set(
                        'slug',
                        Str::slug($state ?? ''),
                    ))
                    ->columnSpanFull(),

                TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->columnSpanFull(),

                RichEditor::make('description')
                    ->required()
                    ->columnSpanFull(),

                TextInput::make('location')
                    ->required()
                    ->columnSpanFull(),

                Grid::make(3)->schema([
                    TextInput::make('latitude')
                        ->numeric()
                        ->step('any')
                        ->helperText('Blank → auto-geocoded from location on save.'),

                    TextInput::make('longitude')
                        ->numeric()
                        ->step('any'),

                    Actions::make([
                        Action::make('geocode')
                            ->label('Geocode from location')
                            ->icon('heroicon-o-map-pin')
                            ->action(function (Get $get, Set $set, GeocodingService $geocoder): void {
                                $coords = $geocoder->geocode((string) $get('location'));
                                if ($coords === null) {
                                    return;
                                }
                                $set('latitude', $coords['lat']);
                                $set('longitude', $coords['lng']);
                            }),
                    ]),
                ])->columnSpanFull(),

                Grid::make(2)->schema([
                    DateTimePicker::make('starts_at')
                        ->required(),

                    DateTimePicker::make('ends_at'),
                ]),

                Select::make('status')
                    ->options(MeetupStatus::class)
                    ->required()
                    ->default(MeetupStatus::Draft->value),

                Select::make('tags')
                    ->relationship('tags', 'name')
                    ->multiple()
                    ->preload(),
            ]),

            Section::make('Images')->schema([
                Repeater::make('images')
                    ->relationship()
                    ->schema([
                        FileUpload::make('path')
                            ->required()
                            ->image()
                            ->visibility('public')
                            ->directory('meetup-images'),

                        TextInput::make('alt')
                            ->label('Alt text'),

                        TextInput::make('order')
                            ->numeric()
                            ->default(0)
                            ->hidden(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]),
        ]);
    }
}
