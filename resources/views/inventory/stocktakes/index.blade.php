@extends('layouts.admin')
@section('title', 'Kiểm kê kho')
@section('content')
    <x-ui.page-header title="Kiểm kê kho" eyebrow="Kho">
        <a class="btn btn-primary" href="{{ route('inventory.stocktakes.create') }}"><i data-lucide="clipboard-plus"></i>Tạo
            kiểm kê</a></x-ui.page-header>
    <form method="get"><x-ui.filter-bar :reset-url="route('inventory.stocktakes.index')"><input class="form-control" name="q"
                value="{{ request('q') }}" placeholder="Mã kiểm kê"><select class="form-select" name="status">
                <option value="">Mọi trạng thái</option>
                @foreach (['draft' => 'Nháp', 'counting' => 'Đang đếm', 'review' => 'Chờ duyệt', 'confirmed' => 'Đã xác nhận'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select></x-ui.filter-bar></form>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Mã</th>
                    <th>Ngày</th>
                    <th>Kho</th>
                    <th>Người tạo</th>
                    <th>Trạng thái</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($stocktakes as $stocktake)
                    <tr>
                        <td>{{ $stocktakes->firstItem() + $loop->index }}</td>
                        <td class="cell-title">{{ $stocktake->stocktake_code }}</td>
                        <td>{{ $stocktake->counted_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $stocktake->warehouse->name }}</td>
                        <td>{{ $stocktake->creator?->name }}</td>
                        <td><x-ui.status-badge :status="$stocktake->status" /></td>
                        <td><a class="btn btn-secondary icon-btn"
                                href="{{ route('inventory.stocktakes.show', $stocktake) }}"><i data-lucide="eye"></i></a>
                        </td>
                    </tr>
                @empty<tr>
                        <td colspan="7"><x-ui.empty-state icon="clipboard-check" title="Chưa có phiếu kiểm kê" /></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $stocktakes->links() }}</div>
@endsection
