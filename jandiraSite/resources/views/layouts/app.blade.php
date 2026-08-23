<!DOCTYPE html>

<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

    <head>

        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>  @yield('title', 'Butique da Jandira') </title>

        {{-- Fonte --}}
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet">

        {{-- CSS global da loja --}}
        @vite('resources/css/index.css')
        {{-- CSS específico da página --}}
        @stack('styles')

    </head>
    
    <body>

        <x-partials.header />

        <main>
            @yield('content')
        </main>

        <x-partials.footer />

        {{-- JavaScript global --}}
        @vite('resources/js/index.js')
        {{-- JavaScript específico da página --}}
        @stack('scripts')

    </body>
</html>