@props(['label', 'value', 'icon', 'tone' => 'teal', 'meta' => null])
<article class="stat-card stat-card--{{ $tone }}">
    <div class="stat-card__top"><span class="stat-card__icon"><i data-lucide="{{ $icon }}"></i></span><span class="stat-card__label">{{ $label }}</span></div>
    <div class="stat-card__value">{{ $value }}</div>
    @if($meta)<div class="stat-card__meta">{{ $meta }}</div>@endif
</article>

