<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStocktakeRequest;
use App\Http\Requests\UpdateStocktakeCountRequest;
use App\Models\InventoryItem;
use App\Models\Stocktake;
use App\Models\Warehouse;
use App\Services\ActivityLogService;
use App\Services\StocktakeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StocktakeController extends Controller
{
    public function __construct(private readonly StocktakeService $stocktakes, private readonly ActivityLogService $activity) {}
    public function index(Request $request): View
    {
        $stocktakes = Stocktake::with('warehouse', 'creator')
            ->when($request->filled('q'), fn ($q) => $q->where('stocktake_code', 'like', '%'.$request->q.'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest('counted_at')->paginate(config('restaurant.pagination'))->withQueryString();
        return view('inventory.stocktakes.index', compact('stocktakes'));
    }
    public function create(): View
    {
        return view('inventory.stocktakes.form', ['warehouses' => Warehouse::where('status', 'active')->orderBy('name')->get(), 'items' => InventoryItem::where('status', 'active')->orderBy('name')->get()]);
    }
    public function store(StoreStocktakeRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $stocktake = $this->stocktakes->create($data['warehouse_id'], $data['item_ids'], $data['counted_at'], $data['note'] ?? null, $request->user()->id);
        $this->activity->write('create_draft', 'inventory', $stocktake, new: $stocktake->toArray());
        return redirect()->route('inventory.stocktakes.show', $stocktake)->with('success', '?? t?o phi?u ki?m k? nh?p.');
    }
    public function show(Stocktake $stocktake): View
    {
        return view('inventory.stocktakes.show', ['stocktake' => $stocktake->load('items.inventoryItem', 'warehouse', 'creator', 'reviewer', 'confirmer')]);
    }
    public function start(Stocktake $stocktake): RedirectResponse
    {
        $this->stocktakes->start($stocktake);
        return back()->with('success', '?? b?t ??u ki?m k?.');
    }
    public function counts(UpdateStocktakeCountRequest $request, Stocktake $stocktake): RedirectResponse
    {
        $this->stocktakes->recordCounts($stocktake, $request->validated('items'));
        return back()->with('success', '?? l?u s? ??m th?c t?.');
    }
    public function review(Request $request, Stocktake $stocktake): RedirectResponse
    {
        $this->stocktakes->review($stocktake, $request->user()->id);
        return back()->with('success', 'Phi?u ?ang ch? x?c nh?n.');
    }
    public function confirm(Request $request, Stocktake $stocktake): RedirectResponse
    {
        $updated = $this->stocktakes->confirm($stocktake, $request->user()->id);
        $this->activity->write('confirm_stocktake', 'inventory', $updated, ['status' => 'review'], ['status' => 'confirmed']);
        return back()->with('success', '?? x?c nh?n ki?m k? v? ghi ch?nh l?ch v?o ledger.');
    }
}
