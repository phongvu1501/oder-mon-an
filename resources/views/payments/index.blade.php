@extends('layouts.admin')

@section('title', 'Thanh toán')

@section('content')
    <x-ui.page-header title="Thanh toán" subtitle="QR chỉ tạo yêu cầu; nhân viên vẫn phải xác nhận tiền thực nhận."
        eyebrow="Vận hành" />
    @can('payments.create')
        <section class="panel" style="margin-bottom:18px">
            <div class="section-heading">
                <div>
                    <h2>Tạo giao dịch</h2>
                    <p>Tối đa 100 hóa đơn chưa thanh toán gần nhất được hiển thị.</p>
                </div><i data-lucide="circle-dollar-sign"></i>
            </div>
            @if ($bills->isNotEmpty())
                <form method="post" action="{{ route('payments.store') }}">@csrf<input type="hidden" name="request_id"
                        value="{{ Str::uuid() }}">
                    <div class="form-grid">
                        <div class="form-group full"><label class="form-label" for="payment-bill">Hóa đơn <span
                                    class="required">*</span></label><select class="form-select" id="payment-bill"
                                name="bill_id" required>
                                @foreach ($bills as $bill)
                                    <option value="{{ $bill->id }}">{{ $bill->bill_code }} ·
                                        {{ $bill->tableSession->restaurantTable->name }} · còn
                                        {{ number_format($bill->total - $bill->paid_amount) }} đ</option>
                                @endforeach
                            </select></div>
                        <div><label class="form-label" for="payment-method">Phương thức</label><select class="form-select"
                                id="payment-method" name="method">
                                <option value="cash">Tiền mặt</option>
                                <option value="bank_transfer">Chuyển khoản</option>
                                <option value="qr">QR</option>
                            </select></div>
                        <div><label class="form-label" for="payment-amount">Số tiền <span
                                    class="required">*</span></label><input class="form-control" id="payment-amount"
                                type="number" min="1" step="0.01" name="amount" required></div>
                        <div class="form-group full"><label class="form-label" for="payment-note">Ghi chú</label><input
                                class="form-control" id="payment-note" name="payment_note" maxlength="500"></div>
                    </div>
                    <div class="form-footer"><button class="btn btn-primary" type="submit"><i data-lucide="plus"></i>Tạo giao
                            dịch</button></div>
                </form>
            @else
                <x-ui.empty-state icon="receipt" title="Không có hóa đơn chờ"
                    description="Hóa đơn chưa thanh toán sẽ xuất hiện tại đây." />
            @endif
        </section>
    @endcan

    <form method="get"><x-ui.filter-bar :reset-url="route('payments.index')">
            <input class="form-control" name="q" value="{{ request('q') }}"
                placeholder="Mã thanh toán, hóa đơn, đối soát" aria-label="Tìm giao dịch">
            <select class="form-select" name="method" aria-label="Lọc phương thức">
                <option value="">Mọi phương thức</option>
                <option value="cash" @selected(request('method') === 'cash')>Tiền mặt</option>
                <option value="bank_transfer" @selected(request('method') === 'bank_transfer')>Chuyển khoản</option>
                <option value="qr" @selected(request('method') === 'qr')>QR</option>
            </select>
            <select class="form-select" name="status" aria-label="Lọc trạng thái">
                <option value="">Mọi trạng thái</option>
                <option value="pending" @selected(request('status') === 'pending')>Đang chờ</option>
                <option value="paid" @selected(request('status') === 'paid')>Đã thanh toán</option>
                <option value="failed" @selected(request('status') === 'failed')>Thất bại</option>
                <option value="cancelled" @selected(request('status') === 'cancelled')>Đã hủy</option>
                <option value="refunded" @selected(request('status') === 'refunded')>Đã hoàn tiền</option>
            </select>
        </x-ui.filter-bar></form>

    @php $methodLabels = ['cash' => 'Tiền mặt', 'bank_transfer' => 'Chuyển khoản', 'qr' => 'QR']; @endphp
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Giao dịch</th>
                    <th>Bàn / hóa đơn</th>
                    <th>Phương thức</th>
                    <th>Số tiền</th>
                    <th>Trạng thái</th>
                    <th>Thời gian</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($payments as $payment)
                    <tr>
                        <td>{{ $payments->firstItem() + $loop->index }}</td>
                        <td>
                            <div class="cell-title">{{ $payment->payment_code }}</div>
                            <div class="cell-meta">{{ $payment->transaction_reference ?: 'Chưa có mã đối soát' }}</div>
                        </td>
                        <td>
                            <div>{{ $payment->tableSession->restaurantTable->name }}</div>
                            <div class="cell-meta">{{ $payment->bill->bill_code }}</div>
                        </td>
                        <td>{{ $methodLabels[$payment->method->value] ?? $payment->method->value }}</td>
                        <td><strong>{{ number_format($payment->amount) }} đ</strong></td>
                        <td><x-ui.status-badge :status="$payment->status" /></td>
                        <td>{{ $payment->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                            @if ($payment->status->value === 'pending')
                                @can('payments.confirm')
                                    <div class="table-action-stack">
                                        <form method="post" action="{{ route('payments.confirm', $payment) }}"
                                            data-confirm="Xác nhận đã thực nhận khoản thanh toán này?">@csrf<input
                                                name="transaction_reference" class="form-control" maxlength="255"
                                                placeholder="Mã đối soát"><button class="btn btn-primary" type="submit"><i
                                                    data-lucide="badge-check"></i>Xác nhận</button></form>
                                        <form method="post" action="{{ route('payments.fail', $payment) }}"
                                            data-confirm="Đánh dấu giao dịch này là thất bại?">@csrf @method('patch')<input
                                                type="hidden" name="status" value="failed"><button class="btn btn-secondary"
                                                type="submit"><i data-lucide="x-circle"></i>Thất bại</button></form>
                                    </div>
                                @endcan
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8"><x-ui.empty-state icon="wallet-cards" title="Chưa có giao dịch"
                                description="Thử thay đổi bộ lọc hoặc tạo giao dịch mới." /></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $payments->links() }}</div>
@endsection
