@extends('layouts.admin')

@section('title', 'Hồ sơ cá nhân')

@section('content')
    <x-ui.page-header title="Hồ sơ cá nhân" subtitle="Quản lý thông tin hiển thị và bảo mật tài khoản." eyebrow="Tài khoản" />
    <div class="profile-layout">
        <aside class="profile-summary panel">
            <div class="profile-avatar-wrap">
                <x-ui.avatar :user="$user" size="xl" />
                <span class="profile-status"><x-ui.status-badge :status="$user->status" /></span>
            </div>
            <h2>{{ $user->name }}</h2>
            <p>{{ $user->email }}</p>
            <div class="role-list">
                @foreach ($user->roles as $role)
                    <span>{{ $role->name }}</span>
                @endforeach
            </div>
            <dl class="profile-meta">
                <div>
                    <dt>Ngày tạo</dt>
                    <dd>{{ $user->created_at->format('d/m/Y') }}</dd>
                </div>
                <div>
                    <dt>Đăng nhập gần nhất</dt>
                    <dd>{{ $user->last_login_at?->format('d/m/Y H:i') ?? 'Chưa ghi nhận' }}</dd>
                </div>
            </dl>
        </aside>

        <div class="profile-content">
            <form class="panel profile-section" method="post" enctype="multipart/form-data"
                action="{{ route('profile.update') }}">
                @csrf @method('put')
                <div class="section-heading">
                    <div>
                        <h2>Thông tin cá nhân</h2>
                        <p>Cập nhật thông tin dùng trong hệ thống.</p>
                    </div><i data-lucide="user-round"></i>
                </div>
                <div class="avatar-upload">
                    <x-ui.avatar :user="$user" size="lg" />
                    <div><label class="btn btn-secondary" for="avatar-input"><i data-lucide="camera"></i> Thay
                            ảnh</label><input id="avatar-input" class="visually-hidden" type="file" name="avatar"
                            accept="image/jpeg,image/png,image/webp" data-avatar-input>
                        <p>JPG, PNG hoặc WebP, tối đa 2 MB.</p>
                    </div>
                    <img class="avatar-preview" data-avatar-preview alt="Xem trước ảnh đại diện" hidden>
                </div>
                <div class="form-grid">
                    <div class="form-group"><label class="form-label" for="profile-name">Họ tên <span
                                class="required">*</span></label><input class="form-control" id="profile-name"
                            name="name" value="{{ old('name', $user->name) }}" required>
                        @error('name')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group"><label class="form-label" for="profile-email">Email <span
                                class="required">*</span></label><input class="form-control" id="profile-email"
                            type="email" name="email" value="{{ old('email', $user->email) }}" required>
                        @error('email')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group"><label class="form-label" for="profile-phone">Số điện thoại</label><input
                            class="form-control" id="profile-phone" name="phone" value="{{ old('phone', $user->phone) }}"
                            placeholder="Ví dụ: 0901 234 567">
                        @error('phone')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="form-footer"><button class="btn btn-primary" type="submit"><i data-lucide="save"></i> Lưu thay
                        đổi</button></div>
            </form>

            <form class="panel profile-section" method="post" action="{{ route('profile.password') }}">
                @csrf @method('put')
                <div class="section-heading">
                    <div>
                        <h2>Bảo mật</h2>
                        <p>Đổi mật khẩu sẽ đăng xuất các phiên đang hoạt động.</p>
                    </div><i data-lucide="shield-check"></i>
                </div>
                <div class="form-grid">
                    <div class="form-group full"><label class="form-label" for="current-password">Mật khẩu hiện tại <span
                                class="required">*</span></label><input class="form-control" id="current-password"
                            type="password" name="current_password" autocomplete="current-password" required>
                        @error('current_password')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group"><label class="form-label" for="new-password">Mật khẩu mới <span
                                class="required">*</span></label><input class="form-control" id="new-password"
                            type="password" name="password" minlength="8" autocomplete="new-password" required>
                        @error('password')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group"><label class="form-label" for="password-confirmation">Xác nhận mật khẩu <span
                                class="required">*</span></label><input class="form-control" id="password-confirmation"
                            type="password" name="password_confirmation" minlength="8" autocomplete="new-password"
                            required></div>
                </div>
                <div class="form-footer"><button class="btn btn-primary" type="submit"><i data-lucide="key-round"></i>
                        Đổi mật khẩu</button></div>
            </form>
        </div>
    </div>
@endsection
