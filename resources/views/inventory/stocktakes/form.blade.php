@extends('layouts.admin')
@section('title', 'Tạo kiểm kê')
@section('content')
    <x-ui.page-header title="Tạo kiểm kê" eyebrow="Kho">
        <a class="btn btn-secondary" href="{{ route('inventory.stocktakes.index') }}"><i data-lucide="arrow-left"></i>Quay
            lại</a></x-ui.page-header>
    <form method="post" action="{{ route('inventory.stocktakes.store') }}">@csrf<section class="panel">
            <div class="form-grid">
                <div><label class="form-label">Kho</label><select class="form-select" name="warehouse_id" required>
                        @foreach ($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" @selected($warehouse->is_default)>{{ $warehouse->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div><label class="form-label">Ngày kiểm kê</label><input class="form-control" type="datetime-local"
                        name="counted_at" value="{{ now()->format('Y-m-d\TH:i') }}" required></div>
                <div class="form-group full"><label class="form-label">Ghi chú</label>
                    <textarea class="form-control" name="note" rows="2"></textarea>
                </div>
            </div>
            <div class="section-heading" style="margin-top:18px">
                <div>
                    <h2>Mặt hàng kiểm kê</h2>
                </div><label class="checkbox"><input type="checkbox" data-check-all><span>Chọn tất cả</span></label>
            </div>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th></th>
                            <th>SKU</th>
                            <th>Mặt hàng</th>
                            <th>Tồn hệ thống</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $item)
                            <tr>
                                <td><input type="checkbox" name="item_ids[]" value="{{ $item->id }}" data-check-item>
                                </td>
                                <td>{{ $item->sku }}</td>
                                <td class="cell-title">{{ $item->name }}</td>
                                <td>{{ number_format($item->current_stock, 3) }} {{ $item->unit }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="form-footer"><button class="btn btn-primary"><i data-lucide="save"></i>Tạo phiếu</button></div>
        </section>
    </form>
    @push('scripts')
        <script>
            document.querySelector('[data-check-all]')?.addEventListener('change', e => document.querySelectorAll(
                '[data-check-item]').forEach(i => i.checked = e.target.checked));
        </script>
    @endpush
@endsection
