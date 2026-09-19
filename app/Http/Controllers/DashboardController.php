<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardService $dashboard): View
    {
        $data = $request->validate([
            'period' => ['nullable', Rule::in(['today', 'yesterday', '7days', '30days', 'month', 'last_month', 'quarter', 'year', 'custom'])],
            'from' => ['nullable', 'date', 'required_if:period,custom'],
            'to' => ['nullable', 'date', 'after_or_equal:from', 'required_if:period,custom'],
        ]);

        return view('dashboard', $dashboard->data(
            $data['period'] ?? 'today',
            $data['from'] ?? null,
            $data['to'] ?? null,
        ));
    }
}
