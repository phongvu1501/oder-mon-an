<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Quản lý') · {{ config('restaurant.name') }}</title>@vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>
    <div class="app-shell">
        <aside class="sidebar" data-sidebar>
            <a href="{{ route('dashboard') }}" class="brand"><span class="brand-mark"><i
                        data-lucide="utensils"></i></span>{{ config('restaurant.name') }}</a>
            @can('dashboard.view')
                <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><i
                        data-lucide="layout-dashboard"></i> Tổng quan</a>
            @endcan
            @canany(['products.view', 'categories.view'])
                <div class="nav-label">Thực đơn</div>
            @endcanany
            @can('products.view')
                <a class="nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}"
                    href="{{ route('products.index') }}"><i data-lucide="soup"></i> Món ăn</a>
            @endcan
            @can('categories.view')
                <a class="nav-link {{ request()->routeIs('categories.*') ? 'active' : '' }}"
                    href="{{ route('categories.index') }}"><i data-lucide="tags"></i> Danh mục</a>
            @endcan
            @canany(['tables.view', 'orders.view', 'payments.view'])
                <div class="nav-label">Vận hành</div>
            @endcanany
            @can('tables.view')
                <a class="nav-link {{ request()->routeIs('tables.*') ? 'active' : '' }}"
                    href="{{ route('tables.index') }}"><i data-lucide="grid-2x2"></i> Bàn</a>
            @endcan
            @can('orders.view')
                <a class="nav-link {{ request()->routeIs('orders.*') ? 'active' : '' }}"
                    href="{{ route('orders.index') }}"><i data-lucide="clipboard-list"></i> Order</a><a
                    class="nav-link {{ request()->routeIs('kitchen.*') ? 'active' : '' }}"
                    href="{{ route('kitchen.index') }}"><i data-lucide="chef-hat"></i> Bếp</a>
            @endcan
            @can('payments.view')
                <a class="nav-link {{ request()->routeIs('payments.*') ? 'active' : '' }}"
                    href="{{ route('payments.index') }}"><i data-lucide="wallet-cards"></i> Thanh toán</a>
            @endcan
            @canany(['inventory.view', 'suppliers.view'])
                <div class="nav-label">Kho</div>
            @endcanany
            @can('inventory.view')
                <a class="nav-link {{ request()->routeIs('inventory.index') ? 'active' : '' }}"
                    href="{{ route('inventory.index') }}"><i data-lucide="boxes"></i> Tồn kho</a><a
                    class="nav-link {{ request()->routeIs('inventory.transactions') ? 'active' : '' }}"
                    href="{{ route('inventory.transactions') }}"><i data-lucide="arrow-left-right"></i> Giao dịch kho</a>
            @endcan
            @can('suppliers.view')
                <a class="nav-link {{ request()->routeIs('suppliers.*') ? 'active' : '' }}"
                    href="{{ route('suppliers.index') }}"><i data-lucide="truck"></i> Nhà cung cấp</a>
            @endcan
            @canany(['users.view', 'roles.view', 'reports.view', 'activity_logs.view'])
                <div class="nav-label">Hệ thống</div>
            @endcanany
            @can('users.view')
                <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}"
                    href="{{ route('users.index') }}"><i data-lucide="users"></i> Nhân viên</a>
            @endcan
            @can('roles.view')
                <a class="nav-link {{ request()->routeIs('roles.*') ? 'active' : '' }}"
                    href="{{ route('roles.index') }}"><i data-lucide="shield-check"></i> Phân quyền</a>
            @endcan
            @can('reports.view')
                <a class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}"
                    href="{{ route('reports.index') }}"><i data-lucide="chart-no-axes-combined"></i> Báo cáo</a>
            @endcan
            @can('activity_logs.view')
                <a class="nav-link {{ request()->routeIs('activity-logs.*') ? 'active' : '' }}"
                    href="{{ route('activity-logs.index') }}"><i data-lucide="history"></i> Nhật ký</a>
            @endcan
        </aside>
        <div class="sidebar-overlay" data-sidebar-overlay></div>
        <main class="main">
            <header class="topbar"><button class="btn btn-secondary icon-btn mobile-nav" data-sidebar-toggle
                    aria-label="Mở menu"><i data-lucide="menu"></i></button>
                <div></div>
                <div class="topbar-user"><span>{{ auth()->user()->name }}</span><span
                        class="avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                    <form method="post" action="{{ route('logout') }}">@csrf<button class="btn btn-ghost icon-btn"
                            title="Đăng xuất" aria-label="Đăng xuất"><i data-lucide="log-out"></i></button></form>
                </div>
            </header>
            <div class="page">
                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                    @endif @if (session('error'))
                        <div class="alert alert-error">{{ session('error') }}</div>
                        @endif @if ($errors->any())
                            <div class="alert alert-error">{{ $errors->first() }}</div>
                        @endif @yield('content')
            </div>
        </main>
    </div>@stack('scripts')
</body>

</html>
