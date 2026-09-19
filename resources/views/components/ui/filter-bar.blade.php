@props(['resetUrl', 'title' => 'Tìm kiếm & bộ lọc'])
<section class="filter-panel" aria-label="{{ $title }}">
    <div class="filter-panel__heading">
        <span><i data-lucide="sliders-horizontal"></i>{{ $title }}</span>
        <button class="icon-btn btn btn-ghost filter-toggle" type="button" data-filter-toggle aria-expanded="true"
            title="Thu gọn bộ lọc"><i data-lucide="chevron-up"></i></button>
    </div>
    <div class="filter-panel__body" data-filter-body>
        <div class="filter-fields">{{ $slot }}</div>
        <div class="filter-actions">
            <button class="btn btn-primary" type="submit"><i data-lucide="search"></i> Áp dụng</button>
            <a class="btn btn-secondary" href="{{ $resetUrl }}"><i data-lucide="rotate-ccw"></i> Đặt lại</a>
        </div>
    </div>
</section>
