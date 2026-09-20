@extends('layouts.admin')

@section('title', 'Đơn hàng')

@section('content')
    <x-ui.page-header title="Đơn hàng" subtitle="Theo dõi toàn bộ vòng đời từ khi khách gửi món đến lúc hoàn thành."
        eyebrow="Vận hành" />
    <form method="get"><x-ui.filter-bar :reset-url="route('orders.index')">
            <input class="form-control" name="q" value="{{ request('q') }}" placeholder="Mã đơn, tên hoặc mã bàn"
                aria-label="Tìm đơn hàng">
            <select class="form-select" name="status" aria-label="Lọc trạng thái">
                <option value="">Mọi trạng thái</option>
                @foreach (['pending' => 'Đang chờ', 'confirmed' => 'Đã xác nhận', 'preparing' => 'Đang chuẩn bị', 'ready' => 'Sẵn sàng', 'served' => 'Đã phục vụ', 'completed' => 'Hoàn thành', 'cancelled' => 'Đã hủy'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <input class="form-control" type="date" name="date" value="{{ request('date') }}"
                aria-label="Lọc theo ngày">
        </x-ui.filter-bar></form>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Đơn hàng</th>
                    <th>Bàn</th>
                    <th>Số món</th>
                    <th>Tổng tiền</th>
                    <th>Trạng thái</th>
                    <th>Thời gian</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                    <tr>
                        <td>{{ $orders->firstItem() + $loop->index }}</td>
                        <td>
                            <div class="cell-title">{{ $order->order_code }}</div>
                            <div class="cell-meta">{{ $order->tableSession->session_code }}</div>
                        </td>
                        <td>{{ $order->tableSession->restaurantTable->name }}</td>
                        <td>{{ $order->items->sum('quantity') }}</td>
                        <td><strong>{{ number_format($order->total) }} đ</strong></td>
                        <td><x-ui.status-badge :status="$order->status" /></td>
                        <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                        <td><a class="btn btn-secondary icon-btn" href="{{ route('orders.show', $order) }}"
                                title="Xem chi tiết" aria-label="Xem {{ $order->order_code }}"><i
                                    data-lucide="eye"></i></a></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8"><x-ui.empty-state icon="clipboard-list" title="Chưa có đơn hàng"
                                description="Thử thay đổi bộ lọc hoặc chờ khách gửi món mới." /></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $orders->links() }}</div>
@endsection
