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

use App\Models\Notification;
use App\Models\Language;
use App\Models\Currency;
use App\Models\User;
use App\Models\UserCompany;
use App\Models\Ticket;
use App\Models\Payment;
use App\Models\PaymentDocument;
use App\Models\TicketFlight;
use App\Models\TicketPassenger;
use App\Models\Airline;

use PDF;
use App\Services\PdfService;
use App\Services\FlightApiService;
use App\Services\FlightApiCreditUsageService;
use App\Mail\FlightChangeMail;
use App\Mail\FlightStatusMail;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;

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

        $query = Payment::with('ticket', 'ticket.passengers', 'paymentDocuments', 'introductionSource', 'country', 'issuedBy', 'airline', 'transferTo', 'paymentMethod', 'issuedCardType', 'cardOwner', 'creator', 'updater', 'deleter');

        // ✅ Detect if any filter applied
        $hasFilter =
            (request()->filled('trip_type') && request()->trip_type != 0) ||
            (request()->filled('airline_id') && request()->airline_id != 0) ||
            (request()->filled('flight_date_range') && request()->flight_date_range != 0) ||
            (request()->filled('invoice_date_range') && request()->invoice_date_range != 0) ||
            (request()->filled('introduction_source_id') && request()->introduction_source_id != 0) ||
            (request()->filled('customer_country_id') && request()->customer_country_id != 0) ||
            (request()->filled('issued_supplier_ids') && request()->issued_supplier_ids != 0) ||
            (request()->filled('issued_by_id') && request()->issued_by_id != 0) ||
            (request()->filled('departure') && request()->departure != 0) ||
            (request()->filled('destination') && request()->destination != 0) ||
            (request()->filled('transfer_to') && request()->transfer_to != 0) ||
            (request()->filled('payment_method') && request()->payment_method != 0) ||
            (request()->filled('issued_card_type') && request()->issued_card_type != 0) ||
            (request()->filled('card_owner') && request()->card_owner != 0) ||
            (request()->filled('payment_status') && request()->payment_status != 0) ||
            (request()->filled('under_loss') && request()->under_loss != 0) ||
            (request()->filled('under_due') && request()->under_due != 0) ||
            (request()->filled('gender') && request()->gender != 0) ||
            request()->filled('payment_date_range') ||
            request()->filled('next_payment_date_range') ||
            (request()->filled('refund_type') && request()->refund_type != 0) ||
            (request()->filled('refund_payment_status') && request()->refund_payment_status != 0) ||
            (request()->has('search') && !empty(request('search')['value']));

        // ✅ Conditional order (from payments table itself)
        if ($hasFilter) {
            // Conditional order logic
            $invoiceRange = request()->invoice_date_range;
            $flightRange  = request()->flight_date_range;

            if (!empty($invoiceRange) && $invoiceRange != 0 && !empty($flightRange) && $flightRange != 0) {
                // Both invoice and flight filters exist → order by invoice_date
                $query->orderBy('invoice_date', 'ASC');

            } elseif (!empty($flightRange) && $flightRange != 0) {
                // Only flight filter exists → order by flight date case
                $query->orderByRaw("
                    CASE
                        WHEN departure_date_time IS NOT NULL THEN departure_date_time
                        ELSE return_date_time
                    END ASC
                ");

            } else {
                // Only invoice filter exists → order by invoice_date
                $query->orderBy('invoice_date', 'ASC');

            }
        } else {
            $query->latest();
        }

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

                        // Search in related ticket invoice_id + client contact
                        $q->orWhereHas('ticket', function ($q2) use ($search) {
                            $q2->where('invoice_id', 'like', "%{$search}%")
                                ->orWhere('contacted_with_client', 'like', "%{$search}%")
                                ->orWhere('client_contact_note', 'like', "%{$search}%");
                        });

                        // Search in related ticket reservation_number
                        $q->orWhereHas('ticket', function ($q2) use ($search) {
                            $q2->where('reservation_number', 'like', "%{$search}%");
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
                    $parts = explode('-', $flightDateRange, 2);
                    $start = trim($parts[0] ?? '');
                    $end = trim($parts[1] ?? '');
                    $startDate = \Carbon\Carbon::createFromFormat('Y/m/d', $start)->startOfDay();
                    $endDate = \Carbon\Carbon::createFromFormat('Y/m/d', $end)->endOfDay();
                    $todayStart = \Carbon\Carbon::today()->startOfDay();

                    // Only show payments whose ticket has an upcoming segment (next main flight) in this range
                    $query->whereHas('ticket', function ($q) use ($startDate, $endDate, $todayStart) {
                        $q->whereHas('allFlights', function ($f) use ($startDate, $endDate, $todayStart) {
                            $f->whereNull('parent_id')
                                ->where('departure_date_time', '>=', $todayStart)
                                ->whereBetween('departure_date_time', [$startDate, $endDate]);
                        }, '>=', 1);
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

                if (!empty(request()->under_loss) && request()->under_loss != 0) {
                    $query->whereColumn('total_purchase_price', '>', 'total_selling_price');
                }

                if (!empty(request()->under_due) && request()->under_due != 0) {
                    $query->whereRaw("
                        (SELECT COALESCE(SUM(JSON_EXTRACT(p, '$.paid_amount')), 0)
                         FROM JSON_TABLE(paymentData, '$[*]' COLUMNS (p JSON PATH '$')) AS t)
                         < total_selling_price
                    ");
                }

                if (!empty(request()->gender) && request()->gender != 0) {
                    $query->whereHas('ticket.passengers', function ($q) {
                        $q->where('gender', request()->gender);
                    });
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

                if (!empty(request()->refund_type) && request()->refund_type != 0) {
                    $refundType = 0;
                    if(request()->refund_type == 'Refund Only'){
                        $refundType = 1;
                    }
                    $query->where('is_refund', $refundType);
                }

                if (!empty(request()->refund_payment_status) && request()->refund_payment_status != 0) {
                    $query->where('refund_payment_status', request()->refund_payment_status);
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
                $countsLine = passengerCountsLineHtml($row->ticket?->passengers ?? []);
                $countsBlock = $countsLine !== '' ? "<div class=\"mb-1\">{$countsLine}</div>" : '';

                return "
                    <div>
                        <strong>{$nameLabel}:</strong> {$name}<br>
                        {$countsBlock}
                        <strong>{$phoneLabel}:</strong> {$phone}<br>
                        <strong>{$emailLabel}:</strong> {$email}
                    </div>
                ";
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
                $statusLabel = $getCurrentTranslation['payment_status'] ?? 'payment_status';
                $refundLabel = $getCurrentTranslation['refund'] ?? 'refund';
                $refundStatusLabel = $getCurrentTranslation['refund_status_label'] ?? 'refund_status_label';
                $refundNoteLabel = $getCurrentTranslation['refund_note_label'] ?? 'refund_note_label';
                $serviceFeeLabel = $getCurrentTranslation['service_fee_label'] ?? 'service_fee_label';
                $cancellationFeeLabel = $getCurrentTranslation['cancellation_fee_label'] ?? 'cancellation_fee_label';

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

                // ✅ Refund section (only when is_refund == 1)
                $refundHtml = '';
                if (!empty($row->is_refund) && $row->is_refund == 1) {
                    $refundHtml = '
                        <div><strong>' . e($cancellationFeeLabel) . ':</strong> <span class="text-danger">' . $currency . number_format($row->cancellation_fee ?? 0, 2) . '</span></div>
                        <div><strong>' . e($serviceFeeLabel) . ':</strong> <span class="text-info">' . $currency . number_format($row->service_fee ?? 0, 2) . '</span></div>
                        <div><strong>' . e($refundStatusLabel) . ':</strong> <span class="badge ' . (($row->refund_payment_status == 'Paid') ? 'badge-success' : 'badge-danger') . '">' . e($row->refund_payment_status ?? 'Unpaid') . '</span></div>
                    ';
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
                        ' . $refundHtml . '
                    </div>';
            })
            
            // ->addColumn('issued_by_id', function ($row) {
            //     return $row->issuedBy->name ?? 'N/A';
            // })

            // ✅ All Activity
            ->addColumn('created_at', function ($row) {
                $activity = '<div class="activity-info">';
                
                // Issued By
                $activity .= '<div class="activity-item">';
                $activity .= '<b>' . (getCurrentTranslation()['issued_by'] ?? 'issued_by') . ':</b> ';
                $activity .= ($row->issuedBy->name ?? 'N/A');
                $activity .= '</div>';

                // Created information (always shown)
                $activity .= '<div class="activity-item">';
                $activity .= '<b>' . (getCurrentTranslation()['created_at'] ?? 'created_at') . ':</b> ';
                $activity .= \Carbon\Carbon::parse($row->created_at)->format('Y-m-d, H:i');
                $activity .= '</div>';
                
                $activity .= '<div class="activity-item">';
                $activity .= '<b>' . (getCurrentTranslation()['created_by'] ?? 'created_by') . ':</b> ';
                $activity .= ($row->creator ? $row->creator->name : 'N/A');
                $activity .= '</div>';
                
                // Updated information (conditional)
                if (!is_null($row->updated_by)) {
                    $activity .= '<div class="activity-item">';
                    $activity .= '<b>' . (getCurrentTranslation()['updated_at'] ?? 'updated_at') . ':</b> ';
                    $activity .= \Carbon\Carbon::parse($row->updated_at)->format('Y-m-d, H:i');
                    $activity .= '</div>';
                    
                    $activity .= '<div class="activity-item">';
                    $activity .= '<b>' . (getCurrentTranslation()['updated_by'] ?? 'updated_by') . ':</b> ';
                    $activity .= ($row->updater ? $row->updater->name : 'N/A');
                    $activity .= '</div>';
                }
                
                // Deleted information (conditional)
                if (!is_null($row->deleted_by)) {
                    $activity .= '<div class="activity-item">';
                    $activity .= '<b>' . (getCurrentTranslation()['deleted_at'] ?? 'deleted_at') . ':</b> ';
                    $activity .= \Carbon\Carbon::parse($row->deleted_at)->format('Y-m-d, H:i');
                    $activity .= '</div>';
                    
                    $activity .= '<div class="activity-item">';
                    $activity .= '<b>' . (getCurrentTranslation()['deleted_by'] ?? 'deleted_by') . ':</b> ';
                    $activity .= ($row->deleter ? $row->deleter->name : 'N/A');
                    $activity .= '</div>';
                }
                
                // Mail sent count (conditional)
                if (!is_null($row->mail_sent_count) && $row->mail_sent_count != 0) {
                    $activity .= '<div class="activity-item">';
                    $activity .= '<b>' . (getCurrentTranslation()['total_mail_sent'] ?? 'total_mail_sent') . ':</b> ';
                    $activity .= '<span class="badge badge-info">' . $row->mail_sent_count . '</span>';
                    $activity .= '</div>';
                }
                
                $activity .= '</div>';
                
                return $activity;
            })

            // ✅ Created By (relationship with users table)
            // ->addColumn('created_by', function ($row) {
            //     return $row->creator ? $row->creator->name : 'N/A';
            // })

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


            ->rawColumns(['payment_invoice_id', 'client_name', 'trip_type', 'total_selling_price', 'created_at', 'action'])
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

        // $ticketData = Ticket::with('flights', 'flights.transits', 'passengers', 'passengers.flights', 'fareSummary', 'user', 'user.company', 'creator')
        //     ->where('booking_status', 'Confirmed')
        //     ->where(function($query) use ($request) {
        //         $query->where('invoice_id', 'like', '%' . $request->search . '%')
        //             ->orWhere('reservation_number', 'like', '%' . $request->search . '%');
        //     })
        //     ->get();

        $ticketData = Ticket::with('flights', 'flights.transits', 'passengers', 'passengers.flights', 'fareSummary', 'user', 'user.company', 'creator')
            ->where('booking_status', 'Confirmed')
            ->where(function($query) use ($request) {
                $query->where('invoice_id', $request->search)
                    ->orWhere('reservation_number', $request->search);
            })
            ->first();

        if($ticketData){
            $paymentData = Payment::where('ticket_id', $ticketData->id)->first();

            if($paymentData){
                return response()->json([
                    'is_success' => 0,
                    'is_alert' => 1,
                    'icon' => 'error',
                    'message' => (getCurrentTranslation()['ticket_already_added_to_payment'] ?? 'ticket_already_added_to_payment') . "\n" . 
                                 (getCurrentTranslation()['ticket_invoice_id'] ?? 'ticket_invoice_id') . ': ' . $ticketData->invoice_id . "\n" . 
                                 (getCurrentTranslation()['payment_invoice_id'] ?? 'payment_invoice_id') . ': ' . $paymentData->payment_invoice_id,
                ]);
            }
        }

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
        $paymentDataDocumentMimes = 'jpg,jpeg,png,pdf';
        $maxImageSize = 3072; // in KB

        $validator = Validator::make($request->all(), [
            'invoice_date' => 'nullable|date',
            'payment_invoice_id' => 'nullable|string',
            'ticket_id' => 'required|integer|exists:tickets,id',
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
            'paymentData.*.document' => 'nullable|file|mimes:' . $paymentDataDocumentMimes . '|max:' . $maxImageSize,
            'paymentData.*.note' => 'nullable|string|max:2000',
            'paymentData.*.document_old' => 'nullable|string',

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
            'paymentData.*.document.max'   => ($messages['max_file_size_message'] ?? 'The maximum allowed file size for this field is: ') . ($maxImageSize / 1024) . ' MB',
            'paymentData.*.document.mimes' => ($messages['mimes_message'] ?? 'The file must be of type:') . ' ' . $paymentDataDocumentMimes,

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

        // if ($request->total_purchase_price > $request->total_selling_price) {
        //     return response()->json([
        //         'is_success' => 0,
        //         'icon' => 'error',
        //         'errors' => [
        //             'total_selling_price' => [
        //                 getCurrentTranslation()['selling_price_must_be_greater_than_or_equal_of_purchase_price']
        //                     ?? 'selling_price_must_be_greater_than_or_equal_of_purchase_price',
        //             ],
        //         ],
        //         //'message' => getCurrentTranslation()['selling_price_must_be_greater_than_or_equal_of_purchase_price'] ?? 'selling_price_must_be_greater_than_or_equal_of_purchase_price',
        //     ]);
        // }


        if (empty($paymentData)) {
            $paymentData = new Payment();
            $paymentData->created_by = $authUser->id;
        } else {
            $paymentData->updated_by = $authUser->id;
        }

        //dd($paymentData);

        // DB::beginTransaction();
        // try {
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

            // Normalize payment collection rows (store file paths in JSON, never UploadedFile objects)
            $rawPaymentRows = $request->paymentData ?? [];
            $existingPaymentRows = (is_array($paymentData->paymentData ?? null)) ? ($paymentData->paymentData ?? []) : [];
            $processedPaymentRows = [];

            if (is_array($rawPaymentRows) && count($rawPaymentRows)) {
                foreach ($rawPaymentRows as $rowIndex => $row) {
                    if (!is_array($row)) continue;

                    $paidAmount = $row['paid_amount'] ?? null;
                    $date = $row['date'] ?? null;
                    $note = array_key_exists('note', $row) ? trim((string) $row['note']) : null;

                    $oldDocPath = $row['document_old'] ?? ($existingPaymentRows[$rowIndex]['document'] ?? null);
                    $docPath = $oldDocPath;

                    if (isset($row['document']) && $row['document'] instanceof \Illuminate\Http\UploadedFile) {
                        $uploadedFile = $row['document'];
                        $extension = strtolower($uploadedFile->getClientOriginalExtension());
                        $imageExtensions = ['jpg', 'jpeg', 'png'];

                        if (in_array($extension, $imageExtensions)) {
                            $newPath = handleImageUpload(
                                $uploadedFile,
                                1920,
                                1080,
                                'payment-collection-documents',
                                null,
                                null
                            );
                            if (!empty($newPath)) {
                                if (!empty($oldDocPath)) {
                                    deleteUploadedFile($oldDocPath);
                                }
                                $docPath = $newPath;
                            }
                        } else {
                            $newPath = uploadFile(
                                $uploadedFile,
                                null,
                                'payment-collection-documents',
                                null
                            );
                            if (!empty($newPath)) {
                                if (!empty($oldDocPath)) {
                                    deleteUploadedFile($oldDocPath);
                                }
                                $docPath = $newPath;
                            }
                        }
                    } else {
                        // Row cleared: no new file — if row is empty, remove document from JSON and delete file from server
                        $paidEmpty = trim((string) $paidAmount) === '' || $paidAmount === 0 || $paidAmount === '0';
                        $rowIsEmpty = $paidEmpty && trim((string) $date) === '' && $note === '';
                        if ($rowIsEmpty && !empty($oldDocPath)) {
                            deleteUploadedFile($oldDocPath);
                            $docPath = null;
                        }
                    }

                    $processedPaymentRows[$rowIndex] = [
                        'paid_amount' => $paidAmount,
                        'date' => $date,
                        'document' => $docPath,
                        'note' => $note,
                    ];
                }
            }

            // Collect document paths we are keeping in the new payload (before removeEmptyArrays)
            $keptDocPaths = [];
            foreach ($processedPaymentRows as $row) {
                if (!empty($row['document'])) {
                    $keptDocPaths[] = $row['document'];
                }
            }
            // Delete payment-collection-documents that are no longer in the new rows (removed or cleared)
            foreach ($existingPaymentRows as $existingRow) {
                $existingDoc = $existingRow['document'] ?? null;
                if (!empty($existingDoc) && !in_array($existingDoc, $keptDocPaths)) {
                    deleteUploadedFile($existingDoc);
                }
            }

            $paymentData->paymentData = removeEmptyArrays($processedPaymentRows);
            $paymentData->payment_status = $request->payment_status ?? null;
            $paymentData->next_payment_deadline = $request->next_payment_deadline ?? null;

            $isStatus = 0;
            if((isset($request->cancellation_fee) && $request->cancellation_fee) > 0 || (isset($request->service_fee) && $request->service_fee)){
                $isStatus = 1;
            }
            $paymentData->is_refund = $isStatus;
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
                    $existingId = isset($docItem['id']) && $docItem['id'] !== '' && $docItem['id'] !== null ? $docItem['id'] : null;
                    $hasNewFile = !empty($docItem['file']);
                    // Skip items that have no existing id and no new file (e.g. cleared row) so we don't create empty records
                    if (!$existingId && !$hasNewFile) {
                        continue;
                    }

                    $docFile = new PaymentDocument();

                    if ($existingId) {
                        $docFile = PaymentDocument::where('payment_id', $paymentData->id)
                                                ->where('id', $existingId)
                                                ->first();
                        if (!$docFile) {
                            $docFile = new PaymentDocument();
                        }
                    }

                    // Only upload if a new file exists
                    if ($hasNewFile) {
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

            // Delete old files not in current upload (when currentFileIds is empty, remove all payment documents)
            $docsToDelete = PaymentDocument::where('payment_id', $paymentData->id);
            if (count($currentFileIds) > 0) {
                $docsToDelete = $docsToDelete->whereNotIn('id', $currentFileIds);
            }
            $docsToDelete->each(function ($oldDoc) {
                deleteUploadedFile($oldDoc->file_url);
                $oldDoc->delete();
            });

            if ($paymentData->payment_status == 'Paid') {
                Notification::where('url', route('payment.show', $paymentData->id, false))->delete();
            }


            DB::commit();
            return [
                'is_success' => 1,
                'icon' => 'success',
                'message' => getCurrentTranslation()['data_saved'] ?? 'data_saved'
            ];
        // } catch (\Exception $e) {
        //     DB::rollBack();
        //     \Log::error('User store error', ['error' => $e->getMessage()]);

        //     return [
        //         'is_success' => 0,
        //         'icon' => 'error',
        //         'message' => getCurrentTranslation()['data_saving_error'] ?? 'data_saving_error'
        //     ];
        // }
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

        $query = Payment::with([
            'ticket', 'paymentDocuments', 'introductionSource', 'country',
            'issuedBy', 'airline', 'transferTo', 'paymentMethod',
            'issuedCardType', 'cardOwner'
        ])
        ->where(function ($q) {
            $q->where('seat_confirmation', 'Not Chosen')
                ->orWhereNull('seat_confirmation')

                ->orWhere('mobility_assistance', 'Not Chosen')
                ->orWhereNull('mobility_assistance')

                ->orWhere('transit_visa_application', 'Need To Do')
                ->orWhereNull('transit_visa_application')

                ->orWhere('halal_meal_request', 'Need To Do')
                ->orWhereNull('halal_meal_request')

                ->orWhere('transit_hotel', 'Need To Do')
                ->orWhereNull('transit_hotel');
        });

        // ✅ Detect if any filter applied
        $hasFilter =
            (request()->filled('trip_type') && request()->trip_type != 0) ||
            (request()->filled('airline_id') && request()->airline_id != 0) ||
            (request()->filled('flight_date_range') && request()->flight_date_range != 0) ||
            (request()->filled('invoice_date_range') && request()->invoice_date_range != 0) ||
            (request()->filled('introduction_source_id') && request()->introduction_source_id != 0) ||
            (request()->filled('customer_country_id') && request()->customer_country_id != 0) ||
            (request()->filled('issued_supplier_ids') && request()->issued_supplier_ids != 0) ||
            (request()->filled('issued_by_id') && request()->issued_by_id != 0) ||
            (request()->filled('departure') && request()->departure != 0) ||
            (request()->filled('destination') && request()->destination != 0) ||
            (request()->filled('transfer_to') && request()->transfer_to != 0) ||
            (request()->filled('payment_method') && request()->payment_method != 0) ||
            (request()->filled('issued_card_type') && request()->issued_card_type != 0) ||
            (request()->filled('card_owner') && request()->card_owner != 0) ||
            (request()->filled('payment_status') && request()->payment_status != 0) ||
            request()->filled('payment_date_range') ||
            request()->filled('next_payment_date_range') ||
            (request()->filled('refund_type') && request()->refund_type != 0) ||
            (request()->filled('refund_payment_status') && request()->refund_payment_status != 0) ||
            (request()->filled('gender') && request()->gender != 0) ||
            (request()->has('search') && !empty(request('search')['value']));

        // ✅ Conditional order (from payments table itself)
        if ($hasFilter) {
            $query->orderByRaw("
                CASE
                    WHEN departure_date_time IS NOT NULL THEN departure_date_time
                    ELSE return_date_time
                END ASC
            ");
        } else {
            $query->latest();
        }

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

                        // Search in related ticket invoice_id + client contact
                        $q->orWhereHas('ticket', function ($q2) use ($search) {
                            $q2->where('invoice_id', 'like', "%{$search}%")
                                ->orWhere('contacted_with_client', 'like', "%{$search}%")
                                ->orWhere('client_contact_note', 'like', "%{$search}%");
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
                    $parts = explode('-', $flightDateRange, 2);
                    $start = trim($parts[0] ?? '');
                    $end = trim($parts[1] ?? '');
                    $startDate = \Carbon\Carbon::createFromFormat('Y/m/d', $start)->startOfDay();
                    $endDate = \Carbon\Carbon::createFromFormat('Y/m/d', $end)->endOfDay();
                    $todayStart = \Carbon\Carbon::today()->startOfDay();

                    // Only show payments whose ticket has an upcoming segment (next main flight) in this range
                    $query->whereHas('ticket', function ($q) use ($startDate, $endDate, $todayStart) {
                        $q->whereHas('allFlights', function ($f) use ($startDate, $endDate, $todayStart) {
                            $f->whereNull('parent_id')
                                ->where('departure_date_time', '>=', $todayStart)
                                ->whereBetween('departure_date_time', [$startDate, $endDate]);
                        }, '>=', 1);
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
                $passengerNameLabel   = $getCurrentTranslation['passenger_name_label'] ?? 'passenger_name_label';
                $passengerPhoneLabel  = $getCurrentTranslation['passenger_phone_label'] ?? 'passenger_phone_label';
                $passengerEmailLabel  = $getCurrentTranslation['passenger_email_label'] ?? 'passenger_email_label';
                $paymentInvoiceLabel  = $getCurrentTranslation['payment_invoice_id_label'] ?? 'payment_invoice_id_label';
                $ticketInvoiceLabel   = $getCurrentTranslation['ticket_invoice_id_label'] ?? 'ticket_invoice_id_label';
                $tripTypeLabel        = $getCurrentTranslation['trip_type_label'] ?? 'trip_type_label';
                $flightRouteLabel     = $getCurrentTranslation['flight_route_label'] ?? 'flight_route_label';
                $departureLabel       = $getCurrentTranslation['departure_label'] ?? 'departure_label';
                $returnLabel          = $getCurrentTranslation['return_label'] ?? 'return_label';
                $airlineLabel         = $getCurrentTranslation['airline_label'] ?? 'airline_label';

                $departure     = $row->departure_date_time ? date('Y-m-d, H:i', strtotime($row->departure_date_time)) : 'N/A';
                $return        = $row->return_date_time ? date('Y-m-d, H:i', strtotime($row->return_date_time)) : 'N/A';
                $airline       = $row->airline->name ?? 'N/A';
                $ticketInvoice = $row->ticket->invoice_id ?? 'N/A';

                $countsLine = passengerCountsLineHtml($row->ticket?->passengers ?? []);
                $countsBlock = $countsLine !== '' ? '<div class="mb-1">' . $countsLine . '</div>' : '';
                $clientContactBlock = $row->ticket ? ticketClientContactSummaryHtml($row->ticket, $getCurrentTranslation) : '';
                return '<div style="max-width: 280px; line-height: 1.6; text-align: left;">
                    <strong>' . $passengerNameLabel . ':</strong> ' . $row->client_name . '<br>
                    ' . $countsBlock . '
                    <strong>' . $passengerPhoneLabel . ':</strong> ' . ($row->client_phone ?? 'N/A') . '<br>
                    <strong>' . $passengerEmailLabel . ':</strong> ' . ($row->client_email ?? 'N/A') . '<br>
                    <strong>' . $paymentInvoiceLabel . ':</strong> ' . $row->payment_invoice_id . '<br>
                    <strong>' . $ticketInvoiceLabel . ':</strong> ' . $ticketInvoice . '<br>
                    <strong>' . $tripTypeLabel . ':</strong> ' . $row->trip_type . '<br>
                    <strong>' . $airlineLabel . ':</strong> ' . $airline . '<br>
                    <strong>' . $flightRouteLabel . ':</strong> ' . $row->flight_route . '<br>
                    <strong>' . $departureLabel . ':</strong> ' . $departure . '<br>
                    <strong>' . $returnLabel . ':</strong> ' . $return . '<br>
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


    public function flightList()
    {
        return view('common.payment-record.flightList', get_defined_vars());
    }

    public function flightListDatatable()
    {
        $user = Auth::user();

        $query = Payment::with([
            'ticket', 'ticket.passengers', 'ticket.allFlights', 'paymentDocuments', 'introductionSource', 'country',
            'issuedBy', 'airline', 'transferTo', 'paymentMethod',
            'issuedCardType', 'cardOwner'
        ]);

        // ✅ Detect if any filter applied
        $hasFilter =
            (request()->filled('trip_type') && request()->trip_type != 0) ||
            (request()->filled('airline_id') && request()->airline_id != 0) ||
            (request()->filled('flight_date_range') && request()->flight_date_range != 0) ||
            (request()->filled('invoice_date_range') && request()->invoice_date_range != 0) ||
            (request()->filled('introduction_source_id') && request()->introduction_source_id != 0) ||
            (request()->filled('customer_country_id') && request()->customer_country_id != 0) ||
            (request()->filled('issued_supplier_ids') && request()->issued_supplier_ids != 0) ||
            (request()->filled('issued_by_id') && request()->issued_by_id != 0) ||
            (request()->filled('departure') && request()->departure != 0) ||
            (request()->filled('destination') && request()->destination != 0) ||
            (request()->filled('transfer_to') && request()->transfer_to != 0) ||
            (request()->filled('payment_method') && request()->payment_method != 0) ||
            (request()->filled('issued_card_type') && request()->issued_card_type != 0) ||
            (request()->filled('card_owner') && request()->card_owner != 0) ||
            (request()->filled('payment_status') && request()->payment_status != 0) ||
            request()->filled('payment_date_range') ||
            request()->filled('next_payment_date_range') ||
            (request()->filled('refund_type') && request()->refund_type != 0) ||
            (request()->filled('refund_payment_status') && request()->refund_payment_status != 0) ||
            (request()->filled('gender') && request()->gender != 0) ||
            (request()->has('search') && !empty(request('search')['value']));

        // ✅ Conditional order (from payments table itself)
        if ($hasFilter) {
            $query->orderByRaw("
                CASE
                    WHEN departure_date_time IS NOT NULL THEN departure_date_time
                    ELSE return_date_time
                END ASC
            ");
        } else {
            $query->latest();
        }

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

                        // Search in related ticket invoice_id + client contact
                        $q->orWhereHas('ticket', function ($q2) use ($search) {
                            $q2->where('invoice_id', 'like', "%{$search}%")
                                ->orWhere('contacted_with_client', 'like', "%{$search}%")
                                ->orWhere('client_contact_note', 'like', "%{$search}%");
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
                    $parts = explode('-', $flightDateRange, 2);
                    $start = trim($parts[0] ?? '');
                    $end = trim($parts[1] ?? '');
                    $startDate = \Carbon\Carbon::createFromFormat('Y/m/d', $start)->startOfDay();
                    $endDate = \Carbon\Carbon::createFromFormat('Y/m/d', $end)->endOfDay();
                    $todayStart = \Carbon\Carbon::today()->startOfDay();

                    // Only show payments whose ticket has an upcoming segment (next main flight) in this range
                    $query->whereHas('ticket', function ($q) use ($startDate, $endDate, $todayStart) {
                        $q->whereHas('allFlights', function ($f) use ($startDate, $endDate, $todayStart) {
                            $f->whereNull('parent_id')
                                ->where('departure_date_time', '>=', $todayStart)
                                ->whereBetween('departure_date_time', [$startDate, $endDate]);
                        }, '>=', 1);
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

                if (!empty(request()->gender) && request()->gender != 0) {
                    $query->whereHas('ticket.passengers', function ($q) {
                        $q->where('gender', request()->gender);
                    });
                }
            })
            ->addIndexColumn()

            ->addColumn('trip_info', function ($row) use ($getCurrentTranslation) {
                $passengerNameLabel = $getCurrentTranslation['passenger_name_label'] ?? 'passenger_name_label';
                $passengerPhoneLabel = $getCurrentTranslation['passenger_phone_label'] ?? 'passenger_phone_label';
                $passengerEmailLabel = $getCurrentTranslation['passenger_email_label'] ?? 'passenger_email_label';
                $tripTypeLabel       = $getCurrentTranslation['trip_type_label'] ?? 'trip_type_label';
                $flightRouteLabel    = $getCurrentTranslation['flight_route_label'] ?? 'flight_route_label';
                $departureLabel      = $getCurrentTranslation['departure_label'] ?? 'departure_label';
                $returnLabel         = $getCurrentTranslation['return_label'] ?? 'return_label';
                $airlineLabel        = $getCurrentTranslation['airline_label'] ?? 'airline_label';
                $introductionSourceLabel = $getCurrentTranslation['introduction_source_label'] ?? 'introduction_source_label';
                $flightStatusMailCountLabel = $getCurrentTranslation['flight_status_mail_count'] ?? 'flight_status_mail_count';

                $upcomingDeparture = $row->ticket?->upcoming_departure_date;
                $segmentBadge = $row->ticket?->upcoming_segment_badge;
                $segmentBadgeLabel = $segmentBadge === 'Return'
                    ? ($getCurrentTranslation['segment_return'] ?? 'Return')
                    : ($segmentBadge === 'Outbound' ? ($getCurrentTranslation['segment_outbound'] ?? 'Outbound') : '');
                $badgeHtml = $segmentBadgeLabel
                    ? ' <span class="badge badge-' . ($segmentBadge === 'Return' ? 'info' : 'primary') . ' ms-1">' . e($segmentBadgeLabel) . '</span>'
                    : '';
                $departureLine = ($upcomingDeparture !== null)
                    ? '<strong>' . $departureLabel . ':</strong> ' . \Carbon\Carbon::parse($upcomingDeparture)->format('Y-m-d, H:i') . $badgeHtml . '<br>'
                    : '';
                $return    = $row->return_date_time ? date('Y-m-d, H:i', strtotime($row->return_date_time)) : 'N/A';
                $airline   = $row->airline->name ?? 'N/A';
                $introductionSource = $row->introductionSource?->name ?? 'N/A';
                $flightStatusMailCount = (int) ($row->flight_status_mail_count ?? 0);
                $countsLine = passengerCountsLineHtml($row->ticket?->passengers ?? []);
                $countsBlock = $countsLine !== '' ? '<div class="mb-1">' . $countsLine . '</div>' : '';
                $clientContactBlock = $row->ticket ? ticketClientContactSummaryHtml($row->ticket, $getCurrentTranslation) : '';

                return '<div style="max-width: 280px; line-height: 1.6; text-align: left;">
                    <strong>' . $passengerNameLabel . ':</strong> ' . $row->client_name . '<br>
                    ' . $countsBlock . '
                    <strong>' . $passengerPhoneLabel . ':</strong> ' . ($row->client_phone ?? 'N/A') . '<br>
                    <strong>' . $passengerEmailLabel . ':</strong> ' . ($row->client_email ?? 'N/A') . '<br>
                    <strong>' . $tripTypeLabel . ':</strong> ' . $row->trip_type . '<br>
                    <strong>' . $airlineLabel . ':</strong> ' . $airline . '<br>
                    <strong>' . $flightRouteLabel . ':</strong> ' . $row->flight_route . '<br>
                    ' . $departureLine . '
                    <strong>' . $returnLabel . ':</strong> ' . $return . '<br>
                    <strong>' . $introductionSourceLabel . ':</strong> ' . $introductionSource . '<br>
                    ' . $clientContactBlock . '
                    <strong>' . $flightStatusMailCountLabel . ':</strong> <span class="badge badge-info">' . $flightStatusMailCount . '</span><br>
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
            ->addColumn('action', function ($row) use ($getCurrentTranslation) {
                $detailsUrl   = route('payment.show', $row->id);
                $editUrl      = route('payment.edit', $row->id);
                $deleteUrl    = route('payment.destroy', $row->id);
                $flightStatusUrl = route('payment.flight.status', $row->id);
                $checkFlightStatusTitle = $getCurrentTranslation['check_flight_status'] ?? 'Check Flight Status';

                $buttons = '';

                // 📇 Client contact popup (first button)
                if ($row->ticket && function_exists('hasPermission') && hasPermission('ticket.edit')) {
                    $tid = (int) $row->ticket->id;
                    $c = e($row->ticket->contacted_with_client ?? '');
                    $n = e($row->ticket->client_contact_note ?? '');
                    $buttons .= '
                        <button type="button" class="btn btn-sm btn-primary my-1 btn-client-contact-popup" data-ticket-id="' . $tid . '" data-contacted="' . $c . '" data-note="' . $n . '">
                            <i class="fa-solid fa-address-book"></i>
                        </button>
                    ';
                }

                // ✈️ Check Flight Status (only when document_type = ticket)
                if (($row->ticket->document_type ?? '') === 'ticket' && hasPermission('payment.flight_status')) {
                    $buttons .= '
                        <a href="' . e($flightStatusUrl) . '" class="btn btn-sm btn-success my-1 check-flight-status-btn" title="' . e($checkFlightStatusTitle) . '">
                            <i class="fa-solid fa-plane-departure"></i>
                        </a>
                    ';
                }

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

    /** Session key for "Changed & Cancelled flights" check result. */
    private function changedCancelledFlightsSessionKey(): string
    {
        return 'changed_cancelled_flights_result';
    }

    private function flightApiBulkPauseCacheKey(): string
    {
        return 'flightapi_track_bulk_pause_v1';
    }

    /**
     * @return array{until: \Illuminate\Support\Carbon, message: string}|null
     */
    private function getFlightApiBulkPause(): ?array
    {
        $v = Cache::get($this->flightApiBulkPauseCacheKey());
        if (!is_array($v)) {
            return null;
        }
        $until = $v['until'] ?? null;
        if (is_string($until)) {
            try {
                $until = Carbon::parse($until);
            } catch (\Throwable $e) {
                return null;
            }
        }
        if (!$until instanceof \DateTimeInterface) {
            return null;
        }
        if (now()->gte(Carbon::parse($until))) {
            return null;
        }
        $v['until'] = $until;

        return $v;
    }

    private function pauseFlightApiBulkTracking(\Throwable $e): void
    {
        $minutes = (int) config('services.flightapi.bulk_pause_minutes', 5);
        $minutes = max(1, min(60, $minutes));
        $until = now()->addMinutes($minutes);
        $msg = $e->getMessage();
        if (strlen($msg) > 400) {
            $msg = substr($msg, 0, 397) . '...';
        }
        Cache::put($this->flightApiBulkPauseCacheKey(), [
            'until' => $until,
            'message' => $msg,
        ], $until);
    }

    private function clearFlightApiBulkPause(): void
    {
        Cache::forget($this->flightApiBulkPauseCacheKey());
    }

    /**
     * Changed & Cancelled flights page: show buttons and table of results from session (if any).
     */
    public function changedCancelledFlights()
    {
        if (!hasPermission('ticket.reminder')) {
            abort(403, getCurrentTranslation()['permission_denied'] ?? 'Permission denied');
        }
        $sessionKey = $this->changedCancelledFlightsSessionKey();
        $userId = (int) auth()->id();
        $cacheData = Cache::get('rescheduled_cancelled_result_' . $userId);
        $sessionData = is_array($cacheData) ? $cacheData : session($sessionKey);
        $results = [];
        if (is_array($sessionData) && !empty($sessionData['payment_ids'])) {
            $paymentIds = $sessionData['payment_ids'];
            $statusByPayment = $sessionData['status_by_payment'] ?? [];
            $payments = Payment::with([
                'ticket', 'ticket.passengers', 'ticket.allFlights', 'airline', 'introductionSource',
            ])->whereIn('id', $paymentIds)->orderBy('departure_date_time')->get();
            foreach ($payments as $row) {
                $pid = $row->id;
                $status = $statusByPayment[$pid] ?? ['has_cancelled' => false, 'has_schedule_changed' => false, 'check_failed' => false, 'live_unavailable' => false];
                $results[] = [
                    'payment' => $row,
                    'has_cancelled' => (bool) ($status['has_cancelled'] ?? false),
                    'has_schedule_changed' => (bool) ($status['has_schedule_changed'] ?? false),
                    'check_failed' => (bool) ($status['check_failed'] ?? false),
                    'live_unavailable' => (bool) ($status['live_unavailable'] ?? false),
                ];
            }
        }
        $getCurrentTranslation = getCurrentTranslation();
        $upcomingFlightCheckDays = (int) config('services.flightapi.upcoming_flight_check_days', 2);
        $upcomingFlightCheckDays = max(1, $upcomingFlightCheckDays);
        $flightApiBulkPause = $this->getFlightApiBulkPause();
        $flightApiBulkPaused = $flightApiBulkPause !== null;
        $flightApiBulkPauseMessage = $flightApiBulkPause['message'] ?? null;
        return view('common.payment-record.changedCancelledFlights', get_defined_vars());
    }

    /**
     * Run the rescheduled/cancelled check (used by job or sync). Returns [ $paymentIds, $statusByPayment ] or null.
     * $paymentIds lists payments with schedule/cancel issues or failed API checks (for display + retry).
     * When $userId is set, checks cache flag rescheduled_cancelled_stop_{userId} each iteration and returns early if user requested stop.
     * @param int|null $userId When set (e.g. from job), stop flag is checked so user can cancel the check.
     * @return array{0: array<int>, 1: array<int, array>}|null
     */
    public function runRescheduledCancelledCheck(?int $userId = null): ?array
    {
        $checkDays = (int) config('services.flightapi.upcoming_flight_check_days', 2);
        $checkDays = max(1, $checkDays);
        $todayStart = Carbon::today()->startOfDay();
        $windowEnd = Carbon::today()->addDays($checkDays)->endOfDay();
        $payments = Payment::with([
            'ticket', 'ticket.allFlights', 'ticket.flights', 'ticket.allFlights.airline', 'ticket.flights.transits',
        ])
            ->whereHas('ticket', function ($q) {
                $q->where('document_type', 'ticket');
            })
            ->whereHas('ticket.allFlights', function ($q) use ($todayStart, $windowEnd) {
                $q->whereNull('parent_id')
                    ->where('departure_date_time', '>=', $todayStart)
                    ->where('departure_date_time', '<=', $windowEnd);
            })
            ->orderBy('departure_date_time')
            ->get();
        $changedPaymentIds = [];
        $failedPaymentIds = [];
        $unavailablePaymentIds = [];
        $statusByPayment = [];
        $creditAttributionUserId = $userId ?? (Auth::check() ? (int) Auth::id() : null);
        foreach ($payments as $payment) {
            if ($userId !== null && Cache::get('rescheduled_cancelled_stop_' . $userId)) {
                Cache::forget('rescheduled_cancelled_stop_' . $userId);
                $paymentIds = array_values(array_unique(array_merge($changedPaymentIds, $failedPaymentIds)));

                return [$paymentIds, $statusByPayment];
            }
            if (!$payment->ticket || ($payment->ticket->document_type ?? '') !== 'ticket') {
                continue;
            }
            $eval = $this->evaluateRescheduledCancelledForPayment($payment, $creditAttributionUserId);
            if (!empty($eval['check_failed'])) {
                $failedPaymentIds[] = $payment->id;
                $statusByPayment[$payment->id] = $eval;
            } elseif (!empty($eval['live_unavailable'])) {
                // Keep in list so UI can show "Live Status Data Unavailable".
                $unavailablePaymentIds[] = $payment->id;
                $statusByPayment[$payment->id] = $eval;
            } elseif (($eval['has_cancelled'] ?? false) || ($eval['has_schedule_changed'] ?? false)) {
                $changedPaymentIds[] = $payment->id;
                $statusByPayment[$payment->id] = $eval;
            }
        }
        $paymentIds = array_values(array_unique(array_merge($changedPaymentIds, $failedPaymentIds, $unavailablePaymentIds)));

        return [$paymentIds, $statusByPayment];
    }

    /**
     * Check all upcoming flights via API; store result in session (sync) or dispatch job (AJAX).
     * When called via AJAX, starts background job and returns immediately so the user can use other tabs.
     */
    public function checkAllUpcomingFlights(Request $request)
    {
        if (!hasPermission('ticket.reminder')) {
            if ($request->wantsJson()) {
                return response()->json(['is_success' => 0, 'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied'], 403);
            }
            abort(403, getCurrentTranslation()['permission_denied'] ?? 'Permission denied');
        }

        if ($request->wantsJson()) {
            $userId = (int) auth()->id();
            Cache::forget('rescheduled_cancelled_stop_' . $userId);
            $this->clearFlightApiBulkPause();
            Cache::put('rescheduled_cancelled_running_' . $userId, true, now()->addMinutes(15));

            // Run inline for reliability (no queue worker dependency).
            // This ensures the list gets populated immediately after "Check All".
            set_time_limit(600);
            $payload = $this->runRescheduledCancelledCheck($userId);
            $paymentIds = $payload[0] ?? [];
            $statusByPayment = $payload[1] ?? [];
            Cache::put('rescheduled_cancelled_result_' . $userId, [
                'payment_ids' => is_array($paymentIds) ? $paymentIds : [],
                'status_by_payment' => is_array($statusByPayment) ? $statusByPayment : [],
                'checked_at' => now()->toDateTimeString(),
            ], now()->addHours(24));
            Cache::forget('rescheduled_cancelled_running_' . $userId);

            $t = getCurrentTranslation();
            $changedCount = count(array_filter($statusByPayment, function ($s) {
                return empty($s['check_failed']) && empty($s['live_unavailable']) && (($s['has_cancelled'] ?? false) || ($s['has_schedule_changed'] ?? false));
            }));
            $failedCount = count(array_filter($statusByPayment, function ($s) {
                return !empty($s['check_failed']);
            }));
            $unavailableCount = count(array_filter($statusByPayment, function ($s) {
                return empty($s['check_failed']) && !empty($s['live_unavailable']);
            }));

            $msg = ($t['check_completed'] ?? 'Check completed.') . ' ' . $changedCount . ' ' . ($t['flights_with_changes_found'] ?? 'flight(s) with changes found.');
            if ($unavailableCount > 0) {
                $msg .= ' ' . $unavailableCount . ' ' . ($t['live_status_data_unavailable'] ?? 'live status data unavailable.');
            }
            if ($failedCount > 0) {
                $msg .= ' ' . $failedCount . ' ' . ($t['status_checks_failed'] ?? 'status check(s) failed.');
            }
            return response()->json([
                'is_success' => 1,
                'started' => false,
                'count' => count($paymentIds),
                'message' => $msg,
            ]);
        }

        set_time_limit(600);
        $this->clearFlightApiBulkPause();
        $payload = $this->runRescheduledCancelledCheck(null);
        $paymentIds = $payload[0] ?? [];
        $statusByPayment = $payload[1] ?? [];
        session([$this->changedCancelledFlightsSessionKey() => [
            'payment_ids' => $paymentIds,
            'status_by_payment' => $statusByPayment,
            'checked_at' => now()->toDateTimeString(),
        ]]);
        $t = getCurrentTranslation();
        $changedCount = count(array_filter($statusByPayment, function ($s) {
            return empty($s['check_failed']) && (($s['has_cancelled'] ?? false) || ($s['has_schedule_changed'] ?? false));
        }));
        $failedCount = count(array_filter($statusByPayment, function ($s) {
            return !empty($s['check_failed']);
        }));
        $msg = ($t['check_completed'] ?? 'Check completed.') . ' ' . $changedCount . ' ' . ($t['flights_with_changes_found'] ?? 'flight(s) with changes found.');
        if ($failedCount > 0) {
            $msg .= ' ' . $failedCount . ' ' . ($t['status_checks_failed'] ?? 'status check(s) failed.');
        }

        return redirect()->route('flight.changedCancelled')
            ->with('success', $msg);
    }

    /**
     * Clear session data for Changed & Cancelled flights result.
     */
    public function clearChangedCancelledResult(Request $request)
    {
        if (!hasPermission('ticket.reminder')) {
            if ($request->wantsJson()) {
                return response()->json(['is_success' => 0, 'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied'], 403);
            }
            abort(403, getCurrentTranslation()['permission_denied'] ?? 'Permission denied');
        }
        session()->forget($this->changedCancelledFlightsSessionKey());
        $uid = (int) auth()->id();
        Cache::forget('rescheduled_cancelled_result_' . $uid);
        Cache::forget('rescheduled_cancelled_running_' . $uid);
        if ($request->wantsJson()) {
            return response()->json(['is_success' => 1, 'message' => getCurrentTranslation()['result_cleared'] ?? 'Result data cleared.']);
        }
        return redirect()->route('flight.changedCancelled')
            ->with('success', getCurrentTranslation()['result_cleared'] ?? 'Result data cleared.');
    }

    /**
     * Stop the background rescheduled/cancelled check for the current user. Sets stop flag and clears running flag.
     */
    public function stopRescheduledCancelledCheck(Request $request)
    {
        if (!hasPermission('ticket.reminder')) {
            return response()->json(['is_success' => 0, 'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied'], 403);
        }
        $uid = (int) auth()->id();
        Cache::put('rescheduled_cancelled_stop_' . $uid, true, now()->addMinutes(2));
        Cache::forget('rescheduled_cancelled_running_' . $uid);
        return response()->json([
            'is_success' => 1,
            'message' => getCurrentTranslation()['check_stopped'] ?? 'Check stopped.',
        ]);
    }

    /**
     * Return whether the background rescheduled/cancelled check is still running (for polling).
     */
    public function rescheduledCancelledCheckStatus(Request $request)
    {
        if (!hasPermission('ticket.reminder')) {
            return response()->json(['running' => false], 403);
        }
        $running = Cache::get('rescheduled_cancelled_running_' . (int) auth()->id(), false);
        return response()->json(['running' => (bool) $running]);
    }

    /**
     * Re-run the flight status API check for one payment (Ajax). Updates session or cache result store.
     */
    public function retryChangedCancelledFlightCheck(Request $request)
    {
        if (!hasPermission('ticket.reminder')) {
            return response()->json(['is_success' => 0, 'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied'], 403);
        }
        $paymentId = (int) $request->input('payment_id');
        if ($paymentId < 1) {
            return response()->json(['is_success' => 0, 'message' => getCurrentTranslation()['invalid_request'] ?? 'Invalid request.'], 422);
        }
        try {
            $payment = Payment::with([
                'ticket', 'ticket.allFlights', 'ticket.flights', 'ticket.allFlights.airline', 'ticket.flights.transits',
            ])->where('id', $paymentId)->first();
            if (!$payment) {
                return response()->json(['is_success' => 0, 'message' => getCurrentTranslation()['not_found'] ?? 'Not found.'], 404);
            }
            if (!$this->paymentInRescheduledCheckWindow($payment)) {
                return response()->json(['is_success' => 0, 'message' => getCurrentTranslation()['flight_not_in_check_window'] ?? 'This payment is outside the upcoming flight check window.'], 422);
            }
            $this->clearFlightApiBulkPause();
            $userId = (int) auth()->id();
            $stored = $this->getRescheduledCancelledResultForUser($userId);
            $source = $stored['source'] ?? 'session';
            $payload = $stored['data'] ?? [
                'payment_ids' => [],
                'status_by_payment' => [],
                'checked_at' => now()->toDateTimeString(),
            ];
            $eval = $this->evaluateRescheduledCancelledForPayment($payment, $userId);
            if (!is_array($eval) || !array_key_exists('check_failed', $eval)) {
                return response()->json(['is_success' => 0, 'message' => getCurrentTranslation()['something_went_wrong'] ?? 'Something went wrong.'], 500);
            }
            $payload = $this->mergeRescheduledResultForPayment($payload, $paymentId, $eval);
            $this->putRescheduledCancelledResultForUser($userId, $source, $payload);
            $t = getCurrentTranslation();

            return response()->json([
                'is_success' => 1,
                'check_failed' => (bool) $eval['check_failed'],
                'has_cancelled' => (bool) ($eval['has_cancelled'] ?? false),
                'has_schedule_changed' => (bool) ($eval['has_schedule_changed'] ?? false),
                'message' => !empty($eval['check_failed'])
                    ? ($t['checking_failed'] ?? 'Checking failed.')
                    : ($t['flight_check_retried'] ?? 'Flight check updated.'),
            ]);
        } catch (\Throwable $e) {
            \Log::error('retryChangedCancelledFlightCheck failed: ' . $e->getMessage(), ['exception' => $e]);

            return response()->json([
                'is_success' => 0,
                'message' => getCurrentTranslation()['something_went_wrong'] ?? 'Something went wrong.',
            ], 500);
        }
    }

    private function getRescheduledCancelledResultForUser(int $userId): ?array
    {
        $cached = Cache::get('rescheduled_cancelled_result_' . $userId);
        if (is_array($cached) && array_key_exists('payment_ids', $cached)) {
            return ['source' => 'cache', 'data' => $cached];
        }
        $sess = session($this->changedCancelledFlightsSessionKey());
        if (is_array($sess) && array_key_exists('payment_ids', $sess)) {
            return ['source' => 'session', 'data' => $sess];
        }

        return null;
    }

    private function putRescheduledCancelledResultForUser(int $userId, string $source, array $data): void
    {
        if ($source === 'cache') {
            Cache::put('rescheduled_cancelled_result_' . $userId, $data, now()->addHours(24));

            return;
        }
        session([$this->changedCancelledFlightsSessionKey() => $data]);
    }

    /**
     * @param array{payment_ids: array<int>, status_by_payment: array<int, array>, checked_at?: string} $payload
     * @param array{check_failed: bool, has_cancelled: bool, has_schedule_changed: bool, live_unavailable?: bool} $eval
     * @return array{payment_ids: array<int>, status_by_payment: array<int, array>, checked_at: string}
     */
    private function mergeRescheduledResultForPayment(array $payload, int $paymentId, array $eval): array
    {
        $ids = $payload['payment_ids'] ?? [];
        $statusByPayment = $payload['status_by_payment'] ?? [];
        $statusByPayment[$paymentId] = $eval;
        // Keep payment in the list if:
        // - check failed (so user can retry), OR
        // - live status unavailable (so UI can show "Live Status Data Unavailable"), OR
        // - actual cancelled/schedule-changed flags are true.
        $include = !empty($eval['check_failed'])
            || !empty($eval['live_unavailable'])
            || !empty($eval['has_cancelled'])
            || !empty($eval['has_schedule_changed']);

        if ($include) {
            if (!in_array($paymentId, $ids, true)) {
                $ids[] = $paymentId;
            }
        } else {
            $ids = array_values(array_diff($ids, [$paymentId]));
            unset($statusByPayment[$paymentId]);
        }
        $payload['payment_ids'] = $ids;
        $payload['status_by_payment'] = $statusByPayment;
        $payload['checked_at'] = $payload['checked_at'] ?? now()->toDateTimeString();

        return $payload;
    }

    private function paymentInRescheduledCheckWindow(Payment $payment): bool
    {
        if (!$payment->ticket || ($payment->ticket->document_type ?? '') !== 'ticket') {
            return false;
        }
        try {
            $checkDays = max(1, (int) config('services.flightapi.upcoming_flight_check_days', 2));
            $todayStart = Carbon::today()->startOfDay();
            $windowEnd = Carbon::today()->addDays($checkDays)->endOfDay();
            $mainFlights = $payment->ticket->allFlights->whereNull('parent_id');
            foreach ($mainFlights as $f) {
                $dep = $f->departure_date_time ?? null;
                if (!$dep) {
                    continue;
                }
                try {
                    $d = Carbon::parse($dep);
                    if ($d->gte($todayStart) && $d->lte($windowEnd)) {
                        return true;
                    }
                } catch (\Throwable $e) {
                    continue;
                }
            }
        } catch (\Throwable $e) {
            \Log::warning('paymentInRescheduledCheckWindow: ' . $e->getMessage(), ['payment_id' => $payment->id ?? null]);
        }

        return false;
    }

    /**
     * True if at least one segment has usable scheduled times from the tracking API (not only unavailable placeholders).
     */
    private function liveDataHasUsableFlightTrack(array $liveData): bool
    {
        foreach ($liveData as $seg) {
            if (!is_array($seg) || $seg === []) {
                continue;
            }
            $dep = $seg['departure'] ?? null;
            if (is_array($dep) && (!empty($dep['departureDateTime']) || !empty($dep['scheduledTime']))) {
                return true;
            }
            $arr = $seg['arrival'] ?? null;
            if (is_array($arr) && (!empty($arr['arrivalDateTime']) || !empty($arr['scheduledTime']))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{check_failed: bool, has_cancelled: bool, has_schedule_changed: bool, live_unavailable?: bool}
     */
    private function evaluateRescheduledCancelledForPayment(Payment $payment, ?int $creditUsedByUserId = null): array
    {
        $fail = ['check_failed' => true, 'has_cancelled' => false, 'has_schedule_changed' => false];
        try {
            if (!$payment->exists || $payment->id < 1) {
                return $fail;
            }
            $fetched = $this->fetchAndCacheFlightStatusData($payment, true, $creditUsedByUserId);
            if (!is_array($fetched) || count($fetched) < 2) {
                return $fail;
            }
            $systemSegments = $fetched[0];
            $liveData = $fetched[1];
            if (!is_array($systemSegments) || !is_array($liveData)) {
                return $fail;
            }
            if ($systemSegments === []) {
                return $fail;
            }
            if (!$this->liveDataHasUsableFlightTrack($liveData)) {
                // Live tracking is unavailable for this flight (provider limitation / unsupported airline/date/etc).
                // This should be shown in the list as "Live Status Data Unavailable", not as "checking failed".
                return [
                    'check_failed' => false,
                    'has_cancelled' => false,
                    'has_schedule_changed' => false,
                    'live_unavailable' => true,
                ];
            }
            $hasCancelled = false;
            foreach ($liveData as $seg) {
                if (!is_array($seg)) {
                    continue;
                }
                $st = $seg['_segment_status'] ?? null;
                // Treat "unavailable" as "no live status", not as "cancelled".
                if ($st === 'cancelled') {
                    $hasCancelled = true;
                    break;
                }
            }
            $hasScheduleChanged = $this->computeDatetimeMismatch($systemSegments, $liveData);

            return [
                'check_failed' => false,
                'has_cancelled' => $hasCancelled,
                'has_schedule_changed' => $hasScheduleChanged,
                'live_unavailable' => false,
            ];
        } catch (\Throwable $e) {
            \Log::warning('Rescheduled/cancelled check failed for payment ' . ($payment->id ?? 0) . ': ' . $e->getMessage(), ['exception' => $e]);

            return $fail;
        }
    }

    /**
     * Evaluate rescheduled/cancelled flags from already-fetched liveData (no extra API call).
     *
     * @return array{check_failed: bool, has_cancelled: bool, has_schedule_changed: bool, live_unavailable?: bool}
     */
    private function evaluateRescheduledCancelledFromLiveData(array $systemSegments, array $liveData): array
    {
        $fail = ['check_failed' => true, 'has_cancelled' => false, 'has_schedule_changed' => false];
        if ($systemSegments === [] || !is_array($liveData) || $liveData === []) {
            return $fail;
        }
        if (!$this->liveDataHasUsableFlightTrack($liveData)) {
            return [
                'check_failed' => false,
                'has_cancelled' => false,
                'has_schedule_changed' => false,
                'live_unavailable' => true,
            ];
        }
        $hasCancelled = false;
        foreach ($liveData as $seg) {
            if (!is_array($seg)) {
                continue;
            }
            if (($seg['_segment_status'] ?? null) === 'cancelled') {
                $hasCancelled = true;
                break;
            }
        }
        $hasScheduleChanged = $this->computeDatetimeMismatch($systemSegments, $liveData);
        return [
            'check_failed' => false,
            'has_cancelled' => $hasCancelled,
            'has_schedule_changed' => $hasScheduleChanged,
            'live_unavailable' => false,
        ];
    }

    /** Session key for cached flight status data (per payment id). Call API only on page load/reload; use this for mail content and send. */
    private function flightStatusSessionKey(int $paymentId): string
    {
        return 'flight_status_data_' . $paymentId;
    }

    /**
     * Build system segments with flight_id/transit_id for applying live updates by id.
     * @param bool $upcomingOnly When true, only main segments within UPCOMMING_FLIGHT_CHECK_DAYS from today (rescheduled/cancelled check).
     * @return array<int, array>
     */
    private function buildSystemSegmentsWithIds(Payment $payment, bool $upcomingOnly = false): array
    {
        $ticket = $payment->ticket;
        $mainFlights = $ticket->allFlights->whereNull('parent_id')->values();
        if ($upcomingOnly) {
            $checkDays = max(1, (int) config('services.flightapi.upcoming_flight_check_days', 2));
            $todayStart = Carbon::today()->startOfDay();
            $windowEnd = Carbon::today()->addDays($checkDays)->endOfDay();
            $mainFlights = $mainFlights->filter(function ($f) use ($todayStart, $windowEnd) {
                $dep = $f->departure_date_time ?? null;
                if (!$dep) {
                    return false;
                }
                $d = Carbon::parse($dep);

                return $d->gte($todayStart) && $d->lte($windowEnd);
            })->values();
        }
        $systemSegments = [];
        foreach ($mainFlights as $f) {
            $systemSegments[] = [
                'airline' => $f->airline->name ?? 'N/A',
                'airline_code' => $f->airline->code ?? FlightApiService::extractAirlineCodeFromFlightNumber($f->flight_number),
                'flight_number' => $f->flight_number,
                'flight_number_only' => FlightApiService::extractFlightNumberOnly($f->flight_number),
                'leaving_from' => $f->leaving_from,
                'going_to' => $f->going_to,
                'departure_date_time' => $f->departure_date_time,
                'arrival_date_time' => $f->arrival_date_time,
                'is_transit' => (bool) ($f->is_transit ?? false),
                'flight_id' => $f->id,
                'transit_id' => null,
            ];
            foreach ($f->transits ?? [] as $transit) {
                $systemSegments[] = [
                    'airline' => $transit->airline->name ?? 'N/A',
                    'airline_code' => $transit->airline->code ?? FlightApiService::extractAirlineCodeFromFlightNumber($transit->flight_number),
                    'flight_number' => $transit->flight_number,
                    'flight_number_only' => FlightApiService::extractFlightNumberOnly($transit->flight_number),
                    'leaving_from' => $transit->leaving_from,
                    'going_to' => $transit->going_to,
                    'departure_date_time' => $transit->departure_date_time,
                    'arrival_date_time' => $transit->arrival_date_time,
                    'is_transit' => true,
                    'flight_id' => null,
                    'transit_id' => $transit->id,
                ];
            }
        }
        return $systemSegments;
    }

    /**
     * Fetch live data from API for all segments, store in session, return [systemSegments, liveData, liveUpdates, trackError].
     * Call this only on page load or reload; mail content load and mail send use session.
     * @param bool $upcomingOnly When true, only fetch status for segments in the configured upcoming-day window (rescheduled/cancelled check).
     */
    private function fetchAndCacheFlightStatusData(Payment $payment, bool $upcomingOnly = false, ?int $creditUsedByUserId = null): array
    {
        $liveData = [];
        $liveUpdates = [];
        $trackError = null;
        $flightApi = app(FlightApiService::class);

        try {
            $systemSegments = $this->buildSystemSegmentsWithIds($payment, $upcomingOnly);
        } catch (\Throwable $e) {
            \Log::warning('buildSystemSegmentsWithIds failed for payment ' . ($payment->id ?? 0) . ': ' . $e->getMessage());
            $systemSegments = [];
        }
        if (!is_array($systemSegments)) {
            $systemSegments = [];
        }

        if (!$flightApi->isConfigured()) {
            $trackError = getCurrentTranslation()['flight_api_not_configured'] ?? 'Flight API is not configured.';
            $this->putFlightStatusSession($payment->id, $liveData, $liveUpdates, $trackError);

            return [$systemSegments, $liveData, $liveUpdates, $trackError];
        }

        if ($systemSegments === []) {
            $this->putFlightStatusSession($payment->id, $liveData, $liveUpdates, $trackError);

            return [$systemSegments, $liveData, $liveUpdates, $trackError];
        }

        if ($upcomingOnly && ($pause = $this->getFlightApiBulkPause()) !== null) {
            $trackError = (string) ($pause['message'] ?? 'Flight API temporarily unavailable. Bulk checks are paused to save API credits.');
            foreach ($systemSegments as $_seg) {
                $liveData[] = ['_segment_status' => 'unavailable', '_bulk_pause' => true];
            }
            $this->putFlightStatusSession($payment->id, $liveData, $liveUpdates, $trackError);

            return [$systemSegments, $liveData, $liveUpdates, $trackError];
        }

        $skipRemainingApiCalls = false;
        $creditSvc = app(FlightApiCreditUsageService::class);
        foreach ($systemSegments as $seg) {
            if (!is_array($seg)) {
                $liveData[] = ['_segment_status' => 'unavailable'];
                continue;
            }
            $airlineCode = $seg['airline_code'] ?? null;
            $num = $seg['flight_number_only'] ?: ($seg['flight_number'] ?? '');
            $num = trim((string) $num);
            $airlineCode = $airlineCode !== null ? trim((string) $airlineCode) : '';
            $date = $seg['departure_date_time'] ?? $payment->departure_date_time;
            $depAp = !empty($seg['leaving_from']) ? trim((string) $seg['leaving_from']) : null;
            $liveData[] = [];
            $lastIdx = array_key_last($liveData);

            if ($skipRemainingApiCalls) {
                $liveData[$lastIdx] = ['_segment_status' => 'unavailable', '_api_skipped' => true];
                continue;
            }

            if ($num === '' || strlen($airlineCode) < 2) {
                if ($trackError === null) {
                    $trackError = getCurrentTranslation()['flight_tracking_requires_airline_and_number'] ?? 'Live tracking requires a 2-letter airline IATA code and flight number for each segment.';
                }
                $liveData[$lastIdx] = ['_segment_status' => 'unavailable'];
                continue;
            }

            $flightStatusMeta = [
                'airline_code' => $airlineCode,
                'flight_number' => $num,
                'segment_index' => $lastIdx,
                'leaving_from' => $depAp,
                'upcoming_window_check' => $upcomingOnly,
            ];
            try {
                $dateStr = (string) ($date ?? now());
                $raw = $flightApi->trackFlight($airlineCode, $num, $dateStr, $depAp);
                $creditSvc->recordFlightStatus($creditUsedByUserId, [
                    'payment_id' => (int) $payment->id,
                    'airline_code' => $airlineCode,
                    'flight_number' => $num,
                    'segment_index' => $lastIdx,
                    'leaving_from' => $depAp,
                    'upcoming_window_check' => $upcomingOnly,
                ]);
                $normalized = $this->normalizeFlightTrackingResponse($raw);
                if (!empty($normalized)) {
                    $liveData[$lastIdx] = $normalized[0];
                    $dep = $normalized[0]['departure'] ?? [];
                    $arr = $normalized[0]['arrival'] ?? [];
                    // Use absolute datetime fields only; scheduledTime strings may not include full date/year/timezone.
                    $newDep = $this->safeParseDateTimeForDb($dep['departureDateTime'] ?? null);
                    $newArr = $this->safeParseDateTimeForDb($arr['arrivalDateTime'] ?? null);
                    if ($newDep || $newArr) {
                        $liveUpdates[] = [
                            'flight_id' => $seg['flight_id'],
                            'transit_id' => $seg['transit_id'],
                            'departure_date_time' => $newDep,
                            'arrival_date_time' => $newArr,
                        ];
                    }
                } else {
                    $liveData[$lastIdx] = ['_segment_status' => 'unavailable'];
                }
            } catch (\Throwable $e) {
                $creditSvc->recordFlightStatus($creditUsedByUserId, [
                    'payment_id' => (int) $payment->id,
                    'airline_code' => $airlineCode,
                    'flight_number' => $num,
                    'segment_index' => $lastIdx,
                    'leaving_from' => $depAp,
                    'upcoming_window_check' => $upcomingOnly,
                    'track_failed' => true,
                ]);
                if ($trackError === null) {
                    $trackError = $e->getMessage();
                }
                $msg = $e->getMessage();
                $liveData[$lastIdx] = [
                    '_segment_status' => (stripos($msg, 'cancelled') !== false ? 'cancelled' : 'unavailable'),
                ];
                \Log::warning('FlightAPI track failed for segment', [
                    'message' => $msg,
                    'airline_code' => $airlineCode,
                    'flight_number' => $num,
                    'date' => $date instanceof \DateTimeInterface ? $date->format('Y-m-d') : $date,
                    'payment_id' => $payment->id,
                ]);

                if ($upcomingOnly
                    && FlightApiService::shouldPauseAllTrackingRequests($e)
                    && !FlightApiService::isLikelyTransientTrackingFailure($e)) {
                    $this->pauseFlightApiBulkTracking($e);
                    $skipRemainingApiCalls = true;
                }
            }
        }

        try {
            $this->putFlightStatusSession($payment->id, $liveData, $liveUpdates, $trackError);
        } catch (\Throwable $e) {
            \Log::warning('putFlightStatusSession failed for payment ' . ($payment->id ?? 0) . ': ' . $e->getMessage());
        }

        return [$systemSegments, $liveData, $liveUpdates, $trackError];
    }

    /** Store only API response data (formatted) in session; System Data is always read from current DB. */
    private function putFlightStatusSession(int $paymentId, array $liveData, array $liveUpdates, ?string $trackError): void
    {
        session([$this->flightStatusSessionKey($paymentId) => [
            'liveData' => $liveData,
            'liveUpdates' => $liveUpdates,
            'trackError' => $trackError,
            'updated_at' => now()->toDateTimeString(),
        ]]);
    }

    private function getFlightStatusSession(int $paymentId): ?array
    {
        $key = $this->flightStatusSessionKey($paymentId);
        $data = session($key);
        if (!is_array($data)) {
            return null;
        }
        return $data;
    }

    /**
     * Compute datetimeMismatch from systemSegments and liveData (same format as flight status view).
     */
    private function computeDatetimeMismatch(array $systemSegments, array $liveData): bool
    {
        foreach ($systemSegments as $idx => $seg) {
            $liveSeg = $liveData[$idx] ?? null;
            if (!$liveSeg) {
                continue;
            }
            $sysDep = $this->safeParseDateTimeForDb($seg['departure_date_time'] ?? null);
            $sysArr = $this->safeParseDateTimeForDb($seg['arrival_date_time'] ?? null);
            $liveDep = $this->safeParseDateTimeForDb($liveSeg['departure']['departureDateTime'] ?? null);
            $liveArr = $this->safeParseDateTimeForDb($liveSeg['arrival']['arrivalDateTime'] ?? null);

            // Compare only when both sides have full comparable datetimes.
            if ($sysDep && $liveDep) {
                if (Carbon::parse($sysDep)->format('Y-m-d H:i') !== Carbon::parse($liveDep)->format('Y-m-d H:i')) {
                    return true;
                }
            }
            if ($sysArr && $liveArr) {
                if (Carbon::parse($sysArr)->format('Y-m-d H:i') !== Carbon::parse($liveArr)->format('Y-m-d H:i')) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Flight status page: show system vs live. Uses session when present (no API call on F5/browser refresh).
     * API is called only when session data is missing or when user clicks "Refresh flight status" (via updateFlightFromApi).
     */
    public function flightStatus($id)
    {
        if (!hasPermission('payment.flight_status')) {
            abort(403, getCurrentTranslation()['permission_denied'] ?? 'Permission denied');
        }

        $payment = Payment::with([
            'ticket', 'ticket.allFlights', 'ticket.flights', 'ticket.passengers', 'ticket.passengers.flights',
            'airline', 'ticket.allFlights.airline', 'ticket.flights.transits',
        ])->findOrFail($id);

        if (!$payment->ticket || ($payment->ticket->document_type ?? '') !== 'ticket') {
            abort(404, 'Ticket not found or not a ticket document.');
        }

        $ticket = $payment->ticket;
        $getCurrentTranslation = getCurrentTranslation();
        $listRoute = route('flight.list');
        $flightListUrl = $listRoute;

        $systemSegments = $this->buildSystemSegmentsWithIds($payment);
        $sessionData = $this->getFlightStatusSession((int) $payment->id);
        if ($sessionData !== null) {
            $liveData = $sessionData['liveData'] ?? [];
            $trackError = $sessionData['trackError'] ?? null;
        } else {
            list($systemSegments, $liveData, $liveUpdates, $trackError) = $this->fetchAndCacheFlightStatusData($payment);
        }
        $datetimeMismatch = $this->computeDatetimeMismatch($systemSegments, $liveData);

        $customerEmail = $payment->client_email;

        return view('common.payment-record.flightStatus', get_defined_vars());
    }

    /**
     * Flight status mail form page (same design as ticket mail).
     */
    public function flightStatusMail($id)
    {
        if (!hasPermission('payment.flight_status')) {
            abort(403, getCurrentTranslation()['permission_denied'] ?? 'Permission denied');
        }

        $payment = Payment::with([
            'ticket', 'ticket.allFlights', 'ticket.flights', 'ticket.passengers', 'ticket.allFlights.airline', 'ticket.flights.transits',
        ])->findOrFail($id);

        if (!$payment->ticket || ($payment->ticket->document_type ?? '') !== 'ticket') {
            abort(404, 'Ticket not found or not a ticket document.');
        }

        $editData = $payment;
        $listRoute = route('flight.list');
        $getCurrentTranslation = getCurrentTranslation();

        return view('common.payment-record.flightStatusSendMailForm', get_defined_vars());
    }

    /**
     * Apply cached live updates to DB (no API call). Used after reload or when sending mail with "update to DB" checked.
     */
    private function applyFlightStatusLiveUpdatesToDb(Payment $payment, array $liveUpdates): bool
    {
        if (empty($liveUpdates)) {
            return false;
        }
        $updated = false;
        DB::beginTransaction();
        try {
            foreach ($liveUpdates as $one) {
                $flightId = $one['flight_id'] ?? null;
                $transitId = $one['transit_id'] ?? null;
                $dep = $one['departure_date_time'] ?? null;
                $arr = $one['arrival_date_time'] ?? null;
                $id = $transitId ?? $flightId;
                if ($id === null) {
                    continue;
                }
                $flight = \App\Models\TicketFlight::find($id);
                if (!$flight) {
                    continue;
                }
                if ($dep && $flight->departure_date_time !== $dep) {
                    $flight->departure_date_time = $dep;
                    $updated = true;
                }
                if ($arr && $flight->arrival_date_time !== $arr) {
                    $flight->arrival_date_time = $arr;
                    $updated = true;
                }
                if ($flight->isDirty(['departure_date_time', 'arrival_date_time'])) {
                    $flight->save();
                }
            }
            if ($updated) {
                $payment->refresh();
                $payment->load(['ticket', 'ticket.allFlights', 'ticket.flights']);
                $payment->departure_date_time = $payment->ticket->flights->first()?->departure_date_time ?? $payment->departure_date_time;
                $payment->return_date_time = $payment->ticket->allFlights->sortByDesc('departure_date_time')->first()?->departure_date_time ?? $payment->return_date_time;
                $payment->save();
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::warning('Apply flight status live updates failed: ' . $e->getMessage());
            return false;
        }
        return $updated;
    }

    /**
     * Reload: call API once for all segments, update DB from response, store in session.
     */
    public function updateFlightFromApi(Request $request, $id)
    {
        if (!hasPermission('payment.flight_status')) {
            return response()->json([
                'is_success' => 0,
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ], 403);
        }

        $payment = Payment::with(['ticket', 'ticket.allFlights', 'ticket.flights', 'ticket.allFlights.airline', 'ticket.flights.transits'])->findOrFail($id);
        if (!$payment->ticket || ($payment->ticket->document_type ?? '') !== 'ticket') {
            return response()->json(['is_success' => 0, 'message' => 'Ticket not found.'], 404);
        }

        $flightApi = app(FlightApiService::class);
        if (!$flightApi->isConfigured()) {
            return response()->json(['is_success' => 0, 'message' => 'Flight API is not configured.'], 400);
        }

        // Fetch latest live data (cached in session) then optionally apply API-derived time updates to DB.
        // Important: the rescheduled/cancelled list compares "System Data" vs "Live Status Data".
        // So after DB updates we must re-build system segments from the refreshed DB before computing mismatch.
        list($systemSegments, $liveData, $liveUpdates, $trackError) = $this->fetchAndCacheFlightStatusData($payment);
        $updated = $this->applyFlightStatusLiveUpdatesToDb($payment, $liveUpdates);
        try {
            $payment->refresh();
            $payment->load(['ticket', 'ticket.allFlights', 'ticket.allFlights.airline', 'ticket.flights', 'ticket.flights.transits']);
            $systemSegments = $this->buildSystemSegmentsWithIds($payment, false);
        } catch (\Throwable $e) {
            // keep previously built segments if refresh fails
        }
        // Keep "Rescheduled & Cancelled Flights" list consistent after refreshing flight status.
        try {
            $userId = (int) auth()->id();
            if ($userId > 0) {
                $stored = $this->getRescheduledCancelledResultForUser($userId);
                if (is_array($stored) && !empty($stored['data']) && is_array($stored['data'])) {
                    $source = (string) ($stored['source'] ?? 'session');
                    $payload = $stored['data'];
                    $eval = $this->evaluateRescheduledCancelledFromLiveData($systemSegments, $liveData);
                    $payload = $this->mergeRescheduledResultForPayment($payload, (int) $payment->id, $eval);
                    $this->putRescheduledCancelledResultForUser($userId, $source, $payload);
                }
            }
        } catch (\Throwable $e) {
            \Log::warning('Failed to sync rescheduled/cancelled result after flight status refresh: ' . $e->getMessage(), [
                'payment_id' => $payment->id ?? null,
            ]);
        }

        $message = $updated
            ? (getCurrentTranslation()['flight_data_updated'] ?? 'Flight data updated.')
            : (getCurrentTranslation()['no_changes_from_api'] ?? 'No changes from API.');

        return response()->json(['is_success' => 1, 'message' => $message]);
    }

    /**
     * Load mail body content for flight status (AJAX).
     */
    public function flightStatusMailContentLoad(Request $request, $id)
    {
        if (!hasPermission('payment.flight_status')) {
            return response()->json([
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ], 403);
        }

        $payment = Payment::with([
            'ticket', 'ticket.allFlights', 'ticket.flights', 'ticket.allFlights.airline', 'ticket.flights.transits',
        ])->findOrFail($id);

        if (!$payment->ticket || ($payment->ticket->document_type ?? '') !== 'ticket') {
            return response()->json([
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['data_not_found'] ?? 'Data not found',
            ], 404);
        }

        list($systemSegments, $liveDataFromDb, $trackErrorFromDb) = $this->buildFlightStatusSegmentsAndLive($payment, false);
        $sessionData = $this->getFlightStatusSession((int) $payment->id);
        if ($sessionData !== null) {
            $liveData = $sessionData['liveData'] ?? [];
            $trackError = $sessionData['trackError'] ?? null;
        } else {
            $liveData = $liveDataFromDb ?? [];
            $trackError = $trackErrorFromDb;
        }
        $ticket = $payment->ticket;
        $getCurrentTranslation = getCurrentTranslation();

        try {
            $viewData = view('common.payment-record.flightStatusMailContent', get_defined_vars())->render();
            return response()->json([
                'is_success' => 1,
                'icon' => 'success',
                'message' => getCurrentTranslation()['mail_content_updated'] ?? 'Mail content updated',
                'mail_content' => $viewData,
            ]);
        } catch (\Throwable $e) {
            \Log::error('Flight status mail content load failed: ' . $e->getMessage());
            return response()->json([
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['something_went_wrong'] ?? 'Something went wrong.',
            ], 500);
        }
    }

    /**
     * Save and send flight status mail: optional update from API, generate PDF, send with custom content.
     */
    public function flightStatusMailSend(Request $request, $id, PdfService $pdfService)
    {
        if (!hasPermission('payment.flight_status')) {
            return response()->json([
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ], 403);
        }

        $messages = getCurrentTranslation();
        $rules = [
            'to_email' => 'required|array|min:1',
            'to_email.*' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'mail_content' => 'nullable|string',
        ];
        $validator = Validator::make($request->all(), $rules, [
            'required' => $messages['required_message'] ?? 'This field is required.',
            'email' => $messages['enter_valid_email_address'] ?? 'Please enter a valid email address.',
            'max' => $messages['max_string_message'] ?? 'Maximum length exceeded.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'is_success' => 0,
                'icon' => 'error',
                'errors' => $validator->errors(),
            ]);
        }

        $payment = Payment::with([
            'ticket', 'ticket.allFlights', 'ticket.flights', 'ticket.allFlights.airline', 'ticket.flights.transits',
        ])->findOrFail($id);

        if (!$payment->ticket || ($payment->ticket->document_type ?? '') !== 'ticket') {
            return response()->json([
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['data_not_found'] ?? 'Data not found',
            ], 404);
        }

        $updateFromApi = $request->boolean('update_flight_before_send', true);
        $attachTicketPdf = $request->has('document_type_ticket') ? $request->boolean('document_type_ticket') : $request->boolean('attach_pdf', true);
        $ticketLayoutId = (int) $request->input('ticket_layout', 1);
        if ($ticketLayoutId < 1 || $ticketLayoutId > 3) {
            $ticketLayoutId = 1;
        }
        $tempPdfPaths = [];

        try {
            if ($updateFromApi) {
                $sessionData = $this->getFlightStatusSession((int) $payment->id);
                $liveUpdates = $sessionData['liveUpdates'] ?? [];
                if (!empty($liveUpdates)) {
                    $this->applyFlightStatusLiveUpdatesToDb($payment, $liveUpdates);
                    $payment->load(['ticket', 'ticket.allFlights', 'ticket.flights', 'ticket.allFlights.airline', 'ticket.flights.transits']);
                }
            }

            $ticket = $payment->ticket;
            if (!$ticket) {
                return response()->json([
                    'is_success' => 0,
                    'icon' => 'error',
                    'message' => getCurrentTranslation()['data_not_found'] ?? 'Data not found',
                ], 404);
            }

            $attachments = [];
            if ($attachTicketPdf) {
                $ticket->load(['flights', 'flights.transits', 'passengers', 'passengers.flights', 'fareSummary', 'user', 'user.company', 'creator']);
                $editData = $ticket;
                $passenger = null;
                $ticket_passengers = $ticket->passengers ?? collect();
                $withPrice = $request->boolean('ticket_with_price', false) ? 1 : 0;
                $isTicket = 1;
                $isInvoice = 0;

                $ticketLayout = 'common.ticket.includes.ticket-' . $ticketLayoutId;
                if (!View::exists($ticketLayout)) {
                    $ticketLayout = 'common.ticket.includes.ticket-1';
                }
                if (!hasPermission('ticket.multiLayout')) {
                    $ticketLayout = 'common.ticket.includes.ticket-1';
                }

                $html = view($ticketLayout, compact('editData', 'passenger', 'ticket_passengers', 'withPrice', 'isTicket', 'isInvoice'))->render();
                if (!file_exists(public_path('temp_pdfs'))) {
                    mkdir(public_path('temp_pdfs'), 0777, true);
                }
                $rawFilename = 'Reservation-' . ($ticket->reservation_number ?? $payment->payment_invoice_id ?? $payment->id) . '.pdf';
                $filename = preg_replace('/[\/\\\\?%*:|"<>]/', '_', $rawFilename);
                $filePath = public_path('temp_pdfs/' . $filename);
                $pdfService->generatePdf($editData, $html, $filePath, 'F', 'ticket');
                $attachments[] = $filePath;
                $tempPdfPaths[] = $filePath;
            }

            $toEmails = is_array($request->input('to_email')) ? array_filter($request->input('to_email')) : [];
            if (empty($toEmails)) {
                return response()->json([
                    'is_success' => 0,
                    'icon' => 'error',
                    'message' => getCurrentTranslation()['enter_valid_email_address'] ?? 'Please enter at least one valid email address.',
                ], 422);
            }
            $toName = $payment->client_name ?: $toEmails[0];
            $subject = $request->input('subject');
            $mailContent = $request->input('mail_content', '');

            $mailContent = str_replace('{passenger_automatic_name_here}', $toName ?: $toEmails[0], $mailContent);
            $passengers = $payment->ticket->passengers ?? collect();
            $mailContent = str_replace('{passenger_automatic_data_here}', getPassengerDataForMail($passengers), $mailContent);

            $mail = Mail::to($toEmails[0], $toName);
            if (count($toEmails) > 1) {
                foreach (array_slice($toEmails, 1) as $extra) {
                    $mail->to($extra);
                }
            }
            if ($request->filled('cc_emails') && is_array($request->cc_emails)) {
                $ccEmails = array_filter($request->cc_emails);
                if (!empty($ccEmails)) {
                    $mail->cc($ccEmails);
                }
            }
            if ($request->filled('bcc_emails') && is_array($request->bcc_emails)) {
                $bccEmails = array_filter($request->bcc_emails);
                if (!empty($bccEmails)) {
                    $mail->bcc($bccEmails);
                }
            }
            $mail->send(new FlightStatusMail($subject, $mailContent, $attachments));

            Payment::where('id', $payment->id)->increment('flight_status_mail_count');

            foreach ($tempPdfPaths as $p) {
                if (is_string($p) && file_exists($p)) {
                    @unlink($p);
                }
            }

            return response()->json([
                'is_success' => 1,
                'icon' => 'success',
                'message' => $messages['mail_sent_successfully'] ?? 'Mail sent successfully.',
                'redirect_url' => route('payment.flight.status', $payment->id),
                'redirection_title' => $messages['back_to_flight_status'] ?? 'Back to flight status',
            ]);
        } catch (\Throwable $e) {
            foreach ($tempPdfPaths as $p) {
                if (is_string($p) && file_exists($p)) {
                    @unlink($p);
                }
            }
            \Log::error('Flight status mail send error: ' . $e->getMessage());
            $isMailError = ($e instanceof \Illuminate\Mail\SendException) || (str_contains($e->getMessage(), 'Mail'));
            return response()->json([
                'is_success' => 0,
                'icon' => 'error',
                'message' => $isMailError
                    ? ($messages['mail_send_failed'] ?? 'Mail could not be sent. Please try again.')
                    : ($messages['something_went_wrong'] ?? 'Something went wrong. Please try again.'),
            ], 500);
        }
    }

    /**
     * Build system segments and optionally live API data for flight status. Returns [systemSegments, liveData, trackError].
     * When $fetchLive is false (e.g. when sending mail), only DB data is used and no API call is made.
     *
     * @return array{0: array, 1: array|null, 2: string|null}
     */
    private function buildFlightStatusSegmentsAndLive(Payment $payment, bool $fetchLive = true, ?int $creditUsedByUserId = null): array
    {
        $ticket = $payment->ticket;
        $mainFlights = $ticket->allFlights->whereNull('parent_id')->values();
        $systemSegments = [];
        foreach ($mainFlights as $f) {
            $systemSegments[] = [
                'airline' => $f->airline->name ?? 'N/A',
                'airline_code' => $f->airline->code ?? FlightApiService::extractAirlineCodeFromFlightNumber($f->flight_number),
                'flight_number' => $f->flight_number,
                'flight_number_only' => FlightApiService::extractFlightNumberOnly($f->flight_number),
                'leaving_from' => $f->leaving_from,
                'going_to' => $f->going_to,
                'departure_date_time' => $f->departure_date_time,
                'arrival_date_time' => $f->arrival_date_time,
                'is_transit' => (bool) ($f->is_transit ?? false),
            ];
            foreach ($f->transits ?? [] as $transit) {
                $systemSegments[] = [
                    'airline' => $transit->airline->name ?? 'N/A',
                    'airline_code' => $transit->airline->code ?? FlightApiService::extractAirlineCodeFromFlightNumber($transit->flight_number),
                    'flight_number' => $transit->flight_number,
                    'flight_number_only' => FlightApiService::extractFlightNumberOnly($transit->flight_number),
                    'leaving_from' => $transit->leaving_from,
                    'going_to' => $transit->going_to,
                    'departure_date_time' => $transit->departure_date_time,
                    'arrival_date_time' => $transit->arrival_date_time,
                    'is_transit' => true,
                ];
            }
        }

        $liveData = null;
        $trackError = null;
        if ($fetchLive) {
            $flightApi = app(FlightApiService::class);
            if ($flightApi->isConfigured() && !empty($systemSegments)) {
                $first = $systemSegments[0];
                $airlineCode = $first['airline_code'] ?? null;
                $num = $first['flight_number_only'] ?: $first['flight_number'];
                $date = $first['departure_date_time'] ?? $payment->departure_date_time;
                if ($airlineCode && $num) {
                    try {
                        $depAp = !empty($first['leaving_from']) ? trim((string) $first['leaving_from']) : null;
                        $meta = [
                            'airline_code' => $airlineCode,
                            'flight_number' => $num,
                            'segment_index' => 0,
                            'leaving_from' => $depAp,
                            'single_segment_preview' => true,
                        ];
                        $raw = $flightApi->trackFlight($airlineCode, $num, $date ?? now(), $depAp);
                        app(FlightApiCreditUsageService::class)->recordFlightStatus($creditUsedByUserId, [
                            'payment_id' => (int) $payment->id,
                            'airline_code' => $airlineCode,
                            'flight_number' => $num,
                            'segment_index' => 0,
                            'leaving_from' => $depAp,
                            'single_segment_preview' => true,
                        ]);
                        $liveData = $this->normalizeFlightTrackingResponse($raw);
                    } catch (\Exception $e) {
                        app(FlightApiCreditUsageService::class)->recordFlightStatus($creditUsedByUserId, [
                            'payment_id' => (int) $payment->id,
                            'airline_code' => $airlineCode,
                            'flight_number' => $num,
                            'segment_index' => 0,
                            'leaving_from' => $depAp,
                            'single_segment_preview' => true,
                            'track_failed' => true,
                        ]);
                        $trackError = $e->getMessage();
                    }
                }
            }
        }
        return [$systemSegments, $liveData, $trackError];
    }

    /**
     * Normalize FlightAPI tracking response to array of [ 'departure' => ..., 'arrival' => ... ].
     * API may return [ { departure }, { arrival } ] or [ { departure, arrival } ].
     */
    private function normalizeFlightTrackingResponse($raw): array
    {
        if (!is_array($raw)) {
            return [];
        }
        $dep = null;
        $arr = null;
        foreach ($raw as $item) {
            if (!is_array($item)) {
                continue;
            }
            if (isset($item['departure'])) {
                $dep = $item['departure'];
            }
            if (isset($item['arrival'])) {
                $arr = $item['arrival'];
            }
        }
        if ($dep !== null || $arr !== null) {
            return [['departure' => $dep ?? [], 'arrival' => $arr ?? []]];
        }
        return $raw;
    }

    /**
     * Parse API datetime into DB-friendly format (Y-m-d H:i:s).
     * Returns null if value can't be parsed safely.
     */
    private function safeParseDateTimeForDb($value): ?string
    {
        if (empty($value)) {
            return null;
        }
        try {
            return Carbon::parse((string) $value)->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return null;
        }
    }

    
}