<?php

namespace App\Http\Controllers;

use App\Enums\InventoryDocumentType;
use App\Http\Requests\CancelInventoryDocumentRequest;
use App\Http\Requests\StoreInventoryDocumentRequest;
use App\Models\InventoryDocument;
use App\Models\InventoryItem;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Services\ActivityLogService;
use App\Services\InventoryDocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryDocumentController extends Controller
{
    public function __construct(private readonly InventoryDocumentService $documents, private readonly ActivityLogService $activity) {}

    public function index(Request $request, string $type): View
    {
        $documentType = InventoryDocumentType::from($type);
        $documents = InventoryDocument::with('warehouse', 'destinationWarehouse', 'supplier', 'creator')
            ->where('type', $type)
            ->when($request->filled('q'), fn($q) => $q->where(fn($i) => $i->where('document_code', 'like', '%' . $request->q . '%')->orWhere('reason', 'like', '%' . $request->q . '%')))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('from'), fn($q) => $q->whereDate('transaction_date', '>=', $request->from))
            ->when($request->filled('to'), fn($q) => $q->whereDate('transaction_date', '<=', $request->to))
            ->latest('transaction_date')->paginate(config('restaurant.pagination'))->withQueryString();
        return view('inventory.documents.index', compact('documents', 'documentType'));
    }

    public function create(string $type): View
    {
        $documentType = InventoryDocumentType::from($type);
        return view('inventory.documents.form', [
            'documentType' => $documentType,
            'items' => InventoryItem::with('baseUnit')->where('status', 'active')->orderBy('name')->get(),
            'units' => Unit::where('status', 'active')->orderBy('name')->get(),
            'warehouses' => Warehouse::where('status', 'active')->orderBy('name')->get(),
            'suppliers' => Supplier::where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function store(StoreInventoryDocumentRequest $request, string $type): RedirectResponse
    {
        $document = $this->documents->create(InventoryDocumentType::from($type), $request->validated(), $request->user()->id);
        $this->activity->write('create_draft', 'inventory', $document, new: $document->toArray());
        if ($request->boolean('confirm')) {
            $document = $this->documents->confirm($document, $request->user()->id);
            $this->activity->write('confirm', 'inventory', $document, ['status' => 'draft'], ['status' => 'confirmed']);
        }
        return redirect()->route('inventory.documents.show', $document)->with('success', $request->boolean('confirm') ? '?? t?o v? x?c nh?n phi?u.' : '?? l?u phi?u nh?p.');
    }

    public function show(InventoryDocument $inventoryDocument): View
    {
        return view('inventory.documents.show', ['document' => $inventoryDocument->load('items.inventoryItem', 'items.unit', 'warehouse', 'destinationWarehouse', 'supplier', 'creator', 'confirmer', 'canceller')]);
    }

    public function confirm(Request $request, InventoryDocument $inventoryDocument): RedirectResponse
    {
        $document = $this->documents->confirm($inventoryDocument, $request->user()->id);
        $this->activity->write('confirm', 'inventory', $document, ['status' => 'draft'], ['status' => 'confirmed']);
        return back()->with('success', '?? x?c nh?n phi?u v? ghi s? kho.');
    }

    public function cancel(CancelInventoryDocumentRequest $request, InventoryDocument $inventoryDocument): RedirectResponse
    {
        $old = $inventoryDocument->status->value;
        $document = $this->documents->cancel($inventoryDocument, $request->user()->id, $request->validated('reason'));
        $this->activity->write('cancel', 'inventory', $document, ['status' => $old], ['status' => 'cancelled', 'reason' => $request->validated('reason')]);
        return back()->with('success', '?? h?y phi?u; bi?n ??ng ?? ???c ??o n?u phi?u t?ng x?c nh?n.');
    }
}
