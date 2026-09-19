@props(['icon' => 'inbox', 'title', 'description' => null])
<div class="empty-state">
    <span class="empty-state__icon"><i data-lucide="{{ $icon }}"></i></span>
    <strong>{{ $title }}</strong>
    @if ($description)
        <p>{{ $description }}</p>
    @endif
    @if (trim((string) $slot))
        <div class="empty-state__action">{{ $slot }}</div>
    @endif
</div>
