@extends('layouts.admin')
@section('title', 'Đơn vị tính')
@section('content')
    <x-ui.page-header title="Đơn vị tính & quy đổi" eyebrow="Kho" />
    <div class="content-grid">
        <section class="panel">
            <div class="section-heading">
                <div>
                    <h2>Thêm đơn vị</h2>
                </div><i data-lucide="ruler"></i>
            </div>
            <form method="post" action="{{ route('inventory.units.store') }}">@csrf<div class="form-grid">
                    <div><label class="form-label">Mã</label><input class="form-control" name="code" required
                            maxlength="30"></div>
                    <div><label class="form-label">Tên</label><input class="form-control" name="name" required></div>
                    <div><label class="form-label">Ký hiệu</label><input class="form-control" name="symbol" required></div>
                    <div><label class="form-label">Nhóm</label><select class="form-select" name="category">
                            <option value="mass">Khối lượng</option>
                            <option value="volume">Thể tích</option>
                            <option value="count">Số lượng</option>
                            <option value="package">Đóng gói</option>
                            <option value="custom">Khác</option>
                        </select></div>
                    <div><label class="form-label">Số lẻ</label><input class="form-control" type="number" min="0"
                            max="3" name="decimal_places" value="3"></div>
                </div>
                <div class="form-footer"><button class="btn btn-primary"><i data-lucide="plus"></i>Thêm đơn vị</button>
                </div>
            </form>
        </section>
        <section class="panel">
            <div class="section-heading">
                <div>
                    <h2>Thêm quy đổi</h2>
                </div><i data-lucide="replace"></i>
            </div>
            <form method="post" action="{{ route('inventory.unit-conversions.store') }}">@csrf<div class="form-grid">
                    <div class="form-group full"><label class="form-label">Mặt hàng riêng</label><select class="form-select"
                            name="inventory_item_id">
                            <option value="">Quy đổi toàn cục</option>
                            @foreach ($items as $item)
                                <option value="{{ $item->id }}">{{ $item->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div><label class="form-label">Từ đơn vị</label><select class="form-select" name="from_unit_id">
                            @foreach ($units as $unit)
                                <option value="{{ $unit->id }}">{{ $unit->symbol }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div><label class="form-label">Sang đơn vị</label><select class="form-select" name="to_unit_id">
                            @foreach ($units as $unit)
                                <option value="{{ $unit->id }}">{{ $unit->symbol }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div><label class="form-label">Hệ số</label><input class="form-control" type="number" min="0.000001"
                            step="0.000001" name="multiplier" required></div>
                </div>
                <div class="form-footer"><button class="btn btn-primary"><i data-lucide="plus"></i>Thêm quy đổi</button>
                </div>
            </form>
        </section>
    </div>
    <div class="table-wrap" style="margin-top:18px">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Mã</th>
                    <th>Tên</th>
                    <th>Ký hiệu</th>
                    <th>Nhóm</th>
                    <th>Trạng thái</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($units as $unit)
                    <tr>
                        <td class="cell-title">{{ $unit->code }}</td>
                        <td>{{ $unit->name }}</td>
                        <td>{{ $unit->symbol }}</td>
                        <td>{{ $unit->category }}</td>
                        <td><x-ui.status-badge :status="$unit->status" /></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="table-wrap" style="margin-top:18px">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Phạm vi</th>
                    <th>Từ</th>
                    <th>Hệ số</th>
                    <th>Sang</th>
                </tr>
            </thead>
            <tbody>
                @forelse($conversions as $conversion)
                    <tr>
                        <td>{{ $conversion->inventoryItem?->name ?? 'Toàn cục' }}</td>
                        <td>{{ $conversion->fromUnit->symbol }}</td>
                        <td>{{ $conversion->multiplier }}</td>
                        <td>{{ $conversion->toUnit->symbol }}</td>
                </tr>@empty<tr>
                        <td colspan="4"><x-ui.empty-state icon="replace" title="Chưa có quy đổi" /></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $conversions->links() }}</div>
@endsection
