@props([
    'title' => null,
    'description' => null,
])

@php
    $resolvedTitle = page_title($title);
    $metaDescription = $description ?? branding('product.tagline');
    $favicon = asset_url(branding('product.favicon', '/favicon.svg'));
    $themeColor = branding('product.theme_color', '#4338ca');
@endphp

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="description" content="{{ $metaDescription }}">
<meta name="application-name" content="{{ product_name() }}">
<meta name="theme-color" content="{{ $themeColor }}">

<title>{{ $resolvedTitle }}</title>

<link rel="icon" href="{{ $favicon }}" type="image/svg+xml">
<link rel="apple-touch-icon" href="{{ $favicon }}">
<link rel="manifest" href="{{ asset('site.webmanifest') }}">

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet">

@vite(['resources/css/app.css', 'resources/js/app.js'])
@livewireStyles
