@extends('layouts.admin') @section('title', $category->name) @section('content')<div class="page-header">
    <div>
        <h1 class="page-title">{{ $category->name }}</h1>
        <p class="page-subtitle">{{ $category->description ?: 'Không có mô tả' }}</p>
    </div>
    <div class="actions"><a class="btn btn-secondary" href="{{ route('categories.index') }}">Quay lại</a>
        @can('categories.update')
            <a class="btn btn-primary" href="{{ route('categories.edit', $category) }}">Sửa</a>
        @endcan
    </div>
</div>
<div class="panel">
    <dl class="detail-list">
        <dt>Slug</dt>
        <dd>{{ $category->slug }}</dd>
        <dt>Thứ tự</dt>
        <dd>{{ $category->sort_order }}</dd>
        <dt>Trạng thái</dt>
        <dd><span class="badge badge-{{ $category->status->value }}">{{ $category->status->value }}</span></dd>
    </dl>
</div>@endsection

