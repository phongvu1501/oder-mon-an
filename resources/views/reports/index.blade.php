@extends('layouts.admin')

@section('title', 'Báo cáo')

@section('content')
    <x-ui.page-header title="Báo cáo kinh doanh" subtitle="Doanh thu chỉ tính từ hóa đơn đã thanh toán trong kỳ."
        eyebrow="Phân tích" />
    <form method="get"><x-ui.filter-bar :reset-url="route('reports.index')" title="Khoảng báo cáo"><label><span class="form-label">Từ
                    ngày</span><input class="form-control" type="date" name="from"
                    value="{{ $from->toDateString() }}"></label><label><span class="form-label">Đến ngày</span><input
                    class="form-control" type="date" name="to"
                    value="{{ $to->toDateString() }}"></label></x-ui.filter-bar></form>
    <section class="metric-grid" aria-label="Tổng hợp doanh thu">
        <article class="card metric">
            <div class="metric-label">Tổng doanh thu</div>
            <div class="metric-value">{{ number_format($revenue) }} đ</div>
            <div class="help">{{ $from->format('d/m/Y') }} – {{ $to->format('d/m/Y') }}</div>
        </article>
        @foreach ($payments as $payment)
            <article class="card metric">
                <div class="metric-label">
                    {{ ['cash' => 'Tiền mặt', 'bank_transfer' => 'Chuyển khoản', 'qr' => 'QR'][$payment->method] ?? $payment->method }}
                </div>
                <div class="metric-value">{{ number_format($payment->total) }} đ</div>
                <div class="help">{{ number_format($payment->count) }} giao dịch</div>
            </article>
        @endforeach
    </section>
    <section class="panel">
        <div class="section-heading">
            <div>
                <h2>Món bán chạy</h2>
                <p>Xếp theo số lượng món thuộc hóa đơn đã thanh toán.</p>
            </div><i data-lucide="ranking"></i>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Hạng</th>
                        <th>Món</th>
                        <th>Số lượng</th>
                        <th>Doanh thu</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($popular as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td class="cell-title">{{ $item->product_name }}</td>
                            <td>{{ number_format($item->quantity) }}</td>
                            <td><strong>{{ number_format($item->revenue) }} đ</strong></td>
                    </tr>@empty<tr>
                            <td colspan="4"><x-ui.empty-state icon="chart-no-axes-combined"
                                    title="Chưa có dữ liệu trong kỳ" description="Thử chọn khoảng thời gian khác." /></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
