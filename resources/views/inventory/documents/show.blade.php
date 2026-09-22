@extends('layouts.admin')
@section('title', $document->document_code)
@section('content')
    <x-ui.page-header :title="$document->document_code" subtitle="Phiếu kho" eyebrow="Kho">
        <a class="btn btn-secondary" href="{{ url()->previous() }}"><i data-lucide="arrow-left"></i>Quay lại</a>
        @if ($document->status->value === 'draft')
            @can('inventory.confirm')
                <form method="post" action="{{ route('inventory.documents.confirm', $document) }}">@csrf<button
                        class="btn btn-primary"><i data-lucide="badge-check"></i>Xác nhận</button></form>
            @endcan
        @endif
    </x-ui.page-header>
    <section class="panel" style="margin-bottom:18px">
        <div class="detail-list">
            <dt>Loại phiếu</dt>
            <dd>{{ $document->type->value }}</dd>
            <dt>Trạng thái</dt>
            <dd><x-ui.status-badge :status="$document->status" /></dd>
            <dt>Ngày giao dịch</dt>
            <dd>{{ $document->transaction_date->format('d/m/Y H:i') }}</dd>
            <dt>Kho</dt>
            <dd>{{ $document->warehouse->name }}</dd>
            <dt>Nhà cung cấp</dt>
            <dd>{{ $document->supplier?->name ?? '—' }}</dd>
            <dt>Kho nhận</dt>
            <dd>{{ $document->destinationWarehouse?->name ?? '—' }}</dd>
            <dt>Lý do</dt>
            <dd>{{ $document->reason ?? '—' }}</dd>
            <dt>Người tạo</dt>
            <dd>{{ $document->creator?->name ?? 'Hệ thống' }}</dd>
            <dt>Người xác nhận</dt>
            <dd>{{ $document->confirmer?->name ?? '—' }}</dd>
            <dt>Tổng tiền</dt>
            <dd><strong>{{ number_format($document->total_amount) }} đ</strong></dd>
        </div>
    </section>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Mặt hàng</th>
                    <th>Số lượng</th>
                    <th>Đơn vị</th>
                    <th>Giá</th>
                    <th>Thành tiền</th>
                    <th>Lô</th>
                    <th>HSD</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($document->items as $line)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            <div class="cell-title">{{ $line->inventoryItem->name }}</div>
                            <div class="cell-meta">{{ $line->inventoryItem->sku }}</div>
                        </td>
                        <td>{{ number_format($line->quantity, 3) }}</td>
                        <td>{{ $line->unit?->symbol ?? $line->inventoryItem->unit }}</td>
                        <td>{{ $line->unit_cost === null ? '—' : number_format($line->unit_cost) . ' đ' }}</td>
                        <td>{{ number_format($line->subtotal) }} đ</td>
                        <td>{{ $line->batch_code ?? '—' }}</td>
                        <td>{{ $line->expiry_date?->format('d/m/Y') ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if ($document->status->value !== 'cancelled')
        @can('inventory.cancel')
            <section class="panel" style="margin-top:18px">
                <form method="post" action="{{ route('inventory.documents.cancel', $document) }}"
                    data-confirm="Xác nhận huỷ phiếu {{ $document->document_code }}?">@csrf<div class="form-grid">
                        <div class="form-group full"><label class="form-label">Lý do huỷ</label><input class="form-control"
                                name="reason" minlength="5" maxlength="500" required></div>
                    </div>
                    <div class="form-footer"><button class="btn btn-danger"><i data-lucide="x-circle"></i>Huỷ phiếu</button>
                    </div>
                </form>
            </section>
        @endcan
    @endif
@endsection
