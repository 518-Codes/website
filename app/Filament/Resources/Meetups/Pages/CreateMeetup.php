<?php

namespace App\Filament\Resources\Meetups\Pages;

use App\Filament\Resources\Meetups\MeetupResource;
use App\Services\GeocodingService;
use Filament\Resources\Pages\CreateRecord;

class CreateMeetup extends CreateRecord
{
    protected static string $resource = MeetupResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->fillCoordinates($data);
    }

    /**
     * Geocode from the location string only when both coordinates are blank.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function fillCoordinates(array $data): array
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
