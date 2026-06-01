<?php

namespace App\Http\Controllers;

use App\Enums\MeetupStatus;
use App\Models\Meetup;
use Illuminate\Http\Response;

class IcsController
{
    public function __invoke(string $slug): Response
    {
        $meetup = Meetup::where('slug', $slug)
            ->where('status', MeetupStatus::Published)
            ->firstOrFail();

        $uid = $meetup->id.'@518.codes';
        $now = now()->utc()->format('Ymd\THis\Z');
        $start = $meetup->starts_at->utc()->format('Ymd\THis\Z');
        $end = ($meetup->ends_at ?? $meetup->starts_at->copy()->addHours(2))->utc()->format('Ymd\THis\Z');
        $summary = $this->escape($meetup->title);
        $location = $this->escape($meetup->location);
        $description = $this->escape(strip_tags($meetup->description));
        $url = route('events.show', $meetup->slug);

        $ics = implode("\r\n", [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//518.codes//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            "UID:{$uid}",
            "DTSTAMP:{$now}",
            "DTSTART:{$start}",
            "DTEND:{$end}",
            "SUMMARY:{$summary}",
            "LOCATION:{$location}",
            "DESCRIPTION:{$description}",
            "URL:{$url}",
            'END:VEVENT',
            'END:VCALENDAR',
        ]);

        $filename = str($meetup->slug)->append('.ics');

        return response($ics, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private function escape(string $value): string
    {
        return str_replace(
            ["\r\n", "\n", "\r", ',', ';', '\\'],
            ['\\n', '\\n', '\\n', '\\,', '\\;', '\\\\'],
            $value
        );
    }
}
