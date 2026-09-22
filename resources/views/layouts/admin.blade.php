<!doctype html>
<html lang="vi" data-app="admin">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Quản lý') · {{ config('restaurant.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="admin-body">
    <div class="app-shell">
        <aside class="sidebar" data-sidebar aria-label="Điều hướng quản trị">
            <div class="sidebar-head">
                <a href="{{ route('dashboard') }}" class="brand"><span class="brand-mark"><i
                            data-lucide="utensils"></i></span><span
                        class="brand-copy"><strong>{{ config('restaurant.name') }}</strong><small>Bảng điều khiển quản
                            trị</small></span></a>
                <button class="btn btn-ghost icon-btn sidebar-close" type="button" data-sidebar-close
                    aria-label="Đóng menu"><i data-lucide="x"></i></button>
            </div>
            <nav class="sidebar-nav">
                @can('dashboard.view')
                    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                        href="{{ route('dashboard') }}"><i data-lucide="layout-dashboard"></i><span>Tổng quan</span></a>
                @endcan
                @canany(['products.view', 'categories.view'])
                    <div class="nav-label">Thực đơn</div>
                @endcanany
                @can('products.view')
                    <a class="nav-link {{ request()->routeIs('products.*', 'recipes.*') ? 'active' : '' }}"
                        href="{{ route('products.index') }}"><i data-lucide="utensils-crossed"></i><span>Món ăn & đồ
                            uống</span></a>
                @endcan
                @can('categories.view')
                    <a class="nav-link {{ request()->routeIs('categories.*') ? 'active' : '' }}"
                        href="{{ route('categories.index') }}"><i data-lucide="tags"></i><span>Danh mục</span></a>
                @endcan

                @canany(['tables.view', 'orders.view', 'payments.view'])
                    <div class="nav-label">Vận hành</div>
                @endcanany
                @can('orders.view')
                    <a class="nav-link {{ request()->routeIs('orders.*') ? 'active' : '' }}"
                        href="{{ route('orders.index') }}"><i data-lucide="clipboard-list"></i><span>Đơn hàng</span></a>
                    <a class="nav-link {{ request()->routeIs('kitchen.*') ? 'active' : '' }}"
                        href="{{ route('kitchen.index') }}"><i data-lucide="chef-hat"></i><span>Màn hình bếp</span></a>
                @endcan
                @can('tables.view')
                    <a class="nav-link {{ request()->routeIs('tables.*') ? 'active' : '' }}"
                        href="{{ route('tables.index') }}"><i data-lucide="layout-grid"></i><span>Bàn & QR</span></a>
                @endcan
                @can('payments.view')
                    <a class="nav-link {{ request()->routeIs('payments.*') ? 'active' : '' }}"
                        href="{{ route('payments.index') }}"><i data-lucide="wallet-cards"></i><span>Thanh toán</span></a>
                @endcan

                @canany(['inventory.view', 'suppliers.view'])
                    <div class="nav-label">Kho</div>
                @endcanany
                @can('inventory.view')
                    <a class="nav-link {{ request()->routeIs('inventory.dashboard') ? 'active' : '' }}" href="{{ route('inventory.dashboard') }}"><i data-lucide="layout-dashboard"></i><span>Thông kê kho</span></a>
                @endcan
                @can('inventory.view')
                    <a class="nav-link {{ request()->routeIs('inventory.index') ? 'active' : '' }}"
                        href="{{ route('inventory.index') }}"><i data-lucide="boxes"></i><span>Tồn kho</span></a>
                    <a class="nav-link {{ request()->routeIs('inventory.transactions') ? 'active' : '' }}"
                        href="{{ route('inventory.transactions') }}"><i data-lucide="arrow-left-right"></i><span>Giao dịch
                            kho</span></a>
                @endcan
                @can('inventory.receive')
                    <a class="nav-link {{ request()->routeIs('inventory.receipts.*') ? 'active' : '' }}" href="{{ route('inventory.receipts.index') }}"><i data-lucide="package-plus"></i><span>Nhập kho</span></a>
                @endcan
                @can('inventory.issue')
                    <a class="nav-link {{ request()->routeIs('inventory.issues.*') ? 'active' : '' }}" href="{{ route('inventory.issues.index') }}"><i data-lucide="package-minus"></i><span>Xuất kho</span></a>
                @endcan
                @can('inventory.adjust')
                    <a class="nav-link {{ request()->routeIs('inventory.adjustments.*') ? 'active' : '' }}" href="{{ route('inventory.adjustments.index') }}"><i data-lucide="sliders-horizontal"></i><span>Điều chỉnh kho</span></a>
                @endcan
                @can('inventory.waste')
                    <a class="nav-link {{ request()->routeIs('inventory.waste.*') ? 'active' : '' }}" href="{{ route('inventory.waste.index') }}"><i data-lucide="trash-2"></i><span>Hao hụt / huỷ</span></a>
                @endcan
                @can('inventory.stocktake')
                    <a class="nav-link {{ request()->routeIs('inventory.stocktakes.*') ? 'active' : '' }}" href="{{ route('inventory.stocktakes.index') }}"><i data-lucide="clipboard-check"></i><span>Kiểm kê</span></a>
                @endcan
                @can('inventory.transfer')
                    <a class="nav-link {{ request()->routeIs('inventory.transfers.*') ? 'active' : '' }}" href="{{ route('inventory.transfers.index') }}"><i data-lucide="repeat-2"></i><span>Chuyển kho</span></a>
                @endcan
                @can('inventory.report')
                    <a class="nav-link {{ request()->routeIs('inventory.reports.*') ? 'active' : '' }}" href="{{ route('inventory.reports.index') }}"><i data-lucide="chart-no-axes-combined"></i><span>Báo cáo kho</span></a>
                @endcan
                @can('inventory.manage')
                    <a class="nav-link {{ request()->routeIs('inventory.units.*') ? 'active' : '' }}" href="{{ route('inventory.units.index') }}"><i data-lucide="ruler"></i><span>Đơn vị tính</span></a>
                @endcan
                @can('suppliers.view')
                    <a class="nav-link {{ request()->routeIs('suppliers.*') ? 'active' : '' }}"
                        href="{{ route('suppliers.index') }}"><i data-lucide="truck"></i><span>Nhà cung cấp</span></a>
                @endcan

                @canany(['users.view', 'roles.view', 'reports.view', 'activity_logs.view'])
                    <div class="nav-label">Quản trị</div>
                @endcanany
                @can('users.view')
                    <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}"
                        href="{{ route('users.index') }}"><i data-lucide="users"></i><span>Nhân viên</span></a>
                @endcan
                @can('roles.view')
                    <a class="nav-link {{ request()->routeIs('roles.*') ? 'active' : '' }}"
                        href="{{ route('roles.index') }}"><i data-lucide="shield-check"></i><span>Vai trò &
                            quyền</span></a>
                @endcan
                @can('reports.view')
                    <a class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}"
                        href="{{ route('reports.index') }}"><i data-lucide="chart-no-axes-combined"></i><span>Báo
                            cáo</span></a>
                @endcan
                @can('activity_logs.view')
                    <a class="nav-link {{ request()->routeIs('activity-logs.*') ? 'active' : '' }}"
                        href="{{ route('activity-logs.index') }}"><i data-lucide="history"></i><span>Nhật ký hoạt
                            động</span></a>
                @endcan
            </nav>
            <div class="sidebar-foot"><span class="sidebar-foot-icon"><i data-lucide="store"></i></span><span><small>Nhà
                        hàng</small><strong>{{ config('restaurant.name') }}</strong></span></div>
        </aside>
        <div class="sidebar-overlay" data-sidebar-overlay></div>
        <div class="main">
            <header class="topbar">
                <div class="topbar-start"><button class="btn btn-secondary icon-btn mobile-nav" type="button"
                        data-sidebar-toggle aria-label="Mở menu" aria-expanded="false"><i
                            data-lucide="menu"></i></button>
                    <div class="topbar-heading">
                        <strong>@yield('title', 'Quản lý')</strong><span>Hôm nay, {{ now()->format('d/m/Y') }}</span></div>
                </div>
                <div class="topbar-actions">
                    <span class="restaurant-context"><i data-lucide="store"></i>{{ config('restaurant.name') }}</span>
                    <a class="topbar-profile" href="{{ route('profile.show') }}"><x-ui.avatar :user="auth()->user()"
                            size="sm" /><span><strong>{{ auth()->user()->name }}</strong><small>{{ auth()->user()->roles->pluck('name')->first() ?? 'Nhân viên' }}</small></span><i
                            data-lucide="chevron-right"></i></a>
                    <form method="post" action="{{ route('logout') }}" data-no-loading>@csrf<button
                            class="btn btn-ghost icon-btn" type="submit" title="Đăng xuất" aria-label="Đăng xuất"><i
                                data-lucide="log-out"></i></button></form>
                </div>
            </header>
            <main class="page" id="main-content">@yield('content')</main>
        </div>
    </div>
    <x-ui.toasts />
    <x-ui.modal-root />
    @stack('scripts')
</body>

</html>
