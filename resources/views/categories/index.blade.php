@extends('layouts.admin')

@section('title', 'Danh mục')

@section('content')
    <x-ui.page-header title="Danh mục" subtitle="Sắp xếp các nhóm món hiển thị trên thực đơn QR." eyebrow="Thực đơn">
        @can('categories.create')
            <a class="btn btn-primary" href="{{ route('categories.create') }}"><i data-lucide="plus"></i>Thêm danh mục</a>
        @endcan
    </x-ui.page-header>
    <form method="get"><x-ui.filter-bar :reset-url="route('categories.index')"><input class="form-control" name="q"
                value="{{ request('q') }}" placeholder="Tìm tên danh mục" aria-label="Tìm danh mục"><select
                class="form-select" name="status" aria-label="Lọc trạng thái">
                <option value="">Mọi trạng thái</option>
                <option value="active" @selected(request('status') === 'active')>Đang dùng</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Ngừng dùng</option>
            </select></x-ui.filter-bar></form>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Danh mục</th>
                    <th>Đường dẫn</th>
                    <th>Thứ tự</th>
                    <th>Số món</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($categories as $category)
                    <tr>
                        <td>{{ $categories->firstItem() + $loop->index }}</td>
                        <td>
                            <div class="cell-title">{{ $category->name }}</div>
                            <div class="cell-meta">{{ Str::limit($category->description ?: 'Chưa có mô tả', 70) }}</div>
                        </td>
                        <td>{{ $category->slug }}</td>
                        <td>{{ $category->sort_order }}</td>
                        <td><strong>{{ $category->products_count }}</strong> món</td>
                        <td><x-ui.status-badge :status="$category->status" /></td>
                        <td>
                            <div class="actions"><a class="btn btn-secondary icon-btn" title="Xem"
                                    aria-label="Xem {{ $category->name }}"
                                    href="{{ route('categories.show', $category) }}"><i data-lucide="eye"></i></a>
                                @can('categories.update')
                                    <a class="btn btn-secondary icon-btn" title="Sửa" aria-label="Sửa {{ $category->name }}"
                                        href="{{ route('categories.edit', $category) }}"><i data-lucide="pencil"></i></a>
                                    @endcan @can('categories.delete')
                                    <form method="post" action="{{ route('categories.destroy', $category) }}"
                                        data-confirm="Xóa danh mục {{ $category->name }}?">@csrf @method('delete')<button
                                            class="btn btn-danger icon-btn" title="Xóa"
                                            aria-label="Xóa {{ $category->name }}"><i data-lucide="trash-2"></i></button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7"><x-ui.empty-state icon="tags" title="Chưa có danh mục"
                                description="Tạo danh mục đầu tiên để tổ chức thực đơn.">
                                @can('categories.create')
                                    <a class="btn btn-primary" href="{{ route('categories.create') }}"><i
                                            data-lucide="plus"></i>Thêm danh mục</a>
                                @endcan
                            </x-ui.empty-state></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $categories->links() }}</div>
@endsection
