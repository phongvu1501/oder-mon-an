@extends('layouts.admin')

@section('title', $role->exists ? 'Phân quyền vai trò' : 'Thêm vai trò')

@section('content')
    @php
        $selected = collect(old('permissions', $role->permissions->modelKeys()))
            ->map(fn($id) => (int) $id)
            ->all();
        $moduleLabels = [
            'dashboard' => 'Tổng quan',
            'products' => 'Món ăn',
            'categories' => 'Danh mục',
            'tables' => 'Bàn & QR',
            'orders' => 'Đơn hàng',
            'payments' => 'Thanh toán',
            'inventory' => 'Kho',
            'suppliers' => 'Nhà cung cấp',
            'users' => 'Nhân viên',
            'roles' => 'Vai trò',
            'permissions' => 'Phân quyền',
            'reports' => 'Báo cáo',
            'activity_logs' => 'Nhật ký hoạt động',
        ];
        $actionLabels = [
            'view' => 'Xem',
            'create' => 'Thêm',
            'update' => 'Sửa',
            'delete' => 'Xóa',
            'qr' => 'Quản lý QR',
            'cancel' => 'Hủy',
            'confirm' => 'Xác nhận',
            'adjust' => 'Điều chỉnh',
            'manage' => 'Quản lý',
        ];
    @endphp

    <x-ui.page-header :title="$role->exists ? 'Phân quyền ' . $role->name : 'Thêm vai trò'"
        subtitle="Chọn quyền theo từng nhóm tài nguyên. Mọi kiểm tra phía máy chủ vẫn được giữ nguyên."
        eyebrow="Vai trò & quyền">
        <a class="btn btn-secondary" href="{{ route('roles.index') }}"><i data-lucide="arrow-left"></i>Quay lại</a>
    </x-ui.page-header>

    <form class="panel" method="post" action="{{ $role->exists ? route('roles.update', $role) : route('roles.store') }}"
        data-permission-editor>
        @csrf
        @if ($role->exists)
            @method('put')
        @endif

        <div class="form-grid">
            <div class="form-group"><label class="form-label" for="role-name">Tên vai trò <span
                        class="required">*</span></label><input class="form-control" id="role-name" name="name" required
                    maxlength="255" value="{{ old('name', $role->name) }}">
                @error('name')
                    <div class="field-error">{{ $message }}</div>
                @enderror
            </div>
            <div class="form-group"><label class="form-label" for="role-slug">Mã vai trò</label><input class="form-control"
                    id="role-slug" name="slug" value="{{ old('slug', $role->slug) }}" placeholder="Tự tạo từ tên">
                @error('slug')
                    <div class="field-error">{{ $message }}</div>
                @enderror
            </div>
            <div class="form-group"><label class="form-label" for="role-status">Trạng thái</label><select
                    class="form-select" id="role-status" name="status">
                    <option value="active" @selected(old('status', $role->status?->value ?? 'active') === 'active')>Hoạt động</option>
                    <option value="inactive" @selected(old('status', $role->status?->value) === 'inactive')>Ngừng dùng</option>
                </select></div>
            <div class="form-group full"><label class="form-label" for="role-description">Mô tả trách nhiệm</label>
                <textarea class="form-control" id="role-description" name="description" maxlength="1000"
                    placeholder="Ví dụ: Tiếp nhận order và hỗ trợ thanh toán tại bàn">{{ old('description', $role->description) }}</textarea>
            </div>
        </div>

        <div class="permission-toolbar">
            <label class="permission-search"><i data-lucide="search"></i><span class="visually-hidden">Tìm kiếm
                    quyền</span><input class="form-control" type="search" placeholder="Tìm theo nhóm, tên hoặc hành động"
                    data-permission-search></label>
            <label class="checkbox permission-filter"><input type="checkbox" data-permission-selected-only><span>Chỉ quyền
                    đã chọn</span></label>
            <span class="permission-count">Đã chọn <strong data-permission-total>0</strong> quyền</span>
        </div>

        <div class="permission-grid">
            @foreach ($permissions as $module => $items)
                @php
                    $moduleLabel = $moduleLabels[$module] ?? Str::headline($module);
                    $searchText = mb_strtolower(
                        $module .
                            ' ' .
                            $moduleLabel .
                            ' ' .
                            $items->pluck('name')->join(' ') .
                            ' ' .
                            $items->pluck('label')->join(' '),
                    );
                @endphp
                <section class="permission-card" data-permission-card data-search="{{ $searchText }}">
                    <div class="permission-card__head">
                        <div class="permission-card__title"><strong>{{ $moduleLabel }}</strong><span>{{ $items->count() }}
                                quyền khả dụng</span></div><span class="permission-card__count" data-permission-card-count>0
                            / {{ $items->count() }}</span>
                    </div>
                    <label class="checkbox permission-parent"><input type="checkbox" data-permission-parent><span>Tất cả
                            quyền</span></label>
                    <div class="permission-children">
                        @foreach ($items as $permission)
                            @php $action = Str::afterLast($permission->name, '.'); @endphp
                            <label class="permission-option" title="{{ $permission->name }}"><input type="checkbox"
                                    name="permissions[]" value="{{ $permission->id }}" data-permission-child
                                    @checked(in_array($permission->id, $selected, true))><span>{{ $actionLabels[$action] ?? $permission->label }}</span></label>
                        @endforeach
                    </div>
                </section>
            @endforeach
            <div class="permission-empty" data-permission-empty hidden>Không tìm thấy nhóm quyền phù hợp.</div>
        </div>

        <div class="permission-footer">
            <p>Đã chọn <strong data-permission-total>0</strong> quyền cho vai trò này.</p>
            <div class="actions"><button class="btn btn-secondary" type="button" data-permission-select-all>Chọn tất
                    cả</button><button class="btn btn-secondary" type="button" data-permission-clear>Bỏ chọn tất
                    cả</button><button class="btn btn-primary" type="submit"><i data-lucide="save"></i>Lưu thay
                    đổi</button></div>
        </div>
    </form>
@endsection
