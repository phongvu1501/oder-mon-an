<?php

namespace App\Http\Controllers;

use App\Enums\InventoryTransactionType;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryLedgerController extends Controller
{
    public function __invoke(Request $request): View
    {
        $transactions = InventoryTransaction::with('items.inventoryItem', 'supplier', 'warehouse', 'creator', 'reference')
            ->when($request->filled('q'), fn($q) => $q->where(fn($i) => $i->where('transaction_code', 'like', '%' . $request->q . '%')->orWhere('reason', 'like', '%' . $request->q . '%')))
            ->when($request->filled('type'), fn($q) => $q->where('type', $request->type))
            ->when($request->filled('warehouse_id'), fn($q) => $q->where('warehouse_id', $request->warehouse_id))
            ->when($request->filled('supplier_id'), fn($q) => $q->where('supplier_id', $request->supplier_id))
            ->when($request->filled('item_id'), fn($q) => $q->whereHas('items', fn($i) => $i->where('inventory_item_id', $request->item_id)))
            ->when($request->filled('from'), fn($q) => $q->whereDate('occurred_at', '>=', $request->from))
            ->when($request->filled('to'), fn($q) => $q->whereDate('occurred_at', '<=', $request->to))
            ->latest('occurred_at')->paginate(config('restaurant.pagination'))->withQueryString();
        return view('inventory.transactions', [
            'transactions' => $transactions,
            'types' => InventoryTransactionType::cases(),
            'items' => InventoryItem::orderBy('name')->get(),
            'suppliers' => Supplier::orderBy('name')->get(),
            'warehouses' => Warehouse::where('status', 'active')->orderBy('name')->get(),
        ]);
    }
}
