<?php

namespace App\Http\Controllers\Admin;


use Auth;
use File;
use Image;
use Session;
use Carbon\Carbon;

use App\Models\User;
use App\Models\Airline;

use App\Models\Ticket;
use App\Models\TicketFlight;
use App\Models\TicketPassenger;
use App\Models\TicketPassengerFlight;
use App\Models\TicketFareSummary;

use App\Models\Payment;
use App\Models\PaymentDocument;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;

class ReportController extends Controller
{
    public function grossProfitLossReport(Request $request){
        if (!hasPermission('admin.grossProfitLossReport')) {
            abort(403, 'Unauthorized action.');
        }
        $user = Auth::user();

        $profitLossQuery = Payment::with('ticket', 'paymentDocuments', 'introductionSource', 'country', 'issuedBy', 'airline', 'transferTo', 'paymentMethod', 'issuedCardType', 'cardOwner');
            if (!empty($request->search)) {
                $search = $request->search;

                $profitLossQuery->where(function ($q) use ($search) {
                    // Search in main table columns
                    $q->where('payment_invoice_id', 'like', "%{$search}%")
                        ->orWhere('client_name', 'like', "%{$search}%")
                        ->orWhere('client_phone', 'like', "%{$search}%")
                        ->orWhere('client_email', 'like', "%{$search}%")
                        ->orWhere('trip_type', 'like', "%{$search}%")
                        ->orWhere('departure', 'like', "%{$search}%")
                        ->orWhere('destination', 'like', "%{$search}%")
                        ->orWhere('flight_route', 'like', "%{$search}%")
                        ->orWhere('seat_confirmation', 'like', "%{$search}%")
                        ->orWhere('mobility_assistance', 'like', "%{$search}%")
                        ->orWhere('transit_visa_application', 'like', "%{$search}%")
                        ->orWhere('halal_meal_request', 'like', "%{$search}%")
                        ->orWhere('transit_hotel', 'like', "%{$search}%")
                        ->orWhere('card_digit', 'like', "%{$search}%")
                        ->orWhere('payment_status', 'like', "%{$search}%");

                    // Search in related ticket invoice_id
                    $q->orWhereHas('ticket', function ($q2) use ($search) {
                        $q2->where('invoice_id', 'like', "%{$search}%");
                    });

                    // Search in related airline name
                    $q->orWhereHas('airline', function ($q3) use ($search) {
                        $q3->where('name', 'like', "%{$search}%");
                    });
                });
            }

            if (!empty($request->introduction_source_id) && $request->introduction_source_id != 0) {
                $profitLossQuery->where('introduction_source_id', $request->introduction_source_id);
            }

            if (!empty($request->customer_country_id) && $request->customer_country_id != 0) {
                $profitLossQuery->where('customer_country_id', $request->customer_country_id);
            }

            if (!empty($request->issued_supplier_ids) && $request->issued_supplier_ids != 0) {
                $ids = (array) $request->issued_supplier_ids;

                $profitLossQuery->where(function ($q) use ($ids) {
                    foreach ($ids as $id) {
                        $q->orWhereJsonContains('issued_supplier_ids', $id);
                    }
                });
            }

            if (!empty($request->issued_by_id) && $request->issued_by_id != 0) {
                $profitLossQuery->where('issued_by_id', $request->issued_by_id);
            }

            if (!empty($request->trip_type) && $request->trip_type != 0) {
                $profitLossQuery->where('trip_type', $request->trip_type);
            }

            if (!empty($request->departure) && $request->departure != 0) {
                $profitLossQuery->where('departure', $request->departure);
            }

            if (!empty($request->destination) && $request->destination != 0) {
                $profitLossQuery->where('destination', $request->destination);
            }

            if (!empty($request->airline_id) && $request->airline_id != 0) {
                $profitLossQuery->where('airline_id', $request->airline_id);
            }

            if (!empty($request->flight_date_range) && $request->flight_date_range != 0) {
                $flightDateRange = $request->flight_date_range;

                // Split the string into start and end dates
                [$start, $end] = explode('-', $flightDateRange);

                // Convert to Carbon instances
                $startDate = Carbon::createFromFormat('Y/m/d', trim($start))->startOfDay();
                $endDate = Carbon::createFromFormat('Y/m/d', trim($end))->endOfDay();

                // Group the date conditions inside a where() closure
                $profitLossQuery->where(function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('departure_date_time', [$startDate, $endDate])
                        ->orWhereBetween('return_date_time', [$startDate, $endDate]);
                });
            } 
            // else {
            //     // Default: last 7 days
            //     $startDate = Carbon::now()->subDays(6)->startOfDay();
            //     $endDate = Carbon::now()->endOfDay();
            // }

            

            if (!empty(request()->invoice_date_range) && request()->invoice_date_range != 0) {
                $invoiceDateRange = request()->invoice_date_range;
                // Split the string into start and end dates
                [$start, $end] = explode('-', $invoiceDateRange);

                // Convert to Carbon instances (optional but safer)
                $startDate = \Carbon\Carbon::createFromFormat('Y/m/d', trim($start))->startOfDay();
                $endDate = \Carbon\Carbon::createFromFormat('Y/m/d', trim($end))->endOfDay();

                $profitLossQuery->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('invoice_date', [$startDate, $endDate]);
                });
            }

            if (!empty(request()->transfer_to) && request()->transfer_to != 0) {
                $profitLossQuery->where('transfer_to_id', request()->transfer_to);
            }

            if (!empty(request()->payment_method) && request()->payment_method != 0) {
                $profitLossQuery->where('payment_method_id', request()->payment_method);
            }

            if (!empty(request()->issued_card_type) && request()->issued_card_type != 0) {
                $profitLossQuery->where('issued_card_type_id', request()->issued_card_type);
            }

            if (!empty(request()->card_owner) && request()->card_owner != 0) {
                $profitLossQuery->where('card_owner_id', request()->card_owner);
            }

            if (!empty(request()->payment_status) && request()->payment_status != 0) {
                $profitLossQuery->where('payment_status', request()->payment_status);
            }

            if (!empty(request()->payment_date_range) && request()->payment_date_range != 0) {
                    $paymentDateRange = request()->payment_date_range;

                    // Split the string into start and end dates
                    [$start, $end] = explode('-', $paymentDateRange);

                    // Convert to Carbon instances
                    $startDate = \Carbon\Carbon::createFromFormat('Y/m/d', trim($start))->startOfDay();
                    $endDate = \Carbon\Carbon::createFromFormat('Y/m/d', trim($end))->endOfDay();

                    $profitLossQuery->whereNotNull('paymentData')
                        ->whereRaw("
                            EXISTS (
                                SELECT 1
                                FROM JSON_TABLE(
                                    paymentData,
                                    '$[*]' COLUMNS (
                                        pay_date DATE PATH '$.date'
                                    )
                                ) AS pd
                                WHERE pd.pay_date BETWEEN ? AND ?
                            )
                        ", [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);
                }

            if (!empty(request()->next_payment_date_range) && request()->next_payment_date_range != 0) {
                $invoiceDateRange = request()->next_payment_date_range;
                // Split the string into start and end dates
                [$start, $end] = explode('-', $invoiceDateRange);

                // Convert to Carbon instances (optional but safer)
                $startDate = \Carbon\Carbon::createFromFormat('Y/m/d', trim($start))->startOfDay();
                $endDate = \Carbon\Carbon::createFromFormat('Y/m/d', trim($end))->endOfDay();

                $profitLossQuery->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('next_payment_deadline', [$startDate, $endDate]);
                });
            }
            

            $profitLossData = $profitLossQuery->get();
        
        return view('admin.report.profitLoss', get_defined_vars());
    }

}