<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\Unit;
use App\Models\UnitConversion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InventoryUnitController extends Controller
{
    public function index(): View
    {
        return view('inventory.units.index', ['units' => Unit::orderBy('category')->orderBy('name')->get(), 'conversions' => UnitConversion::with('inventoryItem', 'fromUnit', 'toUnit')->latest()->paginate(config('restaurant.pagination')), 'items' => InventoryItem::orderBy('name')->get()]);
    }
    public function storeUnit(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'alpha_dash:ascii', 'max:30', 'unique:units,code'],
            'name' => ['required', 'string', 'max:100'],
            'symbol' => ['required', 'string', 'max:30'],
            'category' => ['required', Rule::in(['mass', 'volume', 'count', 'package', 'custom'])],
            'decimal_places' => ['required', 'integer', 'between:0,3'],
        ]);
        Unit::create($data + ['status' => 'active']);
        return back()->with('success', '?? th?m ??n v? t?nh.');
    }
    public function storeConversion(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'inventory_item_id' => ['nullable', 'exists:inventory_items,id'],
            'from_unit_id' => ['required', 'different:to_unit_id', 'exists:units,id'],
            'to_unit_id' => ['required', 'exists:units,id'],
            'multiplier' => ['required', 'numeric', 'gt:0', 'max:1000000'],
        ]);
        $from = Unit::findOrFail($data['from_unit_id']);
        $to = Unit::findOrFail($data['to_unit_id']);
        if (! $data['inventory_item_id'] && $from->category !== $to->category) {
            throw ValidationException::withMessages(['to_unit_id' => 'Quy ??i to?n c?c ch? ?p d?ng cho ??n v? c?ng nh?m.']);
        }
        $scope = $data['inventory_item_id'] ? 'item-' . $data['inventory_item_id'] : 'global';
        UnitConversion::create($data + ['conversion_key' => "{$scope}:{$from->code}:{$to->code}"]);
        return back()->with('success', '?? th?m quy ??i ??n v?.');
    }
}
