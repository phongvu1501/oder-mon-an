@extends('layouts.admin')

@section('title', 'Tồn kho')

@section('content')
    <x-ui.page-header title="Tồn kho" subtitle="Số dư chỉ thay đổi qua giao dịch kho có lịch sử." eyebrow="Kho">
        <a class="btn btn-secondary" href="{{ route('inventory.transactions') }}"><i data-lucide="arrow-left-right"></i>Giao
            dịch kho</a>
    </x-ui.page-header>

    @can('inventory.create')
        <section class="panel" style="margin-bottom:18px">
            <div class="section-heading">
                <div>
                    <h2>Thêm nguyên liệu</h2>
                    <p>Tồn đầu kỳ được ghi nhận bằng giao dịch nhập kho sau khi tạo.</p>
                </div><i data-lucide="package-plus"></i>
            </div>
            <form method="post" action="{{ route('inventory.items.store') }}">@csrf<div class="form-grid">
                <input type="hidden" name="base_unit_id" value="{{ $units->first()?->id }}">
                    <div><label class="form-label" for="item-name">Tên nguyên liệu <span class="required">*</span></label><input
                            class="form-control" id="item-name" name="name" required maxlength="255"></div>
                    <div><label class="form-label" for="item-sku">SKU <span class="required">*</span></label><input
                            class="form-control" id="item-sku" name="sku" required maxlength="50"></div>
                    <div><label class="form-label" for="item-unit">Đơn vị <span class="required">*</span></label><input
                            class="form-control" id="item-unit" name="unit" required maxlength="30"
                            placeholder="kg, lít, chai..."></div>
                    <div><label class="form-label" for="item-minimum">Mức tồn tối thiểu</label><input class="form-control"
                            id="item-minimum" type="number" min="0" step="0.001" name="minimum_stock" value="0"
                            required></div>
                    <div><label class="form-label" for="item-cost">Giá vốn</label><input class="form-control" id="item-cost"
                            type="number" min="0" step="0.01" name="cost_price" value="0" required></div><input
                        type="hidden" name="status" value="active">
                </div>
                <div class="form-footer"><button class="btn btn-primary" type="submit"><i data-lucide="plus"></i>Thêm nguyên
                        liệu</button></div>
            </form>
        </section>
    @endcan

    <form method="get"><x-ui.filter-bar :reset-url="route('inventory.index')"><input class="form-control" name="q"
                value="{{ request('q') }}" placeholder="Tên hoặc SKU" aria-label="Tìm nguyên liệu"><select
                class="form-select" name="status" aria-label="Lọc trạng thái">
                <option value="">Mọi trạng thái</option>
                <option value="active" @selected(request('status') === 'active')>Đang dùng</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Ngừng dùng</option>
            </select><label class="checkbox"><input type="checkbox" name="low" value="1"
                    @checked(request('low') === '1')><span>Chỉ hàng sắp hết</span></label></x-ui.filter-bar></form>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Nguyên liệu</th>
                    <th>Tồn hiện tại</th>
                    <th>Tồn tối thiểu</th>
                    <th>Giá vốn</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $item)
                    <tr>
                        <td>{{ $items->firstItem() + $loop->index }}</td>
                        <td>
                            <div class="cell-title">{{ $item->name }}</div>
                            <div class="cell-meta">{{ $item->sku }} · {{ $item->unit }}</div>
                        </td>
                        <td><strong>{{ number_format($item->current_stock, 3) }} {{ $item->unit }}</strong>
                            @if ($item->current_stock <= $item->minimum_stock)
                                <x-ui.status-badge :status="$item->current_stock <= 0 ? 'out' : 'low'" />
                            @endif
                        </td>
                        <td>{{ number_format($item->minimum_stock, 3) }} {{ $item->unit }}</td>
                        <td>{{ number_format($item->cost_price) }} đ</td>
                        <td><x-ui.status-badge :status="$item->status" /></td>
                        <td>
                            @can('inventory.update')
                                <div class="actions"><button class="btn btn-secondary icon-btn" type="button" title="Sửa"
                                        aria-label="Sửa {{ $item->name }}" data-inventory-edit
                                        data-action="{{ route('inventory.items.update', $item) }}"
                                        data-name="{{ $item->name }}" data-sku="{{ $item->sku }}"
                                        data-unit="{{ $item->unit }}" data-minimum="{{ $item->minimum_stock }}"
                                        data-base-unit="{{ $item->base_unit_id }}"
                                        data-cost="{{ $item->cost_price }}" data-status="{{ $item->status->value }}"><i
                                            data-lucide="pencil"></i></button>
                                    <form method="post" action="{{ route('inventory.items.destroy', $item) }}"
                                        data-confirm="Xóa nguyên liệu {{ $item->name }}?">@csrf @method('delete')<button
                                            class="btn btn-danger icon-btn" title="Xóa"
                                            aria-label="Xóa {{ $item->name }}"><i data-lucide="trash-2"></i></button></form>
                                </div>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7"><x-ui.empty-state icon="package-open" title="Chưa có dữ liệu kho"
                                description="Thêm nguyên liệu đầu tiên để quản lý tồn kho." /></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $items->links() }}</div>

    @can('inventory.update')
        <div class="modal-layer" id="inventory-edit-modal" aria-hidden="true">
            <div class="modal-backdrop" data-inventory-close></div>
            <section class="modal-dialog" role="dialog" aria-modal="true" aria-labelledby="inventory-edit-title"
                tabindex="-1">
                <header class="modal-header">
                    <div>
                        <div class="modal-kicker">Nguyên liệu</div>
                        <h2 id="inventory-edit-title">Cập nhật nguyên liệu</h2>
                    </div><button class="btn btn-ghost icon-btn" type="button" data-inventory-close aria-label="Đóng"><i
                            data-lucide="x"></i></button>
                </header>
                <form method="post" data-inventory-form>@csrf @method('put')<div class="modal-content">
                    <input type="hidden" name="base_unit_id" value="{{ $units->first()?->id }}">
                        <div class="form-grid">
                            <div class="form-group full"><label class="form-label" for="edit-item-name">Tên nguyên
                                    liệu</label><input class="form-control" id="edit-item-name" name="name" required>
                            </div>
                            <div><label class="form-label" for="edit-item-sku">SKU</label><input class="form-control"
                                    id="edit-item-sku" name="sku" required></div>
                            <div><label class="form-label" for="edit-item-unit">Đơn vị</label><input class="form-control"
                                    id="edit-item-unit" name="unit" required></div>
                            <div><label class="form-label" for="edit-item-minimum">Tồn tối thiểu</label><input
                                    class="form-control" id="edit-item-minimum" name="minimum_stock" type="number"
                                    min="0" step="0.001" required></div>
                            <div><label class="form-label" for="edit-item-cost">Giá vốn</label><input class="form-control"
                                    id="edit-item-cost" name="cost_price" type="number" min="0" step="0.01"
                                    required></div>
                            <div><label class="form-label" for="edit-item-status">Trạng thái</label><select
                                    class="form-select" id="edit-item-status" name="status">
                                    <option value="active">Đang dùng</option>
                                    <option value="inactive">Ngừng dùng</option>
                                </select></div>
                        </div>
                    </div>
                    <footer class="modal-footer"><button class="btn btn-secondary" type="button"
                            data-inventory-close>Hủy</button><button class="btn btn-primary" type="submit"><i
                                data-lucide="save"></i>Lưu thay đổi</button></footer>
                </form>
            </section>
        </div>
    @endcan
@endsection
