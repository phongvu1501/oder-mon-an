<?php

namespace App\Http\Controllers;

use App\Enums\TableSessionStatus;
use App\Models\RestaurantTable;
use App\Services\ActivityLogService;
use App\Services\QrCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class TableController extends Controller
{
    public function __construct(private readonly QrCodeService $qr, private readonly ActivityLogService $activity) {}

    public function index(Request $request): View
    {
        $tables = RestaurantTable::with('activeSession')
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($i) => $i->where('name', 'like', '%'.$request->q.'%')->orWhere('code', 'like', '%'.$request->q.'%')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderBy('code')->paginate(config('restaurant.pagination'))->withQueryString();

        return view('tables.index', compact('tables'));
    }

    public function create(): View
    {
        return view('tables.form', ['table' => new RestaurantTable]);
    }

    public function show(RestaurantTable $table): View
    {
        return view('tables.show', ['table' => $table->load('activeSession.bill')]);
    }

    public function edit(RestaurantTable $table): View
    {
        return view('tables.form', compact('table'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['qr_token'] = Str::random(64);
        $table = RestaurantTable::create($data);
        $this->activity->write('create', 'tables', $table, new: $table->makeVisible('qr_token')->toArray());

        return redirect()->route('tables.index')->with('success', 'Đã tạo bàn và QR bảo mật.');
    }

    public function update(Request $request, RestaurantTable $table): RedirectResponse
    {
        $old = $table->toArray();
        $table->update($this->validated($request, $table));
        $this->activity->write('update', 'tables', $table, $old, $table->fresh()->toArray());

        return redirect()->route('tables.index')->with('success', 'Đã cập nhật bàn.');
    }

    public function destroy(RestaurantTable $table): RedirectResponse
    {
        if ($table->sessions()->where('status', TableSessionStatus::Active)->exists()) {
            return back()->with('error', 'Không thể xóa bàn đang phục vụ.');
        }
        $table->delete();
        $this->activity->write('delete', 'tables', $table);

        return back()->with('success', 'Đã chuyển bàn vào thùng rác.');
    }

    public function qr(RestaurantTable $table): Response
    {
        return response($this->qr->svg(route('menu.show', $table->qr_token)))
            ->header('Content-Type', 'image/svg+xml')
            ->header('X-Content-Type-Options', 'nosniff');
    }

    public function downloadQr(RestaurantTable $table): Response
    {
        return $this->qr($table)->header('Content-Disposition', 'attachment; filename="'.$table->code.'-qr.svg"');
    }

    public function regenerateQr(RestaurantTable $table): RedirectResponse
    {
        if ($table->sessions()->where('status', TableSessionStatus::Active)->exists()) {
            return back()->with('error', 'Không thể đổi QR khi bàn đang phục vụ.');
        }
        $table->update(['qr_token' => Str::random(64)]);
        $this->activity->write('regenerate_qr', 'tables', $table);

        return back()->with('success', 'Đã tạo lại QR. Mã QR cũ không còn hiệu lực.');
    }

    private function validated(Request $request, ?RestaurantTable $table = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:30', Rule::unique('restaurant_tables')->ignore($table?->id)],
            'name' => ['required', 'string', 'max:255'],
            'capacity' => ['required', 'integer', 'min:1', 'max:100'],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['available', 'reserved', 'maintenance', 'inactive'])],
        ]);
    }
}
