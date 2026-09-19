<div class="toast-region" aria-live="polite" aria-atomic="true">
    @if (session('success'))
        <div class="toast toast--success" data-toast><i data-lucide="circle-check"></i>
            <div><strong>Thành công</strong><span>{{ session('success') }}</span></div><button type="button"
                data-toast-close aria-label="Đóng"><i data-lucide="x"></i></button>
        </div>
    @endif
    @if (session('error'))
        <div class="toast toast--error" data-toast><i data-lucide="circle-alert"></i>
            <div><strong>Không thể thực hiện</strong><span>{{ session('error') }}</span></div><button type="button"
                data-toast-close aria-label="Đóng"><i data-lucide="x"></i></button>
        </div>
    @endif
</div>
