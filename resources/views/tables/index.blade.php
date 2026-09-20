@extends('layouts.admin')

@section('title', 'Bàn & QR')

@section('content')
    <x-ui.page-header title="Bàn & QR" subtitle="Theo dõi trạng thái bàn, phiên phục vụ và mã QR gọi món." eyebrow="Vận hành">
        @can('tables.create')
            <a class="btn btn-primary" href="{{ route('tables.create') }}"><i data-lucide="plus"></i>Thêm bàn</a>
        @endcan
    </x-ui.page-header>
    <form method="get"><x-ui.filter-bar :reset-url="route('tables.index')"><input class="form-control" name="q"
                value="{{ request('q') }}" placeholder="Tên hoặc mã bàn" aria-label="Tìm bàn"><select class="form-select"
                name="status" aria-label="Lọc trạng thái">
                <option value="">Mọi trạng thái</option>
                @foreach (['available' => 'Sẵn sàng', 'occupied' => 'Đang sử dụng', 'reserved' => 'Đã đặt', 'maintenance' => 'Bảo trì', 'inactive' => 'Ngừng dùng'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </x-ui.filter-bar></form>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Bàn</th>
                    <th>Vị trí</th>
                    <th>Sức chứa</th>
                    <th>Trạng thái</th>
                    <th>Phiên hiện tại</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tables as $table)
                    <tr>
                        <td>{{ $tables->firstItem() + $loop->index }}</td>
                        <td>
                            <div class="cell-title">{{ $table->name }}</div>
                            <div class="cell-meta">{{ $table->code }}</div>
                        </td>
                        <td>{{ $table->location ?: '—' }}</td>
                        <td>{{ $table->capacity }} khách</td>
                        <td><x-ui.status-badge :status="$table->status" /></td>
                        <td>
                            @if ($table->activeSession)
                                <div class="cell-title">{{ $table->activeSession->session_code }}</div>
                                <div class="cell-meta">Mở {{ $table->activeSession->opened_at->format('H:i d/m') }}</div>
                            @else<span class="text-muted">Chưa có phiên</span>
                            @endif
                        </td>
                        <td>
                            <div class="actions"><a class="btn btn-secondary icon-btn"
                                    href="{{ route('tables.show', $table) }}" title="Xem & QR"
                                    aria-label="Xem {{ $table->name }}"><i data-lucide="eye"></i></a>
                                @can('tables.update')
                                    <a class="btn btn-secondary icon-btn" href="{{ route('tables.edit', $table) }}"
                                        title="Sửa" aria-label="Sửa {{ $table->name }}"><i data-lucide="pencil"></i></a>
                                    @endcan @can('tables.delete')
                                    <form method="post" action="{{ route('tables.destroy', $table) }}"
                                        data-confirm="Xóa {{ $table->name }}?">@csrf @method('delete')<button
                                            class="btn btn-danger icon-btn" title="Xóa"
                                            aria-label="Xóa {{ $table->name }}"><i data-lucide="trash-2"></i></button></form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7"><x-ui.empty-state icon="layout-grid" title="Chưa có bàn"
                                description="Tạo bàn để phát hành QR gọi món.">
                                @can('tables.create')
                                    <a class="btn btn-primary" href="{{ route('tables.create') }}"><i
                                            data-lucide="plus"></i>Thêm bàn</a>
                                @endcan
                            </x-ui.empty-state></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $tables->links() }}</div>
@endsection
