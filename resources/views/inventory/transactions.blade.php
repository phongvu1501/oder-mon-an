@extends('layouts.admin')

@section('title', 'Giao dịch kho')

@section('content')
    <x-ui.page-header title="Giao dịch kho" subtitle="Mọi lần nhập, xuất, hao hụt và điều chỉnh đều giữ lịch sử."
        eyebrow="Kho">
        <a class="btn btn-secondary" href="{{ route('inventory.index') }}"><i data-lucide="boxes"></i>Tồn
            kho</a></x-ui.page-header>

    @if(false)
    @can('inventory.adjust')
        <section class="panel" style="margin-bottom:18px">
            <div class="section-heading">
                <div>
                    <h2>Ghi giao dịch</h2>
                    <p>Số lượng được kiểm tra lại và cập nhật trong một database transaction.</p>
                </div><i data-lucide="arrow-left-right"></i>
            </div>
            <form method="post" action="{{ route('inventory.transactions.store') }}" data-stock-form>@csrf
                <div class="form-grid">
                    <div><label class="form-label" for="transaction-type">Loại giao dịch</label><select class="form-select"
                            id="transaction-type" name="type">
                            <option value="in">Nhập kho</option>
                            <option value="out">Xuất kho</option>
                            <option value="adjustment">Điều chỉnh (+/-)</option>
                            <option value="waste">Hủy / hỏng</option>
                            <option value="return">Hoàn kho</option>
                        </select></div>
                    <div><label class="form-label" for="transaction-supplier">Nhà cung cấp</label><select class="form-select"
                            id="transaction-supplier" name="supplier_id">
                            <option value="">Không áp dụng</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group full"><label class="form-label" for="transaction-reason">Lý do</label><input
                            class="form-control" id="transaction-reason" name="reason" maxlength="500"
                            placeholder="Mô tả ngắn mục đích giao dịch"></div>
                </div>
                <div class="table-wrap" style="margin-top:14px">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Nguyên liệu</th>
                                <th>Số lượng thay đổi</th>
                                <th>Đơn giá</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody data-stock-lines>
                            <tr data-stock-line>
                                <td><select class="form-select" name="items[0][inventory_item_id]" required>
                                        <option value="">Chọn nguyên liệu</option>
                                        @foreach ($items as $item)
                                            <option value="{{ $item->id }}">{{ $item->name }} ({{ $item->unit }})
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input class="form-control" type="number" step="0.001" name="items[0][quantity]" required
                                        placeholder="0.000"></td>
                                <td><input class="form-control" type="number" min="0" step="0.01"
                                        name="items[0][unit_cost]" placeholder="0"></td>
                                <td><button class="btn btn-secondary icon-btn" type="button" data-remove-stock-line
                                        aria-label="Xóa dòng"><i data-lucide="x"></i></button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <template data-stock-line-template>
                    <tr data-stock-line>
                        <td><select class="form-select" name="items[0][inventory_item_id]" required>
                                <option value="">Chọn nguyên liệu</option>
                                @foreach ($items as $item)
                                    <option value="{{ $item->id }}">{{ $item->name }} ({{ $item->unit }})</option>
                                @endforeach
                            </select>
                        </td>
                        <td><input class="form-control" type="number" step="0.001" name="items[0][quantity]" required
                                placeholder="0.000"></td>
                        <td><input class="form-control" type="number" min="0" step="0.01" name="items[0][unit_cost]"
                                placeholder="0"></td>
                        <td><button class="btn btn-secondary icon-btn" type="button" data-remove-stock-line
                                aria-label="Xóa dòng"><i data-lucide="x"></i></button></td>
                    </tr>
                </template>
                <div class="form-footer"><button class="btn btn-secondary" type="button" data-add-stock-line><i
                            data-lucide="plus"></i>Thêm dòng</button><button class="btn btn-primary" type="submit"><i
                            data-lucide="save"></i>Ghi giao dịch</button></div>
            </form>
        </section>
    @endcan
    @endif

    <form method="get"><x-ui.filter-bar :reset-url="route('inventory.transactions')"><input class="form-control" name="q"
                value="{{ request('q') }}" placeholder="Mã giao dịch hoặc lý do" aria-label="Tìm giao dịch kho"><select
                class="form-select" name="type" aria-label="Lọc loại giao dịch">
                <option value="">Mọi loại</option>
                @foreach (['in' => 'Nhập kho', 'out' => 'Xuất kho', 'adjustment' => 'Điều chỉnh', 'waste' => 'Hủy / hỏng', 'return' => 'Hoàn kho'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </x-ui.filter-bar></form>
    @php $typeLabels = ['in' => 'Nhập kho', 'out' => 'Xuất kho', 'adjustment' => 'Điều chỉnh', 'waste' => 'Hủy / hỏng', 'return' => 'Hoàn kho']; @endphp
    @php $typeLabels += ['adjustment_in'=>'Điều chỉnh tăng','adjustment_out'=>'Điều chỉnh giảm','transfer_out'=>'Chuyển kho đi','transfer_in'=>'Chuyển kho đến','stocktake_adjustment'=>'Chênh lệch kiểm kê','return_to_supplier'=>'Trả nhà cung cấp','customer_return'=>'Khách trả hàng']; @endphp
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Giao dịch</th>
                    <th>Loại</th>
                    <th>Chi tiết</th>
                    <th>Nhà cung cấp</th>
                    <th>Lý do</th>
                    <th>Thời gian</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($transactions as $transaction)
                    <tr>
                        <td>{{ $transactions->firstItem() + $loop->index }}</td>
                        <td class="cell-title">{{ $transaction->transaction_code }}</td>
                        <td><span class="badge badge-{{ $transaction->type->value }}"><span
                                    class="badge-dot"></span>{{ $typeLabels[$transaction->type->value] }}</span></td>
                        <td>
                            @foreach ($transaction->items as $line)
                                <div>{{ $line->inventoryItem->name }}:
                                    <strong>{{ number_format($line->quantity, 3) }}</strong> <span
                                        class="cell-meta">({{ number_format($line->before_quantity, 3) }} →
                                        {{ number_format($line->after_quantity, 3) }})</span>
                                </div>
                            @endforeach
                        </td>
                        <td>{{ $transaction->supplier?->name ?: '—' }}</td>
                        <td>{{ $transaction->reason ?: '—' }}</td>
                        <td>{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7"><x-ui.empty-state icon="arrow-left-right" title="Chưa có giao dịch kho"
                                description="Giao dịch mới sẽ xuất hiện tại đây và không bị ghi đè." /></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $transactions->links() }}</div>
@endsection
