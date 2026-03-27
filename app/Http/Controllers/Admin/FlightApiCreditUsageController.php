<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FlightApiCreditUsage;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FlightApiCreditUsageController extends Controller
{
    public function index(Request $request)
    {
        if (!hasPermission('expense.index')) {
            abort(403, getCurrentTranslation()['permission_denied'] ?? 'Permission denied');
        }

        $dateRange = $request->date_range;
        $usedFor = $request->used_for;
        $creditUserId = $request->credit_used_by;

        if (empty($dateRange) || $dateRange == 0 || $dateRange == '0') {
            $startDate = Carbon::now()->firstOfMonth()->startOfDay();
            $endDate = Carbon::now()->endOfMonth()->endOfDay();
        } else {
            [$start, $end] = explode('-', $dateRange);
            $startDate = Carbon::createFromFormat('Y/m/d', trim($start))->startOfDay();
            $endDate = Carbon::createFromFormat('Y/m/d', trim($end))->endOfDay();
        }

        $baseQuery = FlightApiCreditUsage::query()
            ->whereBetween('usage_date_time', [$startDate, $endDate])
            ->when($usedFor && $usedFor !== 'all' && $usedFor !== '', function ($q) use ($usedFor) {
                return $q->where('used_for', $usedFor);
            })
            ->when($creditUserId && $creditUserId !== 'all' && $creditUserId !== '', function ($q) use ($creditUserId) {
                return $q->where('credit_used_by', (int) $creditUserId);
            });

        $totalRows = (clone $baseQuery)->count();
        $totalCredits = (int) ((clone $baseQuery)->sum('credit_amount'));

        $rows = (clone $baseQuery)
            ->with(['user'])
            ->orderByDesc('usage_date_time')
            ->orderByDesc('id')
            ->paginate(50)
            ->appends($request->query());

        $users = User::excludeAutomationChatbot()->excludeUserTypeUsers()->where('status', 'Active')
            ->with('designation')
            ->orderBy('name')
            ->get();

        $defaultDateRange = $dateRange ?? ($startDate->format('Y/m/d') . '-' . $endDate->format('Y/m/d'));

        $usedForOptions = [
            FlightApiCreditUsage::USED_FOR_TICKET_SEARCH,
            FlightApiCreditUsage::USED_FOR_FLIGHT_STATUS,
        ];

        return view('admin.report.flight-api-credit-usage', compact(
            'rows',
            'dateRange',
            'usedFor',
            'creditUserId',
            'users',
            'totalCredits',
            'totalRows',
            'defaultDateRange',
            'usedForOptions',
            'startDate',
            'endDate'
        ));
    }
}
