<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\OrderItem;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __invoke(Request $request): View
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);
        $from = Carbon::parse($data['from'] ?? now()->startOfMonth()->toDateString())->startOfDay();
        $to = Carbon::parse($data['to'] ?? now()->toDateString())->endOfDay();
        if ($from->diffInDays($to) > 366) {
            $from = $to->copy()->subYear();
        }

        $revenue = Bill::where('status', 'paid')->whereBetween('paid_at', [$from, $to])->sum('total');
        $payments = Payment::select('method', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->where('status', 'paid')->whereBetween('paid_at', [$from, $to])->groupBy('method')->get();
        $popular = OrderItem::select('product_name', DB::raw('SUM(quantity) as quantity'), DB::raw('SUM(order_items.subtotal) as revenue'))
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('bills', 'bills.table_session_id', '=', 'orders.table_session_id')
            ->where('bills.status', 'paid')->where('order_items.status', '!=', 'cancelled')
            ->whereBetween('bills.paid_at', [$from, $to])->groupBy('product_name')->orderByDesc('quantity')->limit(10)->get();

        return view('reports.index', compact('from', 'to', 'revenue', 'payments', 'popular'));
    }
}
