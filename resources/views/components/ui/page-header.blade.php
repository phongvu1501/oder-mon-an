@props(['title', 'subtitle' => null, 'eyebrow' => null])
<header class="page-header">
    <div class="page-heading">
        @if ($eyebrow)
            <div class="page-eyebrow">{{ $eyebrow }}</div>
        @endif
        <h1 class="page-title">{{ $title }}</h1>
        @if ($subtitle)
            <p class="page-subtitle">{{ $subtitle }}</p>
        @endif
    </div>
    @if (trim((string) $slot))
        <div class="page-actions">{{ $slot }}</div>
    @endif
</header>
