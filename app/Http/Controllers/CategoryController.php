<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(private readonly ActivityLogService $activity) {}

    public function index(Request $request): View
    {
        $categories = Category::query()->withCount('products')
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->q.'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderBy('sort_order')->orderBy('name')->paginate(config('restaurant.pagination'))->withQueryString();

        return view('categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('categories.form', ['category' => new Category]);
    }

    public function show(Category $category): View
    {
        return view('categories.show', compact('category'));
    }

    public function edit(Category $category): View
    {
        return view('categories.form', compact('category'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        $category = Category::create($data);
        $this->activity->write('create', 'categories', $category, new: $category->toArray());

        return redirect()->route('categories.index')->with('success', 'Đã tạo danh mục.');
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $old = $category->toArray();
        $data = $this->validated($request, $category);
        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        $category->update($data);
        $this->activity->write('update', 'categories', $category, $old, $category->fresh()->toArray());

        return redirect()->route('categories.index')->with('success', 'Đã cập nhật danh mục.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->products()->withTrashed()->exists()) {
            return back()->with('error', 'Không thể xóa danh mục đang có món.');
        }
        $category->delete();
        $this->activity->write('delete', 'categories', $category);

        return back()->with('success', 'Đã chuyển danh mục vào thùng rác.');
    }

    private function validated(Request $request, ?Category $category = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'alpha_dash', 'max:255', Rule::unique('categories')->ignore($category?->id)],
            'description' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);
    }
}
