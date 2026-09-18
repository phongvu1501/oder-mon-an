<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Bill;
use App\Models\Payment;
use App\Services\ActivityLogService;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(private readonly PaymentService $payments, private readonly ActivityLogService $activity) {}

    public function index(Request $request): View
    {
        $payments = Payment::with('tableSession.restaurantTable', 'bill', 'confirmer')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('method'), fn ($q) => $q->where('method', $request->method))
            ->latest()->paginate(config('restaurant.pagination'))->withQueryString();
        $bills = Bill::with('tableSession.restaurantTable')->whereIn('status', ['unpaid', 'partial'])->latest()->get();

        return view('payments.index', compact('payments', 'bills'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'bill_id' => ['required', 'exists:bills,id'],
            'request_id' => ['required', 'uuid'],
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'amount' => ['required', 'decimal:0,2', 'gt:0'],
            'payment_note' => ['nullable', 'string', 'max:500'],
        ]);
        $payment = $this->payments->create(Bill::findOrFail($data['bill_id']), PaymentMethod::from($data['method']), $data['amount'], $data['request_id'], $data['payment_note'] ?? null);
        $this->activity->write('create', 'payments', $payment, new: $payment->toArray());

        return back()->with('success', 'Đã tạo giao dịch chờ xác nhận.');
    }

    public function confirm(Request $request, Payment $payment): RedirectResponse
    {
        $data = $request->validate(['transaction_reference' => ['nullable', 'string', 'max:255']]);
        $payment = $this->payments->confirm($payment, $request->user()->id, $data['transaction_reference'] ?? null);
        $this->activity->write('confirm', 'payments', $payment, new: ['status' => 'paid', 'amount' => $payment->amount]);

        return back()->with('success', 'Đã xác nhận thanh toán.');
    }

    public function fail(Request $request, Payment $payment): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['failed', 'cancelled'])]]);
        $this->payments->fail($payment, PaymentStatus::from($data['status']));

        return back()->with('success', 'Đã cập nhật giao dịch.');
    }
}
