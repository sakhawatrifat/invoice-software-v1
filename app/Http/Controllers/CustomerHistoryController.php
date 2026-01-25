<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketPassenger;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerHistoryController extends Controller
{
    public function index(Request $request)
    {
        if (!hasPermission('customerHistory')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'permission_denied',
            ];
        }

        $layout = Auth::user()->user_type == 'admin' ? 'admin.layouts.default' : 'frontend.layouts.default';
        $getCurrentTranslation = getCurrentTranslation();
        $search = trim($request->get('search', ''));
        $leadId = $request->get('lead_id');

        $businessId = Auth::user()->business_id;

        $leadInfo = null;
        if (!empty($leadId)) {
            $leadInfo = Lead::where('id', $leadId)
                ->where('user_id', $businessId)
                ->first();
        }

        $passengers = collect();
        $stats = null;

        if ($search !== '') {
            $passengerQuery = TicketPassenger::with('ticket')
                ->where('user_id', $businessId)
                ->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%')
                        ->orWhere('phone', 'like', '%' . $search . '%');
                })
                ->orderByDesc('id')
                ->limit(50);

            $passengers = $passengerQuery->get();

            if ($passengers->isNotEmpty()) {
                $ticketIds = $passengers->pluck('ticket_id')->filter()->unique();

                $tickets = Ticket::whereIn('id', $ticketIds)
                    ->where('user_id', $businessId)
                    ->orderByDesc('invoice_date')
                    ->get();

                $lastTicket = $tickets->first();

                $primaryPassenger = null;
                if ($lastTicket) {
                    $primaryPassenger = $passengers->firstWhere('ticket_id', $lastTicket->id) ?? $passengers->first();
                }

                $stats = [
                    'total_tickets' => $tickets->count(),
                    'confirmed' => $tickets->where('booking_status', 'Confirmed')->count(),
                    'cancelled' => $tickets->where('booking_status', 'Cancelled')->count(),
                    'on_hold' => $tickets->where('booking_status', 'On Hold')->count(),
                    'last_ticket' => $lastTicket,
                    'primary_passenger' => $primaryPassenger,
                    'pax_types' => $passengers->pluck('pax_type')->filter()->unique()->values(),
                    'genders' => $passengers->pluck('gender')->filter()->unique()->values(),
                    'date_of_births' => $passengers->pluck('date_of_birth')->filter()->unique()->values(),
                ];
            }
        }

        return view('common.crm.customerHistory.index', compact(
            'layout',
            'getCurrentTranslation',
            'search',
            'leadInfo',
            'passengers',
            'stats'
        ));
    }
}

