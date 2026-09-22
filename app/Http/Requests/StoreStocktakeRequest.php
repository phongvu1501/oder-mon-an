<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStocktakeRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', Rule::exists('warehouses', 'id')->where('status', 'active')],
            'counted_at' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:1000'],
            'item_ids' => ['required', 'array', 'min:1'],
            'item_ids.*' => ['required', 'integer', 'distinct', Rule::exists('inventory_items', 'id')->where('status', 'active')->whereNull('deleted_at')],
        ];
    }
}
