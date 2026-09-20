@extends('layouts.admin')

@section('title', 'Vai trò & quyền')

@section('content')
    <x-ui.page-header title="Vai trò & quyền" subtitle="Tổ chức quyền truy cập theo trách nhiệm của từng nhóm nhân viên."
        eyebrow="Hệ thống">
        @can('roles.create')
            <a class="btn btn-primary" href="{{ route('roles.create') }}"><i data-lucide="plus"></i>Thêm vai trò</a>
        @endcan
    </x-ui.page-header>

    <form method="get">
        <x-ui.filter-bar :reset-url="route('roles.index')">
            <input class="form-control" name="q" value="{{ request('q') }}" placeholder="Tìm tên hoặc mã vai trò"
                aria-label="Tìm vai trò">
            <select class="form-select" name="status" aria-label="Lọc trạng thái">
                <option value="">Mọi trạng thái</option>
                <option value="active" @selected(request('status') === 'active')>Hoạt động</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Ngừng dùng</option>
            </select>
        </x-ui.filter-bar>
    </form>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Vai trò</th>
                    <th>Mô tả</th>
                    <th>Nhân viên</th>
                    <th>Quyền</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($roles as $role)
                    <tr>
                        <td>{{ $roles->firstItem() + $loop->index }}</td>
                        <td>
                            <div class="cell-title">{{ $role->name }}</div>
                            <div class="cell-meta">{{ $role->slug }}</div>
                        </td>
                        <td>{{ Str::limit($role->description ?: 'Chưa có mô tả', 70) }}</td>
                        <td>{{ number_format($role->users_count) }}</td>
                        <td><strong>{{ number_format($role->permissions_count) }}</strong> quyền</td>
                        <td><x-ui.status-badge :status="$role->status" /></td>
                        <td>
                            <div class="actions">
                                <a class="btn btn-secondary icon-btn" href="{{ route('roles.show', $role) }}"
                                    title="Xem chi tiết" aria-label="Xem {{ $role->name }}"><i data-lucide="eye"></i></a>
                                @can('roles.update')
                                    <a class="btn btn-secondary" href="{{ route('roles.edit', $role) }}"><i
                                            data-lucide="shield-check"></i>Phân quyền</a>
                                @endcan
                                @can('roles.delete')
                                    <form method="post" action="{{ route('roles.destroy', $role) }}"
                                        data-confirm="Xóa vai trò {{ $role->name }}? Thao tác chỉ thành công khi vai trò chưa được gán.">
                                        @csrf @method('delete')<button class="btn btn-danger icon-btn" type="submit"
                                            title="Xóa" aria-label="Xóa {{ $role->name }}"><i
                                                data-lucide="trash-2"></i></button></form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7"><x-ui.empty-state icon="shield" title="Chưa có vai trò"
                                description="Tạo vai trò để bắt đầu tổ chức quyền truy cập.">
                                @can('roles.create')
                                    <a class="btn btn-primary" href="{{ route('roles.create') }}"><i
                                            data-lucide="plus"></i>Thêm vai trò</a>
                                @endcan
                            </x-ui.empty-state></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $roles->links() }}</div>
@endsection
