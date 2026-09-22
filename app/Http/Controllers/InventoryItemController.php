<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInventoryItemRequest;
use App\Models\InventoryBatch;
use App\Models\InventoryItem;
use App\Models\Unit;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryItemController extends Controller
{
    public function __construct(private readonly ActivityLogService $activity) {}

    public function index(Request $request): View
    {
        $sorts = ['name', 'sku', 'current_stock', 'minimum_stock', 'cost_price', 'status'];
        $sort = in_array($request->string('sort')->toString(), $sorts, true) ? $request->string('sort')->toString() : 'name';
        $direction = $request->string('direction')->toString() === 'desc' ? 'desc' : 'asc';
        $query = InventoryItem::with('baseUnit')
            ->when($request->filled('q'), fn($q) => $q->where(fn($i) => $i->where('name', 'like', '%' . $request->q . '%')->orWhere('sku', 'like', '%' . $request->q . '%')))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->stock === 'low', fn($q) => $q->where('current_stock', '>', 0)->whereColumn('current_stock', '<=', 'minimum_stock'))
            ->when($request->stock === 'out', fn($q) => $q->where('current_stock', '<=', 0))
            ->orderBy($sort, $direction);

        $metrics = [
            'total' => InventoryItem::count(),
            'active' => InventoryItem::where('status', 'active')->count(),
            'low' => InventoryItem::where('current_stock', '>', 0)->whereColumn('current_stock', '<=', 'minimum_stock')->count(),
            'out' => InventoryItem::where('current_stock', '<=', 0)->count(),
            'value' => InventoryItem::selectRaw('COALESCE(SUM(current_stock * cost_price), 0) value')->value('value'),
        ];
        return view('inventory.index', ['items' => $query->paginate(config('restaurant.pagination'))->withQueryString(), 'units' => Unit::where('status', 'active')->orderBy('category')->orderBy('name')->get(), 'metrics' => $metrics]);
    }

    public function show(InventoryItem $inventoryItem): View
    {
        $inventoryItem->load(['baseUnit', 'stocks.warehouse', 'batches' => fn($q) => $q->orderByRaw('expiry_date IS NULL')->orderBy('expiry_date')]);
        $ledger = $inventoryItem->transactionItems()->with(['transaction.creator', 'transaction.warehouse'])->latest()->paginate(config('restaurant.pagination'));
        return view('inventory.show', compact('inventoryItem', 'ledger'));
    }

    public function store(StoreInventoryItemRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $unit = Unit::findOrFail($data['base_unit_id']);
        $item = InventoryItem::create($data + ['unit' => $unit->symbol, 'track_batch' => $request->boolean('track_batch'), 'track_expiry' => $request->boolean('track_expiry')]);
        $this->activity->write('create', 'inventory', $item, new: $item->toArray());
        return back()->with('success', '?? t?o m?t h?ng. T?n ??u k? ph?i ???c ghi b?ng phi?u nh?p ho?c ?i?u ch?nh.');
    }

    public function update(StoreInventoryItemRequest $request, InventoryItem $inventoryItem): RedirectResponse
    {
        $old = $inventoryItem->toArray();
        $data = $request->validated();
        $unit = Unit::findOrFail($data['base_unit_id']);
        $inventoryItem->update($data + ['unit' => $unit->symbol, 'track_batch' => $request->boolean('track_batch'), 'track_expiry' => $request->boolean('track_expiry')]);
        $this->activity->write('update', 'inventory', $inventoryItem, $old, $inventoryItem->fresh()->toArray());
        return back()->with('success', '?? c?p nh?t m?t h?ng.');
    }

    public function destroy(InventoryItem $inventoryItem): RedirectResponse
    {
        $inventoryItem->update(['status' => 'inactive']);
        $inventoryItem->delete();
        $this->activity->write('archive', 'inventory', $inventoryItem, ['deleted_at' => null], ['deleted_at' => $inventoryItem->deleted_at]);
        return back()->with('success', '?? l?u tr? m?t h?ng; l?ch s? v?n ???c gi? nguy?n.');
    }
}
