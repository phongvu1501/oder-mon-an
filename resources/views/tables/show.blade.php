@extends('layouts.admin') @section('title', $table->name) @section('content')<div class="page-header">
    <div>
        <h1 class="page-title">{{ $table->name }}</h1>
        <p class="page-subtitle">{{ $table->code }} · {{ $table->location }}</p>
    </div>
    <div class="actions"><a class="btn btn-secondary" href="{{ route('tables.index') }}">Quay lại</a>
        @can('tables.update')
            <a class="btn btn-primary" href="{{ route('tables.edit', $table) }}">Sửa</a>
        @endcan
    </div>
</div>
<div class="content-grid">
    <section class="panel">
        <dl class="detail-list">
            <dt>Trạng thái</dt>
            <dd><span class="badge badge-{{ $table->status->value }}">{{ $table->status->value }}</span></dd>
            <dt>Sức chứa</dt>
            <dd>{{ $table->capacity }} khách</dd>
            <dt>Phiên hiện tại</dt>
            <dd>{{ $table->activeSession?->session_code ?? 'Không có' }}</dd>
            <dt>Hóa đơn</dt>
            <dd>{{ $table->activeSession?->bill?->bill_code ?? '—' }}</dd>
        </dl>
    </section>
    @can('tables.qr')
        <aside class="panel" style="text-align:center">
            <h2 class="section-title">QR gọi món</h2><img src="{{ route('tables.qr', $table) }}"
                alt="QR {{ $table->name }}" style="width:min(100%,280px);aspect-ratio:1">
            <div class="actions" style="justify-content:center"><a class="btn btn-secondary"
                    href="{{ route('tables.qr.download', $table) }}"><i data-lucide="download"></i> Tải QR</a><button
                    class="btn btn-secondary" onclick="window.print()"><i data-lucide="printer"></i> In</button>
                <form method="post" action="{{ route('tables.qr.regenerate', $table) }}"
                    data-confirm="QR cũ sẽ mất hiệu lực. Tiếp tục?">@csrf<button class="btn btn-danger"><i
                            data-lucide="refresh-cw"></i> Tạo lại</button></form>
            </div>
        </aside>
    @endcan
</div>@endsection
