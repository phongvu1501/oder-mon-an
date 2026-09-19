@extends('layouts.admin')

@section('title', 'Tổng quan')

@section('content')

<style>
    .dashboard-page {
        width: 100%;
    }

    /* =========================
       FILTER BAR
    ========================= */
    .dashboard-period {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .dashboard-period .form-select,
    .dashboard-period .form-control {
        min-height: 40px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background-color: #fff;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
    }

    .dashboard-period .form-select {
        min-width: 160px;
        padding: 8px 36px 8px 12px;
    }

    .dashboard-period .btn {
        min-height: 40px;
        border-radius: 10px;
        padding: 8px 18px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-weight: 500;
    }

    .custom-period {
        display: none;
        align-items: center;
        gap: 8px;
    }

    .custom-period.is-visible {
        display: flex;
    }

    .custom-period > span {
        color: #64748b;
        font-size: 14px;
        white-space: nowrap;
    }

    /* =========================
       KPI GRID & STAT CARDS (REDESIGNED)
    ========================= */
    .stat-grid--dashboard {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }

    .kpi-card {
        background: #ffffff;
        border: 1px solid #e8edf3;
        border-radius: 16px;
        padding: 18px 20px;
        box-shadow: 0 4px 18px rgba(15, 23, 42, 0.03);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(15, 23, 42, 0.06);
    }

    .kpi-card__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 12px;
    }

    .kpi-card__label {
        font-size: 13px;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .kpi-card__icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .kpi-card__icon i, 
    .kpi-card__icon svg {
        width: 20px;
        height: 20px;
    }

    /* Color Tones */
    .tone-teal .kpi-card__icon { background: #ccfbf1; color: #0d9488; }
    .tone-blue .kpi-card__icon { background: #dbeafe; color: #2563eb; }
    .tone-amber .kpi-card__icon { background: #fef3c7; color: #d97706; }
    .tone-green .kpi-card__icon { background: #dcfce7; color: #16a34a; }
    .tone-rose .kpi-card__icon { background: #ffe4e6; color: #e11d48; }

    .kpi-card__value {
        font-size: 22px;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.2;
        margin-bottom: 8px;
    }

    .kpi-card__meta {
        font-size: 12px;
        color: #64748b;
        line-height: 1.4;
        border-top: 1px solid #f1f5f9;
        padding-top: 8px;
        margin-top: auto;
    }

    .kpi-card__meta .badge-status {
        display: inline-block;
        font-weight: 600;
    }

    .text-success { color: #16a34a; }
    .text-danger { color: #dc2626; }

    /* =========================
       CHART PANELS
    ========================= */
    .dashboard-charts {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 20px;
        margin-bottom: 24px;
    }

    .chart-panel {
        min-width: 0;
        background: #fff;
        border: 1px solid #e8edf3;
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 4px 18px rgba(15, 23, 42, 0.03);
    }

    .section-heading {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 18px;
    }

    .section-heading h2 {
        margin: 0 0 4px;
        color: #1e293b;
        font-size: 16px;
        font-weight: 650;
    }

    .section-heading p {
        margin: 0;
        color: #94a3b8;
        font-size: 13px;
    }

    .section-heading i, 
    .section-heading svg {
        width: 20px;
        height: 20px;
        color: #94a3b8;
    }

    .chart-frame {
        position: relative;
        width: 100%;
        height: 280px;
    }

    .chart-empty {
        position: absolute;
        inset: 0;
        display: none;
        align-items: center;
        justify-content: center;
        text-align: center;
        color: #94a3b8;
        font-size: 14px;
        background: rgba(255, 255, 255, 0.95);
        border-radius: 12px;
        z-index: 5;
    }

    .chart-empty.is-visible {
        display: flex;
    }

    /* RESPONSIVE */
    @media (max-width: 1300px) {
        .stat-grid--dashboard { grid-template-columns: repeat(3, 1fr); }
    }
    @media (max-width: 900px) {
        .dashboard-charts { grid-template-columns: 1fr; }
        .stat-grid--dashboard { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 550px) {
        .stat-grid--dashboard { grid-template-columns: 1fr; }
    }
</style>

<div class="dashboard-page">

    {{-- HEADER & FILTER --}}
    <x-ui.page-header
        title="Tổng quan vận hành"
        :subtitle="'Dữ liệu ' . mb_strtolower($period['label']) . ', cập nhật lúc ' . now()->format('H:i')"
        eyebrow="Dashboard"
    >
        <form class="dashboard-period" method="get">
            <label class="visually-hidden" for="dashboard-period">Khoảng thời gian</label>
            <select class="form-select" id="dashboard-period" name="period" data-period-select>
                @foreach ([
                    'today' => 'Hôm nay',
                    'yesterday' => 'Hôm qua',
                    '7days' => '7 ngày',
                    '30days' => '30 ngày',
                    'month' => 'Tháng này',
                    'last_month' => 'Tháng trước',
                    'quarter' => 'Quý này',
                    'year' => 'Năm nay',
                    'custom' => 'Tùy chọn'
                ] as $value =>$label)
                    <option value="{{ $value }}" @selected($period['preset'] ===$value)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>

            <div class="custom-period {{ $period['preset'] === 'custom' ? 'is-visible' : '' }}" data-custom-period>
                <input class="form-control" type="date" name="from" value="{{ request('from', $period['from']->toDateString()) }}">
                <span>đến</span>
                <input class="form-control" type="date" name="to" value="{{ request('to', $period['to']->toDateString()) }}">
            </div>

            <button class="btn btn-primary" type="submit">
                <i data-lucide="calendar-range"></i> Xem
            </button>
        </form>
    </x-ui.page-header>

    {{-- DETAILED KPI CARDS --}}
    <div class="stat-grid stat-grid--dashboard">

        <!-- Doanh thu -->
        <div class="kpi-card tone-teal">
            <div class="kpi-card__header">
                <span class="kpi-card__label">Doanh thu</span>
                <div class="kpi-card__icon">
                    <i data-lucide="banknote"></i>
                </div>
            </div>
            <div class="kpi-card__value">{{ number_format($kpis['revenue']) }} đ</div>
            <div class="kpi-card__meta">
                @if($kpis['revenue_change'] === null)
                    <span class="text-muted">Chưa có kỳ so sánh</span>
                @else
                    <span class="{{ $kpis['revenue_change'] >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ $kpis['revenue_change'] >= 0 ? '+' : '' }}{{$kpis['revenue_change'] }}%
                    </span> so với kỳ trước
                @endif
            </div>
        </div>

        <!-- Đơn hàng -->
        <div class="kpi-card tone-blue">
            <div class="kpi-card__header">
                <span class="kpi-card__label">Đơn hàng</span>
                <div class="kpi-card__icon">
                    <i data-lucide="clipboard-list"></i>
                </div>
            </div>
            <div class="kpi-card__value">{{ number_format($kpis['orders']) }}</div>
            <div class="kpi-card__meta">
                <strong>{{ $kpis['orders_pending'] }}</strong> chờ · 
                <strong>{{ $kpis['orders_preparing'] }}</strong> làm · 
                <strong>{{ $kpis['orders_completed'] }}</strong> xong
            </div>
        </div>

        <!-- Bàn phục vụ -->
        <div class="kpi-card tone-amber">
            <div class="kpi-card__header">
                <span class="kpi-card__label">Bàn phục vụ</span>
                <div class="kpi-card__icon">
                    <i data-lucide="layout-grid"></i>
                </div>
            </div>
            <div class="kpi-card__value">{{ $kpis['tables_occupied'] }} / {{$kpis['tables_total'] }}</div>
            <div class="kpi-card__meta">
                <span class="text-success">● {{ $kpis['tables_available'] }}</span> bàn đang sẵn sàng
            </div>
        </div>

        <!-- Thanh toán -->
        <div class="kpi-card tone-green">
            <div class="kpi-card__header">
                <span class="kpi-card__label">Thanh toán</span>
                <div class="kpi-card__icon">
                    <i data-lucide="wallet-cards"></i>
                </div>
            </div>
            <div class="kpi-card__value">{{ number_format($kpis['payments_paid']) }}</div>
            <div class="kpi-card__meta">
                <strong>{{ $kpis['payments_pending'] }}</strong> giao dịch chờ xử lý
            </div>
        </div>

        <!-- Cảnh báo kho -->
        <div class="kpi-card tone-rose">
            <div class="kpi-card__header">
                <span class="kpi-card__label">Cảnh báo kho</span>
                <div class="kpi-card__icon">
                    <i data-lucide="package-search"></i>
                </div>
            </div>
            <div class="kpi-card__value">{{ number_format($kpis['low_stock'] +$kpis['out_of_stock']) }}</div>
            <div class="kpi-card__meta">
                <span class="text-danger">{{ $kpis['low_stock'] }} sắp hết</span> · 
                <span class="text-danger">{{ $kpis['out_of_stock'] }} hết hàng</span>
            </div>
        </div>

    </div>

    {{-- CHARTS GRID --}}
    <div class="dashboard-charts">

        <section class="chart-panel">
            <div class="section-heading">
                <div>
                    <h2>Doanh thu theo ngày</h2>
                    <p>Tổng doanh thu các hóa đơn hoàn thành.</p>
                </div>
                <i data-lucide="chart-no-axes-combined"></i>
            </div>
            <div class="chart-frame">
                <canvas id="revenue-chart"></canvas>
                <div class="chart-empty" data-chart-empty="revenue">Chưa có dữ liệu doanh thu.</div>
            </div>
        </section>

        <section class="chart-panel">
            <div class="section-heading">
                <div>
                    <h2>Trạng thái order</h2>
                    <p>Phân bổ trạng thái trong kỳ.</p>
                </div>
                <i data-lucide="chart-pie"></i>
            </div>
            <div class="chart-frame">
                <canvas id="status-chart"></canvas>
                <div class="chart-empty" data-chart-empty="status">Chưa có dữ liệu order.</div>
            </div>
        </section>

        <section class="chart-panel">
            <div class="section-heading">
                <div>
                    <h2>Số order theo ngày</h2>
                    <p>Khối lượng order phục vụ theo ngày.</p>
                </div>
                <i data-lucide="chart-column"></i>
            </div>
            <div class="chart-frame">
                <canvas id="orders-chart"></canvas>
                <div class="chart-empty" data-chart-empty="orders">Chưa có dữ liệu order.</div>
            </div>
        </section>

        <section class="chart-panel">
            <div class="section-heading">
                <div>
                    <h2>Món bán chạy</h2>
                    <p>Top món bán chạy nhất trong kỳ.</p>
                </div>
                <i data-lucide="ranking"></i>
            </div>
            <div class="chart-frame">
                <canvas id="products-chart"></canvas>
                <div class="chart-empty" data-chart-empty="products">Chưa có dữ liệu món bán.</div>
            </div>
        </section>

    </div>

    <script type="application/json" id="dashboard-chart-data">
        @json($charts)
    </script>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Lucide Icon init (nếu chưa render)
    if (window.lucide) {
        lucide.createIcons();
    }

    // Dynamic Filter Selector
    const periodSelect = document.querySelector('[data-period-select]');
    const customPeriod = document.querySelector('[data-custom-period]');
    if (periodSelect && customPeriod) {
        periodSelect.addEventListener('change', function () {
            customPeriod.classList.toggle('is-visible', this.value === 'custom');
        });
    }

    // Chart.js Processing
    const rawData = document.getElementById('dashboard-chart-data')?.textContent;
    if (!rawData) return;
    
    let chartData = {};
    try { chartData = JSON.parse(rawData); } catch (e) { return; }

    const showEmpty = (key) => {
        document.querySelector(`[data-chart-empty="${key}"]`)?.classList.add('is-visible');
    };

    // 1. Revenue Line Chart
    const revCanvas = document.getElementById('revenue-chart');
    if (revCanvas && chartData.revenue?.labels?.length && chartData.revenue.values?.some(v => v > 0)) {
        new Chart(revCanvas, {
            type: 'line',
            data: {
                labels: chartData.revenue.labels,
                datasets: [{
                    label: 'Doanh thu',
                    data: chartData.revenue.values,
                    borderColor: '#0d9488',
                    backgroundColor: 'rgba(13, 148, 136, 0.08)',
                    fill: true,
                    tension: 0.3,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });
    } else { showEmpty('revenue'); }

    // 2. Status Doughnut Chart
    const statusCanvas = document.getElementById('status-chart');
    if (statusCanvas && chartData.status?.labels?.length && chartData.status.values?.some(v => v > 0)) {
        new Chart(statusCanvas, {
            type: 'doughnut',
            data: {
                labels: chartData.status.labels,
                datasets: [{
                    data: chartData.status.values,
                    backgroundColor: ['#f59e0b', '#3b82f6', '#10b981', '#ef4444', '#8b5cf6']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    } else { showEmpty('status'); }

    // 3. Orders Bar Chart
    const ordersCanvas = document.getElementById('orders-chart');
    if (ordersCanvas && chartData.orders?.labels?.length && chartData.orders.values?.some(v => v > 0)) {
        new Chart(ordersCanvas, {
            type: 'bar',
            data: {
                labels: chartData.orders.labels,
                datasets: [{
                    data: chartData.orders.values,
                    backgroundColor: '#3b82f6',
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });
    } else { showEmpty('orders'); }

    // 4. Products Horizontal Bar Chart
    const productsCanvas = document.getElementById('products-chart');
    if (productsCanvas && chartData.products?.labels?.length && chartData.products.values?.some(v => v > 0)) {
        new Chart(productsCanvas, {
            type: 'bar',
            data: {
                labels: chartData.products.labels,
                datasets: [{
                    data: chartData.products.values,
                    backgroundColor: '#10b981',
                    borderRadius: 6
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { x: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });
    } else { showEmpty('products'); }
});
</script>

@endsection