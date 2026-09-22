@extends('layouts.admin')
@section('title', 'Thống kê kho')
@section('content')
    <x-ui.page-header title="Thống kê kho" subtitle="Nhập, xuất, tồn và cảnh báo trong một màn hình." eyebrow="Kho">
        @can('inventory.receive')
            <a class="btn btn-primary" href="{{ route('inventory.receipts.create') }}"><i data-lucide="package-plus"></i>Nhập
                kho</a>
        @endcan
    </x-ui.page-header>
    <form method="get" class="panel" style="margin-bottom:18px">
        <div class="filters">
            <select class="form-select" name="period">
                <option value="today" @selected($period === 'today')>Hôm nay</option>
                <option value="7days" @selected($period === '7days')>7 ngày</option>
                <option value="30days" @selected($period === '30days')>30 ngày</option>
                <option value="month" @selected($period === 'month')>Tháng này</option>
                <option value="custom" @selected($period === 'custom')>Tuỳ chọn</option>
            </select>
            <input class="form-control" type="date" name="from" value="{{ request('from') }}"><input
                class="form-control" type="date" name="to" value="{{ request('to') }}">
            <button class="btn btn-primary"><i data-lucide="filter"></i>Lọc</button><a class="btn btn-secondary"
                href="{{ route('inventory.dashboard') }}"><i data-lucide="rotate-ccw"></i>Đặt lại</a>
        </div>
    </form>
    <div class="stat-grid">
        <x-ui.stat-card label="Mặt hàng" :value="number_format($metrics['total'])" icon="boxes" />
        <x-ui.stat-card label="Đang hoạt động" :value="number_format($metrics['active'])" icon="circle-check" tone="blue" />
        <x-ui.stat-card label="Sắp hết" :value="number_format($metrics['low'])" icon="triangle-alert" tone="amber" />
        <x-ui.stat-card label="Hết hàng" :value="number_format($metrics['out'])" icon="circle-x" tone="red" />
        <x-ui.stat-card label="Giá trị tồn" :value="number_format($metrics['value']) . ' đ'" icon="badge-dollar-sign" tone="violet" />
        <x-ui.stat-card label="Giá trị nhập" :value="number_format($metrics['in']) . ' đ'" icon="arrow-down-to-line" />
        <x-ui.stat-card label="Giá trị xuất" :value="number_format($metrics['out_value']) . ' đ'" icon="arrow-up-from-line" tone="blue" />
        <x-ui.stat-card label="Hao hụt" :value="number_format($metrics['waste']) . ' đ'" icon="trash-2" tone="red" />
        <x-ui.stat-card label="Phiếu nhập" :value="number_format($metrics['receipts'])" icon="clipboard-plus" />
        <x-ui.stat-card label="Phiếu xuất" :value="number_format($metrics['issues'])" icon="clipboard-minus" tone="blue" />
        <x-ui.stat-card label="Kiểm kê" :value="number_format($metrics['stocktakes'])" icon="clipboard-check" tone="amber" />
    </div>
    <div class="content-grid" style="margin-top:18px">
        <section class="panel">
            <div class="section-heading">
                <div>
                    <h2>Cảnh báo tồn</h2>
                </div><i data-lucide="triangle-alert"></i>
            </div>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Mặt hàng</th>
                            <th>Tồn</th>
                            <th>Tối thiểu</th>
                            <th>Thiếu</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($lowItems as $item)
                            <tr>
                                <td><a class="cell-title"
                                        href="{{ route('inventory.items.show', $item) }}">{{ $item->name }}</a>
                                    <div class="cell-meta">{{ $item->sku }}</div>
                                </td>
                                <td>{{ number_format($item->current_stock, 3) }} {{ $item->unit }}</td>
                                <td>{{ number_format($item->minimum_stock, 3) }}</td>
                                <td>{{ number_format(max(0, $item->minimum_stock - $item->current_stock), 3) }}</td>
                            </tr>
                        @empty<tr>
                                <td colspan="4"><x-ui.empty-state icon="circle-check" title="Không có cảnh báo tồn" />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
        <section class="panel">
            <div class="section-heading">
                <div>
                    <h2>Hạn sử dụng</h2>
                </div><i data-lucide="calendar-clock"></i>
            </div>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Lô</th>
                            <th>Mặt hàng</th>
                            <th>Còn lại</th>
                            <th>HSD</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($expiringBatches as $batch)
                            <tr>
                                <td class="cell-title">{{ $batch->batch_code }}</td>
                                <td>{{ $batch->inventoryItem->name }}</td>
                                <td>{{ number_format($batch->remaining_quantity, 3) }}</td>
                                <td><x-ui.status-badge :status="$batch->expiry_date->isPast() ? 'out' : 'low'" :label="$batch->expiry_date->format('d/m/Y')" /></td>
                            </tr>
                        @empty<tr>
                                <td colspan="4"><x-ui.empty-state icon="calendar-check"
                                        title="Không có lô sắp hết hạn" /></td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
