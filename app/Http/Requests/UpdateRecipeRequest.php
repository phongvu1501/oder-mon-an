<?php

namespace App\Http\Requests;

use App\Models\InventoryItem;
use App\Models\Unit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRecipeRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'yield_quantity' => ['required', 'decimal:0,3', 'gt:0'],
            'yield_unit_id' => ['required', Rule::exists('units', 'id')->where('status', 'active')],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'note' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.inventory_item_id' => ['required', 'integer', 'distinct', Rule::exists('inventory_items', 'id')->where('status', 'active')->whereNull('deleted_at')],
            'items.*.quantity' => ['required', 'decimal:0,3', 'gt:0'],
            'items.*.unit_id' => ['required', Rule::exists('units', 'id')->where('status', 'active')],
            'items.*.wastage_percent' => ['nullable', 'decimal:0,2', 'between:0,100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $items = collect($this->input('items', []))->map(function (array $line) {
            $line['unit_id'] ??= InventoryItem::whereKey($line['inventory_item_id'] ?? null)->value('base_unit_id');
            $line['wastage_percent'] ??= 0;
            return $line;
        })->all();
        $this->merge([
            'yield_unit_id' => $this->input('yield_unit_id') ?: Unit::where('code', 'phan')->value('id'),
            'status' => $this->input('status', 'active'), 'items' => $items,
        ]);
    }
}
