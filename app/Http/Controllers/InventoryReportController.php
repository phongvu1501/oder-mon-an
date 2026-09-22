<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\InventoryTransactionItem;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventoryReportController extends Controller
{
    public function index(Request $request): View
    {
        [$from, $to] = $this->dates($request);
        $items = InventoryItem::with('baseUnit')
            ->when($request->filled('q'), fn($q) => $q->where(fn($i) => $i->where('name', 'like', '%' . $request->q . '%')->orWhere('sku', 'like', '%' . $request->q . '%')))
            ->orderBy($request->string('sort')->toString() === 'sku' ? 'sku' : 'name', $request->string('direction')->toString() === 'desc' ? 'desc' : 'asc')
            ->paginate(config('restaurant.pagination'))->withQueryString();
        $movements = $this->movements($items->getCollection()->pluck('id'), $from, $to, $request->integer('supplier_id') ?: null);
        return view('inventory.reports.index', ['items' => $items, 'movements' => $movements, 'from' => $from, 'to' => $to, 'suppliers' => Supplier::orderBy('name')->get()]);
    }

    public function export(Request $request): StreamedResponse
    {
        [$from, $to] = $this->dates($request);
        $items = InventoryItem::orderBy('name')->get();
        $movements = $this->movements($items->pluck('id'), $from, $to, $request->integer('supplier_id') ?: null);
        return response()->streamDownload(function () use ($items, $movements) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['SKU', 'M?t h?ng', '??n v?', '??u k?', 'Nh?p', 'Xu?t', 'Hao h?t', '?i?u ch?nh', 'Cu?i k?', 'Gi? v?n', 'Gi? tr? t?n']);
            foreach ($items as $item) {
                $row = $movements->get($item->id);
                $opening = (float) ($row->opening ?? 0);
                $in = (float) ($row->in_qty ?? 0);
                $out = abs((float) ($row->out_qty ?? 0));
                $waste = abs((float) ($row->waste_qty ?? 0));
                $adjustment = (float) ($row->adjustment_qty ?? 0);
                $closing = $opening + (float) ($row->net_qty ?? 0);
                fputcsv($output, [$item->sku, $item->name, $item->unit, $opening, $in, $out, $waste, $adjustment, $closing, $item->cost_price, $closing * (float) $item->cost_price]);
            }
            fclose($output);
        }, 'bao-cao-kho-' . now()->format('Ymd-His') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function dates(Request $request): array
    {
        $data = $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date', 'after_or_equal:from'], 'supplier_id' => ['nullable', 'exists:suppliers,id']]);
        return [Carbon::parse($data['from'] ?? now()->startOfMonth()->toDateString())->startOfDay(), Carbon::parse($data['to'] ?? now()->toDateString())->endOfDay()];
    }

    private function movements(Collection $itemIds, Carbon $from, Carbon $to, ?int $supplierId): Collection
    {
        if ($itemIds->isEmpty()) return collect();
        return InventoryTransactionItem::query()
            ->join('inventory_transactions as t', 't.id', '=', 'inventory_transaction_items.inventory_transaction_id')
            ->whereIn('inventory_item_id', $itemIds)
            ->when($supplierId, fn($q) => $q->where('t.supplier_id', $supplierId))
            ->groupBy('inventory_item_id')
            ->selectRaw('inventory_item_id')
            ->selectRaw('SUM(CASE WHEN t.occurred_at < ? THEN quantity ELSE 0 END) as opening', [$from])
            ->selectRaw("SUM(CASE WHEN t.occurred_at BETWEEN ? AND ? AND t.direction = 'in' THEN quantity ELSE 0 END) as in_qty", [$from, $to])
            ->selectRaw("SUM(CASE WHEN t.occurred_at BETWEEN ? AND ? AND t.direction = 'out' THEN quantity ELSE 0 END) as out_qty", [$from, $to])
            ->selectRaw("SUM(CASE WHEN t.occurred_at BETWEEN ? AND ? AND t.type = 'waste' THEN quantity ELSE 0 END) as waste_qty", [$from, $to])
            ->selectRaw("SUM(CASE WHEN t.occurred_at BETWEEN ? AND ? AND t.type IN ('adjustment_in','adjustment_out','stocktake_adjustment') THEN quantity ELSE 0 END) as adjustment_qty", [$from, $to])
            ->selectRaw('SUM(CASE WHEN t.occurred_at BETWEEN ? AND ? THEN quantity ELSE 0 END) as net_qty', [$from, $to])
            ->get()->keyBy('inventory_item_id');
    }
}
