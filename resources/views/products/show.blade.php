@extends('layouts.admin') @section('title', $product->name) @section('content')<div class="page-header">
    <div>
        <h1 class="page-title">{{ $product->name }}</h1>
        <p class="page-subtitle">{{ $product->category->name }} · {{ $product->sku }}</p>
    </div>
    <div class="actions"><a class="btn btn-secondary" href="{{ route('products.index') }}">Quay lại</a>
        @can('inventory.update')
            <a class="btn btn-secondary" href="{{ route('recipes.edit', $product) }}"><i data-lucide="notebook-tabs"></i> Định
                mức</a>
            @endcan @can('products.update')
            <a class="btn btn-primary" href="{{ route('products.edit', $product) }}">Sửa</a>
        @endcan
    </div>
</div>
<div class="content-grid">
    <div class="panel">
        <dl class="detail-list">
            <dt>Giá bán</dt>
            <dd>{{ number_format($product->price) }} đ</dd>
            <dt>Giá vốn</dt>
            <dd>{{ number_format($product->cost_price) }} đ</dd>
            <dt>Đơn vị</dt>
            <dd>{{ $product->unit }}</dd>
            <dt>Khả dụng</dt>
            <dd>{{ $product->is_available ? 'Đang bán' : 'Tạm hết' }}</dd>
            <dt>Mô tả</dt>
            <dd>{{ $product->description ?: 'Không có mô tả' }}</dd>
        </dl>
    </div>
    <aside class="panel">
        <h2 class="section-title">Định mức</h2>
        @forelse($product->recipe?->items??[] as $item)
            <div>{{ $item->inventoryItem->name }}: <strong>{{ $item->quantity }} {{ $item->unit }}</strong></div>
        @empty<div class="empty">Chưa có định mức.</div>
        @endforelse
    </aside>
</div>@endsection
