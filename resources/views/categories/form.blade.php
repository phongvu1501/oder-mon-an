@extends('layouts.admin') @section('title', $category->exists ? 'Sửa danh mục' : 'Thêm danh mục') @section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">{{ $category->exists ? 'Sửa danh mục' : 'Thêm danh mục' }}</h1>
    </div><a class="btn btn-secondary" href="{{ route('categories.index') }}">Quay lại</a>
</div>
<form class="panel" method="post"
    action="{{ $category->exists ? route('categories.update', $category) : route('categories.store') }}">@csrf @if ($category->exists)
        @method('put')
    @endif
    <div class="form-grid">
        <div class="form-group"><label class="form-label">Tên <span class="required">*</span></label><input
                class="form-control" name="name" value="{{ old('name', $category->name) }}" required></div>
        <div class="form-group"><label class="form-label">Slug</label><input class="form-control" name="slug"
                value="{{ old('slug', $category->slug) }}" placeholder="Tự tạo từ tên"></div>
        <div class="form-group"><label class="form-label">Thứ tự</label><input class="form-control" name="sort_order"
                type="number" min="0" value="{{ old('sort_order', $category->sort_order ?? 0) }}"></div>
        <div class="form-group"><label class="form-label">Trạng thái</label><select class="form-select" name="status">
                <option value="active" @selected(old('status', $category->status?->value ?? 'active') === 'active')>Đang dùng</option>
                <option value="inactive" @selected(old('status', $category->status?->value) === 'inactive')>Ngừng dùng</option>
            </select></div>
        <div class="form-group full"><label class="form-label">Mô tả</label>
            <textarea class="form-control" name="description">{{ old('description', $category->description) }}</textarea>
        </div>
    </div>
    <div class="actions" style="margin-top:18px"><button class="btn btn-primary" type="submit"><i
                data-lucide="save"></i> Lưu</button><a class="btn btn-secondary"
            href="{{ route('categories.index') }}">Hủy</a></div>
</form>
@endsection
