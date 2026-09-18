<?php

namespace App\Http\Controllers;

use App\Enums\TableSessionStatus;
use App\Http\Requests\StoreOrderRequest;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class CustomerOrderController extends Controller
{
    public function store(StoreOrderRequest $request, OrderService $orders): JsonResponse|RedirectResponse
    {
        $session = CustomerMenuController::boundSession($request);
        if ($session->status !== TableSessionStatus::Active) {
            abort(409, 'Phiên bàn đã đóng.');
        }

        $order = $orders->create(
            $session,
            $request->validated('items'),
            $request->validated('idempotency_key'),
            $request->validated('customer_note'),
        );

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Đã gửi món tới quầy.', 'data' => $order], 201);
        }

        return redirect()->route('menu.status')->with('success', 'Đã gửi món tới quầy.');
    }
}
