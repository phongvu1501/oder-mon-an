@extends('layouts.admin')
@section('title', 'Báo cáo kho')
@section('content')
    <x-ui.page-header title="Báo cáo nhập xuất tồn" eyebrow="Kho">
        @can('inventory.export')
            <a class="btn btn-secondary" href="{{ route('inventory.reports.export', request()->query()) }}"><i
                    data-lucide="download"></i>CSV</a>
        @endcan
    </x-ui.page-header>
    <form method="get"><x-ui.filter-bar :reset-url="route('inventory.reports.index')"><input class="form-control" name="q"
                value="{{ request('q') }}" placeholder="SKU hoặc mặt hàng"><input class="form-control" type="date"
                name="from" value="{{ request('from', $from->toDateString()) }}"><input class="form-control"
                type="date" name="to" value="{{ request('to', $to->toDateString()) }}"><select class="form-select"
                name="supplier_id">
                <option value="">Mọi nhà cung cấp</option>
                @foreach ($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" @selected(request('supplier_id') == $supplier->id)>{{ $supplier->name }}</option>
                @endforeach
            </select></x-ui.filter-bar></form>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Mặt hàng</th>
                    <th>Đơn vị</th>
                    <th>Đầu kỳ</th>
                    <th>Nhập</th>
                    <th>Xuất</th>
                    <th>Hao hụt</th>
                    <th>Điều chỉnh</th>
                    <th>Cuối kỳ</th>
                    <th>Giá vốn</th>
                    <th>Giá trị tồn</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                    @php
                        $row = $movements->get($item->id);
                        $opening = (float) ($row->opening ?? 0);
                        $in = (float) ($row->in_qty ?? 0);
                        $out = abs((float) ($row->out_qty ?? 0));
                        $waste = abs((float) ($row->waste_qty ?? 0));
                        $adjust = (float) ($row->adjustment_qty ?? 0);
                        $closing = $opening + (float) ($row->net_qty ?? 0);
                    @endphp
                    <tr>
                        <td>{{ $item->sku }}</td>
                        <td class="cell-title">{{ $item->name }}</td>
                        <td>{{ $item->unit }}</td>
                        <td>{{ number_format($opening, 3) }}</td>
                        <td>{{ number_format($in, 3) }}</td>
                        <td>{{ number_format($out, 3) }}</td>
                        <td>{{ number_format($waste, 3) }}</td>
                        <td>{{ number_format($adjust, 3) }}</td>
                        <td><strong>{{ number_format($closing, 3) }}</strong></td>
                        <td>{{ number_format($item->cost_price) }} đ</td>
                        <td>{{ number_format($closing * (float) $item->cost_price) }} đ</td>
                    </tr>
                @empty<tr>
                        <td colspan="11"><x-ui.empty-state icon="chart-no-axes-combined" title="Không có dữ liệu" /></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $items->links() }}</div>
@endsection
