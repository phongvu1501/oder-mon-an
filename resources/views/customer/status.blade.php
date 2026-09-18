@extends('layouts.customer') @section('title', 'Đơn gọi món') @section('table-label', $tableSession->restaurantTable->name . ' · '
. $tableSession->session_code) @section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Đơn của bàn</h1>
            <p class="page-subtitle">Cập nhật lần cuối {{ now()->format('H:i') }}</p>
        </div><button class="btn btn-secondary" onclick="location.reload()"><i data-lucide="refresh-cw"></i> Làm mới</button>
    </div>
    @forelse($tableSession->orders as $order)
        <article class="order-card">
            <div class="section-title"><span>{{ $order->order_code }}</span><span
                    class="badge badge-{{ $order->status->value }}">{{ $order->status->value }}</span></div>
            <ul class="stat-list">
                @foreach ($order->items as $item)
                    <li><span>{{ $item->product_name }} ×
                            {{ $item->quantity }}</span><strong>{{ number_format($item->subtotal) }} đ</strong></li>
                @endforeach
            </ul>
    </article>@empty<div class="empty">Chưa có order.</div>
    @endforelse
    <section class="panel">
        <h2 class="section-title">Hóa đơn hiện tại <span
                class="badge badge-{{ $tableSession->bill->status->value }}">{{ $tableSession->bill->status->value }}</span>
        </h2>
        <div class="bill-total"><span>Tổng cộng</span><span>{{ number_format($tableSession->bill->total) }} đ</span></div>
        <div class="cell-meta">Đã thanh toán: {{ number_format($tableSession->bill->paid_amount) }} đ</div>
        @if (
            $tableSession->status->value === 'active' &&
                $tableSession->bill->status->value !== 'paid' &&
                $tableSession->bill->total > 0)
            <form method="post" action="{{ route('menu.payment.store') }}" style="margin-top:16px">@csrf<input
                    type="hidden" name="request_id" value="{{ Str::uuid() }}"><button
                    class="btn btn-primary btn-block"><i data-lucide="qr-code"></i> Thanh toán bằng QR</button></form>
        @endif
    </section>
@endsection
