@extends('layouts.admin')
@php
    $meta = match ($documentType) {
        \App\Enums\InventoryDocumentType::Receipt => ['Tạo phiếu nhập', 'inventory.receipts.store'],
        \App\Enums\InventoryDocumentType::Issue => ['Tạo phiếu xuất', 'inventory.issues.store'],
        \App\Enums\InventoryDocumentType::Adjustment => ['Tạo phiếu điều chỉnh', 'inventory.adjustments.store'],
        \App\Enums\InventoryDocumentType::Waste => ['Ghi nhận hao hụt', 'inventory.waste.store'],
        \App\Enums\InventoryDocumentType::Transfer => ['Tạo phiếu chuyển kho', 'inventory.transfers.store'],
        default => ['Tạo phiếu kho', 'inventory.receipts.store'],
    };
    $isReceipt = $documentType === \App\Enums\InventoryDocumentType::Receipt;
    $isTransfer = $documentType === \App\Enums\InventoryDocumentType::Transfer;
    $isAdjustment = $documentType === \App\Enums\InventoryDocumentType::Adjustment;
@endphp
@section('title', $meta[0])
@section('content')
    <x-ui.page-header :title="$meta[0]" eyebrow="Kho">
        <a class="btn btn-secondary" href="{{ url()->previous() }}"><i data-lucide="arrow-left"></i>Quay
            lại</a></x-ui.page-header>
    <form method="post" action="{{ route($meta[1]) }}" data-stock-form>@csrf
        <input type="hidden" name="idempotency_key"
            value="{{ old('idempotency_key', (string) \Illuminate\Support\Str::uuid()) }}">
        <section class="panel" style="margin-bottom:18px">
            <div class="form-grid">
                <div><label class="form-label">Kho <span class="required">*</span></label><select class="form-select"
                        name="warehouse_id" required>
                        @foreach ($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" @selected(old('warehouse_id', $warehouse->is_default) == $warehouse->id)>{{ $warehouse->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @if ($isTransfer)
                    <div><label class="form-label">Kho nhận <span class="required">*</span></label><select
                            class="form-select" name="destination_warehouse_id" required>
                            <option value="">Chọn kho nhận</option>
                            @foreach ($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}" @selected(old('destination_warehouse_id') == $warehouse->id)>{{ $warehouse->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                @if ($isReceipt)
                    <div><label class="form-label">Nhà cung cấp <span class="required">*</span></label><select
                            class="form-select" name="supplier_id" required>
                            <option value="">Chọn nhà cung cấp</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>{{ $supplier->code }} ·
                                    {{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div><label class="form-label">Ngày giao dịch <span class="required">*</span></label><input
                        class="form-control" type="datetime-local" name="transaction_date"
                        value="{{ old('transaction_date', now()->format('Y-m-d\TH:i')) }}" required></div>
                <div><label class="form-label">Người nhận</label><input class="form-control" name="recipient"
                        maxlength="255" value="{{ old('recipient') }}"></div>
                <div class="form-group full"><label class="form-label">Lý do</label><input class="form-control"
                        name="reason" maxlength="255" value="{{ old('reason') }}" @required(!$isReceipt && !$isTransfer)></div>
                <div class="form-group full"><label class="form-label">Ghi chú</label>
                    <textarea class="form-control" name="note" rows="2">{{ old('note') }}</textarea>
                </div>
            </div>
        </section>
        <section class="panel">
            <div class="section-heading">
                <div>
                    <h2>Chi tiết</h2>
                </div><button class="btn btn-secondary" type="button" data-add-stock-line><i data-lucide="plus"></i>Thêm
                    dòng</button>
            </div>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Mặt hàng</th>
                            <th>Số lượng</th>
                            <th>Đơn vị</th>
                            @if ($isReceipt)
                                <th>Giá nhập</th>
                            @endif
                            <th>
                                Lô</th>
                            <th>HSD</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody data-stock-lines>
                        <tr data-stock-line>
                            <td><select class="form-select" name="items[0][inventory_item_id]" required>
                                    <option value="">Chọn mặt hàng</option>
                                    @foreach ($items as $item)
                                        <option value="{{ $item->id }}">{{ $item->name }} ·
                                            {{ number_format($item->current_stock, 3) }} {{ $item->unit }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td><input class="form-control" type="number" step="0.001"
                                    @unless ($isAdjustment) min="0.001" @endunless name="items[0][quantity]"
                                    required></td>
                            <td><select class="form-select" name="items[0][unit_id]" required>
                                    @foreach ($units as $unit)
                                        <option value="{{ $unit->id }}">{{ $unit->symbol }}</option>
                                    @endforeach
                                </select>
                            </td>
                            @if ($isReceipt)
                                <td><input class="form-control" type="number" min="0" step="0.01"
                                        name="items[0][unit_cost]" required></td>
                            @endif
                            <td><input class="form-control" name="items[0][batch_code]" maxlength="60"></td>
                            <td><input class="form-control" type="date" name="items[0][expiry_date]"></td>
                            <td><button class="btn btn-secondary icon-btn" type="button" data-remove-stock-line
                                    aria-label="Xoá dòng"><i data-lucide="x"></i></button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <template data-stock-line-template>
                <tr data-stock-line>
                    <td><select class="form-select" name="items[0][inventory_item_id]" required>
                            <option value="">Chọn mặt hàng</option>
                            @foreach ($items as $item)
                                <option value="{{ $item->id }}">{{ $item->name }} ·
                                    {{ number_format($item->current_stock, 3) }} {{ $item->unit }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td><input class="form-control" type="number" step="0.001"
                            @unless ($isAdjustment) min="0.001" @endunless name="items[0][quantity]" required>
                    </td>
                    <td><select class="form-select" name="items[0][unit_id]" required>
                            @foreach ($units as $unit)
                                <option value="{{ $unit->id }}">{{ $unit->symbol }}</option>
                            @endforeach
                        </select>
                    </td>
                    @if ($isReceipt)
                        <td><input class="form-control" type="number" min="0" step="0.01"
                                name="items[0][unit_cost]" required></td>
                    @endif
                    <td>
                        <input class="form-control" name="items[0][batch_code]" maxlength="60">
                    </td>
                    <td><input class="form-control" type="date" name="items[0][expiry_date]"></td>
                    <td><button class="btn btn-secondary icon-btn" type="button" data-remove-stock-line><i
                                data-lucide="x"></i></button></td>
                </tr>
            </template>
            <div class="form-footer"><button class="btn btn-secondary" type="submit" name="confirm" value="0"><i
                        data-lucide="save"></i>Lưu nháp</button>
                @can('inventory.confirm')
                    <button class="btn btn-primary" type="submit" name="confirm" value="1"><i
                            data-lucide="badge-check"></i>Lưu và xác nhận</button>
                @endcan
            </div>
        </section>
    </form>
@endsection
