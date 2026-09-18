<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['in', 'out', 'adjustment', 'waste', 'return'])],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'reason' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.inventory_item_id' => ['required', 'integer', 'distinct', 'exists:inventory_items,id'],
            'items.*.quantity' => ['required', 'decimal:0,3', 'not_in:0,0.0,0.00,0.000'],
            'items.*.unit_cost' => ['nullable', 'decimal:0,2', 'min:0'],
        ];
    }
}
