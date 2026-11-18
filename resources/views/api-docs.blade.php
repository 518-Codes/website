<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    {{-- Unlisted until the launch talk. Drop this when the docs get a nav link. --}}
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $config->get('ui.title') }} · 518.codes</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;700;800&display=swap" rel="stylesheet">
    @include('scramble::dev-tools', ['renderer' => 'scalar'])
</head>
<body>
<div id="app"></div>
<script src="{{ $config->renderer()->get('cdn', 'https://cdn.jsdelivr.net/npm/@scalar/api-reference') }}"></script>

<script>
    const CSRF_TOKEN_COOKIE_KEY = "XSRF-TOKEN";
    const CSRF_TOKEN_HEADER_KEY = "X-XSRF-TOKEN";
    const getCookieValue = (key) => {
        const cookie = document.cookie.split(';').find((cookie) => cookie.trim().startsWith(key));
        return cookie?.split("=")[1];
    };

    // Matches the site's terminal palette in layouts/app.blade.php.
    const terminalCss = `
        .dark-mode, .light-mode {
            --scalar-font: 'JetBrains Mono', ui-monospace, monospace;
            --scalar-font-code: 'JetBrains Mono', ui-monospace, monospace;
            --scalar-radius: 0;
            --scalar-radius-lg: 0;
            --scalar-radius-xl: 0;
            --scalar-background-1: #0B0F0B;
            --scalar-background-2: #121812;
            --scalar-background-3: #1A211A;
            --scalar-background-accent: rgba(94, 252, 141, 0.1);
            --scalar-color-1: #E6FFE6;
            --scalar-color-2: #9AB89C;
            --scalar-color-3: #5A6E5C;
            --scalar-color-accent: #5EFC8D;
            --scalar-border-color: #2A3A2A;
            --scalar-color-green: #5EFC8D;
            --scalar-color-blue: #5EE2FC;
            --scalar-color-orange: #FFB627;
            --scalar-color-yellow: #FFB627;
            --scalar-color-red: #FF3DAA;
            --scalar-color-purple: #FF3DAA;
            --scalar-button-1: #5EFC8D;
            --scalar-button-1-color: #0B0F0B;
            --scalar-button-1-hover: #2DC964;
            --scalar-sidebar-background-1: #0B0F0B;
            --scalar-sidebar-color-1: #E6FFE6;
            --scalar-sidebar-color-2: #9AB89C;
            --scalar-sidebar-color-active: #5EFC8D;
            --scalar-sidebar-item-active-background: rgba(94, 252, 141, 0.1);
            --scalar-sidebar-border-color: #2A3A2A;
            --scalar-sidebar-search-background: #121812;
            --scalar-sidebar-search-border-color: #2A3A2A;
        }
        body { background: #0B0F0B; }
        ::selection { background: #5EFC8D; color: #0B0F0B; }
    `;

    Scalar.createApiReference('#app', {
        content: @json($spec),
        ...@json($config->renderer()->all(except: ['cdn', 'credentials', 'view'])),
        customCss: terminalCss,
        onBeforeRequest: ({ requestBuilder }) => {
            requestBuilder.headers.set(CSRF_TOKEN_HEADER_KEY, decodeURIComponent(getCookieValue(CSRF_TOKEN_COOKIE_KEY)))
        },
        customFetch: (input, init) => {
            return window.fetch(input, { ...init, credentials: @json($config->renderer()->get('credentials', 'include')) })
        }
    })
</script>
</body>
</html>
