<?php

namespace App\Services;

use App\Enums\BillStatus;
use App\Enums\OrderStatus;
use App\Enums\TableSessionStatus;
use App\Enums\TableStatus;
use App\Models\RestaurantTable;
use App\Models\TableSession;
use App\Support\BusinessCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TableSessionService
{
    public function open(RestaurantTable $table, int $guestCount = 1): TableSession
    {
        return DB::transaction(function () use ($table, $guestCount) {
            $lockedTable = RestaurantTable::query()->lockForUpdate()->findOrFail($table->id);
            $active = TableSession::query()
                ->where('restaurant_table_id', $lockedTable->id)
                ->where('status', TableSessionStatus::Active)
                ->lockForUpdate()->first();

            if ($active) {
                return $active->load('restaurantTable', 'bill');
            }

            if ($lockedTable->status !== TableStatus::Available) {
                throw ValidationException::withMessages(['table' => 'Bàn hiện không sẵn sàng nhận khách.']);
            }

            $session = TableSession::create([
                'restaurant_table_id' => $lockedTable->id,
                'session_code' => BusinessCode::make('SES'),
                'status' => TableSessionStatus::Active,
                'guest_count' => max(1, min($guestCount, $lockedTable->capacity)),
                'opened_at' => now(),
            ]);
            $session->bill()->create([
                'bill_code' => BusinessCode::make('BILL'),
                'status' => BillStatus::Unpaid,
            ]);
            $lockedTable->update(['status' => TableStatus::Occupied]);

            return $session->load('restaurantTable', 'bill');
        }, 3);
    }

    public function close(TableSession $session): TableSession
    {
        return DB::transaction(function () use ($session) {
            $locked = TableSession::query()->lockForUpdate()->findOrFail($session->id);
            $bill = $locked->bill()->lockForUpdate()->firstOrFail();

            if ($bill->status !== BillStatus::Paid) {
                throw ValidationException::withMessages(['payment' => 'Hóa đơn chưa được thanh toán đủ.']);
            }

            $unfinished = $locked->orders()->whereIn('status', [
                OrderStatus::Pending, OrderStatus::Confirmed, OrderStatus::Preparing, OrderStatus::Ready,
            ])->exists();
            if ($unfinished) {
                throw ValidationException::withMessages(['orders' => 'Vẫn còn món chưa phục vụ xong.']);
            }

            $locked->orders()->where('status', OrderStatus::Served)->update(['status' => OrderStatus::Completed]);
            $locked->update(['status' => TableSessionStatus::Closed, 'closed_at' => now()]);
            $locked->restaurantTable()->update(['status' => TableStatus::Available]);

            return $locked->refresh();
        }, 3);
    }
}
