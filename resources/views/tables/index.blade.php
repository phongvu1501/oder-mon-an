@extends('layouts.admin') @section('title', 'Bàn') @section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Bàn phục vụ</h1>
        <p class="page-subtitle">Trạng thái bàn, phiên hiện tại và QR gọi món.</p>
    </div>
    @can('tables.create')
        <a class="btn btn-primary" href="{{ route('tables.create') }}"><i data-lucide="plus"></i> Thêm bàn</a>
    @endcan
</div>
<form class="filters"><input class="form-control" name="q" value="{{ request('q') }}"
        placeholder="Tên hoặc mã bàn"><select class="form-select" name="status">
        <option value="">Mọi trạng thái</option>
        @foreach (['available' => 'Sẵn sàng', 'occupied' => 'Đang dùng', 'reserved' => 'Đã đặt', 'maintenance' => 'Bảo trì', 'inactive' => 'Ngừng dùng'] as $value => $label)
            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
        @endforeach
    </select>
    <button class="btn btn-secondary">Lọc</button>
</form>
<div class="table-wrap">
    <table class="data-table">
        <thead>
            <tr>
                <th>STT</th>
                <th>Bàn</th>
                <th>Vị trí</th>
                <th>Sức chứa</th>
                <th>Trạng thái</th>
                <th>Phiên</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($tables as $table)
                <tr>
                    <td>{{ $tables->firstItem() + $loop->index }}</td>
                    <td>
                        <div class="cell-title">{{ $table->name }}</div>
                        <div class="cell-meta">{{ $table->code }}</div>
                    </td>
                    <td>{{ $table->location ?: '—' }}</td>
                    <td>{{ $table->capacity }} khách</td>
                    <td><span class="badge badge-{{ $table->status->value }}">{{ $table->status->value }}</span></td>
                    <td>{{ $table->activeSession?->session_code ?? '—' }}</td>
                    <td>
                        <div class="actions"><a class="btn btn-secondary icon-btn"
                                href="{{ route('tables.show', $table) }}" title="Xem"><i data-lucide="eye"></i></a>
                            @can('tables.update')
                                <a class="btn btn-secondary icon-btn" href="{{ route('tables.edit', $table) }}"
                                    title="Sửa"><i data-lucide="pencil"></i></a>
                                @endcan @can('tables.delete')
                                <form method="post" action="{{ route('tables.destroy', $table) }}"
                                    data-confirm="Bạn có chắc muốn xóa bàn này?">@csrf @method('delete')<button
                                        class="btn btn-danger icon-btn"><i data-lucide="trash-2"></i></button></form>
                            @endcan
                        </div>
                    </td>
            </tr>@empty<tr>
                    <td class="empty" colspan="7">Chưa có bàn.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="pagination">{{ $tables->links() }}</div>
@endsection
