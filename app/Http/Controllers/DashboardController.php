<?php

namespace App\Http\Controllers;

use App\Enums\BillStatus;
use App\Enums\OrderStatus;
use App\Enums\TableStatus;
use App\Models\Bill;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\RestaurantTable;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('dashboard', [
            'revenueToday' => Bill::where('status', BillStatus::Paid)->whereDate('paid_at', today())->sum('total'),
            'ordersToday' => Order::whereDate('created_at', today())->count(),
            'occupiedTables' => RestaurantTable::where('status', TableStatus::Occupied)->count(),
            'unpaidBills' => Bill::whereIn('status', [BillStatus::Unpaid->value, BillStatus::Partial->value])->count(),
            'processingOrders' => Order::whereIn('status', [OrderStatus::Pending->value, OrderStatus::Confirmed->value, OrderStatus::Preparing->value, OrderStatus::Ready->value])->count(),
            'lowStock' => InventoryItem::whereColumn('current_stock', '<=', 'minimum_stock')->orderBy('current_stock')->limit(8)->get(),
            'recentOrders' => Order::with('tableSession.restaurantTable', 'items')->latest()->limit(8)->get(),
        ]);
    }
}
