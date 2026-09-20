<!doctype html>
<html lang="vi" data-app="customer">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('restaurant.name'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="customer-body">
    <header class="customer-header">
        <div class="customer-header-inner">
            <div class="customer-identity"><span class="customer-logo"><i
                        data-lucide="utensils"></i></span><span><strong
                        class="customer-brand">{{ config('restaurant.name') }}</strong><small
                        class="customer-table">@yield('table-label', 'Gọi món tại bàn')</small></span></div>
            @if (session()->has('customer.table_session_id'))
                <a class="btn btn-ghost customer-status-link" href="{{ route('menu.status') }}"><i
                        data-lucide="receipt-text"></i><span>Đơn của bạn</span></a>
            @endif
        </div>
    </header>
    <main class="customer-main" id="main-content">@yield('content')</main>
    <x-ui.toasts />
    <x-ui.modal-root />
    @stack('scripts')
</body>

</html>
