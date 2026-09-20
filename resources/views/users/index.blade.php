@extends('layouts.admin')

@section('title', 'Nhân viên')

@section('content')
    <x-ui.page-header title="Nhân viên" subtitle="Quản lý tài khoản, vai trò và trạng thái truy cập hệ thống."
        eyebrow="Quản trị">
        @can('users.create')
            <a class="btn btn-primary" href="{{ route('users.create') }}"><i data-lucide="user-plus"></i>Thêm nhân viên</a>
        @endcan
    </x-ui.page-header>
    <form method="get"><x-ui.filter-bar :reset-url="route('users.index')"><input class="form-control" name="q"
                value="{{ request('q') }}" placeholder="Tên hoặc email" aria-label="Tìm nhân viên"><select
                class="form-select" name="role_id" aria-label="Lọc vai trò">
                <option value="">Mọi vai trò</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->id }}" @selected(request('role_id') == $role->id)>{{ $role->name }}</option>
                @endforeach
            </select>
            <select class="form-select" name="status" aria-label="Lọc trạng thái">
                <option value="">Mọi trạng thái</option>
                <option value="active" @selected(request('status') === 'active')>Hoạt động</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Đã khóa</option>
            </select></x-ui.filter-bar></form>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Nhân viên</th>
                    <th>Vai trò</th>
                    <th>Điện thoại</th>
                    <th>Trạng thái</th>
                    <th>Đăng nhập cuối</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td>{{ $users->firstItem() + $loop->index }}</td>
                        <td>
                            <div class="user-cell"><x-ui.avatar :user="$user" size="sm" />
                                <div>
                                    <div class="cell-title">{{ $user->name }}</div>
                                    <div class="cell-meta">{{ $user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td>{{ $user->roles->pluck('name')->join(', ') ?: 'Chưa gán' }}</td>
                        <td>{{ $user->phone ?: '—' }}</td>
                        <td><x-ui.status-badge :status="$user->status" /></td>
                        <td>{{ $user->last_login_at?->format('d/m/Y H:i') ?? 'Chưa ghi nhận' }}</td>
                        <td>
                            <div class="actions"><a class="btn btn-secondary icon-btn"
                                    href="{{ route('users.show', $user) }}" title="Xem"
                                    aria-label="Xem {{ $user->name }}"><i data-lucide="eye"></i></a>
                                @can('users.update')
                                    <a class="btn btn-secondary icon-btn" href="{{ route('users.edit', $user) }}"
                                        title="Sửa" aria-label="Sửa {{ $user->name }}"><i data-lucide="pencil"></i></a>
                                    @endcan @can('users.delete')
                                    @unless (auth()->user()->is($user))
                                        <form method="post" action="{{ route('users.destroy', $user) }}"
                                            data-confirm="Khóa và lưu trữ tài khoản {{ $user->name }}?">@csrf
                                            @method('delete')<button class="btn btn-danger icon-btn" title="Khóa tài khoản"
                                                aria-label="Khóa {{ $user->name }}"><i data-lucide="user-x"></i></button></form>
                                    @endunless
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7"><x-ui.empty-state icon="users" title="Chưa có nhân viên"
                                description="Thêm tài khoản và gán vai trò phù hợp.">
                                @can('users.create')
                                    <a class="btn btn-primary" href="{{ route('users.create') }}"><i
                                            data-lucide="user-plus"></i>Thêm nhân viên</a>
                                @endcan
                            </x-ui.empty-state></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $users->links() }}</div>
@endsection
