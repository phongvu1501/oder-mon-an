<?php

namespace App\Http\Controllers;

use App\Models\InventoryBatch;
use App\Models\InventoryItem;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryBatchController extends Controller
{
    public function __invoke(Request $request): View
    {
        $batches = InventoryBatch::with('inventoryItem', 'warehouse', 'supplier')
            ->when($request->filled('q'), fn($q) => $q->where(fn($i) => $i->where('batch_code', 'like', '%' . $request->q . '%')->orWhereHas('inventoryItem', fn($item) => $item->where('name', 'like', '%' . $request->q . '%'))))
            ->when($request->filled('item_id'), fn($q) => $q->where('inventory_item_id', $request->item_id))
            ->when($request->expiry === 'near', fn($q) => $q->whereBetween('expiry_date', [now()->toDateString(), now()->addDays(config('restaurant.inventory.near_expiry_days'))->toDateString()]))
            ->when($request->expiry === 'expired', fn($q) => $q->whereDate('expiry_date', '<', now()))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->orderByRaw('expiry_date IS NULL')->orderBy('expiry_date')->paginate(config('restaurant.pagination'))->withQueryString();
        return view('inventory.batches.index', ['batches' => $batches, 'items' => InventoryItem::orderBy('name')->get()]);
    }
}
