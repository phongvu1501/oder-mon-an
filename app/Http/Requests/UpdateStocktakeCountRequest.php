<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStocktakeCountRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => ['required', 'integer', 'distinct'],
            'items.*.physical_quantity' => ['required', 'decimal:0,3', 'min:0'],
            'items.*.reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
