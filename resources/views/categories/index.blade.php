@extends('layouts.admin') @section('title', 'Danh mục') @section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Danh mục</h1>
        <p class="page-subtitle">Sắp xếp nhóm món hiển thị trên menu.</p>
    </div>
    @can('categories.create')
        <a class="btn btn-primary" href="{{ route('categories.create') }}"><i data-lucide="plus"></i> Thêm danh mục</a>
    @endcan
</div>
<form class="filters"><input class="form-control" name="q" value="{{ request('q') }}"
        placeholder="Tìm danh mục"><select class="form-select" name="status">
        <option value="">Mọi trạng thái</option>
        <option value="active" @selected(request('status') === 'active')>Đang dùng</option>
        <option value="inactive" @selected(request('status') === 'inactive')>Ngừng dùng</option>
    </select><button class="btn btn-secondary">Lọc</button></form>
<div class="table-wrap">
    <table class="data-table">
        <thead>
            <tr>
                <th>STT</th>
                <th>Tên</th>
                <th>Slug</th>
                <th>Món</th>
                <th>Trạng thái</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($categories as $category)
                <tr>
                    <td>{{ $categories->firstItem() + $loop->index }}</td>
                    <td class="cell-title">{{ $category->name }}</td>
                    <td>{{ $category->slug }}</td>
                    <td>{{ $category->products_count }}</td>
                    <td><span class="badge badge-{{ $category->status->value }}">{{ $category->status->value }}</span>
                    </td>
                    <td>
                        <div class="actions"><a class="btn btn-secondary icon-btn" title="Xem"
                                href="{{ route('categories.show', $category) }}"><i data-lucide="eye"></i></a>
                            @can('categories.update')
                                <a class="btn btn-secondary icon-btn" title="Sửa"
                                    href="{{ route('categories.edit', $category) }}"><i data-lucide="pencil"></i></a>
                                @endcan @can('categories.delete')
                                <form method="post" action="{{ route('categories.destroy', $category) }}"
                                    data-confirm="Bạn có chắc muốn xóa danh mục này?">@csrf @method('delete')<button
                                        class="btn btn-danger icon-btn" title="Xóa"><i
                                            data-lucide="trash-2"></i></button></form>
                            @endcan
                        </div>
                    </td>
            </tr>@empty<tr>
                    <td colspan="6" class="empty">Chưa có danh mục.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="pagination">{{ $categories->links() }}</div>
@endsection
