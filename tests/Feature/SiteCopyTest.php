<?php

use App\Livewire\Terminal;
use App\Models\Meetup;

use function Pest\Livewire\livewire;

test('homepage about tells the real origin story', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Startup Tech Valley')
        ->assertDontSee('brewery')
        ->assertDontSee('Postgres meetup');
});

test('homepage derives the city list from config and drops stale cities', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Albany')
        ->assertSee('Troy')
        ->assertSee('Schenectady')
        ->assertSee('Saratoga Springs')
        ->assertDontSee('Delmar')   // was hardcoded in the about chips
        ->assertDontSee('Cohoes');  // was in the old config list
});

test('homepage applies the honesty relabels', function () {
    $this->get('/')
        ->assertOk()
        ->assertDontSee('cities reached')
        ->assertDontSee('events hosted');
});

test('homepage featured block uses the typical-night label', function () {
    Meetup::factory()->upcoming()->create();

    $this->get('/')
        ->assertOk()
        ->assertSee('a typical night')
        ->assertDontSee('how it runs');
});

test('nav links to discord instead of a dead subscribe button', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('JOIN DISCORD')
        ->assertSee('https://discord.gg/mHeDCkqZj', false)
        ->assertDontSee('SUBSCRIBE');
});

test('footer drops the dead feed links and keeps discord', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('https://discord.gg/mHeDCkqZj', false)
        ->assertDontSee('weekly digest')
        ->assertDontSee('rss feed')
        ->assertDontSee('ical link');
});

test('code of conduct page renders the rules and reporting route', function () {
    $this->get(route('code-of-conduct'))
        ->assertOk()
        ->assertSee('Code of Conduct')
        ->assertSee('Be respectful.')
        ->assertSee('No spam or self-promotion without asking.')
        ->assertSee('Listen to the organizers.')
        ->assertSee('https://discord.gg/mHeDCkqZj', false);
});

test('footer code of conduct link points at the page', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee(route('code-of-conduct'), false);
});

test('events index host CTAs point at the host page, not dead anchors', function () {
    $this->get(route('events.index'))
        ->assertOk()
        ->assertSee(route('host'), false)
        ->assertSee('https://discord.gg/mHeDCkqZj', false)
        ->assertDontSee('One short email on Mondays');
});

test('terminal whois lists the four cities and not the old region', function () {
    livewire(Terminal::class)
        ->set('input', 'whois')
        ->call('submit')
        ->assertSee('Albany · Troy · Schenectady · Saratoga Springs')
        ->assertDontSee('Hudson Valley');
});
