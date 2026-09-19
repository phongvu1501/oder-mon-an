<div class="modal-layer" id="app-modal" aria-hidden="true">
    <div class="modal-backdrop" data-modal-close></div>
    <section class="modal-dialog modal-dialog--lg" role="dialog" aria-modal="true" aria-labelledby="app-modal-title" tabindex="-1">
        <header class="modal-header"><div><div class="modal-kicker">Chi tiết</div><h2 id="app-modal-title">Đang tải...</h2></div><button class="btn btn-ghost icon-btn" type="button" data-modal-close aria-label="Đóng cửa sổ"><i data-lucide="x"></i></button></header>
        <div class="modal-content" id="app-modal-content"><div class="modal-loading"><span class="spinner"></span> Đang tải dữ liệu...</div></div>
    </section>
</div>

<div class="modal-layer" id="confirm-modal" aria-hidden="true">
    <div class="modal-backdrop" data-confirm-cancel></div>
    <section class="modal-dialog modal-dialog--sm" role="alertdialog" aria-modal="true" aria-labelledby="confirm-modal-title" aria-describedby="confirm-modal-message" tabindex="-1">
        <header class="modal-header"><div><div class="modal-kicker modal-kicker--danger">Xác nhận thao tác</div><h2 id="confirm-modal-title">Bạn có chắc chắn?</h2></div><button class="btn btn-ghost icon-btn" type="button" data-confirm-cancel aria-label="Đóng"><i data-lucide="x"></i></button></header>
        <div class="modal-content"><div class="confirm-symbol"><i data-lucide="triangle-alert"></i></div><p id="confirm-modal-message">Thao tác này có thể ảnh hưởng dữ liệu.</p></div>
        <footer class="modal-footer"><button class="btn btn-secondary" type="button" data-confirm-cancel>Hủy</button><button class="btn btn-danger" type="button" data-confirm-accept>Xác nhận</button></footer>
    </section>
</div>

