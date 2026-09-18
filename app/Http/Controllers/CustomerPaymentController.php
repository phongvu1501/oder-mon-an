<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Services\PaymentService;
use App\Services\QrCodeService;
use App\Support\Decimal;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CustomerPaymentController extends Controller
{
    public function store(Request $request, PaymentService $payments, QrCodeService $qr): View
    {
        $data = $request->validate(['request_id' => ['required', 'uuid']]);
        $session = CustomerMenuController::boundSession($request);
        $bill = $session->bill()->lockForUpdate()->firstOrFail();
        $remaining = Decimal::toMinor($bill->total) - Decimal::toMinor($bill->paid_amount);
        if ($remaining <= 0) {
            throw ValidationException::withMessages(['payment' => 'Hóa đơn không còn số dư cần thanh toán.']);
        }

        $payment = $payments->create($bill, PaymentMethod::Qr, Decimal::fromMinor($remaining), $data['request_id']);
        $payload = $payments->qrPayload($payment);

        return view('customer.payment', [
            'payment' => $payment,
            'bill' => $bill->refresh(),
            'qrSvg' => $payload ? $qr->svg($payload) : null,
        ]);
    }
}
