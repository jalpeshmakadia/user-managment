<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'User Management') }}</title>

        <link
            href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
            rel="stylesheet"
            integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
            crossorigin="anonymous"
        >
        @stack('styles')
    </head>
    <body class="bg-light">
        <nav class="navbar navbar-expand-lg bg-white border-bottom shadow-sm">
            <div class="container">
                <span class="navbar-brand fw-semibold">User Management</span>
            </div>
        </nav>

        <main class="container py-4">
            @yield('content')
        </main>

        <script
            src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"
            integrity="sha384-1H217gwSVyLSIfaLxHbE7dRb3v4mYCKbpQvzx0cegeju1MVsGrX5xXxAvs/HgeFs"
            crossorigin="anonymous"
        ></script>
        <script
            src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.min.js"
            integrity="sha384-aEDtD4n2FLrMdE9psop0SHdNyy/W9cBjH22rSRp+3wPHd62Y32uijc0H2eLmgaSn"
            crossorigin="anonymous"
        ></script>
        <script
            src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
            crossorigin="anonymous"
        ></script>
        @stack('scripts')
        @yield('scripts')
    </body>
</html>
