<?php

namespace App\Http\Controllers;

use App\Models\InventoryBatch;
use App\Models\InventoryDocument;
use App\Models\InventoryItem;
use App\Models\InventoryTransactionItem;
use App\Models\Stocktake;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InventoryDashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $data = $request->validate(['period' => ['nullable', Rule::in(['today', '7days', '30days', 'month', 'custom'])], 'from' => ['nullable', 'date', 'required_if:period,custom'], 'to' => ['nullable', 'date', 'after_or_equal:from', 'required_if:period,custom']]);
        $period = $data['period'] ?? 'today';
        [$from, $to] = match ($period) {
            '7days' => [now()->subDays(6)->startOfDay(), now()->endOfDay()],
            '30days' => [now()->subDays(29)->startOfDay(), now()->endOfDay()],
            'month' => [now()->startOfMonth(), now()->endOfDay()],
            'custom' => [Carbon::parse($data['from'])->startOfDay(), Carbon::parse($data['to'])->endOfDay()],
            default => [now()->startOfDay(), now()->endOfDay()],
        };
        $movement = InventoryTransactionItem::query()->join('inventory_transactions as t', 't.id', '=', 'inventory_transaction_items.inventory_transaction_id')->whereBetween('t.occurred_at', [$from, $to]);
        $metrics = [
            'total' => InventoryItem::count(),
            'active' => InventoryItem::where('status', 'active')->count(),
            'low' => InventoryItem::where('current_stock', '>', 0)->whereColumn('current_stock', '<=', 'minimum_stock')->count(),
            'out' => InventoryItem::where('current_stock', '<=', 0)->count(),
            'value' => InventoryItem::selectRaw('COALESCE(SUM(current_stock * cost_price), 0) value')->value('value'),
            'in' => (clone $movement)->where('t.direction', 'in')->sum('inventory_transaction_items.stock_value'),
            'out_value' => (clone $movement)->where('t.direction', 'out')->sum('inventory_transaction_items.stock_value'),
            'waste' => (clone $movement)->where('t.type', 'waste')->sum('inventory_transaction_items.stock_value'),
            'receipts' => InventoryDocument::where('type', 'receipt')->whereBetween('transaction_date', [$from, $to])->count(),
            'issues' => InventoryDocument::whereIn('type', ['issue', 'waste'])->whereBetween('transaction_date', [$from, $to])->count(),
            'stocktakes' => Stocktake::whereBetween('counted_at', [$from, $to])->count(),
        ];
        return view('inventory.dashboard', [
            'metrics' => $metrics,
            'period' => $period,
            'from' => $from,
            'to' => $to,
            'lowItems' => InventoryItem::whereColumn('current_stock', '<=', 'minimum_stock')->orderBy('current_stock')->limit(10)->get(),
            'expiringBatches' => InventoryBatch::with('inventoryItem')->where('remaining_quantity', '>', 0)->whereNotNull('expiry_date')->whereDate('expiry_date', '<=', now()->addDays(config('restaurant.inventory.near_expiry_days')))->orderBy('expiry_date')->limit(10)->get(),
        ]);
    }
}
