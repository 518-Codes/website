<?php

namespace App\Filament\Resources\Meetups\Pages;

use App\Filament\Resources\Meetups\MeetupResource;
use App\Services\GeocodingService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMeetup extends EditRecord
{
    protected static string $resource = MeetupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (empty($data['latitude']) && empty($data['longitude']) && ! empty($data['location'])) {
            $coords = app(GeocodingService::class)->geocode($data['location']);
            if ($coords !== null) {
                $data['latitude'] = $coords['lat'];
                $data['longitude'] = $coords['lng'];
            }
        }

        return $data;
    }
}
