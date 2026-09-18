<?php

namespace App\Services;

use App\Enums\BillStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Bill;
use App\Models\Payment;
use App\Support\BusinessCode;
use App\Support\Decimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(private readonly TableSessionService $sessions) {}

    public function create(Bill $bill, PaymentMethod $method, string $amount, string $requestId, ?string $note = null): Payment
    {
        return DB::transaction(function () use ($bill, $method, $amount, $requestId, $note) {
            $existing = Payment::where('request_id', $requestId)->first();
            if ($existing) {
                return $existing;
            }

            $lockedBill = Bill::query()->lockForUpdate()->findOrFail($bill->id);
            $amountCents = Decimal::toMinor($amount);
            $remaining = Decimal::toMinor($lockedBill->total) - Decimal::toMinor($lockedBill->paid_amount);

            if ($lockedBill->status === BillStatus::Paid || $amountCents <= 0 || $amountCents > $remaining) {
                throw ValidationException::withMessages(['amount' => 'Số tiền vượt quá số dư hoặc hóa đơn đã thanh toán.']);
            }

            return Payment::create([
                'payment_code' => BusinessCode::make('PAY'),
                'request_id' => $requestId,
                'table_session_id' => $lockedBill->table_session_id,
                'bill_id' => $lockedBill->id,
                'method' => $method,
                'amount' => Decimal::fromMinor($amountCents),
                'status' => PaymentStatus::Pending,
                'payment_note' => $note,
            ]);
        }, 3);
    }

    public function confirm(Payment $payment, int $userId, ?string $reference = null): Payment
    {
        return DB::transaction(function () use ($payment, $userId, $reference) {
            $locked = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            if ($locked->status === PaymentStatus::Paid) {
                return $locked;
            }
            if ($locked->status !== PaymentStatus::Pending) {
                throw ValidationException::withMessages(['payment' => 'Chỉ có thể xác nhận giao dịch đang chờ.']);
            }

            $bill = Bill::query()->lockForUpdate()->findOrFail($locked->bill_id);
            $remaining = Decimal::toMinor($bill->total) - Decimal::toMinor($bill->paid_amount);
            if (Decimal::toMinor($locked->amount) > $remaining) {
                throw ValidationException::withMessages(['payment' => 'Xác nhận này sẽ làm hóa đơn bị thanh toán thừa.']);
            }

            $locked->update([
                'status' => PaymentStatus::Paid,
                'confirmed_by' => $userId,
                'transaction_reference' => $reference,
                'paid_at' => now(),
            ]);

            $paidCents = Decimal::toMinor((string) $bill->payments()->where('status', PaymentStatus::Paid->value)->sum('amount'));
            $totalCents = Decimal::toMinor($bill->total);
            $isPaid = $totalCents > 0 && $paidCents >= $totalCents;
            $bill->update([
                'paid_amount' => Decimal::fromMinor($paidCents),
                'status' => $isPaid ? BillStatus::Paid : BillStatus::Partial,
                'paid_at' => $isPaid ? now() : null,
            ]);

            if ($isPaid) {
                $this->sessions->close($bill->tableSession);
            }

            return $locked->refresh()->load('bill', 'tableSession.restaurantTable');
        }, 3);
    }

    public function fail(Payment $payment, PaymentStatus $status): Payment
    {
        if (! in_array($status, [PaymentStatus::Failed, PaymentStatus::Cancelled], true)) {
            throw ValidationException::withMessages(['status' => 'Trạng thái thanh toán không hợp lệ.']);
        }
        if ($payment->status !== PaymentStatus::Pending) {
            throw ValidationException::withMessages(['payment' => 'Giao dịch không còn ở trạng thái chờ.']);
        }
        $payment->update(['status' => $status]);

        return $payment->refresh();
    }

    public function qrPayload(Payment $payment): ?string
    {
        $bank = config('restaurant.bank');
        if (! $bank['code'] || ! $bank['account_number'] || ! $bank['account_name']) {
            return null;
        }

        return implode('|', [
            'BANK='.$bank['code'],
            'ACCOUNT='.$bank['account_number'],
            'NAME='.$bank['account_name'],
            'AMOUNT='.$payment->amount,
            'CONTENT='.$payment->payment_code,
        ]);
    }
}
