@extends('layouts.admin') @section('title', 'Thanh toán') @section('content')<div class="page-header">
    <div>
        <h1 class="page-title">Thanh toán</h1>
        <p class="page-subtitle">QR chỉ tạo yêu cầu; nhân viên phải xác nhận tiền thực nhận.</p>
    </div>
</div>
@can('payments.create')
    <form class="panel" method="post" action="{{ route('payments.store') }}" style="margin-bottom:18px">@csrf<input
            type="hidden" name="request_id" value="{{ Str::uuid() }}">
        <div class="form-grid">
            <div><label class="form-label">Hóa đơn</label><select class="form-select" name="bill_id" required>
                    @foreach ($bills as $bill)
                        <option value="{{ $bill->id }}">{{ $bill->bill_code }} ·
                            {{ $bill->tableSession->restaurantTable->name }} ·
                            {{ number_format($bill->total - $bill->paid_amount) }} đ</option>
                    @endforeach
                </select>
            </div>
            <div><label class="form-label">Phương thức</label><select class="form-select" name="method">
                    <option value="cash">Tiền mặt</option>
                    <option value="bank_transfer">Chuyển khoản</option>
                    <option value="qr">QR</option>
                </select></div>
            <div><label class="form-label">Số tiền</label><input class="form-control" type="number" min="1"
                    step="0.01" name="amount" required></div>
            <div><label class="form-label">Ghi chú</label><input class="form-control" name="payment_note"></div>
        </div><button class="btn btn-primary" style="margin-top:14px"><i data-lucide="plus"></i> Tạo giao dịch</button>
</form>@endcan
<div class="table-wrap">
    <table class="data-table">
        <thead>
            <tr>
                <th>Mã</th>
                <th>Bàn</th>
                <th>Phương thức</th>
                <th>Số tiền</th>
                <th>Trạng thái</th>
                <th>Thời gian</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($payments as $payment)
                <tr>
                    <td class="cell-title">{{ $payment->payment_code }}</td>
                    <td>{{ $payment->tableSession->restaurantTable->name }}</td>
                    <td>{{ $payment->method->value }}</td>
                    <td>{{ number_format($payment->amount) }} đ</td>
                    <td><span class="badge badge-{{ $payment->status->value }}">{{ $payment->status->value }}</span>
                    </td>
                    <td>{{ $payment->created_at->format('d/m H:i') }}</td>
                    <td>
                        @if ($payment->status->value === 'pending')
                            @can('payments.confirm')
                                <form method="post" action="{{ route('payments.confirm', $payment) }}"
                                    data-confirm="Xác nhận đã thực nhận khoản thanh toán này?">@csrf<input
                                        name="transaction_reference" class="form-control" placeholder="Mã đối soát"><button
                                        class="btn btn-primary" style="margin-top:6px">Xác nhận</button></form>
                            @endcan
                        @endif
                    </td>
            </tr>@empty<tr>
                    <td colspan="7" class="empty">Chưa có giao dịch.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="pagination">{{ $payments->links() }}</div>@endsection
