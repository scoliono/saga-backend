<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Scripts -->
    <script src="{{ asset('js/app.js') }}" defer></script>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">

    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">

    <!-- PWA -->
    @if (App::environment('production', 'staging'))
        <script type="text/javascript">
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', () => {
                    navigator.serviceWorker.register('/sw.js')
                        .then(function (registration) {
                            console.log('ServiceWorker registration successful with scope: ', registration.scope);
                        })
                        .catch(function (err) {
                            console.error('ServiceWorker registration failed: ', err);
                        });
                });
            }
        </script>
    @endif
    <link rel="manifest" href="/manifest.json">
</head>
<body>
    <App />
</body>
</html>
