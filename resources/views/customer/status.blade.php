@extends('layouts.customer')

@section('title', 'Đơn gọi món')
@section('table-label', $tableSession->restaurantTable->name . ' · ' . $tableSession->session_code)

@section('content')
    @php
        $latestOrder = $tableSession->orders->first();
        $progress = ['pending' => 0, 'confirmed' => 1, 'preparing' => 2, 'ready' => 3, 'served' => 4, 'completed' => 4];
        $currentStep = $latestOrder ? $progress[$latestOrder->status->value] ?? -1 : -1;
        $steps = [
            ['Đã nhận đơn', 'clipboard-check'],
            ['Đã xác nhận', 'badge-check'],
            ['Đang chuẩn bị', 'cooking-pot'],
            ['Sẵn sàng', 'bell-ring'],
            ['Đã phục vụ', 'circle-check'],
        ];
    @endphp

    <section class="status-hero">
        <div class="status-hero-top">
            <div>
                <h1>Đơn của {{ $tableSession->restaurantTable->name }}</h1>
                <p>Cập nhật lúc {{ now()->format('H:i') }} · Mã phiên {{ $tableSession->session_code }}</p>
            </div>
            <div class="actions">
                <a class="btn btn-secondary" href="{{ route('menu.show', $tableSession->restaurantTable->qr_token) }}"><i
                        data-lucide="plus"></i>Gọi thêm món</a>
                <button class="btn btn-secondary icon-btn" type="button" onclick="window.location.reload()"
                    title="Làm mới trạng thái" aria-label="Làm mới trạng thái"><i data-lucide="refresh-cw"></i></button>
            </div>
        </div>
        @if ($latestOrder && $latestOrder->status->value !== 'cancelled')
            <div class="order-timeline" aria-label="Tiến độ đơn hàng gần nhất">
                @foreach ($steps as $index => [$label, $icon])
                    <div
                        class="timeline-step {{ $index < $currentStep ? 'is-done' : ($index === $currentStep ? 'is-current' : '') }}">
                        <span class="timeline-dot"><i
                                data-lucide="{{ $index < $currentStep ? 'check' : $icon }}"></i></span><span>{{ $label }}</span>
                    </div>
                @endforeach
            </div>
        @elseif ($latestOrder)
            <div class="alert alert-error" style="margin:18px 0 0">Đơn gần nhất đã bị hủy. Vui lòng gọi lại món hoặc liên hệ
                nhân viên.</div>
        @endif
    </section>

    <div class="customer-status-grid">
        <section>
            @forelse ($tableSession->orders as $order)
                <article class="customer-order-card">
                    <header class="customer-order-head">
                        <div>
                            <strong>{{ $order->order_code }}</strong><small>{{ $order->created_at->format('H:i · d/m/Y') }}</small>
                        </div><x-ui.status-badge :status="$order->status" />
                    </header>
                    <ul class="stat-list">
                        @foreach ($order->items as $item)
                            <li><span>{{ $item->product_name }} × {{ $item->quantity }} @if ($item->note)
                                        <small class="cell-meta">· {{ $item->note }}</small>
                                    @endif
                                </span><strong>{{ number_format($item->subtotal) }} đ</strong></li>
                        @endforeach
                    </ul>
                    @if ($order->customer_note)
                        <p class="help"><strong>Ghi chú:</strong> {{ $order->customer_note }}</p>
                    @endif
                </article>
            @empty
                <section class="panel"><x-ui.empty-state icon="clipboard-list" title="Chưa có đơn gọi món"
                        description="Chọn món từ thực đơn để gửi yêu cầu tới bếp."><a class="btn btn-primary"
                            href="{{ route('menu.show', $tableSession->restaurantTable->qr_token) }}">Xem thực
                            đơn</a></x-ui.empty-state></section>
            @endforelse
        </section>

        <aside class="panel payment-card">
            <div class="section-heading">
                <div>
                    <h2>Hóa đơn hiện tại</h2>
                    <p>{{ $tableSession->bill->bill_code }}</p>
                </div><x-ui.status-badge :status="$tableSession->bill->status" />
            </div>
            <div class="dashboard-list-row"><span>Tạm tính</span><strong>{{ number_format($tableSession->bill->subtotal) }}
                    đ</strong></div>
            @if ((float) $tableSession->bill->discount > 0)
                <div class="dashboard-list-row"><span>Giảm
                        giá</span><strong>-{{ number_format($tableSession->bill->discount) }} đ</strong></div>
            @endif
            @if ((float) $tableSession->bill->tax > 0)
                <div class="dashboard-list-row"><span>Thuế</span><strong>{{ number_format($tableSession->bill->tax) }}
                        đ</strong></div>
            @endif
            <div class="bill-total"><span>Tổng cộng</span><span>{{ number_format($tableSession->bill->total) }} đ</span>
            </div>
            <p class="help">Đã thanh toán: <strong>{{ number_format($tableSession->bill->paid_amount) }} đ</strong></p>
            @if (
                $tableSession->status->value === 'active' &&
                    $tableSession->bill->status->value !== 'paid' &&
                    $tableSession->bill->total > 0)
                <form method="post" action="{{ route('menu.payment.store') }}" style="margin-top:16px">@csrf<input
                        type="hidden" name="request_id" value="{{ Str::uuid() }}"><button
                        class="btn btn-primary btn-block" type="submit"><i data-lucide="qr-code"></i>Thanh toán bằng
                        QR</button></form>
            @endif
        </aside>
    </div>
@endsection
