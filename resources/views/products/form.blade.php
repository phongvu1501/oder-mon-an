@extends('layouts.admin') @section('title', $product->exists ? 'Sửa món' : 'Thêm món') @section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">{{ $product->exists ? 'Sửa món' : 'Thêm món' }}</h1>
    </div><a class="btn btn-secondary" href="{{ route('products.index') }}">Quay lại</a>
</div>
<form class="panel" method="post" enctype="multipart/form-data"
    action="{{ $product->exists ? route('products.update', $product) : route('products.store') }}">@csrf @if ($product->exists)
        @method('put')
    @endif
    <div class="form-grid">
        <div><label class="form-label">Tên món <span class="required">*</span></label><input class="form-control"
                name="name" required value="{{ old('name', $product->name) }}"></div>
        <div><label class="form-label">Danh mục <span class="required">*</span></label><select class="form-select"
                name="category_id" required>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>{{ $category->name }}</option>
                @endforeach
            </select></div>
        <div><label class="form-label">SKU</label><input class="form-control" name="sku"
                value="{{ old('sku', $product->sku) }}"></div>
        <div><label class="form-label">Slug</label><input class="form-control" name="slug"
                value="{{ old('slug', $product->slug) }}" placeholder="Tự tạo từ tên"></div>
        <div><label class="form-label">Giá bán <span class="required">*</span></label><input class="form-control"
                name="price" type="number" min="0" step="0.01" required
                value="{{ old('price', $product->price) }}"></div>
        <div><label class="form-label">Giá vốn</label><input class="form-control" name="cost_price" type="number"
                min="0" step="0.01" value="{{ old('cost_price', $product->cost_price ?? 0) }}"></div>
        <div><label class="form-label">Đơn vị <span class="required">*</span></label><input class="form-control"
                name="unit" required value="{{ old('unit', $product->unit ?? 'phần') }}"></div>
        <div><label class="form-label">Thứ tự</label><input class="form-control" name="sort_order" type="number"
                min="0" value="{{ old('sort_order', $product->sort_order ?? 0) }}"></div>
        <div><label class="form-label">Trạng thái</label><select class="form-select" name="status">
                <option value="active" @selected(old('status', $product->status?->value ?? 'active') === 'active')>Đang dùng</option>
                <option value="inactive" @selected(old('status', $product->status?->value) === 'inactive')>Ngừng dùng</option>
            </select></div>
        <div><label class="form-label">Ảnh món</label><input class="form-control" type="file" name="image"
                accept="image/jpeg,image/png,image/webp">
            <div class="help">JPG, PNG hoặc WebP; tối đa 2 MB.</div>
        </div>
        <div class="form-group full checkbox"><input type="hidden" name="is_available" value="0"><input
                id="available" type="checkbox" name="is_available" value="1" @checked(old('is_available', $product->is_available ?? true))><label
                for="available">Hiện đang bán</label></div>
        <div class="form-group full"><label class="form-label">Mô tả</label>
            <textarea class="form-control" name="description">{{ old('description', $product->description) }}</textarea>
        </div>
    </div>
    <div class="actions" style="margin-top:18px"><button class="btn btn-primary"><i data-lucide="save"></i>
            Lưu</button><a class="btn btn-secondary" href="{{ route('products.index') }}">Hủy</a></div>
</form>
@endsection
