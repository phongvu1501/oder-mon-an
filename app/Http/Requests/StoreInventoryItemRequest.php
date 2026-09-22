<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventoryItemRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        $item = $this->route('inventoryItem');
        return [
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:50', Rule::unique('inventory_items')->ignore($item?->id)],
            'base_unit_id' => ['required', 'integer', Rule::exists('units', 'id')->where('status', 'active')],
            'minimum_stock' => ['required', 'decimal:0,3', 'min:0'],
            'track_batch' => ['sometimes', 'boolean'],
            'track_expiry' => ['sometimes', 'boolean'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }
}
