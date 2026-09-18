<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class KitchenController extends Controller
{
    public function index(): View
    {
        $orders = Order::with('items', 'tableSession.restaurantTable')
            ->whereIn('status', [OrderStatus::Confirmed->value, OrderStatus::Preparing->value, OrderStatus::Ready->value])
            ->oldest()->get()->groupBy(fn (Order $order) => $order->status->value);

        return view('kitchen.index', compact('orders'));
    }

    public function update(Request $request, Order $order, OrderService $orders): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['preparing', 'ready'])]]);
        $orders->transition($order, OrderStatus::from($data['status']), $request->user()->id);

        return back()->with('success', 'Đã cập nhật bếp.');
    }
}
