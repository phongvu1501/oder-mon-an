<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\Unit;
use App\Http\Requests\UpdateRecipeRequest;
use App\Services\RecipeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RecipeController extends Controller
{
    public function edit(Product $product): View
    {
        return view('recipes.form', [
            'product' => $product->load('recipe.items.inventoryItem'),
            'inventoryItems' => InventoryItem::where('status', 'active')->orderBy('name')->get(),
            'units' => Unit::where('status', 'active')->orderBy('category')->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateRecipeRequest $request, Product $product): RedirectResponse
    {
        app(RecipeService::class)->save($product, $request->validated());
        return redirect()->route('products.show', $product)->with('success', 'Recipe updated.');

        $data = $request->validate([
            'yield_quantity' => ['required', 'decimal:0,3', 'gt:0'],
            'items' => ['required', 'array', 'min:1', 'max:30'],
            'items.*.inventory_item_id' => ['required', 'integer', 'distinct', 'exists:inventory_items,id'],
            'items.*.quantity' => ['required', 'decimal:0,3', 'gt:0'],
            'items.*.unit' => ['required', 'string', 'max:30'],
        ]);

        DB::transaction(function () use ($product, $data) {
            $recipe = $product->recipe()->updateOrCreate([], ['yield_quantity' => $data['yield_quantity']]);
            $recipe->items()->delete();
            $recipe->items()->createMany($data['items']);
        });

        return redirect()->route('products.show', $product)->with('success', 'Đã cập nhật định mức nguyên liệu.');
    }
}
