<?php

namespace App\Console\Commands;

use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\InventoryTransactionItem;
use Illuminate\Console\Command;

class InventoryReconcile extends Command
{
    protected $signature = 'inventory:reconcile {--item= : SKU or item ID}';
    protected $description = 'Compare inventory caches with the immutable ledger without changing data';

    public function handle(): int
    {
        $items = InventoryItem::withTrashed()
            ->when($this->option('item'), fn ($q, $value) => $q->where(fn ($i) => $i->where('id', $value)->orWhere('sku', $value)))
            ->orderBy('id')->get();
        $ledger = InventoryTransactionItem::selectRaw('inventory_item_id, SUM(quantity) calculated_stock')->groupBy('inventory_item_id')->pluck('calculated_stock', 'inventory_item_id');
        $stocks = InventoryStock::selectRaw('inventory_item_id, SUM(current_stock) cached_stock')->groupBy('inventory_item_id')->pluck('cached_stock', 'inventory_item_id');
        $rows = [];
        $hasMismatch = false;
        foreach ($items as $item) {
            $calculated = (float) ($ledger[$item->id] ?? 0);
            $stockCache = (float) ($stocks[$item->id] ?? 0);
            $itemCache = (float) $item->current_stock;
            $difference = round($itemCache - $calculated, 3);
            $stockDifference = round($stockCache - $calculated, 3);
            $status = abs($difference) < 0.0005 && abs($stockDifference) < 0.0005 ? 'OK' : 'MISMATCH';
            $hasMismatch = $hasMismatch || $status !== 'OK';
            $rows[] = [$item->id, $item->sku, $item->name, number_format($itemCache, 3, '.', ''), number_format($stockCache, 3, '.', ''), number_format($calculated, 3, '.', ''), number_format($difference, 3, '.', ''), $status];
        }
        $this->table(['ID', 'SKU', 'Item', 'Item cache', 'Stock cache', 'Ledger', 'Difference', 'Result'], $rows);
        $anomalies = InventoryTransactionItem::with('inventoryItem', 'transaction')->where('cost_review_required', true)->get();
        if ($anomalies->isNotEmpty()) {
            $this->warn('Cost lines require review; no values were changed:');
            $this->table(['Transaction', 'SKU', 'Unit cost', 'Current cost', 'Note'], $anomalies->map(fn ($line) => [$line->transaction?->transaction_code, $line->inventoryItem?->sku, $line->unit_cost, $line->inventoryItem?->cost_price, $line->note])->all());
        }
        if ($hasMismatch) {
            $this->error('Inventory mismatch detected. No data was changed.');
            return self::FAILURE;
        }
        $this->info('Inventory caches match the ledger.');
        return self::SUCCESS;
    }
}
