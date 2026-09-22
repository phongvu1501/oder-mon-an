@extends('layouts.admin')
@section('title', $inventoryItem->name)
@section('content')
    <x-ui.page-header :title="$inventoryItem->name" :subtitle="$inventoryItem->sku . ' · ' . $inventoryItem->unit" eyebrow="Kho"><a class="btn btn-secondary"
            href="{{ route('inventory.index') }}"><i data-lucide="arrow-left"></i>Danh sách</a></x-ui.page-header>
    <div class="stat-grid">
        <x-ui.stat-card label="Tồn hiện tại" :value="number_format($inventoryItem->current_stock, 3) . ' ' . $inventoryItem->unit" icon="boxes" />
        <x-ui.stat-card label="Tồn tối thiểu" :value="number_format($inventoryItem->minimum_stock, 3) . ' ' . $inventoryItem->unit" icon="triangle-alert" tone="amber" />
        <x-ui.stat-card label="Giá vốn bình quân" :value="number_format($inventoryItem->cost_price) . ' đ'" icon="badge-dollar-sign" tone="blue" />
        <x-ui.stat-card label="Giá trị tồn" :value="number_format($inventoryItem->current_stock * $inventoryItem->cost_price) . ' đ'" icon="wallet" tone="violet" />
    </div>
    <section class="panel" style="margin-top:18px">
        <dl class="detail-list">
            <dt>Đơn vị cơ sở</dt>
            <dd>{{ $inventoryItem->baseUnit?->name ?? $inventoryItem->unit }}</dd>
            <dt>Theo dõi lô</dt>
            <dd>{{ $inventoryItem->track_batch ? 'Có' : 'Không' }}</dd>
            <dt>Theo dõi HSD</dt>
            <dd>{{ $inventoryItem->track_expiry ? 'Có' : 'Không' }}</dd>
            <dt>Trạng thái tồn</dt>
            <dd><x-ui.status-badge :status="$inventoryItem->stock_status" /></dd>
        </dl>
    </section>
    <section class="panel" style="margin-top:18px">
        <div class="section-heading">
            <div>
                <h2>Tồn theo kho</h2>
            </div><i data-lucide="warehouse"></i>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Kho</th>
                        <th>Tồn</th>
                        <th>Giá vốn bình quân</th>
                        <th>Giá trị</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($inventoryItem->stocks as $stock)
                        <tr>
                            <td>{{ $stock->warehouse->name }}</td>
                            <td>{{ number_format($stock->current_stock, 3) }} {{ $inventoryItem->unit }}</td>
                            <td>{{ number_format($stock->average_cost) }} đ</td>
                            <td>{{ number_format($stock->current_stock * $stock->average_cost) }} đ</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
    <section class="panel" style="margin-top:18px">
        <div class="section-heading">
            <div>
                <h2>Lô hàng</h2>
            </div><i data-lucide="package-search"></i>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Lô</th>
                        <th>Còn lại</th>
                        <th>Giá lô</th>
                        <th>HSD</th>
                        <th>Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($inventoryItem->batches as $batch)
                        <tr>
                            <td>{{ $batch->batch_code }}</td>
                            <td>{{ number_format($batch->remaining_quantity, 3) }}</td>
                            <td>{{ number_format($batch->unit_cost) }} đ</td>
                            <td>{{ $batch->expiry_date?->format('d/m/Y') ?? '—' }}</td>
                            <td>{{ $batch->status }}</td>
                    </tr>@empty<tr>
                            <td colspan="5">Không theo dõi lô hoặc chưa có lô.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    <section class="panel" style="margin-top:18px">
        <div class="section-heading">
            <div>
                <h2>Lịch sử biến động</h2>
            </div><i data-lucide="history"></i>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Ngày</th>
                        <th>Mã giao dịch</th>
                        <th>Loại</th>
                        <th>Tồn trước</th>
                        <th>Nhập</th>
                        <th>Xuất</th>
                        <th>Tồn sau</th>
                        <th>Đơn giá</th>
                        <th>Giá trị</th>
                        <th>Người thực hiện</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ledger as $line)
                        <tr>
                            <td>{{ $line->transaction->occurred_at?->format('d/m/Y H:i') }}</td>
                            <td>{{ $line->transaction->transaction_code }}</td>
                            <td>{{ $line->transaction->type->value }}</td>
                            <td>{{ number_format($line->before_quantity, 3) }}</td>
                            <td>{{ $line->quantity > 0 ? number_format($line->quantity, 3) : '—' }}</td>
                            <td>{{ $line->quantity < 0 ? number_format(abs($line->quantity), 3) : '—' }}</td>
                            <td>{{ number_format($line->after_quantity, 3) }}</td>
                            <td>{{ number_format($line->unit_cost) }} đ</td>
                            <td>{{ number_format($line->stock_value) }} đ</td>
                            <td>{{ $line->transaction->creator?->name ?? 'Hệ thống' }}</td>
                    </tr>@empty<tr>
                            <td colspan="10"><x-ui.empty-state icon="history" title="Chưa có biến động" /></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $ledger->links() }}</div>
    </section>
@endsection
