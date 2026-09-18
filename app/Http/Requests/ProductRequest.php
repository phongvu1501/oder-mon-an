<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('product')?->id;

        return [
            'category_id' => ['required', 'exists:categories,id'],
            'sku' => ['nullable', 'string', 'max:50', Rule::unique('products')->ignore($id)],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'alpha_dash', 'max:255', Rule::unique('products')->ignore($id)],
            'description' => ['nullable', 'string', 'max:2000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'price' => ['required', 'decimal:0,2', 'min:0'],
            'cost_price' => ['nullable', 'decimal:0,2', 'min:0'],
            'unit' => ['required', 'string', 'max:30'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'is_available' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }
}
