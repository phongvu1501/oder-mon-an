@php
    $toast = session('success')
        ? ['success', 'Thành công', session('success')]
        : (session('error')
            ? ['error', 'Không thể thực hiện', session('error')]
            : (session('warning')
                ? ['warning', 'Cần lưu ý', session('warning')]
                : (session('info')
                    ? ['info', 'Thông tin', session('info')]
                    : null)));
    if (!$toast && $errors->any()) {
        $toast = ['error', 'Dữ liệu chưa hợp lệ', $errors->first()];
    }
    $icons = ['success' => 'circle-check', 'error' => 'circle-alert', 'warning' => 'triangle-alert', 'info' => 'info'];
@endphp
<div class="toast-region" data-toast-region aria-live="polite" aria-atomic="true">
    @if ($toast)
        <div class="toast toast--{{ $toast[0] }}" data-toast role="status">
            <span class="toast-icon"><i data-lucide="{{ $icons[$toast[0]] }}"></i></span>
            <div class="toast-copy"><strong>{{ $toast[1] }}</strong><span>{{ $toast[2] }}</span></div>
            <button class="toast-close" type="button" data-toast-close aria-label="Đóng thông báo"><i
                    data-lucide="x"></i></button>
            <span class="toast-progress" aria-hidden="true"></span>
        </div>
    @endif
</div>
