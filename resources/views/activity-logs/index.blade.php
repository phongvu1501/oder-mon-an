@extends('layouts.admin')

@section('title', 'Nhật ký hoạt động')

@section('content')
    <x-ui.page-header title="Nhật ký hoạt động"
        subtitle="Theo dõi các thao tác quan trọng; mật khẩu và token không được lưu tại đây." eyebrow="Hệ thống" />
    <form method="get"><x-ui.filter-bar :reset-url="route('activity-logs.index')"><input class="form-control" name="q"
                value="{{ request('q') }}" placeholder="Người dùng, module, hành động hoặc IP"
                aria-label="Tìm nhật ký"><select class="form-select" name="module" aria-label="Lọc module">
                <option value="">Mọi module</option>
                @foreach ($modules as $module)
                    <option value="{{ $module }}" @selected(request('module') === $module)>{{ Str::headline($module) }}</option>
                @endforeach
            </select>
            <select class="form-select" name="action" aria-label="Lọc hành động">
                <option value="">Mọi hành động</option>
                @foreach ($actions as $action)
                    <option value="{{ $action }}" @selected(request('action') === $action)>{{ Str::headline($action) }}</option>
                @endforeach
            </select><input class="form-control" type="date" name="from" value="{{ request('from') }}"
                aria-label="Từ ngày"><input class="form-control" type="date" name="to" value="{{ request('to') }}"
                aria-label="Đến ngày"></x-ui.filter-bar></form>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Thời gian</th>
                    <th>Người dùng</th>
                    <th>Module</th>
                    <th>Hành động</th>
                    <th>IP</th>
                    <th>Thay đổi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                    <tr>
                        <td>{{ $logs->firstItem() + $loop->index }}</td>
                        <td>{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                        <td>{{ $log->user?->name ?? 'Hệ thống' }}</td>
                        <td><span class="badge badge-info">{{ Str::headline($log->module) }}</span></td>
                        <td class="cell-title">{{ Str::headline($log->action) }}</td>
                        <td>{{ $log->ip_address ?: '—' }}</td>
                        <td>
                            <details class="log-details">
                                <summary>Xem dữ liệu</summary>
                                <pre>{{ json_encode(['old' => $log->old_values, 'new' => $log->new_values], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            </details>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7"><x-ui.empty-state icon="history" title="Chưa có nhật ký"
                                description="Các thao tác quan trọng sẽ xuất hiện tại đây." /></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $logs->links() }}</div>
@endsection
