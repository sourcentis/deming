<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Deming - @yield('title', 'ISMS Controls Made Easy')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @yield('styles')
    <script @nonce>
    try {
        if (localStorage.getItem('sidebar_hidden') === 'true') {
            document.documentElement.classList.add('sidebar-preload-hidden');
        }
    } catch (_) {
        // Storage unavailable; no-op
    }
    </script>
</head>
<body>
@if (Config::get('app.test'))
<div class="test-banner bg-warning">
    <i class="bi bi-exclamation-triangle"></i> {{ trans('menu.test') }}
</div>
@endif
@include('partials.navbar')
<div class="d-flex">
    @include('partials.sidebar')
    <main id="content-home" class="main-content flex-grow-1">
        @yield('content')
    </main>
</div>
<script @nonce>
    (function () {
        let hidden = false;
        try {
            hidden = localStorage.getItem('sidebar_hidden') === 'true';
        } catch (_) {
            // Storage unavailable
        }
        document.documentElement.classList.remove('sidebar-preload-hidden');
        if (hidden) {
            document.getElementById('sidebar').classList.add('sidebar-hidden');
            document.getElementById('content-home').classList.add('content-expanded');
        }
    })();
</script>
<form id="logout-form" action="/logout" method="POST" style="display: none;">
    @csrf
</form>
</body>
</html>
