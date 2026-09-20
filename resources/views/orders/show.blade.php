@extends('layouts.admin')

@section('title', $order->order_code)

@section('content')
    @php
        $nextStatuses = match ($order->status->value) {
            'pending' => ['confirmed', 'cancelled'],
            'confirmed' => ['preparing', 'cancelled'],
            'preparing' => ['ready', 'cancelled'],
            'ready' => ['served'],
            'served' => ['completed'],
            default => [],
        };
        $statusLabels = [
            'confirmed' => 'Xác nhận đơn',
            'preparing' => 'Bắt đầu chuẩn bị',
            'ready' => 'Báo sẵn sàng',
            'served' => 'Đã phục vụ',
            'completed' => 'Hoàn thành',
            'cancelled' => 'Hủy đơn',
        ];
    @endphp
    <x-ui.page-header :title="$order->order_code" :subtitle="$order->tableSession->restaurantTable->name . ' · ' . $order->created_at->format('d/m/Y H:i')" eyebrow="Chi tiết đơn hàng">
        <x-ui.status-badge :status="$order->status" /><a class="btn btn-secondary" href="{{ route('orders.index') }}"><i
                data-lucide="arrow-left"></i>Quay lại</a>
    </x-ui.page-header>
    <div class="content-grid">
        <section class="panel">
            <div class="section-heading">
                <div>
                    <h2>Chi tiết món</h2>
                    <p>{{ $order->items->sum('quantity') }} món trong đơn.</p>
                </div><i data-lucide="utensils"></i>
            </div>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Món</th>
                            <th>SL</th>
                            <th>Đơn giá</th>
                            <th>Thành tiền</th>
                            <th>Ghi chú</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($order->items as $item)
                            <tr>
                                <td class="cell-title">{{ $item->product_name }}</td>
                                <td>{{ $item->quantity }}</td>
                                <td>{{ number_format($item->unit_price) }} đ</td>
                                <td><strong>{{ number_format($item->subtotal) }} đ</strong></td>
                                <td>{{ $item->note ?: '—' }}</td>
                                <td><x-ui.status-badge :status="$item->status" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($order->customer_note)
                <div class="alert alert-success" style="margin-top:14px"><strong>Ghi chú chung:</strong>
                    {{ $order->customer_note }}</div>
            @endif
            <div class="bill-total"><span>Tổng đơn</span><span>{{ number_format($order->total) }} đ</span></div>
        </section>
        <aside class="panel">
            <div class="section-heading">
                <div>
                    <h2>Cập nhật trạng thái</h2>
                    <p>Chỉ các bước hợp lệ tiếp theo được hiển thị.</p>
                </div><i data-lucide="list-checks"></i>
            </div>
            @can('orders.update')
                @forelse ($nextStatuses as $status)
                    <form method="post" action="{{ route('orders.status', $order) }}"
                        @if ($status === 'cancelled') data-confirm="Hủy đơn này? Kho đã trừ sẽ không tự động hoàn." @endif
                        style="margin-bottom:10px">
                        @csrf @method('patch')<input type="hidden" name="status" value="{{ $status }}">
                        @if ($status === 'cancelled')
                            <label class="form-label" for="cancel-reason">Lý do hủy</label><input class="form-control"
                                id="cancel-reason" name="reason" required maxlength="500" placeholder="Nhập lý do hủy"
                                style="margin-bottom:8px">
                        @endif
                        <button class="btn {{ $status === 'cancelled' ? 'btn-danger' : 'btn-primary' }} btn-block"
                            type="submit"><i
                                data-lucide="{{ $status === 'cancelled' ? 'ban' : 'arrow-right' }}"></i>{{ $statusLabels[$status] }}</button>
                    </form>
                @empty
                    <x-ui.empty-state icon="circle-check" title="Đơn đã kết thúc"
                        description="Không còn bước xử lý nào cho đơn này." />
                @endforelse
            @else
                <x-ui.empty-state icon="lock-keyhole" title="Chỉ có quyền xem"
                    description="Tài khoản của bạn không thể đổi trạng thái đơn." />
            @endcan
        </aside>
    </div>
@endsection
