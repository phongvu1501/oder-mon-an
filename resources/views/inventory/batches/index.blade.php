@extends('layouts.admin')
@section('title', 'Lô hàng & hạn sử dụng')
@section('content')
    <x-ui.page-header title="Lô hàng & hạn sử dụng" eyebrow="Kho" />
    <form method="get"><x-ui.filter-bar :reset-url="route('inventory.batches.index')"><input class="form-control" name="q"
                value="{{ request('q') }}" placeholder="Mã lô hoặc mặt hàng"><select class="form-select" name="item_id">
                <option value="">Mọi mặt hàng</option>
                @foreach ($items as $item)
                    <option value="{{ $item->id }}" @selected(request('item_id') == $item->id)>{{ $item->name }}</option>
                @endforeach
            </select>
            <select class="form-select" name="expiry">
                <option value="">Mọi hạn dùng</option>
                <option value="near" @selected(request('expiry') === 'near')>Sắp hết hạn</option>
                <option value="expired" @selected(request('expiry') === 'expired')>Đã hết hạn</option>
            </select></x-ui.filter-bar></form>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Mã lô</th>
                    <th>Mặt hàng</th>
                    <th>Kho</th>
                    <th>Ban đầu</th>
                    <th>Còn lại</th>
                    <th>Giá lô</th>
                    <th>HSD</th>
                    <th>Trạng thái</th>
                </tr>
            </thead>
            <tbody>
                @forelse($batches as $batch)
                    <tr>
                        <td>{{ $batches->firstItem() + $loop->index }}</td>
                        <td class="cell-title">{{ $batch->batch_code }}</td>
                        <td>{{ $batch->inventoryItem->name }}</td>
                        <td>{{ $batch->warehouse->name }}</td>
                        <td>{{ number_format($batch->initial_quantity, 3) }}</td>
                        <td>{{ number_format($batch->remaining_quantity, 3) }}</td>
                        <td>{{ number_format($batch->unit_cost) }} đ</td>
                        <td>{{ $batch->expiry_date?->format('d/m/Y') ?? '—' }} @if ($batch->expiry_date)
                                <x-ui.status-badge :status="$batch->expiry_date->isPast()
                                    ? 'out'
                                    : ($batch->expiry_date->lte(
                                        now()->addDays(config('restaurant.inventory.near_expiry_days')),
                                    )
                                        ? 'low'
                                        : 'active')" />
                            @endif
                        </td>
                        <td>{{ ucfirst($batch->status) }}</td>
                    </tr>
                @empty<tr>
                        <td colspan="9"><x-ui.empty-state icon="package-open" title="Chưa có lô hàng" /></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $batches->links() }}</div>
@endsection
