
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">

        <meta name="viewport" content="width=device-width, initial-scale=1">

        <!-- Browser Tab Title -->
        <title inertia>{{ config('app.name', "Imposter Game") }}</title>

        <!-- Browser Favicon -->
        <link rel="icon" type="image/svg+xml" href="{{ asset('fitymi.svg') }}">

        @vite('resources/js/app.js')

        @inertiaHead
    </head>
    <body>
        @inertia
    </body>
</html>
