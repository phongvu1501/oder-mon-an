<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('restaurant.name'))</title>@vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="customer-body">
    <header class="customer-header">
        <div class="customer-header-inner">
            <div>
                <div class="customer-brand">{{ config('restaurant.name') }}</div>
                <div class="customer-table">@yield('table-label', 'Gọi món tại bàn')</div>
            </div><a class="btn btn-ghost customer-status-link" href="{{ route('menu.status') }}"><i
                    data-lucide="receipt-text"></i> Đơn & hóa đơn</a>
        </div>
    </header>
    <main class="customer-main">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
            @endif @if ($errors->any())
                <div class="alert alert-error">{{ $errors->first() }}</div>
            @endif @yield('content')
    </main>@stack('scripts')
</body>

</html>
