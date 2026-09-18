@extends('layouts.admin') @section('title', 'Tổng quan') @section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Tổng quan vận hành</h1>
        <p class="page-subtitle">Số liệu cập nhật từ order, hóa đơn và kho.</p>
    </div>
</div>
<div class="metric-grid">
    @foreach ([['Doanh thu hôm nay', number_format($revenueToday) . ' đ'], ['Order hôm nay', $ordersToday], ['Bàn đang dùng', $occupiedTables], ['Bill chưa thanh toán', $unpaidBills], ['Order đang xử lý', $processingOrders]] as [$label, $value])
        <div class="card metric">
            <div class="metric-label">{{ $label }}</div>
            <div class="metric-value">{{ $value }}</div>
        </div>
    @endforeach
</div>
<div class="content-grid">
    <section class="panel">
        <h2 class="section-title">Order gần đây <a class="btn btn-secondary" href="{{ route('orders.index') }}">Xem tất
                cả</a></h2>
        @if ($recentOrders->isEmpty())
        <div class="empty">Chưa có order.</div>@else<div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Mã</th>
                            <th>Bàn</th>
                            <th>Món</th>
                            <th>Tổng</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentOrders as $order)
                            <tr>
                                <td><a class="cell-title"
                                        href="{{ route('orders.show', $order) }}">{{ $order->order_code }}</a></td>
                                <td>{{ $order->tableSession->restaurantTable->name }}</td>
                                <td>{{ $order->items->sum('quantity') }}</td>
                                <td>{{ number_format($order->total) }} đ</td>
                                <td><span
                                        class="badge badge-{{ $order->status->value }}">{{ $order->status->value }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
    <aside class="panel">
        <h2 class="section-title">Sắp hết hàng</h2>
        @if ($lowStock->isEmpty())
        <div class="empty">Tồn kho đang ổn định.</div>@else<ul class="stat-list">
                @foreach ($lowStock as $item)
                    <li><span>{{ $item->name }}</span><strong>{{ $item->current_stock }}
                            {{ $item->unit }}</strong></li>
                @endforeach
            </ul>
        @endif
    </aside>
</div>
@endsection
