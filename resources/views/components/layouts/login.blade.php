<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-[#F3F4F6] antialiased">
        <div class="flex min-h-screen items-center justify-center p-6">
            {{ $slot }}
        </div>

        @fluxScripts
    </body>
</html>
