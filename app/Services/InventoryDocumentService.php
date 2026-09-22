<?php

namespace App\Services;

use App\Enums\InventoryDocumentStatus;
use App\Enums\InventoryDocumentType;
use App\Enums\InventoryTransactionType;
use App\Enums\RecordStatus;
use App\Models\InventoryDocument;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Support\BusinessCode;
use App\Support\Decimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryDocumentService
{
    public function __construct(private readonly InventoryMovementService $inventory, private readonly UnitConversionService $units) {}

    public function create(InventoryDocumentType $type, array $data, int $userId): InventoryDocument
    {
        return DB::transaction(function () use ($type, $data, $userId) {
            $itemIds = collect($data['items'])->pluck('inventory_item_id')->map(fn ($id) => (int) $id)->unique();
            $items = InventoryItem::with('baseUnit')->whereIn('id', $itemIds)->get()->keyBy('id');
            if ($items->count() !== $itemIds->count()) throw ValidationException::withMessages(['items' => 'Danh s?ch m?t h?ng kh?ng h?p l?.']);

            $total = 0;
            $lines = [];
            foreach ($data['items'] as $index => $line) {
                $item = $items->get((int) $line['inventory_item_id']);
                if ($item->status !== RecordStatus::Active) throw ValidationException::withMessages(["items.{$index}.inventory_item_id" => "{$item->name} ?ang ng?ng s? d?ng."]);
                if (! empty($line['expiry_date']) && $line['expiry_date'] < substr($data['transaction_date'], 0, 10)) {
                    throw ValidationException::withMessages(["items.{$index}.expiry_date" => 'H?n s? d?ng kh?ng ???c tr??c ng?y giao d?ch.']);
                }
                $base = $this->units->toBase($item, $line['quantity'], $line['unit_id'] ?? $item->base_unit_id);
                $unitCost = $line['unit_cost'] ?? null;
                $subtotal = $unitCost === null ? '0.00' : Decimal::fromMinor((int) round(abs(Decimal::toMinor($line['quantity'], 3)) * Decimal::toMinor($unitCost, 2) / 1000), 2);
                $total += Decimal::toMinor($subtotal, 2);
                $lines[] = $line + ['unit_id' => $line['unit_id'] ?? $item->base_unit_id, 'base_quantity' => $base, 'subtotal' => $subtotal];
            }

            $document = InventoryDocument::create([
                'document_code' => BusinessCode::make($type->prefix()), 'type' => $type,
                'status' => InventoryDocumentStatus::Draft, 'warehouse_id' => $data['warehouse_id'],
                'destination_warehouse_id' => $data['destination_warehouse_id'] ?? null,
                'supplier_id' => $data['supplier_id'] ?? null, 'transaction_date' => $data['transaction_date'],
                'reason' => $data['reason'] ?? null, 'recipient' => $data['recipient'] ?? null,
                'note' => $data['note'] ?? null, 'total_amount' => Decimal::fromMinor($total, 2),
                'idempotency_key' => $data['idempotency_key'] ?? null, 'created_by' => $userId,
            ]);
            $document->items()->createMany($lines);
            return $document->load('items.inventoryItem', 'warehouse', 'supplier');
        }, 3);
    }

    public function confirm(InventoryDocument $document, int $userId): InventoryDocument
    {
        return DB::transaction(function () use ($document, $userId) {
            $document = InventoryDocument::query()->lockForUpdate()->findOrFail($document->id);
            if ($document->status !== InventoryDocumentStatus::Draft) {
                throw ValidationException::withMessages(['status' => 'Ch? phi?u nh?p m?i ???c x?c nh?n.']);
            }
            $document->load('items.inventoryItem');
            $lines = $document->items->map(fn ($line) => [
                'inventory_item_id' => $line->inventory_item_id, 'quantity' => $line->base_quantity,
                'unit_id' => $line->unit_id, 'unit_cost' => $line->unit_cost, 'batch_code' => $line->batch_code,
                'manufactured_date' => $line->manufactured_date?->toDateString(), 'expiry_date' => $line->expiry_date?->toDateString(), 'note' => $line->note,
            ])->all();

            if ($document->type === InventoryDocumentType::Transfer) {
                $this->inventory->transfer($lines, $document->warehouse_id, $document->destination_warehouse_id, $document->reason ?? $document->document_code, $userId, "document:{$document->id}:confirm");
            } else {
                $type = match ($document->type) {
                    InventoryDocumentType::Receipt => InventoryTransactionType::In,
                    InventoryDocumentType::Issue => InventoryTransactionType::Out,
                    InventoryDocumentType::Adjustment => InventoryTransactionType::StocktakeAdjustment,
                    InventoryDocumentType::Waste => InventoryTransactionType::Waste,
                    InventoryDocumentType::ReturnToSupplier => InventoryTransactionType::ReturnToSupplier,
                    InventoryDocumentType::Transfer => throw new \LogicException('Transfer handled separately.'),
                };
                $this->inventory->record($type, $lines, $document->reason, $document->supplier_id, $userId, $document, $document->warehouse_id, "document:{$document->id}:confirm");
            }
            $document->update(['status' => InventoryDocumentStatus::Confirmed, 'confirmed_by' => $userId, 'confirmed_at' => now()]);
            return $document->refresh()->load('items.inventoryItem', 'warehouse', 'supplier', 'confirmer');
        }, 3);
    }

    public function cancel(InventoryDocument $document, int $userId, string $reason): InventoryDocument
    {
        return DB::transaction(function () use ($document, $userId, $reason) {
            $document = InventoryDocument::query()->lockForUpdate()->findOrFail($document->id);
            if ($document->status === InventoryDocumentStatus::Cancelled) throw ValidationException::withMessages(['status' => 'Phi?u ?? h?y.']);
            if ($document->status === InventoryDocumentStatus::Confirmed) {
                $transactions = InventoryTransaction::where('reference_type', $document->getMorphClass())->where('reference_id', $document->id)->get();
                if ($transactions->isEmpty() && $document->type === InventoryDocumentType::Transfer) {
                    $transactions = InventoryTransaction::whereIn('idempotency_key', ["document:{$document->id}:confirm:out", "document:{$document->id}:confirm:in"])->get();
                }
                foreach ($transactions as $transaction) $this->inventory->reverse($transaction, $userId, "H?y {$document->document_code}: {$reason}");
            }
            $document->update(['status' => InventoryDocumentStatus::Cancelled, 'cancelled_by' => $userId, 'cancelled_at' => now(), 'cancel_reason' => $reason]);
            return $document->refresh();
        }, 3);
    }
}
