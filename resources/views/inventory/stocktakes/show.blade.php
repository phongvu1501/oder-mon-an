@extends('layouts.admin')
@section('title', $stocktake->stocktake_code)
@section('content')
    <x-ui.page-header :title="$stocktake->stocktake_code" subtitle="Kiểm kê kho" eyebrow="Kho"><a class="btn btn-secondary"
            href="{{ route('inventory.stocktakes.index') }}"><i data-lucide="arrow-left"></i>Danh sách</a>
        @if ($stocktake->status->value === 'draft')
            <form method="post" action="{{ route('inventory.stocktakes.start', $stocktake) }}">@csrf<button
                    class="btn btn-primary"><i data-lucide="play"></i>Bắt đầu đếm</button></form>
        @endif
        @if ($stocktake->status->value === 'review')
            @can('inventory.confirm')
                <form method="post" action="{{ route('inventory.stocktakes.confirm', $stocktake) }}">@csrf<button
                        class="btn btn-primary"><i data-lucide="badge-check"></i>Xác nhận</button></form>
            @endcan
        @endif
    </x-ui.page-header>
    <section class="panel" style="margin-bottom:18px">
        <dl class="detail-list">
            <dt>Kho</dt>
            <dd>{{ $stocktake->warehouse->name }}</dd>
            <dt>Ngày</dt>
            <dd>{{ $stocktake->counted_at->format('d/m/Y H:i') }}</dd>
            <dt>Trạng thái</dt>
            <dd><x-ui.status-badge :status="$stocktake->status" /></dd>
            <dt>Người kiểm kê</dt>
            <dd>{{ $stocktake->creator?->name }}</dd>
            <dt>Người duyệt</dt>
            <dd>{{ $stocktake->reviewer?->name ?? '—' }}</dd>
            <dt>Người xác nhận</dt>
            <dd>{{ $stocktake->confirmer?->name ?? '—' }}</dd>
        </dl>
    </section>
    <form method="post" action="{{ route('inventory.stocktakes.counts', $stocktake) }}">@csrf @method('put')
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Mặt hàng</th>
                        <th>Tồn hệ thống</th>
                        <th>Tồn thực tế</th>
                        <th>Chênh lệch</th>
                        <th>Giá vốn</th>
                        <th>Giá trị lệch</th>
                        <th>Lý do</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($stocktake->items as $line)
                        <tr>
                            <td>
                                <div class="cell-title">{{ $line->inventoryItem->name }}</div>
                                <div class="cell-meta">{{ $line->inventoryItem->sku }} · {{ $line->inventoryItem->unit }}
                                </div><input type="hidden" name="items[{{ $loop->index }}][inventory_item_id]"
                                    value="{{ $line->inventory_item_id }}">
                            </td>
                            <td>{{ number_format($line->system_quantity, 3) }}</td>
                            <td>
                                @if (in_array($stocktake->status->value, ['draft', 'counting']))
                                    <input class="form-control" type="number" min="0" step="0.001"
                                        name="items[{{ $loop->index }}][physical_quantity]"
                                        value="{{ old('items.' . $loop->index . '.physical_quantity', $line->physical_quantity) }}"
                                        required>@else{{ number_format($line->physical_quantity, 3) }}
                                @endif
                            </td>
                            <td>{{ $line->difference_quantity === null ? '—' : number_format($line->difference_quantity, 3) }}
                            </td>
                            <td>{{ number_format($line->unit_cost) }} đ</td>
                            <td>{{ $line->difference_value === null ? '—' : number_format($line->difference_value) . ' đ' }}</td>
                            <td>
                                @if (in_array($stocktake->status->value, ['draft', 'counting']))
                                    <input class="form-control" name="items[{{ $loop->index }}][reason]"
                                        value="{{ $line->reason }}" maxlength="255">@else{{ $line->reason ?? '—' }}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if (in_array($stocktake->status->value, ['draft', 'counting']))
            <div class="form-footer"><button class="btn btn-primary"><i data-lucide="save"></i>Lưu số đếm</button></div>
        @endif
    </form>
    @if ($stocktake->status->value === 'counting')
        <form method="post" action="{{ route('inventory.stocktakes.review', $stocktake) }}" style="margin-top:12px">
            @csrf<button class="btn btn-secondary"><i data-lucide="send"></i>Gửi duyệt</button></form>
    @endif
@endsection
