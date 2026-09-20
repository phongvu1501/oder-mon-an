@extends('layouts.admin')

@section('title', 'Bảng điều khiển')

@section('content')
    @php
        $revenueMeta =
            $kpis['revenue_change'] === null
                ? 'Chưa có dữ liệu kỳ trước'
                : ($kpis['revenue_change'] >= 0 ? '+' : '') . $kpis['revenue_change'] . '% so với kỳ trước';
    @endphp

    <x-ui.page-header title="Bảng điều khiển" :subtitle="'Tổng quan hoạt động ' . mb_strtolower($period['label']) . ' · cập nhật ' . now()->format('H:i')" eyebrow="Tổng quan">
        <form class="dashboard-filter" method="get">
            <label class="visually-hidden" for="dashboard-period">Khoảng thời gian</label>
            <select class="form-select" id="dashboard-period" name="period" data-period-select>
                @foreach (['today' => 'Hôm nay', 'yesterday' => 'Hôm qua', '7days' => '7 ngày', '30days' => '30 ngày', 'month' => 'Tháng này', 'last_month' => 'Tháng trước', 'quarter' => 'Quý này', 'year' => 'Năm nay', 'custom' => 'Tùy chọn'] as $value => $label)
                    <option value="{{ $value }}" @selected($period['preset'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <div class="custom-period {{ $period['preset'] === 'custom' ? 'is-visible' : '' }}" data-custom-period>
                <input class="form-control" aria-label="Từ ngày" type="date" name="from"
                    value="{{ request('from', $period['from']->toDateString()) }}">
                <span>đến</span>
                <input class="form-control" aria-label="Đến ngày" type="date" name="to"
                    value="{{ request('to', $period['to']->toDateString()) }}">
            </div>
            <button class="btn btn-primary" type="submit"><i data-lucide="calendar-range"></i><span>Xem</span></button>
        </form>
    </x-ui.page-header>

    <section class="stat-grid" aria-label="Chỉ số vận hành">
        <x-ui.stat-card label="Doanh thu" :value="number_format($kpis['revenue']) . ' đ'" icon="banknote" tone="teal" :meta="$revenueMeta" />
        <x-ui.stat-card label="Đơn hàng" :value="number_format($kpis['orders'])" icon="clipboard-list" tone="blue" :meta="$kpis['orders_pending'] .
            ' chờ · ' .
            $kpis['orders_preparing'] .
            ' đang làm · ' .
            $kpis['orders_completed'] .
            ' hoàn thành'" />
        <x-ui.stat-card label="Bàn đang phục vụ" :value="$kpis['tables_occupied'] . ' / ' . $kpis['tables_total']" icon="layout-grid" tone="amber" :meta="$kpis['tables_available'] . ' bàn đang sẵn sàng'" />
        <x-ui.stat-card label="Thanh toán" :value="number_format($kpis['payments_paid'])" icon="wallet-cards" tone="violet" :meta="$kpis['payments_pending'] . ' giao dịch chờ xác nhận'" />
        <x-ui.stat-card label="Cảnh báo kho" :value="number_format($kpis['low_stock'] + $kpis['out_of_stock'])" icon="package-search" tone="rose" :meta="$kpis['low_stock'] . ' sắp hết · ' . $kpis['out_of_stock'] . ' hết hàng'" />
    </section>

    <div class="dashboard-primary">
        <section class="dashboard-panel">
            <div class="section-heading">
                <div>
                    <h2>Hiệu suất theo thời gian</h2>
                    <p>Doanh thu đã thanh toán và số đơn phát sinh trong kỳ.</p>
                </div>
                <div class="segmented" aria-label="Chỉ số đang hiển thị"><span class="active">Doanh thu</span><span>Đơn
                        hàng</span></div>
            </div>
            <div class="chart-frame">
                <canvas id="performance-chart" aria-label="Biểu đồ doanh thu và đơn hàng"></canvas>
                <div class="chart-empty" data-chart-empty="performance">Chưa có dữ liệu trong khoảng thời gian này.</div>
            </div>
        </section>

        <section class="dashboard-panel">
            <div class="section-heading">
                <div>
                    <h2>Trạng thái đơn hàng</h2>
                    <p>Phân bổ các trạng thái trong kỳ.</p>
                </div><i data-lucide="chart-pie"></i>
            </div>
            <div class="chart-frame chart-frame--small">
                <canvas id="status-chart" aria-label="Biểu đồ trạng thái đơn hàng"></canvas>
                <div class="chart-empty" data-chart-empty="status">Chưa có đơn hàng trong kỳ.</div>
            </div>
        </section>
    </div>

    <div class="dashboard-details">
        <section class="dashboard-panel">
            <div class="section-heading">
                <div>
                    <h2>Đơn hàng gần đây</h2>
                    <p>Các đơn mới nhất trong kỳ đã chọn.</p>
                </div>
                @can('orders.view')
                    <a class="dashboard-panel-link" href="{{ route('orders.index') }}">Xem tất cả<i
                            data-lucide="arrow-right"></i></a>
                @endcan
            </div>
            <div class="dashboard-list">
                @forelse ($recentOrders as $order)
                    <div class="dashboard-list-row">
                        <div class="dashboard-list-copy">
                            <strong>{{ $order->order_code }}</strong><span>{{ $order->tableSession->restaurantTable->name }}
                                · {{ $order->created_at->format('H:i d/m') }}</span>
                        </div>
                        <div class="dashboard-list-value"><x-ui.status-badge
                                :status="$order->status" /><span>{{ number_format($order->total) }} đ</span></div>
                    </div>
                @empty
                    <x-ui.empty-state icon="clipboard-list" title="Chưa có đơn hàng"
                        description="Đơn phát sinh trong kỳ sẽ xuất hiện tại đây." />
                @endforelse
            </div>
        </section>

        <section class="dashboard-panel">
            <div class="section-heading">
                <div>
                    <h2>Món bán chạy</h2>
                    <p>Xếp theo số lượng món không bị hủy.</p>
                </div><i data-lucide="ranking"></i>
            </div>
            <div class="dashboard-list">
                @forelse ($topProducts as $item)
                    <div class="dashboard-list-row">
                        <div class="dashboard-list-copy">
                            <strong>{{ $item->product_name }}</strong><span>{{ $item->category_name ?: 'Không còn danh mục' }}</span>
                        </div>
                        <div class="dashboard-list-value">
                            <strong>{{ number_format($item->quantity) }}</strong><span>{{ number_format($item->revenue) }}
                                đ</span>
                        </div>
                    </div>
                @empty
                    <x-ui.empty-state icon="utensils" title="Chưa có dữ liệu bán"
                        description="Dữ liệu sẽ được tổng hợp khi có đơn trong kỳ." />
                @endforelse
            </div>
        </section>

        <section class="dashboard-panel">
            <div class="section-heading">
                <div>
                    <h2>Cảnh báo tồn kho</h2>
                    <p>Nguyên liệu bằng hoặc dưới mức tối thiểu.</p>
                </div>
                @can('inventory.view')
                    <a class="dashboard-panel-link" href="{{ route('inventory.index', ['low' => 1]) }}">Kiểm tra kho<i
                            data-lucide="arrow-right"></i></a>
                @endcan
            </div>
            <div class="dashboard-list">
                @forelse ($lowStockItems as $item)
                    <div class="dashboard-list-row">
                        <div class="dashboard-list-copy">
                            <strong>{{ $item->name }}</strong><span>{{ $item->sku }}</span>
                        </div>
                        <div class="dashboard-list-value"><x-ui.status-badge
                                :status="$item->current_stock <= 0 ? 'out' : 'low'" /><span>{{ number_format($item->current_stock, 3) }}
                                {{ $item->unit }}</span></div>
                    </div>
                @empty
                    <x-ui.empty-state icon="package-check" title="Tồn kho ổn định"
                        description="Không có nguyên liệu nào dưới mức cảnh báo." />
                @endforelse
            </div>
        </section>
    </div>

    <script type="application/json" id="dashboard-chart-data">@json($charts)</script>
@endsection
