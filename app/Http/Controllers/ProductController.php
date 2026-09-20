<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(private readonly ActivityLogService $activity) {}

    public function index(Request $request): View
    {
        $products = Product::with('category')
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($inner) => $inner->where('name', 'like', '%'.$request->q.'%')->orWhere('sku', 'like', '%'.$request->q.'%')))
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->category_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('availability'), fn ($q) => $q->where('is_available', $request->availability === 'available'))
            ->orderBy('sort_order')->latest()->paginate(config('restaurant.pagination'))->withQueryString();

        return view('products.index', ['products' => $products, 'categories' => Category::orderBy('name')->get()]);
    }

    public function create(): View
    {
        return view('products.form', ['product' => new Product, 'categories' => Category::where('status', 'active')->orderBy('name')->get()]);
    }

    public function show(Product $product): View
    {
        return view('products.show', ['product' => $product->load('category', 'recipe.items.inventoryItem')]);
    }

    public function edit(Product $product): View
    {
        return view('products.form', ['product' => $product, 'categories' => Category::orderBy('name')->get()]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $data = $this->payload($request);
        $product = Product::create($data);
        $this->activity->write('create', 'products', $product, new: $product->toArray());

        return redirect()->route('products.index')->with('success', 'Đã tạo món.');
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $old = $product->toArray();
        $data = $this->payload($request, $product);
        $product->update($data);
        $this->activity->write('update', 'products', $product, $old, $product->fresh()->toArray());

        return redirect()->route('products.index')->with('success', 'Đã cập nhật món.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();
        $this->activity->write('delete', 'products', $product);

        return back()->with('success', 'Đã chuyển món vào thùng rác.');
    }

    private function payload(ProductRequest $request, ?Product $product = null): array
    {
        $data = $request->validated();
        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        $data['is_available'] = $request->boolean('is_available');
        $data['cost_price'] ??= 0;
        $data['sort_order'] ??= 0;
        if ($request->hasFile('image')) {
            if ($product?->image) {
                Storage::disk('public')->delete($product->image);
            }
            $data['image'] = $request->file('image')->store('products', 'public');
        } else {
            unset($data['image']);
        }

        return $data;
    }
}
