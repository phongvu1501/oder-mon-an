@extends('layouts.admin')

@section('title', 'Món ăn & đồ uống')

@section('content')
    <x-ui.page-header title="Món ăn & đồ uống" subtitle="Quản lý giá bán, hình ảnh và khả năng phục vụ trên menu."
        eyebrow="Thực đơn">
        @can('products.create')
            <a class="btn btn-primary" href="{{ route('products.create') }}"><i data-lucide="plus"></i>Thêm món</a>
        @endcan
    </x-ui.page-header>
    <form method="get"><x-ui.filter-bar :reset-url="route('products.index')">
            <input class="form-control" name="q" value="{{ request('q') }}" placeholder="Tên món hoặc SKU"
                aria-label="Tìm món">
            <select class="form-select" name="category_id" aria-label="Lọc danh mục">
                <option value="">Mọi danh mục</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            <select class="form-select" name="availability" aria-label="Lọc khả dụng">
                <option value="">Mọi khả dụng</option>
                <option value="available" @selected(request('availability') === 'available')>Đang bán</option>
                <option value="unavailable" @selected(request('availability') === 'unavailable')>Tạm hết</option>
            </select>
            <select class="form-select" name="status" aria-label="Lọc trạng thái">
                <option value="">Mọi trạng thái</option>
                <option value="active" @selected(request('status') === 'active')>Đang dùng</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Ngừng dùng</option>
            </select>
        </x-ui.filter-bar></form>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Món</th>
                    <th>Danh mục</th>
                    <th>Giá bán</th>
                    <th>Khả dụng</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    <tr>
                        <td>{{ $products->firstItem() + $loop->index }}</td>
                        <td>
                            <div class="product-cell">
                                @if ($product->image)
                                    <img class="product-thumb" src="{{ Storage::url($product->image) }}"
                                    alt="{{ $product->name }}" loading="lazy">@else<span
                                        class="product-thumb placeholder-thumb">{{ mb_strtoupper(mb_substr($product->name, 0, 1)) }}</span>
                                @endif
                                <div>
                                    <div class="cell-title">{{ $product->name }}</div>
                                    <div class="cell-meta">{{ $product->sku ?: 'Chưa có SKU' }}</div>
                                </div>
                            </div>
                        </td>
                        <td>{{ $product->category->name }}</td>
                        <td><strong>{{ number_format($product->price) }} đ</strong></td>
                        <td><x-ui.status-badge :status="$product->is_available ? 'available' : 'inactive'" :label="$product->is_available ? 'Đang bán' : 'Tạm hết'" /></td>
                        <td><x-ui.status-badge :status="$product->status" /></td>
                        <td>
                            <div class="actions"><a class="btn btn-secondary icon-btn" title="Xem"
                                    aria-label="Xem {{ $product->name }}" href="{{ route('products.show', $product) }}"><i
                                        data-lucide="eye"></i></a>
                                @can('products.update')
                                    <a class="btn btn-secondary icon-btn" title="Sửa" aria-label="Sửa {{ $product->name }}"
                                        href="{{ route('products.edit', $product) }}"><i data-lucide="pencil"></i></a>
                                    @endcan @can('products.delete')
                                    <form method="post" action="{{ route('products.destroy', $product) }}"
                                        data-confirm="Xóa món {{ $product->name }}?">@csrf @method('delete')<button
                                            class="btn btn-danger icon-btn" title="Xóa"
                                            aria-label="Xóa {{ $product->name }}"><i data-lucide="trash-2"></i></button></form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7"><x-ui.empty-state icon="utensils" title="Chưa có món ăn"
                                description="Tạo món đầu tiên để bắt đầu xây dựng thực đơn.">
                                @can('products.create')
                                    <a class="btn btn-primary" href="{{ route('products.create') }}"><i
                                            data-lucide="plus"></i>Thêm món</a>
                                @endcan
                            </x-ui.empty-state></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $products->links() }}</div>
@endsection
