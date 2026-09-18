@extends('layouts.admin') @section('title', 'Món ăn') @section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Món ăn & đồ uống</h1>
        <p class="page-subtitle">Giá bán, khả dụng và hình ảnh menu.</p>
    </div>
    @can('products.create')
        <a class="btn btn-primary" href="{{ route('products.create') }}"><i data-lucide="plus"></i> Thêm món</a>
    @endcan
</div>
<form class="filters"><input class="form-control" name="q" value="{{ request('q') }}"
        placeholder="Tên hoặc SKU"><select class="form-select" name="category_id">
        <option value="">Mọi danh mục</option>
        @foreach ($categories as $category)
            <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}</option>
        @endforeach
    </select>
    <select class="form-select" name="status">
        <option value="">Mọi trạng thái</option>
        <option value="active" @selected(request('status') === 'active')>Đang dùng</option>
        <option value="inactive" @selected(request('status') === 'inactive')>Ngừng dùng</option>
    </select><button class="btn btn-secondary">Lọc</button>
</form>
<div class="table-wrap">
    <table class="data-table">
        <thead>
            <tr>
                <th>STT</th>
                <th>Món</th>
                <th>Danh mục</th>
                <th>Giá</th>
                <th>Khả dụng</th>
                <th>Trạng thái</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($products as $product)
                <tr>
                    <td>{{ $products->firstItem() + $loop->index }}</td>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px">
                            @if ($product->image)
                                <img class="product-thumb" src="{{ Storage::url($product->image) }}"
                                alt="">@else<div class="product-thumb placeholder-thumb">
                                    {{ mb_strtoupper(mb_substr($product->name, 0, 1)) }}</div>
                            @endif
                            <div>
                                <div class="cell-title">{{ $product->name }}</div>
                                <div class="cell-meta">{{ $product->sku }}</div>
                            </div>
                        </div>
                    </td>
                    <td>{{ $product->category->name }}</td>
                    <td>{{ number_format($product->price) }} đ</td>
                    <td><span
                            class="badge badge-{{ $product->is_available ? 'available' : 'inactive' }}">{{ $product->is_available ? 'Đang bán' : 'Tạm hết' }}</span>
                    </td>
                    <td><span class="badge badge-{{ $product->status->value }}">{{ $product->status->value }}</span>
                    </td>
                    <td>
                        <div class="actions"><a class="btn btn-secondary icon-btn" title="Xem"
                                href="{{ route('products.show', $product) }}"><i data-lucide="eye"></i></a>
                            @can('products.update')
                                <a class="btn btn-secondary icon-btn" title="Sửa"
                                    href="{{ route('products.edit', $product) }}"><i data-lucide="pencil"></i></a>
                                @endcan @can('products.delete')
                                <form method="post" action="{{ route('products.destroy', $product) }}"
                                    data-confirm="Bạn có chắc muốn xóa món này?">@csrf @method('delete')<button
                                        class="btn btn-danger icon-btn" title="Xóa"><i
                                            data-lucide="trash-2"></i></button></form>
                            @endcan
                        </div>
                    </td>
            </tr>@empty<tr>
                    <td colspan="7" class="empty">Chưa có món ăn.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="pagination">{{ $products->links() }}</div>
@endsection
