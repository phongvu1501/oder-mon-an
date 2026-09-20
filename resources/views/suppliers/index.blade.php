@extends('layouts.admin')

@section('title', 'Nhà cung cấp')

@section('content')
    <x-ui.page-header title="Nhà cung cấp" subtitle="Thông tin đối tác phục vụ mua và nhập nguyên liệu." eyebrow="Kho">
        @can('suppliers.create')
            <a class="btn btn-primary" href="{{ route('suppliers.create') }}"><i data-lucide="plus"></i>Thêm nhà cung cấp</a>
        @endcan
    </x-ui.page-header>
    <form method="get"><x-ui.filter-bar :reset-url="route('suppliers.index')"><input class="form-control" name="q"
                value="{{ request('q') }}" placeholder="Tên, liên hệ, điện thoại hoặc email"
                aria-label="Tìm nhà cung cấp"><select class="form-select" name="status" aria-label="Lọc trạng thái">
                <option value="">Mọi trạng thái</option>
                <option value="active" @selected(request('status') === 'active')>Đang dùng</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Ngừng dùng</option>
            </select></x-ui.filter-bar></form>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Nhà cung cấp</th>
                    <th>Người liên hệ</th>
                    <th>Điện thoại</th>
                    <th>Email</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($suppliers as $supplier)
                    <tr>
                        <td>{{ $suppliers->firstItem() + $loop->index }}</td>
                        <td>
                            <div class="cell-title">{{ $supplier->name }}</div>
                            <div class="cell-meta">{{ Str::limit($supplier->address ?: 'Chưa có địa chỉ', 60) }}</div>
                        </td>
                        <td>{{ $supplier->contact_name ?: '—' }}</td>
                        <td>{{ $supplier->phone ?: '—' }}</td>
                        <td>{{ $supplier->email ?: '—' }}</td>
                        <td><x-ui.status-badge :status="$supplier->status" /></td>
                        <td>
                            <div class="actions"><a class="btn btn-secondary icon-btn"
                                    href="{{ route('suppliers.show', $supplier) }}" title="Xem"
                                    aria-label="Xem {{ $supplier->name }}"><i data-lucide="eye"></i></a>
                                @can('suppliers.update')
                                    <a class="btn btn-secondary icon-btn" href="{{ route('suppliers.edit', $supplier) }}"
                                        title="Sửa" aria-label="Sửa {{ $supplier->name }}"><i data-lucide="pencil"></i></a>
                                    @endcan @can('suppliers.delete')
                                    <form method="post" action="{{ route('suppliers.destroy', $supplier) }}"
                                        data-confirm="Xóa nhà cung cấp {{ $supplier->name }}?">@csrf @method('delete')<button
                                            class="btn btn-danger icon-btn" title="Xóa"
                                            aria-label="Xóa {{ $supplier->name }}"><i data-lucide="trash-2"></i></button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7"><x-ui.empty-state icon="truck" title="Chưa có nhà cung cấp"
                                description="Thêm đối tác để liên kết với giao dịch nhập kho.">
                                @can('suppliers.create')
                                    <a class="btn btn-primary" href="{{ route('suppliers.create') }}"><i
                                            data-lucide="plus"></i>Thêm nhà cung cấp</a>
                                @endcan
                            </x-ui.empty-state></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $suppliers->links() }}</div>
@endsection
