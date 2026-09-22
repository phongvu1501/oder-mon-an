<?php

namespace App\Http\Requests;

use App\Enums\InventoryDocumentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventoryDocumentRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        $type = $this->route('type') instanceof InventoryDocumentType ? $this->route('type')->value : (string) $this->route('type');
        return [
            'warehouse_id' => ['required', Rule::exists('warehouses', 'id')->where('status', 'active')],
            'destination_warehouse_id' => [$type === 'transfer' ? 'required' : 'nullable', 'different:warehouse_id', Rule::exists('warehouses', 'id')->where('status', 'active')],
            'supplier_id' => [$type === 'receipt' ? 'required' : 'nullable', Rule::exists('suppliers', 'id')->where('status', 'active')->whereNull('deleted_at')],
            'transaction_date' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:255', Rule::requiredIf(in_array($type, ['issue', 'adjustment', 'waste', 'return_to_supplier'], true))],
            'recipient' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
            'idempotency_key' => ['nullable', 'uuid'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.inventory_item_id' => ['required', 'integer', 'distinct', Rule::exists('inventory_items', 'id')->where('status', 'active')->whereNull('deleted_at')],
            'items.*.unit_id' => ['required', 'integer', Rule::exists('units', 'id')->where('status', 'active')],
            'items.*.quantity' => ['required', 'decimal:0,3', $type === 'adjustment' ? 'not_in:0,0.0,0.00,0.000' : 'gt:0'],
            'items.*.unit_cost' => [$type === 'receipt' ? 'required' : 'nullable', 'decimal:0,2', 'min:0'],
            'items.*.batch_code' => ['nullable', 'string', 'max:60'],
            'items.*.manufactured_date' => ['nullable', 'date', 'before_or_equal:items.*.expiry_date'],
            'items.*.expiry_date' => ['nullable', 'date', 'after_or_equal:transaction_date'],
            'items.*.reason' => ['nullable', 'string', 'max:255'],
            'items.*.note' => ['nullable', 'string', 'max:500'],
            'confirm' => ['sometimes', 'boolean'],
        ];
    }
}
