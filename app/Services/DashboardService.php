<?php

namespace App\Services;

use App\Enums\BillStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\TableStatus;
use App\Models\Bill;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\RestaurantTable;
use App\Models\TableSession;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function data(string $preset, ?string $customFrom = null, ?string $customTo = null): array
    {
        [$from, $to, $label] = $this->period($preset, $customFrom, $customTo);
        $seconds = max(1, $from->diffInSeconds($to));
        $previousTo = $from->subSecond();
        $previousFrom = $previousTo->subSeconds($seconds);

        $revenue = (float) Bill::where('status', BillStatus::Paid)->whereBetween('paid_at', [$from, $to])->sum('total');
        $previousRevenue = (float) Bill::where('status', BillStatus::Paid)->whereBetween('paid_at', [$previousFrom, $previousTo])->sum('total');

        $orderBase = Order::query()->whereBetween('created_at', [$from, $to]);
        $orderStatuses = (clone $orderBase)->select('status', DB::raw('COUNT(*) AS aggregate'))->groupBy('status')->pluck('aggregate', 'status');
        $paymentStatuses = Payment::query()->whereBetween('created_at', [$from, $to])->select('status', DB::raw('COUNT(*) AS aggregate'))->groupBy('status')->pluck('aggregate', 'status');

        $dateExpression = $this->dateExpression('created_at');
        $paidDateExpression = $this->dateExpression('paid_at');

        $ordersByDate = (clone $orderBase)->selectRaw("{$dateExpression} AS period, COUNT(*) AS aggregate")
            ->groupBy('period')->orderBy('period')->get();
        $revenueByDate = Bill::query()->where('status', BillStatus::Paid)->whereBetween('paid_at', [$from, $to])
            ->selectRaw("{$paidDateExpression} AS period, SUM(total) AS aggregate")
            ->groupBy('period')->orderBy('period')->get();

        $topProducts = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->leftJoin('products', 'products.id', '=', 'order_items.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->whereBetween('orders.created_at', [$from, $to])
            ->where('orders.status', '!=', OrderStatus::Cancelled->value)
            ->where('order_items.status', '!=', 'cancelled')
            ->select('order_items.product_name', 'categories.name as category_name', DB::raw('SUM(order_items.quantity) AS quantity'), DB::raw('SUM(order_items.subtotal) AS revenue'))
            ->groupBy('order_items.product_name', 'categories.name')->orderByDesc('quantity')->limit(8)->get();

        $ordersByPeriod = $ordersByDate->pluck('aggregate', 'period');
        $revenueByPeriod = $revenueByDate->pluck('aggregate', 'period');
        $timelinePeriods = $ordersByPeriod->keys()->merge($revenueByPeriod->keys())->unique()->sort()->values();
        $statusLabels = [
            'pending' => 'Đang chờ',
            'confirmed' => 'Đã xác nhận',
            'preparing' => 'Đang chuẩn bị',
            'ready' => 'Sẵn sàng',
            'served' => 'Đã phục vụ',
            'completed' => 'Hoàn thành',
            'cancelled' => 'Đã hủy',
        ];

        $tableCounts = RestaurantTable::query()->select('status', DB::raw('COUNT(*) AS aggregate'))->groupBy('status')->pluck('aggregate', 'status');
        $inventoryTotal = InventoryItem::count();
        $lowStockCount = InventoryItem::whereColumn('current_stock', '<=', 'minimum_stock')->where('current_stock', '>', 0)->count();
        $outOfStockCount = InventoryItem::where('current_stock', '<=', 0)->count();

        return [
            'period' => compact('preset', 'from', 'to', 'label'),
            'kpis' => [
                'revenue' => $revenue,
                'revenue_change' => $this->percentageChange($revenue, $previousRevenue),
                'orders' => (int) $orderStatuses->sum(),
                'orders_pending' => (int) ($orderStatuses[OrderStatus::Pending->value] ?? 0),
                'orders_preparing' => (int) ($orderStatuses[OrderStatus::Preparing->value] ?? 0),
                'orders_completed' => (int) ($orderStatuses[OrderStatus::Completed->value] ?? 0),
                'tables_total' => (int) $tableCounts->sum(),
                'tables_available' => (int) ($tableCounts[TableStatus::Available->value] ?? 0),
                'tables_occupied' => (int) ($tableCounts[TableStatus::Occupied->value] ?? 0),
                'payments_paid' => (int) ($paymentStatuses[PaymentStatus::Paid->value] ?? 0),
                'payments_pending' => (int) ($paymentStatuses[PaymentStatus::Pending->value] ?? 0),
                'inventory_total' => $inventoryTotal,
                'low_stock' => $lowStockCount,
                'out_of_stock' => $outOfStockCount,
            ],
            'charts' => [
                'timeline' => [
                    'labels' => $timelinePeriods->map(fn ($date) => CarbonImmutable::parse($date)->format('d/m')),
                    'orders' => $timelinePeriods->map(fn ($date) => (int) ($ordersByPeriod[$date] ?? 0)),
                    'revenue' => $timelinePeriods->map(fn ($date) => (float) ($revenueByPeriod[$date] ?? 0)),
                ],
                'statuses' => [
                    'labels' => $orderStatuses->keys()->map(fn ($status) => $statusLabels[$status] ?? $status),
                    'values' => $orderStatuses->values()->map(fn ($value) => (int) $value),
                ],
            ],
            'recentOrders' => Order::with('tableSession.restaurantTable')->whereBetween('created_at', [$from, $to])->latest()->limit(8)->get(),
            'topProducts' => $topProducts,
            'occupiedSessions' => TableSession::with('restaurantTable', 'bill')->withCount('orders')->where('status', 'active')->latest('opened_at')->limit(8)->get(),
            'lowStockItems' => InventoryItem::whereColumn('current_stock', '<=', 'minimum_stock')->orderBy('current_stock')->limit(8)->get(),
        ];
    }

    private function period(string $preset, ?string $customFrom, ?string $customTo): array
    {
        $now = CarbonImmutable::now();
        return match ($preset) {
            'yesterday' => [$now->subDay()->startOfDay(), $now->subDay()->endOfDay(), 'Hôm qua'],
            '7days' => [$now->subDays(6)->startOfDay(), $now->endOfDay(), '7 ngày gần nhất'],
            '30days' => [$now->subDays(29)->startOfDay(), $now->endOfDay(), '30 ngày gần nhất'],
            'month' => [$now->startOfMonth(), $now->endOfDay(), 'Tháng này'],
            'last_month' => [$now->subMonthNoOverflow()->startOfMonth(), $now->subMonthNoOverflow()->endOfMonth(), 'Tháng trước'],
            'quarter' => [$now->startOfQuarter(), $now->endOfDay(), 'Quý này'],
            'year' => [$now->startOfYear(), $now->endOfDay(), 'Năm nay'],
            'custom' => $this->customPeriod($customFrom, $customTo),
            default => [$now->startOfDay(), $now->endOfDay(), 'Hôm nay'],
        };
    }

    private function customPeriod(?string $from, ?string $to): array
    {
        $start = CarbonImmutable::parse($from ?: 'today')->startOfDay();
        $end = CarbonImmutable::parse($to ?: $from ?: 'today')->endOfDay();
        if ($start->gt($end) || $start->diffInDays($end) > 366) {
            $start = $end->subDays(30)->startOfDay();
        }
        return [$start, $end, $start->format('d/m/Y').' - '.$end->format('d/m/Y')];
    }

    private function dateExpression(string $column): string
    {
        return DB::getDriverName() === 'sqlite' ? "strftime('%Y-%m-%d', {$column})" : "DATE_FORMAT({$column}, '%Y-%m-%d')";
    }

    private function percentageChange(float $current, float $previous): ?float
    {
        if ($previous <= 0) return $current > 0 ? 100.0 : null;
        return round((($current - $previous) / $previous) * 100, 1);
    }
}
