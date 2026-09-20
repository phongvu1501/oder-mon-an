<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\ActivityLogService;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderManagementController extends Controller
{
    public function __construct(private readonly OrderService $orders, private readonly ActivityLogService $activity) {}

    public function index(Request $request): View
    {
        $orders = Order::with('tableSession.restaurantTable', 'items')
            ->when($request->filled('q'), fn ($query) => $query->where(fn ($inner) => $inner
                ->where('order_code', 'like', '%'.$request->string('q').'%')
                ->orWhereHas('tableSession.restaurantTable', fn ($table) => $table
                    ->where('name', 'like', '%'.$request->string('q').'%')
                    ->orWhere('code', 'like', '%'.$request->string('q').'%'))))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('date'), fn ($q) => $q->whereDate('created_at', $request->date))
            ->latest()->paginate(config('restaurant.pagination'))->withQueryString();

        return view('orders.index', compact('orders'));
    }

    public function show(Order $order): View
    {
        return view('orders.show', ['order' => $order->load('items', 'tableSession.restaurantTable', 'creator')]);
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(OrderStatus::class)],
            'reason' => ['nullable', 'string', 'max:500', 'required_if:status,cancelled'],
        ]);
        $old = $order->status->value;
        $updated = $this->orders->transition($order, OrderStatus::from($data['status']), $request->user()->id, $data['reason'] ?? null);
        $this->activity->write('change_status', 'orders', $updated, ['status' => $old], ['status' => $updated->status->value]);

        return back()->with('success', 'Đã cập nhật trạng thái order.');
    }
}
