@php
    $metaTitle = $title ?? '518.codes — Upstate NY Developer Group';
    $metaDescription = $description ?? 'Upstate NY Developer Group. Meetups, talks, and a community for developers in the 518.';
@endphp

{{-- Primary --}}
<meta name="description" content="{{ $metaDescription }}">
<link rel="canonical" href="{{ url()->current() }}">
<meta name="theme-color" content="#5EFC8D">

{{-- Icons --}}
<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="icon" href="/favicon-32.png" type="image/png" sizes="32x32">
<link rel="icon" href="/favicon-16.png" type="image/png" sizes="16x16">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">
<link rel="manifest" href="/site.webmanifest">

{{-- Open Graph (Facebook, LinkedIn, Slack, iMessage, Discord, Instagram…) --}}
<meta property="og:type" content="website">
<meta property="og:site_name" content="518.codes">
<meta property="og:title" content="{{ $metaTitle }}">
<meta property="og:description" content="{{ $metaDescription }}">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:image" content="{{ url('/og-image.png') }}">
<meta property="og:image:secure_url" content="{{ secure_url('/og-image.png') }}">
<meta property="og:image:type" content="image/png">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="{518} codes — Upstate NY Developer Group">
<meta property="og:locale" content="en_US">

{{-- Twitter / X (no account → no site/creator handle tags) --}}
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $metaTitle }}">
<meta name="twitter:description" content="{{ $metaDescription }}">
<meta name="twitter:image" content="{{ url('/og-image.png') }}">
<meta name="twitter:image:alt" content="{518} codes — Upstate NY Developer Group">
