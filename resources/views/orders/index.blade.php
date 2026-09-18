@extends('layouts.admin') @section('title', 'Order') @section('content')<div class="page-header">
    <div>
        <h1 class="page-title">Order</h1>
        <p class="page-subtitle">Theo dõi từ xác nhận đến phục vụ.</p>
    </div>
</div>
<form class="filters"><select class="form-select" name="status">
        <option value="">Mọi trạng thái</option>
        @foreach (['pending', 'confirmed', 'preparing', 'ready', 'served', 'completed', 'cancelled'] as $status)
            <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
        @endforeach
    </select>
    <input class="form-control" type="date" name="date" value="{{ request('date') }}"><button
        class="btn btn-secondary">Lọc</button>
</form>
<div class="table-wrap">
    <table class="data-table">
        <thead>
            <tr>
                <th>STT</th>
                <th>Order</th>
                <th>Bàn</th>
                <th>Món</th>
                <th>Tổng</th>
                <th>Trạng thái</th>
                <th>Thời gian</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $order)
                <tr>
                    <td>{{ $orders->firstItem() + $loop->index }}</td>
                    <td class="cell-title">{{ $order->order_code }}</td>
                    <td>{{ $order->tableSession->restaurantTable->name }}</td>
                    <td>{{ $order->items->sum('quantity') }}</td>
                    <td>{{ number_format($order->total) }} đ</td>
                    <td><span class="badge badge-{{ $order->status->value }}">{{ $order->status->value }}</span></td>
                    <td>{{ $order->created_at->format('d/m H:i') }}</td>
                    <td><a class="btn btn-secondary icon-btn" href="{{ route('orders.show', $order) }}"><i
                                data-lucide="eye"></i></a></td>
            </tr>@empty<tr>
                    <td colspan="8" class="empty">Chưa có order.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="pagination">{{ $orders->links() }}</div>@endsection
