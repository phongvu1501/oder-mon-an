@extends('layouts.admin')
@php
    $meta = match ($documentType) {
        \App\Enums\InventoryDocumentType::Receipt => ['Phiếu nhập kho', 'inventory.receipts.create', 'Nhập kho'],
        \App\Enums\InventoryDocumentType::Issue => ['Phiếu xuất kho', 'inventory.issues.create', 'Xuất kho'],
        \App\Enums\InventoryDocumentType::Adjustment => [
            'Điều chỉnh kho',
            'inventory.adjustments.create',
            'Điều chỉnh',
        ],
        \App\Enums\InventoryDocumentType::Waste => ['Hao hụt / huỷ', 'inventory.waste.create', 'Ghi hao hụt'],
        \App\Enums\InventoryDocumentType::Transfer => ['Chuyển kho', 'inventory.transfers.create', 'Tạo phiếu chuyển'],
        default => ['Phiếu kho', 'inventory.receipts.create', 'Tạo phiếu'],
    };
@endphp
@section('title', $meta[0])
@section('content')
    <x-ui.page-header :title="$meta[0]" eyebrow="Kho"><a class="btn btn-primary" href="{{ route($meta[1]) }}"><i
                data-lucide="plus"></i>{{ $meta[2] }}</a></x-ui.page-header>
    <form method="get"><x-ui.filter-bar :reset-url="url()->current()">
            <input class="form-control" name="q" value="{{ request('q') }}" placeholder="Mã phiếu hoặc lý do">
            <select class="form-select" name="status">
                <option value="">Mọi trạng thái</option>
                <option value="draft" @selected(request('status') === 'draft')>Nháp</option>
                <option value="confirmed" @selected(request('status') === 'confirmed')>Đã xác nhận</option>
                <option value="cancelled" @selected(request('status') === 'cancelled')>Đã huỷ</option>
            </select>
            <input class="form-control" type="date" name="from" value="{{ request('from') }}"><input
                class="form-control" type="date" name="to" value="{{ request('to') }}">
        </x-ui.filter-bar></form>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Mã phiếu</th>
                    <th>Ngày</th>
                    <th>Kho</th>
                    <th>Nhà cung cấp / Kho nhận</th>
                    <th>Tổng tiền</th>
                    <th>Trạng thái</th>
                    <th>Người tạo</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($documents as $document)
                    <tr>
                        <td>{{ $documents->firstItem() + $loop->index }}</td>
                        <td class="cell-title">{{ $document->document_code }}</td>
                        <td>{{ $document->transaction_date->format('d/m/Y H:i') }}</td>
                        <td>{{ $document->warehouse->name }}</td>
                        <td>{{ $document->supplier?->name ?? ($document->destinationWarehouse?->name ?? '—') }}</td>
                        <td>{{ number_format($document->total_amount) }} đ</td>
                        <td><x-ui.status-badge :status="$document->status" /></td>
                        <td>{{ $document->creator?->name ?? 'Hệ thống' }}</td>
                        <td><a class="btn btn-secondary icon-btn" title="Chi tiết"
                                href="{{ route('inventory.documents.show', $document) }}"><i data-lucide="eye"></i></a></td>
                </tr>@empty<tr>
                        <td colspan="9"><x-ui.empty-state icon="clipboard-list" title="Chưa có phiếu" /></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $documents->links() }}</div>
@endsection
