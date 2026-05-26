<?php

namespace App\Filament\Resources\Rsvps;

use App\Filament\Resources\Rsvps\Pages\ListRsvps;
use App\Filament\Resources\Rsvps\Schemas\RsvpForm;
use App\Filament\Resources\Rsvps\Tables\RsvpsTable;
use App\Models\Rsvp;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class RsvpResource extends Resource
{
    protected static ?string $model = Rsvp::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::UserGroup;

    protected static ?string $navigationLabel = 'RSVPs';

    public static function form(Schema $schema): Schema
    {
        return RsvpForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RsvpsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRsvps::route('/'),
        ];
    }
}
