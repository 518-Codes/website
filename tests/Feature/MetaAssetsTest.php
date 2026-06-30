<?php

test('production favicon and social assets are published to the web root', function () {
    $root = public_path();

    $files = [
        'favicon.ico', 'favicon.svg', 'favicon-16.png', 'favicon-32.png', 'favicon-48.png',
        'apple-touch-icon.png', 'icon-192.png', 'icon-512.png', 'og-image.png', 'site.webmanifest',
    ];

    foreach ($files as $file) {
        expect(file_exists($root.DIRECTORY_SEPARATOR.$file))->toBeTrue("Missing public/{$file}");
    }

    // Manifest is valid JSON with the expected PWA name.
    $manifest = json_decode((string) file_get_contents($root.'/site.webmanifest'), true);
    expect($manifest)->toBeArray();
    expect($manifest['short_name'] ?? null)->toBe('518.codes');
});
