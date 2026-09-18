<?php

namespace App\Http\Controllers;

use App\Enums\InventoryTransactionType;
use App\Http\Requests\InventoryTransactionRequest;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\Supplier;
use App\Services\ActivityLogService;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function __construct(private readonly InventoryService $inventory, private readonly ActivityLogService $activity) {}

    public function index(Request $request): View
    {
        $items = InventoryItem::query()
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($i) => $i->where('name', 'like', '%'.$request->q.'%')->orWhere('sku', 'like', '%'.$request->q.'%')))
            ->when($request->low === '1', fn ($q) => $q->whereColumn('current_stock', '<=', 'minimum_stock'))
            ->orderBy('name')->paginate(config('restaurant.pagination'))->withQueryString();

        return view('inventory.index', compact('items'));
    }

    public function storeItem(Request $request): RedirectResponse
    {
        InventoryItem::create($this->itemData($request));

        return back()->with('success', 'Đã tạo nguyên liệu. Hãy dùng giao dịch nhập kho để ghi nhận tồn.');
    }

    public function updateItem(Request $request, InventoryItem $inventoryItem): RedirectResponse
    {
        $data = $this->itemData($request, $inventoryItem);
        unset($data['current_stock']);
        $inventoryItem->update($data);

        return back()->with('success', 'Đã cập nhật nguyên liệu.');
    }

    public function destroyItem(InventoryItem $inventoryItem): RedirectResponse
    {
        $inventoryItem->delete();

        return back()->with('success', 'Đã chuyển nguyên liệu vào thùng rác.');
    }

    public function transactions(): View
    {
        return view('inventory.transactions', [
            'transactions' => InventoryTransaction::with('items.inventoryItem', 'supplier')->latest()->paginate(config('restaurant.pagination')),
            'items' => InventoryItem::where('status', 'active')->orderBy('name')->get(),
            'suppliers' => Supplier::where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function storeTransaction(InventoryTransactionRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $transaction = $this->inventory->record(InventoryTransactionType::from($data['type']), $data['items'], $data['reason'] ?? null, $data['supplier_id'] ?? null, $request->user()->id);
        $this->activity->write('create', 'inventory', $transaction, new: $transaction->toArray());

        return back()->with('success', 'Đã ghi giao dịch kho.');
    }

    private function itemData(Request $request, ?InventoryItem $item = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:50', Rule::unique('inventory_items')->ignore($item?->id)],
            'unit' => ['required', 'string', 'max:30'],
            'minimum_stock' => ['required', 'decimal:0,3', 'min:0'],
            'cost_price' => ['required', 'decimal:0,2', 'min:0'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);
    }
}
