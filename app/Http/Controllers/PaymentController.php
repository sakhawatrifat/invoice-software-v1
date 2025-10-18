<?php

namespace App\Http\Controllers;


use Auth;
use File;
use Image;
use Session;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

use App\Models\Language;
use App\Models\Currency;
use App\Models\User;
use App\Models\UserCompany;
use App\Models\Ticket;
use App\Models\Payment;
use App\Models\PaymentDocument;

use PDF;
use App\Services\PdfService;

class PaymentController extends Controller
{
    public function index()
    {
        if (!hasPermission('payment.index')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }
        
        $createRoute = hasPermission('payment.create') ? route('payment.create') : '';
        $dataTableRoute = hasPermission('payment.index') ? route('payment.datatable') : '';

        return view('common.payment-record.index', get_defined_vars());
    }

    public function datatable()
    {
        $user = Auth::user();

        $query = Payment::with('ticket', 'paymentDocuments', 'introductionSource', 'country', 'issuedBy', 'airline', 'transferTo', 'paymentMethod', 'issuedCardType', 'cardOwner')->latest();

        $getCurrentTranslation = getCurrentTranslation();

        return DataTables::of($query)
            ->filter(function ($query) {
                if (!empty(request('search')['value'])) {
                    $search = request('search')['value'];

                    $query->where(function ($q) use ($search) {
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

                if (!empty(request()->introduction_source_id) && request()->introduction_source_id != 0) {
                    $query->where('introduction_source_id', request()->introduction_source_id);
                }

                if (!empty(request()->customer_country_id) && request()->customer_country_id != 0) {
                    $query->where('customer_country_id', request()->customer_country_id);
                }

                if (!empty(request()->issued_supplier_ids) && request()->issued_supplier_ids != 0) {
                    $ids = (array) request()->issued_supplier_ids;

                    $query->where(function ($q) use ($ids) {
                        foreach ($ids as $id) {
                            $q->orWhereJsonContains('issued_supplier_ids', $id);
                        }
                    });
                }

                if (!empty(request()->issued_by_id) && request()->issued_by_id != 0) {
                    $query->where('issued_by_id', request()->issued_by_id);
                }

                if (!empty(request()->trip_type) && request()->trip_type != 0) {
                    $query->where('trip_type', request()->trip_type);
                }

                if (!empty(request()->departure) && request()->departure != 0) {
                    $query->where('departure', request()->departure);
                }

                if (!empty(request()->destination) && request()->destination != 0) {
                    $query->where('destination', request()->destination);
                }

                if (!empty(request()->airline_id) && request()->airline_id != 0) {
                    $query->where('airline_id', request()->airline_id);
                }

                if (!empty(request()->flight_date_range) && request()->flight_date_range != 0) {
                    $flightDateRange = request()->flight_date_range;
                    // Split the string into start and end dates
                    [$start, $end] = explode('-', $flightDateRange);

                    // Convert to Carbon instances (optional but safer)
                    $startDate = \Carbon\Carbon::createFromFormat('Y/m/d', trim($start))->startOfDay();
                    $endDate = \Carbon\Carbon::createFromFormat('Y/m/d', trim($end))->endOfDay();

                    $query->where(function ($q) use ($startDate, $endDate) {
                        $q->whereBetween('departure_date_time', [$startDate, $endDate])
                            ->orWhereBetween('return_date_time', [$startDate, $endDate]);
                    });
                }

                if (!empty(request()->invoice_date_range) && request()->invoice_date_range != 0) {
                    $invoiceDateRange = request()->invoice_date_range;
                    // Split the string into start and end dates
                    [$start, $end] = explode('-', $invoiceDateRange);

                    // Convert to Carbon instances (optional but safer)
                    $startDate = \Carbon\Carbon::createFromFormat('Y/m/d', trim($start))->startOfDay();
                    $endDate = \Carbon\Carbon::createFromFormat('Y/m/d', trim($end))->endOfDay();

                    $query->where(function ($q) use ($startDate, $endDate) {
                        $q->whereBetween('invoice_date', [$startDate, $endDate]);
                    });
                }

                if (!empty(request()->transfer_to) && request()->transfer_to != 0) {
                    $query->where('transfer_to_id', request()->transfer_to);
                }

                if (!empty(request()->payment_method) && request()->payment_method != 0) {
                    $query->where('payment_method_id', request()->payment_method);
                }

                if (!empty(request()->issued_card_type) && request()->issued_card_type != 0) {
                    $query->where('issued_card_type_id', request()->issued_card_type);
                }

                if (!empty(request()->card_owner) && request()->card_owner != 0) {
                    $query->where('card_owner_id', request()->card_owner);
                }

                if (!empty(request()->payment_status) && request()->payment_status != 0) {
                    $query->where('payment_status', request()->payment_status);
                }

                if (!empty(request()->payment_date_range) && request()->payment_date_range != 0) {
                    $paymentDateRange = request()->payment_date_range;

                    // Split the string into start and end dates
                    [$start, $end] = explode('-', $paymentDateRange);

                    // Convert to Carbon instances
                    $startDate = \Carbon\Carbon::createFromFormat('Y/m/d', trim($start))->startOfDay();
                    $endDate = \Carbon\Carbon::createFromFormat('Y/m/d', trim($end))->endOfDay();

                    $query->whereNotNull('paymentData')
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

                    $query->where(function ($q) use ($startDate, $endDate) {
                        $q->whereBetween('next_payment_deadline', [$startDate, $endDate]);
                    });
                }
            })

            ->addIndexColumn()

            // ✅ User (relationship with users table)
            // ->addColumn('user_id', function ($row) {
            //     return $row->user ? $row->user->name : 'N/A';
            // })

            // ✅ Invoice Info
            ->addColumn('payment_invoice_id', function ($row) use ($getCurrentTranslation) {
                $paymentInvoiceIDLabel = $getCurrentTranslation['payment_invoice_id_label'] ?? 'payment_invoice_id_label';
                $ticketInvoiceIDLabel = $getCurrentTranslation['ticket_invoice_id_label'] ?? 'ticket_invoice_id_label';
                $ticketReservationLabel = $getCurrentTranslation['reservation_number_label'] ?? 'reservation_number_label';
                $ticketInvoiceDateLabel = $getCurrentTranslation['invoice_date_label'] ?? 'invoice_date_label';

                $paymentInvoiceID = $row->payment_invoice_id ?? 'N/A';
                $ticketInvoiceID = $row->ticket->invoice_id ?? 'N/A';
                $ticketReservation = $row->ticket->reservation_number ?? 'N/A';
                $ticketInvoiceDate = $row->invoice_date
                                        ? date('Y-m-d', strtotime($row->invoice_date))
                                        : 'N/A';

                return "
                    <div>
                        <strong>{$paymentInvoiceIDLabel}:</strong> {$paymentInvoiceID}<br>
                        <strong>{$ticketInvoiceIDLabel}:</strong> {$ticketInvoiceID}<br>
                        <strong>{$ticketReservationLabel}:</strong> {$ticketReservation}<br>
                        <strong>{$ticketInvoiceDateLabel}:</strong> {$ticketInvoiceDate}<br>
                    </div>
                ";
            })

            // ✅ Client Info
            ->addColumn('client_name', function ($row) use ($getCurrentTranslation) {
                $nameLabel = $getCurrentTranslation['name'] ?? 'name';
                $phoneLabel = $getCurrentTranslation['phone'] ?? 'phone';
                $emailLabel = $getCurrentTranslation['email'] ?? 'email';

                $name = $row->client_name ?? 'N/A';
                $phone = $row->client_phone ?? 'N/A';
                $email = $row->client_email ?? 'N/A';

                return "
                    <div>
                        <strong>{$nameLabel}:</strong> {$name}<br>
                        <strong>{$phoneLabel}:</strong> {$phone}<br>
                        <strong>{$emailLabel}:</strong> {$email}
                    </div>
                ";
            })


            ->addColumn('issued_by_id', function ($row) {
                return $row->issuedBy->name ?? 'N/A';
            })

            // ✅ Trip Info
            ->addColumn('trip_type', function ($row) use ($getCurrentTranslation) {
                $tripTypeLabel = $getCurrentTranslation['trip_type_label'] ?? 'trip_type_label';
                $departureLabel = $getCurrentTranslation['departure_label'] ?? 'departure_label';
                $returnLabel = $getCurrentTranslation['return_label'] ?? 'return_label';
                $flightRouteLabel = $getCurrentTranslation['flight_route_label'] ?? 'flight_route_label';
                $departureFromLabel = $getCurrentTranslation['departure_label'] ?? 'departure_label';
                $destinationToLabel = $getCurrentTranslation['destination_label'] ?? 'destination_label';
                $seatLabel = $getCurrentTranslation['seat_label'] ?? 'seat_label';
                $airlineLabel = $getCurrentTranslation['airline_label'] ?? 'airline_label';
                $halalMealLabel = $getCurrentTranslation['halal_meal_label'] ?? 'halal_meal_label';
                $hotelTransitLabel = $getCurrentTranslation['transit_hotel_label'] ?? 'transit_hotel_label';
                $mobilityAssistLabel = $getCurrentTranslation['mobility_assistance_label'] ?? 'mobility_assistance_label';
                $transitVisaLabel = $getCurrentTranslation['transit_visa_application_label'] ?? 'transit_visa_application_label';

                $tripType = $row->trip_type ?? '—';
                $departure = $row->departure_date_time ? date('Y-m-d, H:i', strtotime($row->departure_date_time)) : '—';
                $return = $row->return_date_time ? date('Y-m-d, H:i', strtotime($row->return_date_time)) : '—';
                $departureFrom = $row->departure ?? '—';
                $destinationTo = $row->destination ?? '—';
                $flightRoute = $row->flight_route ?? '—';
                $seat = $row->seat_confirmation ?? '—';
                $airline = $row->airline->name ?? '—'; // assuming relation ->airline
                $halalMeal = $row->halal_meal_request ?? '—';
                $hotelTransit = $row->transit_hotel ?? '—';
                $mobilityAssist = $row->mobility_assistance ?? '—';
                $transitVisa = $row->transit_visa_application ?? '—';

                $seatBadge = match($seat) {
                    'Window' => '<span class="badge bg-primary">Window</span>',
                    'Aisle' => '<span class="badge bg-success">Aisle</span>',
                    'Not Chosen' => '<span class="badge bg-warning text-dark">Not Chosen</span>',
                    default => '<span class="badge bg-light text-dark">—</span>'
                };

                $halalBadge = match($halalMeal) {
                    'Need To Do' => '<span class="badge bg-danger text-white">Need To Do</span>',
                    'Done' => '<span class="badge bg-success">Done</span>',
                    'No Need' => '<span class="badge bg-secondary text-dark">No Need</span>',
                    default => '<span class="badge bg-light text-dark">—</span>'
                };

                $hotelBadge = match($hotelTransit) {
                    'Need To Do' => '<span class="badge bg-danger text-white">Need To Do</span>',
                    'Done' => '<span class="badge bg-success">Done</span>',
                    'No Need' => '<span class="badge bg-secondary text-dark">No Need</span>',
                    default => '<span class="badge bg-light text-dark">—</span>'
                };

                $mobilityBadge = match($mobilityAssist) {
                    'Wheelchair' => '<span class="badge bg-primary">Wheelchair</span>',
                    'Baby Bassinet Seat' => '<span class="badge bg-info">Baby Bassinet Seat</span>',
                    'Meet & Assist' => '<span class="badge bg-success">Meet & Assist</span>',
                    'Not Chosen' => '<span class="badge bg-warning text-dark">Not Chosen</span>',
                    default => '<span class="badge bg-light text-dark">—</span>'
                };

                $visaBadge = match($transitVisa) {
                    'Need To Do' => '<span class="badge bg-danger text-white">Need To Do</span>',
                    'Done' => '<span class="badge bg-success">Done</span>',
                    'No Need' => '<span class="badge bg-secondary text-dark">No Need</span>',
                    default => '<span class="badge bg-light text-dark">—</span>'
                };

                return "
                    <div style='max-width: 280px; line-height: 1.6;'>
                        <strong>{$tripTypeLabel}:</strong> {$tripType}<br>
                        <strong>{$departureLabel}:</strong> {$departure}<br>
                        <strong>{$returnLabel}:</strong> {$return}<br>
                        <strong>{$flightRouteLabel}:</strong> {$flightRoute}<br>
                        <strong>{$airlineLabel}:</strong> {$airline}<br>
                        <strong>{$seatLabel}:</strong> {$seatBadge}<br>
                        <strong>{$mobilityAssistLabel}:</strong> {$mobilityBadge}<br>
                        <strong>{$transitVisaLabel}:</strong> {$visaBadge}<br>
                        <strong>{$halalMealLabel}:</strong> {$halalBadge}<br>
                        <strong>{$hotelTransitLabel}:</strong> {$hotelBadge}
                    </div>
                ";
            })



            // ✅ Total & Payment Summary
            ->addColumn('total_selling_price', function ($row) use ($getCurrentTranslation) {
                $currency = Auth::user()->company_data->currency->short_name ?? 'JPY';

                // 💰 Badge class based on status
                $paymentBadgeClass = match ($row->payment_status) {
                    'Paid' => 'badge badge-success',
                    'Partial' => 'badge badge-primary',
                    'Unpaid' => 'badge badge-danger',
                    default => 'badge badge-secondary',
                };

                // 🕒 Next Payment Deadline
                $nextPayment = $row->next_payment_deadline
                    ? date('Y-m-d', strtotime($row->next_payment_deadline))
                    : '—';

                // 💵 Calculate total paid amount from paymentData
                $totalPaid = 0;
                if (!empty($row->paymentData) && is_array($row->paymentData)) {
                    foreach ($row->paymentData as $p) {
                        if (!empty($p['paid_amount']) && is_numeric($p['paid_amount'])) {
                            $totalPaid += $p['paid_amount'];
                        }
                    }
                }

                // 📉 Calculate due
                $totalDue = ($row->total_selling_price ?? 0) - $totalPaid;

                // ✅ Localized labels
                $totalPurchaseLabel = $getCurrentTranslation['purchase_price'] ?? 'purchase_price';
                $totalSellLabel = $getCurrentTranslation['selling_price'] ?? 'selling_price';
                $totalProfitLabel = $getCurrentTranslation['total_profit'] ?? 'total_profit';
                $totalLossLabel = $getCurrentTranslation['total_loss'] ?? 'total_loss';
                $totalPaidLabel = $getCurrentTranslation['total_paid'] ?? 'total_paid';
                $totalDueLabel = $getCurrentTranslation['due'] ?? 'due';
                $nextPaymentLabel = $getCurrentTranslation['next_payment'] ?? 'next_payment';
                $statusLabel = $getCurrentTranslation['status'] ?? 'status';

                $totalProfitLoss = ($row->total_selling_price ?? 0) - ($row->total_purchase_price ?? 0);

                // ✅ Determine label and color for profit/loss
                if ($totalProfitLoss > 0) {
                    $profitLossLabel = $totalProfitLabel;
                    $profitLossClass = 'text-success';
                    $profitLossSign = ''; // No sign for profit
                } elseif ($totalProfitLoss < 0) {
                    $profitLossLabel = $totalLossLabel;
                    $profitLossClass = 'text-danger';
                    $profitLossSign = '-'; // Add minus sign for loss
                } else {
                    $profitLossLabel = $totalProfitLabel;
                    $profitLossClass = 'text-muted';
                    $profitLossSign = ''; // No sign for zero
                }

                // ✅ Conditionally include Next Payment only if Due > 0
                $nextPaymentHtml = '';
                if ($totalDue > 0) {
                    $nextPaymentHtml = '<div><strong>' . e($nextPaymentLabel) . ':</strong> ' . e($nextPayment) . '</div>';
                }

                // ✅ Build HTML output (ordered as requested)
                return '
                    <div>
                        <div><strong>' . e($totalPurchaseLabel) . ':</strong> <span class="text-primary">' . $currency . number_format($row->total_purchase_price ?? 0, 2) . '</span></div>
                        <div><strong>' . e($totalSellLabel) . ':</strong> <span class="text-info">' . $currency . number_format($row->total_selling_price ?? 0, 2) . '</span></div>
                        <div><strong>' . e($profitLossLabel) . ':</strong> <span class="' . $profitLossClass . '">' . $profitLossSign . $currency . number_format(abs($totalProfitLoss), 2) . '</span></div>
                        <div><strong>' . e($totalPaidLabel) . ':</strong> <span class="text-success">' . $currency . number_format($totalPaid, 2) . '</span></div>
                        <div><strong>' . e($totalDueLabel) . ':</strong> <span class="text-warning">' . $currency . number_format($totalDue, 2) . '</span></div>
                        ' . $nextPaymentHtml . '
                        <div><strong>' . e($statusLabel) . ':</strong> <span class="' . $paymentBadgeClass . '">' . e($row->payment_status) . '</span></div>
                    </div>';
            })


            // ✅ Created At
            ->editColumn('created_at', function ($row) {
                return $row->created_at ? Carbon::parse($row->created_at)->format('Y-m-d, H:i') : 'N/A';
            })

            // ✅ Created By (relationship with users table)
            ->addColumn('created_by', function ($row) {
                return $row->creator ? $row->creator->name : 'N/A';
            })

            // ✅ Actions
            ->addColumn('action', function ($row) {
                $detailsUrl   = route('payment.show', $row->id);
                $editUrl      = route('payment.edit', $row->id);
                $deleteUrl    = route('payment.destroy', $row->id);

                $buttons = '';

                // 👁️ Details
                if (hasPermission('payment.show')) {
                    $buttons .= '
                        <a href="' . $detailsUrl . '" class="btn btn-sm btn-info my-1" title="Details">
                            <i class="fa-solid fa-pager"></i>
                        </a>
                    ';
                }

                // ✏️ Edit
                if (hasPermission('payment.edit')) {
                    $buttons .= '
                        <a href="' . $editUrl . '" class="btn btn-sm btn-primary my-1" title="Edit">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </a>
                    ';
                }

                // 🗑️ Delete
                if (hasPermission('payment.delete')) {
                    $buttons .= '
                        <button class="btn btn-sm btn-danger my-1 delete-table-data-btn"
                            data-id="' . $row->id . '"
                            data-url="' . $deleteUrl . '"
                            title="Delete">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    ';
                }

                return !empty($buttons) ? $buttons : 'N/A';
            })


            ->rawColumns(['payment_invoice_id', 'client_name', 'trip_type', 'total_selling_price', 'action'])
            ->make(true);

    }

    public function create()
    {
        if (!hasPermission('payment.create')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        $listRoute = hasPermission('payment.index') ? route('payment.index') : '';
        $saveRoute = hasPermission('payment.create') ? route('payment.store') : '';

        $currencies = Currency::where('status', 'Active')->orderBy('currency_name', 'asc')->get();
        return view('common.payment-record.paymentAddEdit', get_defined_vars());
    }

    public function ticketSearch(Request $request)
    {
        if (!hasPermission('payment.create')) {
            return response()->json([
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ]);
        }

        $ticketData = Ticket::with('flights', 'flights.transits', 'passengers', 'passengers.flights', 'fareSummary', 'user', 'user.company', 'creator')->where('invoice_id', 'like', '%' . $request->search . '%')
            ->orWhere('reservation_number', 'like', '%' . $request->search . '%')
            ->get();

        return response()->json([
            'is_success' => 1,
            'icon' => 'success',
            'ticketData' => $ticketData
        ]);
    }


    public function store(Request $request)
    {
        if (!hasPermission('payment.create')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }
        
        return $this->saveData($request);
    }


    public function show($id)
    {
        if (!hasPermission('payment.show')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        $user = Auth::user();
        $listRoute = hasPermission('payment.index') ? route('payment.index') : '';
        $saveRoute = hasPermission('payment.edit') ? route('payment.update', $id) : '';

        $editData = Payment::with('ticket', 'paymentDocuments', 'introductionSource', 'country', 'issuedBy', 'airline', 'transferTo', 'paymentMethod', 'issuedCardType', 'cardOwner')->where('id', $id)->first();
        if(empty($editData)){
            abort(404);
        }
        // if($user->user_type != 'admin' && $editData->user_id != $user->business_id){
        //     abort(404);
        // }
        //dd($editData);
        return view('common.payment-record.details', get_defined_vars());
    }

    public function edit($id)
    {
        if (!hasPermission('payment.edit')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        $user = Auth::user();
        $listRoute = hasPermission('payment.index') ? route('payment.index') : '';
        $saveRoute = hasPermission('payment.edit') ? route('payment.update', $id) : '';

        $query = Payment::with('ticket', 'paymentDocuments', 'introductionSource', 'country', 'issuedBy', 'airline', 'transferTo', 'paymentMethod', 'issuedCardType', 'cardOwner')->where('id', $id);
        // if(Auth::user()->user_type == 'user'){
        //     $query->where('user_id', $user->business_id);
        // }
        $editData = $query->first();
        
        if(empty($editData)){
            abort(404);
        }
        // if($user->user_type != 'admin' && $editData->user_id != $user->id){
            //     abort(404);
            // }
            
        //dd($editData);
        $currencies = Currency::where('status', 'Active')->orderBy('currency_name', 'asc')->get();
        return view('common.payment-record.paymentAddEdit', get_defined_vars());
    }

    public function update(Request $request, $id)
    {
        if (!hasPermission('payment.edit')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        return $this->saveData($request, $id);
    }


    public function destroy($id)
    {
        if (!hasPermission('payment.delete')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        $user = Auth::user();
        $query = Payment::with('ticket', 'paymentDocuments', 'introductionSource', 'country', 'issuedBy', 'airline', 'transferTo', 'paymentMethod', 'issuedCardType', 'cardOwner')->where('id', $id);
        // if(Auth::user()->user_type == 'user'){
        //     $query->where('user_id', $user->business_id);
        // }
        $data = $query->first();

        if(empty($data)){
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['data_not_found'] ?? 'data_not_found'
            ];
        }
        // if($user->user_type != 'admin' && $data->user_id != $user->id){
        //     return [
        //         'is_success' => 0,
        //         'icon' => 'error',
        //         'message' => getCurrentTranslation()['data_not_found'] ?? 'data_not_found'
        //     ];
        // }

        if($data->paymentDocuments && count($data->paymentDocuments)){
            foreach($data->paymentDocuments as $docItem){
                deleteUploadedFile($docItem->file_url);
                $docItem->delete();
            }
        }

        $data->deleted_by = $user->id;
        $data->save();

        $data->delete();

        return [
            'is_success' => 1,
            'icon' => 'success',
            'message' => getCurrentTranslation()['data_deleted'] ?? 'data_deleted'
        ];
    }

    public function saveData(Request $request, $id = null)
    {
        $invoiceId = $id; // or however you determine update
        
        $messages = getCurrentTranslation();

        $logoMimes = 'heic,jpg,jpeg,png';
        $documentMimes = 'heic,jpg,jpeg,png,pdf,doc,docx';
        $maxImageSize = 3072; // in KB

        $validator = Validator::make($request->all(), [
            'invoice_date' => 'nullable|date',
            'payment_invoice_id' => 'nullable|string',
            'ticket_id' => 'nullable|integer',
            'client_name' => 'nullable|string|max:255',
            'client_phone' => 'nullable|string|max:50',
            'client_email' => 'nullable|email|max:255',
            'introduction_source_id' => 'nullable|integer',
            'customer_country_id' => 'nullable|string|max:10',
            'issued_supplier_ids' => 'nullable|array',
            'issued_by_id' => 'nullable|integer',

            'documents' => 'nullable|array',
            'documents.*.file' => 'nullable|mimes:' . $documentMimes . '|max:' . $maxImageSize,

            'trip_type' => 'nullable|in:One Way,Round Trip,Multi City',
            'departure_date_time' => 'nullable|date',
            'return_date_time' => 'nullable|date|after_or_equal:departure_date_time',
            'departure' => 'nullable|string',
            'destination' => 'nullable|string',
            'flight_route' => 'nullable|string',
            'seat_confirmation' => 'nullable|in:Window,Aisle,Not Chosen',
            'mobility_assistance' => 'nullable|in:Wheelchair,Baby Bassinet Seat,Meet & Assist,Not Chosen',
            'airline_id' => 'nullable|integer',
            'transit_visa_application' => 'nullable|in:Need To Do,Done,No Need',
            'halal_meal_request' => 'nullable|in:Need To Do,Done,No Need',
            'transit_hotel' => 'nullable|in:Need To Do,Done,No Need',
            'note' => 'nullable',

            'transfer_to_id' => 'nullable|integer',
            'payment_method_id' => 'nullable|integer',
            'issued_card_type_id' => 'nullable|integer',
            'card_owner_id' => 'nullable|integer',
            'card_digit' => 'nullable|integer',

            'total_purchase_price' => 'nullable|numeric|min:0',
            'total_selling_price' => 'nullable|numeric|min:0',

            'paymentData' => 'nullable|array',
            'paymentData.*.paid_amount' => 'nullable|numeric|min:0',
            'paymentData.*.date' => 'nullable|date',

            'payment_status' => 'nullable|in:Unpaid,Paid,Partial,Unknown',
            'next_payment_deadline' => 'nullable|date',

            'cancellation_fee' => 'nullable|numeric|min:0',
            'service_fee' => 'nullable|numeric|min:0',
            'refund_payment_status' => 'nullable|in:0,Unpaid,Paid',

        ], [
            // Generic
            'required'   => $messages['required_message'] ?? 'This field is required.',
            'unique'     => $messages['unique_message'] ?? 'This value has already been taken.',
            'exists'     => $messages['exists_message'] ?? 'The selected value is invalid.',
            'in'         => $messages['in_message'] ?? 'The selected value is invalid.',
            'confirmed'  => $messages['confirmed_message'] ?? 'The confirmation does not match.',
            'date'       => $messages['date_message'] ?? 'Please enter a valid date.',
            'date_format'=> $messages['date_format_message'] ?? 'The format must be HH:MM.',

            // Min / Max with placeholders
            'string.min'  => ($messages['min_string_message']  ?? 'This field allowed minimum character length is: ') . ':min',
            'string.max'  => ($messages['max_string_message']  ?? 'This field allowed maximum character length is: ') . ':max',
            'numeric.min' => ($messages['min_numeric_message'] ?? 'This value must be at least: ') . ':min',
            'numeric.max' => ($messages['max_numeric_message'] ?? 'This value may not be greater than: ') . ':max',
            'integer.min' => ($messages['min_integer_message'] ?? 'This value must be at least: ') . ':min',

            // File messages
            'documents.*.file.max'   => ($messages['max_file_size_message'] ?? 'The maximum allowed file size for this field is: ') . ($maxImageSize / 1024) . ' MB',
            'documents.*.file.mimes' => ($messages['mimes_message'] ?? 'The file must be of type:') . ' ' . $documentMimes,

            // Payment Data
            'paymentData.*.paid_amount.min' => ($messages['min_string_message'] ?? 'This field allowed maximum character length is: ') . ':min',
            'passenger_info.*.date.date' => ($messages['date_message'] ?? 'Please enter a valid date.'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'is_success' => 0,
                'icon' => 'error',
                'errors' => $validator->errors()
            ]);
        }

        //dd($request->all());

        $authUser = Auth::user();
        $paymentData = null;
        if (isset($id)) {
            $query = Payment::with('ticket', 'paymentDocuments', 'introductionSource', 'country', 'issuedBy', 'airline', 'transferTo', 'paymentMethod', 'issuedCardType', 'cardOwner')->where('id', $id);
            // if(Auth::user()->user_type == 'user'){
            //     $query->where('user_id', $authUser->business_id);
            // }
            $paymentData = $query->first();
            if(empty($paymentData)){
                return [
                    'is_success' => 0,
                    'icon' => 'error',
                    'message' => getCurrentTranslation()['data_not_found'] ?? 'data_not_found'
                ];
            }
        }

        if ($request->total_purchase_price > $request->total_selling_price) {
            return response()->json([
                'is_success' => 0,
                'icon' => 'error',
                'errors' => [
                    'total_selling_price' => [
                        getCurrentTranslation()['selling_price_must_be_greater_than_or_equal_of_purchase_price']
                            ?? 'selling_price_must_be_greater_than_or_equal_of_purchase_price',
                    ],
                ],
                //'message' => getCurrentTranslation()['selling_price_must_be_greater_than_or_equal_of_purchase_price'] ?? 'selling_price_must_be_greater_than_or_equal_of_purchase_price',
            ]);
        }


        if (empty($paymentData)) {
            $paymentData = new Payment();
            $paymentData->created_by = $authUser->id;
        } else {
            $paymentData->updated_by = $authUser->id;
        }

        //dd($paymentData);

        DB::beginTransaction();
        try {
            $paymentData->invoice_date = $request->invoice_date ?? date('Y-m-d H:i:s');
            $paymentData->payment_invoice_id = $request->payment_invoice_id ?? null;
            $paymentData->ticket_id = $request->ticket_id ?? null;
            $paymentData->client_name = $request->client_name ?? null;
            $paymentData->client_phone = $request->client_phone ?? null;
            $paymentData->client_email = $request->client_email ?? null;
            $paymentData->introduction_source_id = $request->introduction_source_id ?? null;
            $paymentData->customer_country_id = $request->customer_country_id ?? null;
            $paymentData->issued_supplier_ids = $request->issued_supplier_ids ?? null;
            $paymentData->issued_by_id = $request->issued_by_id ?? null;
            $paymentData->trip_type = $request->trip_type ?? null;
            $paymentData->departure_date_time = $request->departure_date_time ?? null;
            $paymentData->return_date_time = $request->return_date_time ?? null;
            $paymentData->departure = $request->departure ?? null;
            $paymentData->destination = $request->destination ?? null;
            $paymentData->flight_route = $request->flight_route ?? null;
            $paymentData->seat_confirmation = $request->seat_confirmation ?? null;
            $paymentData->mobility_assistance = $request->mobility_assistance ?? null;
            $paymentData->airline_id = $request->airline_id ?? null;
            $paymentData->transit_visa_application = $request->transit_visa_application ?? null;
            $paymentData->halal_meal_request = $request->halal_meal_request ?? null;
            $paymentData->transit_hotel = $request->transit_hotel ?? null;
            $paymentData->note = $request->note ?? null;
            $paymentData->transfer_to_id = $request->transfer_to_id ?? null;
            $paymentData->payment_method_id = $request->payment_method_id ?? null;
            $paymentData->issued_card_type_id = $request->issued_card_type_id ?? null;
            $paymentData->card_owner_id = $request->card_owner_id ?? null;
            $paymentData->card_digit = $request->card_digit ?? null;
            $paymentData->total_purchase_price = $request->total_purchase_price ?? null;
            $paymentData->total_selling_price = $request->total_selling_price ?? null;
            $paymentData->paymentData = removeEmptyArrays($request->paymentData ?? []);
            $paymentData->payment_status = $request->payment_status ?? null;
            $paymentData->next_payment_deadline = $request->next_payment_deadline ?? null;

            $refundStatus = 0;
            if((isset($request->cancellation_fee) && $request->cancellation_fee) > 0 || (isset($request->service_fee) && $request->service_fee)){
                $refundStatus = 1;
            }
            $paymentData->is_refund = $refundStatus;
            $paymentData->cancellation_fee = $request->cancellation_fee ?? 0;
            $paymentData->service_fee = $request->service_fee ?? 0;
            $refundStatus = $request->refund_payment_status != 0 ? $request->refund_payment_status : null;
            $paymentData->refund_payment_status = $refundStatus;
            $paymentData->refund_note = $request->refund_note ?? null;
            
            $paymentData->save();  
            

            //dd($request->documents);

            $documents = removeEmptyArrays($request->documents ?? []);
            $currentFileIds = [];

            if (is_array($documents) && count($documents)) {
                foreach ($documents as $docItem) {

                    $docFile = new PaymentDocument();

                    if (isset($docItem['id']) && $docItem['id'] != null) {
                        $docFile = PaymentDocument::where('payment_id', $paymentData->id)
                                                ->where('id', $docItem['id'])
                                                ->first();
                        if (!$docFile) {
                            $docFile = new PaymentDocument();
                        }
                    }

                    // Only upload if a new file exists
                    if (!empty($docItem['file'])) {
                        $uploadedFile = $docItem['file'];

                        // Determine file type
                        $extension = strtolower($uploadedFile->getClientOriginalExtension());
                        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'heic'];

                        if (in_array($extension, $imageExtensions)) {
                            // Use handleImageUpload for images
                            $uploadedFilePath = handleImageUpload(
                                $uploadedFile,
                                1920, // optional max width, adjust as needed
                                1080, // optional max height, adjust as needed
                                'payment-documents',
                                null,
                                $docFile->file_url // old file
                            );
                        } else {
                            // Use uploadFile for other file types
                            $uploadedFilePath = uploadFile(
                                $uploadedFile,
                                null,
                                'payment-documents',
                                $docFile->file_url // old file
                            );
                        }

                        if ($uploadedFilePath) {
                            $docFile->file_url = $uploadedFilePath;
                        }
                    }

                    // Always assign payment_id and save
                    $docFile->payment_id = $paymentData->id;
                    $docFile->save();

                    // Always keep track of this document ID
                    $currentFileIds[] = $docFile->id;
                }
            }

            // Delete old files not in current upload
            PaymentDocument::where('payment_id', $paymentData->id)
                ->whereNotIn('id', $currentFileIds)
                ->each(function($oldDoc){
                    deleteUploadedFile($oldDoc->file_url);
                    $oldDoc->delete();
                });

            //DB::commit();
            return [
                'is_success' => 1,
                'icon' => 'success',
                'message' => getCurrentTranslation()['data_saved'] ?? 'data_saved'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('User store error', ['error' => $e->getMessage()]);

            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['data_saving_error'] ?? 'data_saving_error'
            ];
        }
    }


    public function toDoList(){
        if (!hasPermission('toDoList')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        return view('common.payment-record.toDoList', get_defined_vars());
    }
    
    public function toDoDatatable()
    {
        $user = Auth::user();

        $query = Payment::with('ticket', 'paymentDocuments', 'introductionSource', 'country', 'issuedBy', 'airline', 'transferTo', 'paymentMethod', 'issuedCardType', 'cardOwner')->latest();

        $getCurrentTranslation = getCurrentTranslation();

        return DataTables::of($query)
            ->filter(function ($query) {
                if (!empty(request('search')['value'])) {
                    $search = request('search')['value'];

                    $query->where(function ($q) use ($search) {
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

                if (!empty(request()->introduction_source_id) && request()->introduction_source_id != 0) {
                    $query->where('introduction_source_id', request()->introduction_source_id);
                }

                if (!empty(request()->customer_country_id) && request()->customer_country_id != 0) {
                    $query->where('customer_country_id', request()->customer_country_id);
                }

                if (!empty(request()->issued_supplier_ids) && request()->issued_supplier_ids != 0) {
                    $ids = (array) request()->issued_supplier_ids;

                    $query->where(function ($q) use ($ids) {
                        foreach ($ids as $id) {
                            $q->orWhereJsonContains('issued_supplier_ids', $id);
                        }
                    });
                }

                if (!empty(request()->issued_by_id) && request()->issued_by_id != 0) {
                    $query->where('issued_by_id', request()->issued_by_id);
                }

                if (!empty(request()->trip_type) && request()->trip_type != 0) {
                    $query->where('trip_type', request()->trip_type);
                }

                if (!empty(request()->departure) && request()->departure != 0) {
                    $query->where('departure', request()->departure);
                }

                if (!empty(request()->destination) && request()->destination != 0) {
                    $query->where('destination', request()->destination);
                }

                if (!empty(request()->airline_id) && request()->airline_id != 0) {
                    $query->where('airline_id', request()->airline_id);
                }

                if (!empty(request()->flight_date_range) && request()->flight_date_range != 0) {
                    $flightDateRange = request()->flight_date_range;
                    // Split the string into start and end dates
                    [$start, $end] = explode('-', $flightDateRange);

                    // Convert to Carbon instances (optional but safer)
                    $startDate = \Carbon\Carbon::createFromFormat('Y/m/d', trim($start))->startOfDay();
                    $endDate = \Carbon\Carbon::createFromFormat('Y/m/d', trim($end))->endOfDay();

                    $query->where(function ($q) use ($startDate, $endDate) {
                        $q->whereBetween('departure_date_time', [$startDate, $endDate])
                            ->orWhereBetween('return_date_time', [$startDate, $endDate]);
                    });
                }

                if (!empty(request()->invoice_date_range) && request()->invoice_date_range != 0) {
                    $invoiceDateRange = request()->invoice_date_range;
                    // Split the string into start and end dates
                    [$start, $end] = explode('-', $invoiceDateRange);

                    // Convert to Carbon instances (optional but safer)
                    $startDate = \Carbon\Carbon::createFromFormat('Y/m/d', trim($start))->startOfDay();
                    $endDate = \Carbon\Carbon::createFromFormat('Y/m/d', trim($end))->endOfDay();

                    $query->where(function ($q) use ($startDate, $endDate) {
                        $q->whereBetween('invoice_date', [$startDate, $endDate]);
                    });
                }

                if (!empty(request()->transfer_to) && request()->transfer_to != 0) {
                    $query->where('transfer_to_id', request()->transfer_to);
                }

                if (!empty(request()->payment_method) && request()->payment_method != 0) {
                    $query->where('payment_method_id', request()->payment_method);
                }

                if (!empty(request()->issued_card_type) && request()->issued_card_type != 0) {
                    $query->where('issued_card_type_id', request()->issued_card_type);
                }

                if (!empty(request()->card_owner) && request()->card_owner != 0) {
                    $query->where('card_owner_id', request()->card_owner);
                }

                if (!empty(request()->payment_status) && request()->payment_status != 0) {
                    $query->where('payment_status', request()->payment_status);
                }

                if (!empty(request()->payment_date_range) && request()->payment_date_range != 0) {
                    $paymentDateRange = request()->payment_date_range;

                    // Split the string into start and end dates
                    [$start, $end] = explode('-', $paymentDateRange);

                    // Convert to Carbon instances
                    $startDate = \Carbon\Carbon::createFromFormat('Y/m/d', trim($start))->startOfDay();
                    $endDate = \Carbon\Carbon::createFromFormat('Y/m/d', trim($end))->endOfDay();

                    $query->whereNotNull('paymentData')
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

                    $query->where(function ($q) use ($startDate, $endDate) {
                        $q->whereBetween('next_payment_deadline', [$startDate, $endDate]);
                    });
                }
            })
            ->addIndexColumn()

            ->addColumn('trip_info', function ($row) use ($getCurrentTranslation) {
                $paymentInvoiceLabel = isset($getCurrentTranslation['payment_invoice_id_label']) ? $getCurrentTranslation['payment_invoice_id_label'] : 'payment_invoice_id_label';
                $ticketInvoiceLabel  = isset($getCurrentTranslation['ticket_invoice_id_label']) ? $getCurrentTranslation['ticket_invoice_id_label'] : 'ticket_invoice_id_label';
                $tripTypeLabel       = isset($getCurrentTranslation['trip_type_label']) ? $getCurrentTranslation['trip_type_label'] : 'trip_type_label';
                $flightRouteLabel    = isset($getCurrentTranslation['flight_route_label']) ? $getCurrentTranslation['flight_route_label'] : 'flight_route_label';
                $departureLabel      = isset($getCurrentTranslation['departure_label']) ? $getCurrentTranslation['departure_label'] : 'departure_label';
                $returnLabel         = isset($getCurrentTranslation['return_label']) ? $getCurrentTranslation['return_label'] : 'return_label';
                $airlineLabel        = isset($getCurrentTranslation['airline_label']) ? $getCurrentTranslation['airline_label'] : 'airline_label';

                $departure = $row->departure_date_time ? date('Y-m-d, H:i', strtotime($row->departure_date_time)) : 'N/A';
                $return    = $row->return_date_time ? date('Y-m-d, H:i', strtotime($row->return_date_time)) : 'N/A';
                $airline   = $row->airline->name ?? 'N/A';
                $ticketInvoice = $row->ticket->invoice_id ?? 'N/A';

                return '<div style="max-width: 280px; line-height: 1.6; text-align: left;">
                    <strong>' . $paymentInvoiceLabel . ':</strong> ' . $row->payment_invoice_id . '<br>
                    <strong>' . $ticketInvoiceLabel . ':</strong> ' . $ticketInvoice . '<br>
                    <strong>' . $tripTypeLabel . ':</strong> ' . $row->trip_type . '<br>
                    <strong>' . $flightRouteLabel . ':</strong> ' . $row->flight_route . '<br>
                    <strong>' . $departureLabel . ':</strong> ' . $departure . '<br>
                    <strong>' . $returnLabel . ':</strong> ' . $return . '<br>
                    <strong>' . $airlineLabel . ':</strong> ' . $airline . '
                </div>';
            })

            // ✅ Seat Confirmation
            ->addColumn('seat_confirmation', function ($row) {
                return match($row->seat_confirmation) {
                    'Window' => '<span class="badge bg-primary">Window</span>',
                    'Aisle' => '<span class="badge bg-success">Aisle</span>',
                    'Not Chosen' => '<span class="badge bg-warning text-dark">Not Chosen</span>',
                    default => '<span class="badge bg-light text-dark">—</span>',
                };
            })

            // ✅ Mobility Assistance
            ->addColumn('mobility_assistance', function ($row) {
                return match($row->mobility_assistance) {
                    'Wheelchair' => '<span class="badge bg-primary">Wheelchair</span>',
                    'Baby Bassinet Seat' => '<span class="badge bg-info">Baby Bassinet Seat</span>',
                    'Meet & Assist' => '<span class="badge bg-success">Meet & Assist</span>',
                    'Not Chosen' => '<span class="badge bg-warning text-dark">Not Chosen</span>',
                    default => '<span class="badge bg-light text-dark">—</span>',
                };
            })

            // ✅ Transit Visa Application
            ->addColumn('transit_visa_application', function ($row) {
                return match($row->transit_visa_application) {
                    'Need To Do' => '<span class="badge bg-danger text-white">Need To Do</span>',
                    'Done' => '<span class="badge bg-success">Done</span>',
                    'No Need' => '<span class="badge bg-secondary text-dark">No Need</span>',
                    default => '<span class="badge bg-light text-dark">—</span>',
                };
            })

            // ✅ Halal Meal Request
            ->addColumn('halal_meal_request', function ($row) {
                return match($row->halal_meal_request) {
                    'Need To Do' => '<span class="badge bg-danger text-white">Need To Do</span>',
                    'Done' => '<span class="badge bg-success">Done</span>',
                    'No Need' => '<span class="badge bg-secondary text-dark">No Need</span>',
                    default => '<span class="badge bg-light text-dark">—</span>',
                };
            })

            // ✅ Transit Hotel
            ->addColumn('transit_hotel', function ($row) {
                return match($row->transit_hotel) {
                    'Need To Do' => '<span class="badge bg-danger text-white">Need To Do</span>',
                    'Done' => '<span class="badge bg-success">Done</span>',
                    'No Need' => '<span class="badge bg-secondary text-dark">No Need</span>',
                    default => '<span class="badge bg-light text-dark">—</span>',
                };
            })

            // ✅ Actions
            ->addColumn('action', function ($row) {
                $detailsUrl   = route('payment.show', $row->id);
                $editUrl      = route('payment.edit', $row->id);
                $deleteUrl    = route('payment.destroy', $row->id);

                $buttons = '';

                // 👁️ Details
                if (hasPermission('payment.show')) {
                    $buttons .= '
                        <a href="' . $detailsUrl . '" class="btn btn-sm btn-info my-1" title="Details">
                            <i class="fa-solid fa-pager"></i>
                        </a>
                    ';
                }

                // ✏️ Edit
                if (hasPermission('payment.edit')) {
                    $buttons .= '
                        <a href="' . $editUrl . '" class="btn btn-sm btn-primary my-1" title="Edit">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </a>
                    ';
                }

                // 🗑️ Delete
                if (hasPermission('payment.delete')) {
                    $buttons .= '
                        <button class="btn btn-sm btn-danger my-1 delete-table-data-btn"
                            data-id="' . $row->id . '"
                            data-url="' . $deleteUrl . '"
                            title="Delete">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    ';
                }

                return !empty($buttons) ? $buttons : 'N/A';
            })


            ->rawColumns([
                'trip_info',
                'seat_confirmation',
                'mobility_assistance',
                'transit_visa_application',
                'halal_meal_request',
                'transit_hotel',
                'action',
            ])
            ->make(true);

    }

    
}