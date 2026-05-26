<?php

namespace App\Filament\Resources\Meetups;

use App\Filament\Resources\Meetups\Pages\CreateMeetup;
use App\Filament\Resources\Meetups\Pages\EditMeetup;
use App\Filament\Resources\Meetups\Pages\ListMeetups;
use App\Filament\Resources\Meetups\RelationManagers\RsvpsRelationManager;
use App\Filament\Resources\Meetups\Schemas\MeetupForm;
use App\Filament\Resources\Meetups\Tables\MeetupsTable;
use App\Models\Meetup;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MeetupResource extends Resource
{
    protected static ?string $model = Meetup::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::CalendarDays;

    public static function form(Schema $schema): Schema
    {
        return MeetupForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MeetupsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RsvpsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMeetups::route('/'),
            'create' => CreateMeetup::route('/create'),
            'edit' => EditMeetup::route('/{record}/edit'),
        ];
    }
}
