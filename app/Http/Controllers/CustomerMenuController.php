<?php

namespace App\Http\Controllers;

use App\Enums\RecordStatus;
use App\Enums\TableStatus;
use App\Models\Category;
use App\Models\RestaurantTable;
use App\Models\TableSession;
use App\Services\TableSessionService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CustomerMenuController extends Controller
{
    public function show(Request $request, string $token, TableSessionService $sessions): View
    {
        $table = RestaurantTable::query()->where('qr_token', $token)->firstOrFail();
        if (! in_array($table->status, [TableStatus::Available, TableStatus::Occupied], true)) {
            throw ValidationException::withMessages(['table' => 'Bàn này hiện không nhận gọi món.']);
        }

        $tableSession = $sessions->open($table, (int) $request->integer('guests', 1));
        $request->session()->put('customer.table_session_id', $tableSession->id);
        $request->session()->put('customer.qr_token_hash', hash('sha256', $token));

        $categories = Category::query()->where('status', RecordStatus::Active)
            ->whereHas('products', fn ($q) => $q->where('status', RecordStatus::Active)->where('is_available', true))
            ->with(['products' => fn ($q) => $q->where('status', RecordStatus::Active)->where('is_available', true)->orderBy('sort_order')])
            ->orderBy('sort_order')->get();

        return view('customer.menu', [
            'table' => $table,
            'tableSession' => $tableSession,
            'categories' => $categories,
            'idempotencyKey' => (string) Str::uuid(),
        ]);
    }

    public function status(Request $request): View
    {
        $tableSession = $this->boundSession($request)->load([
            'restaurantTable',
            'orders' => fn ($q) => $q->with('items')->latest(),
            'bill.payments',
        ]);

        return view('customer.status', compact('tableSession'));
    }

    public static function boundSession(Request $request): TableSession
    {
        $id = $request->session()->get('customer.table_session_id');
        if (! $id) {
            abort(403, 'Phiên gọi món không hợp lệ. Vui lòng quét lại QR tại bàn.');
        }

        return TableSession::query()->findOrFail($id);
    }
}
