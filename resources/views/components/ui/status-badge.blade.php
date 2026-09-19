@props(['status'])
@php
    $value = $status instanceof \BackedEnum ? $status->value : (string) $status;
    $labels = [
        'active' => 'Hoạt động',
        'inactive' => 'Ngừng dùng',
        'available' => 'Sẵn sàng',
        'occupied' => 'Đang sử dụng',
        'reserved' => 'Đã đặt',
        'maintenance' => 'Bảo trì',
        'pending' => 'Đang chờ',
        'confirmed' => 'Đã xác nhận',
        'preparing' => 'Đang chuẩn bị',
        'ready' => 'Sẵn sàng',
        'served' => 'Đã phục vụ',
        'completed' => 'Hoàn thành',
        'cancelled' => 'Đã hủy',
        'paid' => 'Đã thanh toán',
        'unpaid' => 'Chưa thanh toán',
        'partial' => 'Thanh toán một phần',
        'failed' => 'Thất bại',
        'refunded' => 'Đã hoàn tiền',
        'low' => 'Sắp hết',
        'out' => 'Hết hàng',
    ];
@endphp
<span class="badge badge-{{ $value }}"><span
        class="badge-dot"></span>{{ $labels[$value] ?? ucfirst($value) }}</span>
