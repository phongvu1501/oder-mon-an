@extends('layouts.admin')

@section('title', $role->name)

@section('content')
    <x-ui.page-header :title="$role->name" :subtitle="$role->description ?: 'Vai trò chưa có mô tả.'" eyebrow="Chi tiết vai trò">
        <a class="btn btn-secondary" href="{{ route('roles.index') }}"><i data-lucide="arrow-left"></i>Quay lại</a>
        @can('roles.update')
            <a class="btn btn-primary" href="{{ route('roles.edit', $role) }}"><i data-lucide="shield-check"></i>Phân quyền</a>
        @endcan
    </x-ui.page-header>
    <div class="content-grid">
        <section class="panel">
            <div class="section-heading">
                <div>
                    <h2>Quyền được cấp</h2>
                    <p>{{ $role->permissions->count() }} quyền trên {{ $role->permissions->groupBy('module')->count() }}
                        nhóm tài nguyên.</p>
                </div><x-ui.status-badge :status="$role->status" />
            </div>
            @forelse ($role->permissions->groupBy('module') as $module => $items)
                <div class="dashboard-list-row">
                    <div class="dashboard-list-copy">
                        <strong>{{ Str::headline($module) }}</strong><span>{{ $items->pluck('name')->join(' · ') }}</span>
                    </div><span class="badge badge-confirmed">{{ $items->count() }} quyền</span>
                </div>
            @empty
                <x-ui.empty-state icon="shield-off" title="Chưa có quyền"
                    description="Vai trò này chưa thể truy cập module nào." />
            @endforelse
        </section>
        <aside class="panel">
            <div class="section-heading">
                <div>
                    <h2>Nhân viên</h2>
                    <p>Các tài khoản đang nhận vai trò này.</p>
                </div><i data-lucide="users"></i>
            </div>
            @forelse ($role->users as $user)
                <div class="dashboard-list-row">
                    <div class="dashboard-list-copy"><strong>{{ $user->name }}</strong><span>{{ $user->email }}</span>
                    </div><x-ui.status-badge :status="$user->status" />
            </div>@empty<x-ui.empty-state icon="user-round-x" title="Chưa được gán"
                    description="Không có nhân viên nào đang dùng vai trò này." />
            @endforelse
        </aside>
    </div>
@endsection
