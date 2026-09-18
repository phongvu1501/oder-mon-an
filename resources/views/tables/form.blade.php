@extends('layouts.admin') @section('title', $table->exists ? 'Sửa bàn' : 'Thêm bàn') @section('content')<div class="page-header">
    <h1 class="page-title">{{ $table->exists ? 'Sửa bàn' : 'Thêm bàn' }}</h1><a class="btn btn-secondary"
        href="{{ route('tables.index') }}">Quay lại</a>
</div>
<form class="panel" method="post" action="{{ $table->exists ? route('tables.update', $table) : route('tables.store') }}">@csrf
    @if ($table->exists)
        @method('put')
    @endif
    <div class="form-grid">
        <div><label class="form-label">Mã bàn <span class="required">*</span></label><input class="form-control"
                name="code" required value="{{ old('code', $table->code) }}"></div>
        <div><label class="form-label">Tên bàn <span class="required">*</span></label><input class="form-control"
                name="name" required value="{{ old('name', $table->name) }}"></div>
        <div><label class="form-label">Sức chứa</label><input class="form-control" type="number" min="1"
                max="100" name="capacity" value="{{ old('capacity', $table->capacity ?? 4) }}"></div>
        <div><label class="form-label">Vị trí</label><input class="form-control" name="location"
                value="{{ old('location', $table->location) }}"></div>
        <div><label class="form-label">Trạng thái</label><select class="form-select" name="status">
                @foreach (['available' => 'Sẵn sàng', 'reserved' => 'Đã đặt', 'maintenance' => 'Bảo trì', 'inactive' => 'Ngừng dùng'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $table->status?->value ?? 'available') === $value)>{{ $label }}</option>
                @endforeach
            </select></div>
    </div>
    <div class="actions" style="margin-top:18px"><button class="btn btn-primary"><i data-lucide="save"></i>
            Lưu</button><a class="btn btn-secondary" href="{{ route('tables.index') }}">Hủy</a></div>
</form>@endsection
