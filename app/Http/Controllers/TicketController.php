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
use App\Mail\TicketInvoiceMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\View;

use App\Models\User;
use App\Models\UserCompany;
use App\Models\Language;
use App\Models\Currency;

use App\Models\Airline;

use App\Models\Ticket;
use App\Models\TicketFlight;
use App\Models\TicketPassenger;
use App\Models\TicketPassengerFlight;
use App\Models\TicketFareSummary;

use App\Models\Payment;
use App\Models\PaymentDocument;

use PDF;
use App\Services\PdfService;

use App\Services\TravelpayoutsService;
use Illuminate\Http\JsonResponse;

class TicketController extends Controller
{

    protected TravelpayoutsService $travelpayouts;

    public function __construct(TravelpayoutsService $travelpayouts)
    {
        $this->travelpayouts = $travelpayouts;
    }


    public function index()
    {   
        if (!hasPermission('ticket.index')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        $createRoute = hasPermission('ticket.create') ? route('ticket.create') : '';
        $dataTableRoute = hasPermission('ticket.index') ? route('ticket.datatable') : '';

        return view('common.ticket.index', get_defined_vars());
    }

    public function datatable()
    {   
        $user = Auth::user();
        $hasFilter =
            (request()->filled('trip_type') && request()->trip_type != 0) ||
            (request()->filled('airline_id') && request()->airline_id != 0) ||
            request()->filled('flight_date_range') ||
            request()->filled('invoice_date_range') ||
            (request()->has('search') && !empty(request('search')['value']));

        $query = Ticket::with('flights', 'flights.transits', 'passengers', 'passengers.flights', 'fareSummary', 'user', 'creator', 'updater', 'deleter');

        
        if ($hasFilter) {
            // ✅ Order by related flight’s departure_date_time using whereHas + orderBySub
            $query->whereHas('flights') // ensures related flights exist
                ->orderBy(
                    TicketFlight::select('departure_date_time')
                        ->whereColumn('ticket_flights.ticket_id', 'tickets.id')
                        ->orderBy('departure_date_time', 'asc')
                        ->limit(1),
                    'asc'
                );
        } else {
            $query->latest('tickets.created_at');
        }

        $currentTranslation = getCurrentTranslation();

        return DataTables::of($query)
            ->filter(function ($query) use ($user) {

                // Restrict by user
                if ($user->user_type != 'admin') {
                    $query->where('user_id', $user->business_id);
                }

                // Filter by user
                if (request()->has('data_for') && request()->data_for == 'agent') {
                    $query->whereNotIn('user_id', [$user->business_id]);
                }else{
                    $query->where('user_id', $user->business_id);
                }

                // Filter by document type
                if (request()->has('document_type') && request()->document_type != 'all') {
                    $documentTypes = array_map('strtolower', explode('-', request()->document_type));
                    $query->whereIn('document_type', $documentTypes);
                }

                // Filter by booking status
                if (request()->has('booking_status') && request()->booking_status != 0) {
                    $documentTypes = array_map('strtolower', explode('-', request()->booking_status));
                    $query->whereIn('booking_status', $documentTypes);
                }

                // ✅ Trip Type
                if (!empty(request()->trip_type) && request()->trip_type != 0) {
                    $query->where('trip_type', request()->trip_type);
                }

                // ✅ Airline filter
                if (!empty(request()->airline_id) && request()->airline_id != 0) {
                    $airlineIds = TicketFlight::where('airline_id', request()->airline_id)
                        ->distinct()
                        ->pluck('ticket_id');

                    if ($airlineIds->isNotEmpty()) {
                        $query->whereIn('id', $airlineIds);
                    }
                }

                // ✅ Flight date range filter
                if (!empty(request()->flight_date_range) && request()->flight_date_range != 0) {
                    [$start, $end] = explode('-', request()->flight_date_range);

                    $startDate = \Carbon\Carbon::createFromFormat('Y/m/d', trim($start))->startOfDay();
                    $endDate = \Carbon\Carbon::createFromFormat('Y/m/d', trim($end))->endOfDay();

                    $query->where(function ($main) use ($startDate, $endDate) {
                        $main->whereHas('allFlights', function ($q) use ($startDate, $endDate) {
                            $q->whereBetween('departure_date_time', [$startDate, $endDate]);
                        })
                        ->orWhere(function ($sub) use ($startDate, $endDate) {
                            $sub->where('trip_type', 'Round Trip')
                                ->whereHas('allFlights', function ($q) use ($startDate, $endDate) {
                                    $q->whereBetween('departure_date_time', [$startDate, $endDate]);
                                });
                        });
                    });
                }

                // ✅ Invoice date range filter
                if (!empty(request()->invoice_date_range) && request()->invoice_date_range != 0) {
                    [$start, $end] = explode('-', request()->invoice_date_range);

                    $startDate = \Carbon\Carbon::createFromFormat('Y/m/d', trim($start))->startOfDay();
                    $endDate = \Carbon\Carbon::createFromFormat('Y/m/d', trim($end))->endOfDay();

                    $query->whereBetween('invoice_date', [$startDate, $endDate]);
                }

                // ✅ Combined Global Search
                if (request()->has('search') && $search = request('search')['value']) {

                    $query->where(function ($q) use ($search) {

                        // Main table fields
                        $q->where('document_type', 'like', "%{$search}%")
                            ->orWhere('user_id', 'like', "%{$search}%")
                            ->orWhere('invoice_date', 'like', "%{$search}%")
                            ->orWhere('invoice_id', 'like', "%{$search}%")
                            ->orWhere('reservation_number', 'like', "%{$search}%")
                            ->orWhere('ticket_type', 'like', "%{$search}%")
                            ->orWhere('booking_status', 'like', "%{$search}%")
                            ->orWhere('bill_to', 'like', "%{$search}%")
                            ->orWhere('bill_to_info', 'like', "%{$search}%")
                            ->orWhere('footer_title', 'like', "%{$search}%")
                            ->orWhere('footer_text', 'like', "%{$search}%")
                            ->orWhere('bank_details', 'like', "%{$search}%");

                        // Search passengers
                        $passengerTicketIds = TicketPassenger::where(function ($sub) use ($search) {
                                $sub->where('name', 'like', "%{$search}%")
                                    ->orWhere('email', 'like', "%{$search}%")
                                    ->orWhere('phone', 'like', "%{$search}%");
                            })
                            ->distinct()
                            ->pluck('ticket_id');

                        if ($passengerTicketIds->isNotEmpty()) {
                            $q->orWhereIn('id', $passengerTicketIds);
                        }

                        // Search users
                        $userIds = User::where('name', 'like', "%{$search}%")->pluck('id');
                        if ($userIds->isNotEmpty()) {
                            $q->orWhereIn('user_id', $userIds);
                        }

                        // Search creators
                        $creatorIds = User::where('name', 'like', "%{$search}%")->pluck('id');
                        if ($creatorIds->isNotEmpty()) {
                            $q->orWhereIn('created_by', $creatorIds);
                        }
                    });
                }
            })
            ->addIndexColumn()
            ->addColumn('document_type', function ($row) {
                switch ($row->document_type) {
                    case 'ticket':
                        $badgeClass = 'badge bg-success';
                        break;
                    case 'invoice':
                        $badgeClass = 'badge bg-info';
                        break;
                    default:
                        $badgeClass = 'badge bg-primary';
                }

                return '<span class="' . $badgeClass . '">' . ucfirst($row->document_type) . '</span>';
            })
            ->addColumn('user_id', function ($row) {
                return $row->user ? $row->user->name : 'N/A';
            })
            ->addColumn('invoice_date', function ($row) use ($currentTranslation) {
                $invoiceId = '<strong>' . ($currentTranslation['invoice_id_label'] ?? 'invoice_id_label') . ':</strong> ' . e($row->invoice_id);

                $invoiceDateValue = $row->invoice_date
                    ? date('Y-m-d', strtotime($row->invoice_date))
                    : 'N/A';

                $invoiceDate = '<strong>' . ($currentTranslation['invoice_date_label'] ?? 'invoice_date_label') . ':</strong> ' . $invoiceDateValue;

                return $invoiceId . '<br>' . $invoiceDate;
            })

            ->addColumn('reservation_number', function ($row) use ($currentTranslation) {
                $reservationNumber = '<strong>' . ($currentTranslation['reservation_number_label'] ?? 'reservation_number_label') . ':</strong> ' . $row->reservation_number;

                $tripType = '';
                if(!empty($row->trip_type)){
                    $tripType = '<br><strong>' . ($currentTranslation['trip_type_label'] ?? 'trip_type_label') . ':</strong> ' . ($row->trip_type ?? '-');
                }

                $ticketType = '';
                if(!empty($row->ticket_type)){
                    $ticketType = '<br><strong>' . ($currentTranslation['ticket_type_label'] ?? 'ticket_type_label') . ':</strong> ' . ($row->ticket_type ?? '-');
                }

                // $passengerNames = ''; Dont Remove those code
                // if (!empty($row->passengers) && is_iterable($row->passengers)) {
                //     $names = collect($row->passengers)->pluck('name')->filter()->implode(', ');
                //     $passengerNames = '<br><strong>' . ($currentTranslation['passengers'] ?? 'passengers') . ':</strong> ' . $names;
                // }
                
                $passengerNames = '';
                if (!empty($row->passengers) && is_iterable($row->passengers)) {
                    $firstName = collect($row->passengers)->pluck('name')->filter()->first();
                    if ($firstName) {
                        $passengerNames = '<br><strong>' . ($currentTranslation['passenger'] ?? 'passenger') . ':</strong> ' . $firstName;
                    }
                }

                $departureDate = ''; 
                $departureAirline = ''; 
                $departureRouteInfo = '';
                
                if ($row->document_type == 'ticket' && !empty($row->flights) && is_iterable($row->flights)) {
                    $departureDate = '<br><strong>' . ($currentTranslation['departure_date_time_label'] ?? 'departure_date_time_label') . ':</strong> ' . ($row->flights[0]->departure_date_time ?? 'N/A');
                    $departureAirline = '<br><strong>' . ($currentTranslation['departure_airline'] ?? 'departure_airline') . ':</strong> ' . ($row->flights[0]->airline->name ?? 'N/A');
                    
                    // Show departure_route for Round Trip
                    if ($row->trip_type == 'Round Trip') {
                        $departureRoute = $row->departure_route ?? 'N/A';
                        $departureRouteInfo = '<br><strong>' . ($currentTranslation['departure_route_label'] ?? 'departure_route_label') . ':</strong> ' . $departureRoute;
                    } else {
                        // Show flight_route for One Way and Multi City
                        $flightRouteText = $row->flight_route ?? 'N/A';
                        $departureRouteInfo = '<br><strong>' . ($currentTranslation['flight_route_label'] ?? 'flight_route_label') . ':</strong> ' . $flightRouteText;
                    }
                }

                $returnDate = ''; 
                $returnAirline = '';
                $returnRouteInfo = '';
                if (
                    $row->document_type == 'ticket' &&
                    !empty($row->flights) &&
                    is_iterable($row->flights) &&
                    $row->trip_type == 'Round Trip' // ✅ Only for Round Trip
                ) {
                    $flights = collect($row->flights);
                    $lastFlight = $flights->last(); // Get the last flight in the collection

                    if ($lastFlight) {
                        $returnDate = '<br><strong>' . ($currentTranslation['return_date_time_label'] ?? 'return_date_time_label') . ':</strong> ' . ($lastFlight->departure_date_time ?? 'N/A');
                        $returnAirline = '<br><strong>' . ($currentTranslation['return_airline'] ?? 'return_airline') . ':</strong> ' . ($lastFlight->airline->name ?? 'N/A');
                        
                        // Add return route after return airline
                        $returnRouteText = $row->return_route ?? 'N/A';
                        $returnRouteInfo = '<br><strong>' . ($currentTranslation['return_route_label'] ?? 'return_route_label') . ':</strong> ' . $returnRouteText;
                    }
                }

                return $reservationNumber . $tripType . $ticketType . $passengerNames . $departureDate . $departureAirline . $departureRouteInfo . $returnDate . $returnAirline . $returnRouteInfo;
            })

            ->addColumn('booking_status', function ($row) use ($currentTranslation) {
                switch ($row->booking_status) {
                    case 'On Hold':
                        $badgeClass = 'badge bg-warning';
                        break;
                    case 'Processing':
                        $badgeClass = 'badge bg-info';
                        break;
                    case 'Confirmed':
                        $badgeClass = 'badge bg-success';
                        break;
                    case 'Cancelled':
                        $badgeClass = 'badge bg-danger';
                        break;
                    default:
                        $badgeClass = 'badge bg-secondary';
                }

                return '<span class="' . $badgeClass . '">' . $row->booking_status . '</span>';
            })
            // ->addColumn('created_by', function ($row) {
            //     return $row->creator ? $row->creator->name : 'N/A';
            // })
            ->addColumn('action', function ($row) use ($currentTranslation) {
                $duplicateUrl = route('ticket.duplicate', $row->id);
                $editUrl = route('ticket.edit', $row->id);
                $deleteUrl = route('ticket.destroy', $row->id);
                $detailsUrl = route('ticket.show', $row->id);
                $mailUrl = route('ticket.mail', $row->id);

                $buttons = '';

                // Mail button (requires permission)
                if (hasPermission('ticket.mail')) {
                    $buttons .= '
                        <a href="' . $mailUrl . '" class="btn btn-sm btn-secondary my-1" title="Mail">
                            <i class="fa-solid fa-envelope"></i>
                        </a>
                    ';
                }

                // View Details button (requires permission)
                // if (hasPermission('ticket.show')) {
                //     $buttons .= '
                //         <a href="' . $detailsUrl . '" class="btn btn-sm btn-info my-1" title="Details">
                //             <i class="fa-solid fa-pager"></i>
                //         </a>
                //     ';
                // }

                if (hasPermission('ticket.show')) {
                    if (hasPermission('ticket.multiLayout') && $row->document_type == 'ticket') {
                        $buttons .= '<button type="button" class="btn btn-sm btn-info my-1 show-ticket-btn"
                                data-url="' . $detailsUrl . '">
                            <i class="fa-solid fa-pager"></i>
                        </button>';
                    }else{
                        $buttons .= '
                            <a href="' . $detailsUrl . '" class="btn btn-sm btn-info my-1" title="Details">
                                <i class="fa-solid fa-pager"></i>
                            </a>
                        ';
                    }
                }

                // Edit button (requires permission)
                if (hasPermission('ticket.edit')) {
                    $buttons .= '
                        <a href="' . $editUrl . '" class="btn btn-sm btn-primary my-1" title="Edit">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </a>
                    ';
                }

                // Duplicate button (requires permission)
                if (hasPermission('ticket.duplicate')) {
                    $buttons .= '
                        <a href="' . $duplicateUrl . '" class="btn btn-sm btn-warning my-1 data-confirm-button" title="Duplicate">
                            <i class="fa-solid fa-clone"></i>
                        </a>
                    ';
                }

                // Delete button (requires permission and user is not the owner)
                if (
                    hasPermission('ticket.delete') &&
                    (
                        Auth::user()->user_type == 'admin' ||
                        Auth::user()->business_id == $row->id
                    )
                ) {
                    $buttons .= '
                        <button class="btn btn-sm btn-danger my-1 delete-table-data-btn confirm-relational-delete" rel-del-title="'.($currentTranslation['delete_payment_data_also'] ?? 'delete_payment_data_also').'" title="Delete"
                            data-id="' . $row->id . '"
                            data-url="' . $deleteUrl . '">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    ';
                }

                return !empty(trim($buttons)) ? '
                    <div class="d-flex align-items-center gap-2">
                        ' . $buttons . '
                    </div>' : 'N/A';
            })

            // ✅ All Activity
            ->addColumn('created_at', function ($row) {
                $activity = '<div class="activity-info">';
                
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
                //if (!is_null($row->mail_sent_count) && $row->mail_sent_count != 0) {
                    $activity .= '<div class="activity-item">';
                    $activity .= '<b>' . (getCurrentTranslation()['total_mail_sent'] ?? 'total_mail_sent') . ':</b> ';
                    $activity .= '<span class="badge badge-info">' . ($row->mail_sent_count > 0 ? $row->mail_sent_count : 0) . '</span>';
                    $activity .= '</div>';
                // }
                
                $activity .= '</div>';
                
                return $activity;
            })
            ->rawColumns(['document_type', 'invoice_date', 'reservation_number', 'booking_status', 'created_at', 'action']) // Allow HTML rendering
            ->make(true);
    }


    public function create(Request $request)
    {
        if (!hasPermission('ticket.create')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        $listRoute = hasPermission('ticket.index') ? route('ticket.index') : '';
        $saveRoute = hasPermission('ticket.create') ? route('ticket.store') : '';

        $airlines = Airline::where('status', 1)->orderBy('name', 'asc')->get();
        //dd($ticketAccountPlatforms);
        $page = 'common.ticket.ticketAddEdit';
        if($request->document_type == 'invoice'){
            $page = 'common.ticket.invoiceAddEdit';
        }
        if($request->document_type == 'ticket'){
            $page = 'common.ticket.ticketAddEdit';
        }
        if($request->document_type == 'quotation'){
            $page = 'common.ticket.quotationAddEdit';
        }
        return view($page, get_defined_vars());
    }

    public function store(Request $request)
    {
        if (!hasPermission('ticket.create')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        return $this->saveTicketData($request);
    }

    public function status($id, $status)
    {
        if (!hasPermission('ticket.status')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        if(!in_array($status, [0,1])){
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['status_is_incorrect'] ?? 'status_is_incorrect'
            ];
        }
        
        $user = Auth::user();
        $ticket = Ticket::where('id', $id)->first();
        if(empty($ticket)){
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['data_not_found'] ?? 'data_not_found'
            ];
        }
        if($user->user_type != 'admin' && $ticket->user_id != $user->business_id){
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['data_not_found'] ?? 'data_not_found'
            ];
        }

        $ticket->status = $status;
        $ticket->updated_by = $user->id;
        $ticket->save();
        
        $statusName = $status == 1 ? 'Active' : 'Inactive';
        return [
            'is_success' => 1,
            'icon' => 'success',
            'message' => (getCurrentTranslation()['status_updated'] ?? 'status_updated') . ' (' . $statusName . ')',
        ];
    }

    public function show(Request $request, $id)
    {
        if (!hasPermission('ticket.show')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        $user = Auth::user();
        $listRoute = hasPermission('ticket.index') ? route('ticket.index') : '';
        $saveRoute = hasPermission('ticket.edit') ? route('ticket.update', $id) : '';

        $editData = Ticket::with('flights', 'flights.transits', 'passengers', 'passengers.flights', 'fareSummary', 'user', 'user.company', 'creator')->where('id', $id)->first();
        if(empty($editData)){
            abort(404);
        }
        if($user->user_type != 'admin' && $editData->user_id != $user->business_id){
            abort(404);
        }

        $airlines = Airline::where('status', 1)->orderBy('name', 'asc')->get();
        
        $ticketLayout = 'common.ticket.includes.ticket-1';
        $ticketLayoutId = 1;
        if (isset($request->layout) && filter_var($request->layout, FILTER_VALIDATE_INT) !== false) {
            $ticketLayout = 'common.ticket.includes.ticket-' . $request->layout;
            $ticketLayoutId = $request->layout;

            if (!View::exists($ticketLayout)) {
                $ticketLayout = 'common.ticket.includes.ticket-1';
                $ticketLayoutId = 1;
            }
        }

        if (!hasPermission('ticket.multiLayout')) {
            $ticketLayout = 'common.ticket.includes.ticket-1';
            $ticketLayoutId = 1;
        }

        //dd($ticketLayout);
        return view('common.ticket.details', get_defined_vars());
    }

    // public function downloadPdf(Request $request, $id){
    //     $user = Auth::user();
    //     $editData = Ticket::with('flights', 'flights.transits', 'passengers', 'passengers.flights', 'fareSummary', 'user', 'user.company', 'creator')->where('id', $id)->first();
    //     if(empty($editData)){
    //         abort(404);
    //     }
    //     if($user->user_type != 'admin' && $editData->user_id != $user->business_id){
    //         abort(404);
    //     }

    //     // Pass data to the view
    //     $pdf = PDF::loadView('common.ticket.includes.invoice', compact('editData'));

    //     // Dynamic filename with datetime
    //     $filename = 'invoice_' . $editData->id . '_' . Carbon::now()->format('Ymd_His') . '.pdf';

    //     // Return the PDF download response
    //     return $pdf->download($filename);
    // }

    public function downloadPdf(Request $request, $id, PdfService $pdfService)
    {
        if (!hasPermission('ticket.show')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }
        
        $user = Auth::user();

        $editData = Ticket::with('flights', 'flights.transits', 'passengers', 'passengers.flights', 'fareSummary', 'user', 'user.company', 'creator')
            ->where('id', $id)
            ->first();
        
        $passenger = null;
        if(isset($request->passenger)){
            $passenger = $editData->passengers->firstWhere('id', $request->passenger);
            if (empty($passenger)) {
                abort(404);
            }
        }

        if (empty($editData)) {
            abort(404);
        }

        if ($user->user_type != 'admin' && $editData->user_id != $user->business_id) {
            abort(404);
        }

        // Render the Blade view to HTML string
        $withPrice = isset($request->withPrice) && $request->withPrice == 1 ? 1 : 0;
        $isTicket = isset($request->ticket) && $request->ticket == 1 ? 1 : 0;
        $isInvoice = isset($request->invoice) && $request->invoice == 1 ? 1 : 0;

        $ticketLayout = 'common.ticket.includes.ticket-1';
        $blade = 'common.ticket.includes.ticket-1';
        $ticketLayoutId = 1;
        if (isset($request->layout) && filter_var($request->layout, FILTER_VALIDATE_INT) !== false) {
            $ticketLayout = 'common.ticket.includes.ticket-' . $request->layout;
            $blade = 'common.ticket.includes.ticket-' . $request->layout;
            $ticketLayoutId = $request->layout;

            if (!View::exists($ticketLayout)) {
                $ticketLayout = 'common.ticket.includes.ticket-1';
                $blade = 'common.ticket.includes.ticket-1';
                $ticketLayoutId = 1;
            }
        }

        if (!hasPermission('ticket.multiLayout')) {
            $ticketLayout = 'common.ticket.includes.ticket-1';
            $blade = 'common.ticket.includes.ticket-1';
            $ticketLayoutId = 1;
        }

        if (isset($request->invoice) && $request->invoice == 1) {
            $blade = 'common.ticket.includes.invoice';
        }

        $html = view($blade, compact('editData', 'passenger', 'withPrice', 'isTicket', 'isInvoice'))->render();

        // Dynamic filename with datetime
        //$filename = 'invoice_' . $editData->id . '_' . Carbon::now()->format('Ymd_His') . '.pdf';
        
        $pdfType = isset(request()->ticket) && request()->ticket == 1 ? 'ticket' : 'invoice';
        if($pdfType == 'ticket'){
            $filename = 'Reservation-' . $editData->reservation_number . '.pdf';
        }else{
            $filename = 'Invoice-' . $editData->invoice_id . '.pdf';
        }

        // Generate and return the PDF inline in browser
        // If you want to force download, change 'I' to 'D' in Output()
        $IorD = env('UNDER_DEVELOPMENT') == true ? 'I' : 'D';
        return $pdfService->generatePdf($editData, $html, $filename, 'I', $pdfType);
    }

    public function duplicate($id)
    {
        if (!hasPermission('ticket.duplicate')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        $user = Auth::user();
        
        $editData = Ticket::with('flights', 'flights.transits', 'passengers', 'passengers.flights', 'fareSummary', 'user', 'user.company', 'creator')->where('id', $id)->first();
        if(empty($editData)){
            abort(404);
        }
        if($user->user_type != 'admin' && $editData->user_id != $user->business_id){
            abort(404);
        }

        DB::beginTransaction();
        try {
            $original = $editData;

            // Duplicate main ticket
            $newTicket = $original->replicate();
            $newTicket->reservation_number = $original->reservation_number . '-' . strtoupper(Str::random(4));
            $newTicket->invoice_id = generateInvoiceId();
            $newTicket->booking_status = 'On Hold';
            $newTicket->created_by = $user->id;
            $newTicket->updated_by = $user->id;
            $newTicket->created_at = now();
            $newTicket->updated_at = now();
            $newTicket->save();

            // Duplicate fare summaries (possibly multiple rows)
            foreach ($original->fareSummary as $fareSummary) {
                $newFare = $fareSummary->replicate();
                $newFare->ticket_id = $newTicket->id;
                $newFare->created_by = $user->id;
                $newFare->updated_by = $user->id;
                $newFare->save();
            }

            // Duplicate flights
            foreach ($original->flights as $flight) {
                $newFlight = $flight->replicate();
                $newFlight->ticket_id = $newTicket->id;
                $newFlight->created_by = $user->id;
                $newFlight->updated_by = $user->id;
                $newFlight->save();

                // Duplicate transits
                foreach ($flight->transits as $transit) {
                    $newTransit = $transit->replicate();
                    $newTransit->ticket_id = $newTicket->id;
                    $newTransit->parent_id = $newFlight->id;
                    $newTransit->created_by = $user->id;
                    $newTransit->updated_by = $user->id;
                    $newTransit->save();
                }
            }

            // Duplicate passengers and their flights
            foreach ($original->passengers as $passenger) {
                $newPassenger = $passenger->replicate();
                $newPassenger->ticket_id = $newTicket->id;
                $newPassenger->created_by = $user->id;
                $newPassenger->updated_by = $user->id;
                $newPassenger->save();

                foreach ($passenger->flights as $pf) {
                    $newPF = $pf->replicate();
                    $newPF->ticket_id = $newTicket->id;
                    $newPF->passenger_id = $newPassenger->id;
                    $newPF->created_by = $user->id;
                    $newPF->updated_by = $user->id;
                    $newPF->save();
                }
            }
            DB::commit();

            $alert = 'success';
            $message= getCurrentTranslation()['data_duplicated'] ?? 'data_duplicated';

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Ticket duplicate error', ['error' => $e->getMessage()]);

            $alert = 'error';
            $message= getCurrentTranslation()['something_went_wrong'] ?? 'something_went_wrong';
        }

        return redirect()->back()->withInput()->with($alert, $message);
    }

    public function edit($id)
    {
        if (!hasPermission('ticket.edit')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        $user = Auth::user();

        $listRoute = hasPermission('ticket.index') ? route('ticket.index') : '';
        $saveRoute = hasPermission('ticket.edit') ? route('ticket.update', $id) : '';

        $editData = Ticket::with('flights', 'flights.transits', 'passengers', 'passengers.flights', 'fareSummary', 'user', 'user.company', 'creator')->where('id', $id)->first();
        if(empty($editData)){
            abort(404);
        }
        if($user->user_type != 'admin' && $editData->user_id != $user->business_id){
            abort(404);
        }

        $airlines = Airline::where('status', 1)->orderBy('name', 'asc')->get();

        //dd($editData);
        $page = 'common.ticket.ticketAddEdit';
        if($editData->document_type == 'invoice'){
            $page = 'common.ticket.invoiceAddEdit';
        }
        if($editData->document_type == 'ticket'){
            $page = 'common.ticket.ticketAddEdit';
        }
        if($editData->document_type == 'quotation'){
            $page = 'common.ticket.quotationAddEdit';
        }
        return view($page, get_defined_vars());
    }

    public function update(Request $request, $id)
    {
        if (!hasPermission('ticket.edit')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        return $this->saveTicketData($request, $id);
    }

    public function destroy(Request $request, $id)
    {   
        if (!hasPermission('ticket.delete')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        $user = Auth::user();
        $ticket = Ticket::where('id', $id)->first();
        if(empty($ticket)){
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['data_not_found'] ?? 'data_not_found'
            ];
        }
        if($user->user_type != 'admin' && $ticket->user_id != $user->business_id){
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['data_not_found'] ?? 'data_not_found'
            ];
        }

        $forUserId = $ticket->user_id;
        $userId = Auth::user()->id;

        DB::beginTransaction();
        try {
            // Handle TicketFlight
            TicketFlight::where('user_id', $forUserId)->where('ticket_id', $ticket->id)
                ->each(function ($item) use ($userId) {
                    $item->deleted_by = $userId;
                    $item->save();
                    $item->delete();
                });

            // Handle TicketPassenger
            TicketPassenger::where('user_id', $forUserId)->where('ticket_id', $ticket->id)
                ->each(function ($item) use ($userId) {
                    $item->deleted_by = $userId;
                    $item->save();
                    $item->delete();
                });

            // Handle TicketPassenger
            TicketPassengerFlight::where('user_id', $forUserId)->where('ticket_id', $ticket->id)
                ->each(function ($item) use ($userId) {
                    $item->deleted_by = $userId;
                    $item->save();
                    $item->delete();
                });

            // Handle TicketFareSummary
            TicketFareSummary::where('user_id', $forUserId)->where('ticket_id', $ticket->id)
                ->each(function ($item) use ($userId) {
                    $item->deleted_by = $userId;
                    $item->save();
                    $item->delete();
                });

            // Handle the Ticket itself
            $ticket->deleted_by = $userId;
            $ticket->save();
            $ticket->delete();

            if (isset($request->delete_relational_data) && $request->delete_relational_data == 1) {
                $payments = Payment::with(
                    'ticket',
                    'paymentDocuments',
                    'introductionSource',
                    'country',
                    'issuedBy',
                    'airline',
                    'transferTo',
                    'paymentMethod',
                    'issuedCardType',
                    'cardOwner'
                )->where('ticket_id', $ticket->id)->get();

                if ($payments->count()) {
                    foreach ($payments as $paymentData) {
                        // Delete all related payment documents
                        if ($paymentData->paymentDocuments && $paymentData->paymentDocuments->count()) {
                            foreach ($paymentData->paymentDocuments as $docItem) {
                                deleteUploadedFile($docItem->file_url);
                                $docItem->delete();
                            }
                        }

                        // Track who deleted
                        $paymentData->deleted_by = $user->id;
                        $paymentData->save();

                        // Soft delete the payment
                        $paymentData->delete();
                    }
                }
            }

            DB::commit();

            return [
                'is_success' => 1,
                'icon' => 'success',
                'message' => getCurrentTranslation()['data_deleted'] ?? 'data_deleted'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['something_went_wrong'] ?? 'something_went_wrong',
                'error' => $e->getMessage()
            ];
        }
    }




    public function saveTicketData(Request $request, $id = null)
    {
        // $validator = Validator::make($request->all(), [
        //     'document_type' => 'nullable|in:ticket,invoice',
        //     'booking_type' => 'nullable|in:e-Booking,e-Ticket',
        //     'invoice_date' => 'nullable|date_format:Y-m-d',
        //     'invoice_id' => 'nullable|string|max:255|unique:tickets,invoice_id' . ($id ? ',' . $id . ',id' : ''),
        //     'reservation_number' => 'nullable|string|max:255',
        //     //'airlines_pnr' => 'nullable|string|max:255',
        //     'trip_type' => 'nullable|in:One Way,Round Trip,Multi City',
        //     'ticket_type' => 'nullable|in:Economy,Premium Economy,Business Class,First Class',
        //     'booking_status' => 'nullable|in:On Hold,Processing,Confirmed,Cancelled',

        //     'ticket_flight_info' => 'nullable|array',
        //     'ticket_flight_info.*.airline_id' => 'nullable|integer|exists:airlines,id',
        //     //'ticket_flight_info.*.flight_number' => 'nullable|string|max:255',
        //     'ticket_flight_info.*.leaving_from' => 'nullable|string|max:255',
        //     'ticket_flight_info.*.going_to' => 'nullable|string|max:255',
        //     'ticket_flight_info.*.departure_date_time' => 'nullable|date_format:'.$dateTimeFormat,
        //     'ticket_flight_info.*.arrival_date_time' => 'nullable|date_format:'.$dateTimeFormat,
        //     'ticket_flight_info.*.total_fly_time' => 'nullable|max:255',
        //     'ticket_flight_info.*.is_transit' => 'nullable|in:0,1',
            
        //     'ticket_flight_info.*.transit' => 'nullable|array',
        //     'ticket_flight_info.*.transit.*.airline_id' => 'nullable|integer|exists:airlines,id',
        //     'ticket_flight_info.*.transit.*.leaving_from' => 'nullable|string|max:255',
        //     'ticket_flight_info.*.transit.*.going_to' => 'nullable|string|max:255',
        //     'ticket_flight_info.*.transit.*.departure_date_time' => 'nullable|date_format:'.$dateTimeFormat,
        //     'ticket_flight_info.*.transit.*.arrival_date_time' => 'nullable|date_format:'.$dateTimeFormat,
        //     'ticket_flight_info.*.transit.*.total_fly_time' => 'nullable|max:255',
        //     'ticket_flight_info.*.transit.*.total_transit_time' => 'nullable|max:255',

        //     'passenger_info' => 'required|array',
        //     'passenger_info.*.name' => 'required|string|max:255',
        //     'passenger_info.*.pax_type' => 'required|in:Adult,Child,Infant',
        //     //'passenger_info.*.ticket_number' => 'nullable|string|max:255',
        //     'passenger_info.*.ticket_price' => 'nullable|numeric',
        //     'passenger_info.*.baggage_allowance' => 'nullable|string|max:500',

        //     'passenger_info.*.flight' => 'nullable|array',
        //     'passenger_info.*.flight.*.airlines_pnr' => 'required|string|max:255',
        //     'passenger_info.*.flight.*.flight_number' => 'nullable|string|max:255',
        //     'passenger_info.*.flight.*.ticket_number' => 'nullable|string|max:255',

        //     'fare_summary' => 'nullable|array',
        //     'fare_summary.*.pax_type' => 'nullable|in:Adult,Child,Infant',
        //     'fare_summary.*.unit_price' => 'nullable|numeric',
        //     'fare_summary.*.pax_count' => 'nullable|numeric',
        //     'fare_summary.*.total' => 'nullable|numeric',

        //     'bill_to' => 'nullable|string|max:255',
        //     'bill_to_info' => 'nullable|string|max:500',

        //     'footer_title' => 'nullable|string|max:255',
        //     'footer_text' => 'nullable|string',
        //     'bank_details' => 'nullable|string',
        // ]);


        $messages = getCurrentTranslation();
        $dateFormat = 'Y-m-d';
        $dateTimeFormat = 'Y-m-d H:i';
        $rules = [
            'document_type' => 'nullable|in:ticket,invoice,quotation',
            'booking_type' => 'nullable|in:e-Booking,e-Ticket',
            'invoice_date' => 'nullable|date_format:'.$dateFormat,
            'invoice_id' => 'nullable|string|max:100|unique:tickets,invoice_id' . ($id ? ',' . $id . ',id' : ''),
            'reservation_number' => 'nullable|string|max:100',
            //'airlines_pnr' => 'nullable|string|max:100',
            'trip_type' => 'nullable|in:One Way,Round Trip,Multi City',
            'ticket_type' => 'nullable|in:Economy,Premium Economy,Business Class,First Class',
            'booking_status' => 'nullable|in:On Hold,Processing,Confirmed,Cancelled',

            'ticket_flight_info' => 'nullable|array',
            'ticket_flight_info.*.airline_id' => 'nullable|integer|exists:airlines,id',
            //'ticket_flight_info.*.flight_number' => 'nullable|string|max:100',
            'ticket_flight_info.*.leaving_from' => 'nullable|string|max:100',
            'ticket_flight_info.*.going_to' => 'nullable|string|max:100',
            'ticket_flight_info.*.departure_date_time' => 'nullable|date_format:'.$dateTimeFormat,
            'ticket_flight_info.*.arrival_date_time' => 'nullable|date_format:'.$dateTimeFormat,
            'ticket_flight_info.*.total_fly_time' => 'nullable|max:100',
            'ticket_flight_info.*.is_transit' => 'nullable|in:0,1',
            
            'ticket_flight_info.*.transit' => 'nullable|array',
            'ticket_flight_info.*.transit.*.airline_id' => 'nullable|integer|exists:airlines,id',
            'ticket_flight_info.*.transit.*.leaving_from' => 'nullable|string|max:100',
            'ticket_flight_info.*.transit.*.going_to' => 'nullable|string|max:100',
            'ticket_flight_info.*.transit.*.departure_date_time' => 'nullable|date_format:'.$dateTimeFormat,
            'ticket_flight_info.*.transit.*.arrival_date_time' => 'nullable|date_format:'.$dateTimeFormat,
            'ticket_flight_info.*.transit.*.total_fly_time' => 'nullable|max:100',
            'ticket_flight_info.*.transit.*.total_transit_time' => 'nullable|max:100',

            'passenger_info' => 'nullable|array',
            'passenger_info.*.name' => 'nullable|string|max:100',
            'passenger_info.*.phone' => 'nullable|string|max:20',
            'passenger_info.*.email' => 'nullable|email|max:100',
            'passenger_info.*.pax_type' => 'nullable|in:Adult,Child,Infant',
            //'passenger_info.*.ticket_number' => 'nullable|string|max:100',
            'passenger_info.*.ticket_price' => 'nullable|numeric',
            'passenger_info.*.baggage_allowance' => 'nullable|string|max:500',

            'passenger_info.*.flight' => 'nullable|array',
            'passenger_info.*.flight.*.airlines_pnr' => 'nullable|string|max:100',
            'passenger_info.*.flight.*.flight_number' => 'nullable|string|max:100',
            'passenger_info.*.flight.*.ticket_number' => 'nullable|string|max:100',

            'fare_summary' => 'nullable|array',
            'fare_summary.*.pax_type' => 'nullable|in:Adult,Child,Infant',
            'fare_summary.*.unit_price' => 'nullable|numeric',
            'fare_summary.*.pax_count' => 'nullable|numeric',
            'fare_summary.*.total' => 'nullable|numeric',

            'bill_to' => 'nullable|string|max:100',
            'bill_to_info' => 'nullable|string|max:500',

            'footer_title' => 'nullable|string|max:100',
            'footer_text' => 'nullable|string',
            'bank_details' => 'nullable|string',
        ];

        $validator = Validator::make($request->all(), $rules, [
            'required' => $messages['required_message'] ?? 'This field is required.',
            'array' => $messages['array_message'] ?? 'This field must be an array.',
            'string' => $messages['string_message'] ?? 'This field must be a string.',
            'integer' => $messages['integer_message'] ?? 'This field must be an integer.',
            'numeric' => $messages['numeric_message'] ?? 'This field must be numeric.',
            'unique' => $messages['unique_message'] ?? 'This value has already been taken.',
            'exists' => $messages['exists_message'] ?? 'The selected value is invalid.',
            'date_format' => $messages['date_format_message'] ?? 'The date format is invalid.',
            'in' => $messages['in_message'] ?? 'The selected value is invalid.',
            
            // Max length messages
            'invoice_id.max' => ($messages['max_string_message'] ?? 'This field allowed maximum character length is: ') . '100',
            'reservation_number.max' => ($messages['max_string_message'] ?? 'This field allowed maximum character length is: ') . '100',
            'ticket_flight_info.*.leaving_from.max' => ($messages['max_string_message'] ?? 'This field allowed maximum character length is: ') . '100',
            'ticket_flight_info.*.going_to.max' => ($messages['max_string_message'] ?? 'This field allowed maximum character length is: ') . '100',
            'ticket_flight_info.*.total_fly_time.max' => ($messages['max_string_message'] ?? 'This field allowed maximum character length is: ') . '100',
            'ticket_flight_info.*.transit.*.leaving_from.max' => ($messages['max_string_message'] ?? 'This field allowed maximum character length is: ') . '100',
            'ticket_flight_info.*.transit.*.going_to.max' => ($messages['max_string_message'] ?? 'This field allowed maximum character length is: ') . '100',
            'ticket_flight_info.*.transit.*.total_fly_time.max' => ($messages['max_string_message'] ?? 'This field allowed maximum character length is: ') . '100',
            'ticket_flight_info.*.transit.*.total_transit_time.max' => ($messages['max_string_message'] ?? 'This field allowed maximum character length is: ') . '100',
            'passenger_info.*.name.max' => ($messages['max_string_message'] ?? 'This field allowed maximum character length is: ') . '100',
            'passenger_info.*.phone.max' => ($messages['max_string_message'] ?? 'This field allowed maximum character length is: ') . '20',
            'passenger_info.*.email.email' => ($messages['enter_valid_email_address'] ?? 'Please enter a valid email address.'),
            'passenger_info.*.email.max' => ($messages['max_string_message'] ?? 'This field allowed maximum character length is: ') . '20',
            'passenger_info.*.baggage_allowance.max' => ($messages['max_string_message'] ?? 'This field allowed maximum character length is: ') . '500',
            'passenger_info.*.flight.*.airlines_pnr.max' => ($messages['max_string_message'] ?? 'This field allowed maximum character length is: ') . '100',
            'passenger_info.*.flight.*.flight_number.max' => ($messages['max_string_message'] ?? 'This field allowed maximum character length is: ') . '100',
            'passenger_info.*.flight.*.ticket_number.max' => ($messages['max_string_message'] ?? 'This field allowed maximum character length is: ') . '100',
            'bill_to.max' => ($messages['max_string_message'] ?? 'This field allowed maximum character length is: ') . '100',
            'bill_to_info.max' => ($messages['max_string_message'] ?? 'This field allowed maximum character length is: ') . '500',
            'footer_title.max' => ($messages['max_string_message'] ?? 'This field allowed maximum character length is: ') . '100',
            
            // Specific validation messages for enums
            'document_type.in' => ($messages['in_message'] ?? 'The selected value is invalid.'),
            'booking_type.in' => ($messages['in_message'] ?? 'The selected value is invalid.'),
            'trip_type.in' => ($messages['in_message'] ?? 'The selected value is invalid.'),
            'ticket_type.in' => ($messages['in_message'] ?? 'The selected value is invalid.'),
            'booking_status.in' => ($messages['in_message'] ?? 'The selected value is invalid.'),
            'ticket_flight_info.*.is_transit.in' => ($messages['in_message'] ?? 'The selected value is invalid.'),
            'passenger_info.*.pax_type.in' => ($messages['in_message'] ?? 'The selected value is invalid.'),
            'fare_summary.*.pax_type.in' => ($messages['in_message'] ?? 'The selected value is invalid.'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'is_success' => 0,
                'icon' => 'error',
                'errors' => $validator->errors()
            ]);
        }

        if($request->document_type == 'ticket'){
            // $validator = Validator::make($request->all(), [
            //     'ticket_flight_info' => 'required|array',
            //     'ticket_flight_info.*.airline_id' => 'required|integer|exists:airlines,id',
            //     //'ticket_flight_info.*.flight_number' => 'required|string|max:255',
            //     'ticket_flight_info.*.leaving_from' => 'required|string|max:255',
            //     'ticket_flight_info.*.going_to' => 'required|string|max:255',
            //     'ticket_flight_info.*.departure_date_time' => 'required|date_format:'.$dateTimeFormat,
            //     'ticket_flight_info.*.arrival_date_time' => 'required|date_format:'.$dateTimeFormat,
            //     'ticket_flight_info.*.total_fly_time' => 'required|max:255',
            //     'ticket_flight_info.*.is_transit' => 'nullable|in:0,1',
            // ]);


            $messages = getCurrentTranslation();
            $rules = [
                'ticket_flight_info' => 'required|array',
                'ticket_flight_info.*.airline_id' => 'required|integer|exists:airlines,id',
                //'ticket_flight_info.*.flight_number' => 'required|string|max:100',
                'ticket_flight_info.*.leaving_from' => 'required|string|max:100',
                'ticket_flight_info.*.going_to' => 'required|string|max:100',
                'ticket_flight_info.*.departure_date_time' => 'required|date_format:'.$dateTimeFormat,
                'ticket_flight_info.*.arrival_date_time' => 'required|date_format:'.$dateTimeFormat,
                'ticket_flight_info.*.total_fly_time' => 'required|max:100',
                'ticket_flight_info.*.is_transit' => 'nullable|in:0,1',

                'passenger_info' => 'required|array',
                'passenger_info.*.name' => 'required|string|max:100',
                'passenger_info.*.phone' => 'nullable|string|max:20',
                'passenger_info.*.email' => 'nullable|email|max:100',
                'passenger_info.*.pax_type' => 'required|in:Adult,Child,Infant',
                //'passenger_info.*.ticket_number' => 'nullable|string|max:100',
                'passenger_info.*.ticket_price' => 'nullable|numeric',
                'passenger_info.*.baggage_allowance' => 'nullable|string|max:500',

                'passenger_info.*.flight' => 'nullable|array',
                'passenger_info.*.flight.*.airlines_pnr' => 'required|string|max:100',
                'passenger_info.*.flight.*.flight_number' => 'nullable|string|max:100',
                'passenger_info.*.flight.*.ticket_number' => 'nullable|string|max:100',
            ];

            $validator = Validator::make($request->all(), $rules, [
                'required' => $messages['required_message'] ?? 'This field is required.',
                'array' => $messages['array_message'] ?? 'This field must be an array.',
                'string' => $messages['string_message'] ?? 'This field must be a string.',
                'integer' => $messages['integer_message'] ?? 'This field must be an integer.',
                'exists' => $messages['exists_message'] ?? 'The selected value is invalid.',
                'date_format' => $messages['date_format_message'] ?? 'The selected date format is invalid.',
                'in' => $messages['in_message'] ?? 'The selected value is invalid.',
                
                // Max length messages
                'ticket_flight_info.*.leaving_from.max' => ($messages['max_string_message'] ?? 'This field allowed maximum character length is: ') . '100',
                'ticket_flight_info.*.going_to.max' => ($messages['max_string_message'] ?? 'This field allowed maximum character length is: ') . '100',
                'ticket_flight_info.*.total_fly_time.max' => ($messages['max_string_message'] ?? 'This field allowed maximum character length is: ') . '100',
                
                // Specific validation messages
                'ticket_flight_info.required' => $messages['ticket_flight_info_required'] ?? 'Flight information is required.',
                'ticket_flight_info.array' => $messages['ticket_flight_info_array'] ?? 'Flight information must be an array.',
                'ticket_flight_info.*.airline_id.required' => $messages['airline_required'] ?? 'Airline is required for each flight.',
                'ticket_flight_info.*.airline_id.exists' => $messages['airline_exists'] ?? 'Selected airline does not exist.',
                'ticket_flight_info.*.leaving_from.required' => $messages['departure_location_required'] ?? 'Departure location is required for each flight.',
                'ticket_flight_info.*.going_to.required' => $messages['arrival_location_required'] ?? 'Arrival location is required for each flight.',
                'ticket_flight_info.*.departure_date_time.required' => $messages['departure_time_required'] ?? 'Departure date and time is required for each flight.',
                'ticket_flight_info.*.departure_date_time.date_format' => ($messages['departure_time_format'] ?? 'Departure date and time format is: ').$dateTimeFormat,
                'ticket_flight_info.*.arrival_date_time.required' => $messages['arrival_time_required'] ?? 'Arrival date and time is required for each flight.',
                'ticket_flight_info.*.arrival_date_time.date_format' => ($messages['arrival_time_format'] ?? 'Arrival date and time format is: ').$dateTimeFormat,
                'ticket_flight_info.*.total_fly_time.required' => $messages['fly_time_required'] ?? 'Total fly time is required for each flight.',
                'ticket_flight_info.*.is_transit.in' => ($messages['in_message'] ?? 'The selected value is invalid.') . ' Allowed values: 0, 1.',

                'passenger_info.*.name.max' => ($messages['max_string_message'] ?? 'This field allowed maximum character length is: ') . '100',
                'passenger_info.*.phone.max' => ($messages['max_string_message'] ?? 'This field allowed maximum character length is: ') . '20',
                'passenger_info.*.email.email' => ($messages['enter_valid_email_address'] ?? 'Please enter a valid email address.'),
                'passenger_info.*.email.max' => ($messages['max_string_message'] ?? 'This field allowed maximum character length is: ') . '100',
                'passenger_info.*.baggage_allowance.max' => ($messages['max_string_message'] ?? 'This field allowed maximum character length is: ') . '500',
                'passenger_info.*.flight.*.airlines_pnr.max' => ($messages['max_string_message'] ?? 'This field allowed maximum character length is: ') . '100',
                'passenger_info.*.flight.*.flight_number.max' => ($messages['max_string_message'] ?? 'This field allowed maximum character length is: ') . '100',
                'passenger_info.*.flight.*.ticket_number.max' => ($messages['max_string_message'] ?? 'This field allowed maximum character length is: ') . '100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'is_success' => 0,
                    'icon' => 'error',
                    'errors' => $validator->errors()
                ]);
            }
        }

        //dd($request->all());

        $user = Auth::user();
        $ticket = null;
        if (isset($id)) {
            $ticket = Ticket::where('id', $id)->first();
            if(empty($ticket)){
                return [
                    'is_success' => 0,
                    'icon' => 'error',
                    'message' => getCurrentTranslation()['data_not_found'] ?? 'data_not_found'
                ];
            }
            if($user->user_type != 'admin' && $ticket->user_id != $user->business_id){
                return [
                    'is_success' => 0,
                    'icon' => 'error',
                    'message' => getCurrentTranslation()['data_not_found'] ?? 'data_not_found'
                ];
            }
        }


        // DB::beginTransaction();
        // try {

            $queryForUserId = $ticket->user_id ?? Auth::user()->business_id;
            $userId = $ticket->user_id ?? Auth::user()->business_id;

            if (empty($ticket)) {
                $ticket = new Ticket();
                $ticket->created_by = Auth::id();
                $ticket->document_type = $request->document_type ?? 'ticket';
            }else{
                $ticket->updated_by = Auth::id();
            }

            $ticket->booking_type = $request->booking_type ?? 'e-Ticket';
            $ticket->user_id = $userId;
            $ticket->invoice_date = $request->invoice_date ?? null;
            $ticket->invoice_id = $request->invoice_id ?? null;
            $ticket->reservation_number = $request->reservation_number ?? null;
            //$ticket->airlines_pnr = $request->airlines_pnr ?? null;
            $ticket->trip_type = $request->trip_type ?? null;
            $ticket->ticket_type = $request->ticket_type ?? null;
            $ticket->booking_status = $request->booking_status ?? null;

            $ticket->bill_to = $request->bill_to ?? null;
            $ticket->bill_to_info = $request->bill_to_info ?? null;
            
            $ticket->footer_title = $request->footer_title ?? null;
            $ticket->footer_text = $request->footer_text ?? null;
            $ticket->bank_details = $request->bank_details ?? null;

            $ticket->save();

            // TicketFlight
            $flight_ids = [];
            $transit_ids = [];
            if (isset($request->ticket_flight_info) && !empty($request->ticket_flight_info)) {
                foreach ($request->ticket_flight_info as $flight) {
                    if(isArrayNotEmpty($flight) == true){
                        $ticketFlight = null;

                        if (isset($flight['flight_id']) && !empty($flight['flight_id'])) {
                            $ticketFlight = TicketFlight::where('user_id', $queryForUserId)
                                                            ->where('ticket_id', $ticket->id)
                                                            ->where('id', $flight['flight_id'])
                                                            ->first();
                        }

                        if (empty($ticketFlight)) {
                            $ticketFlight = new TicketFlight();
                            $ticketFlight->created_by = Auth::id();
                        }else{
                            $ticketFlight->updated_by = Auth::id();
                        }

                        $ticketFlight->user_id = $userId;
                        $ticketFlight->ticket_id = $ticket->id;
                        $ticketFlight->airline_id = $flight['airline_id'] ?? null;
                        $ticketFlight->flight_number = $flight['flight_number'] ?? null;
                        $ticketFlight->leaving_from = $flight['leaving_from'] ?? null;
                        $ticketFlight->going_to = $flight['going_to'] ?? null;
                        $ticketFlight->departure_date_time = $flight['departure_date_time'] ?? null;
                        $ticketFlight->arrival_date_time = $flight['arrival_date_time'] ?? null;
                        $ticketFlight->total_fly_time = $flight['total_fly_time'] ?? null;
                        $ticketFlight->total_transit_time = $flight['total_transit_time'] ?? null;
                        $ticketFlight->is_transit = $flight['is_transit'] ?? 0;
                        $ticketFlight->save();

                        $flight_ids[] = $ticketFlight->id;

                        if (isset($flight['is_transit']) && $flight['is_transit'] == 1 && isset($flight['transit']) && !empty($flight['transit'])) {
                            foreach ($flight['transit'] as $transit) {
                                if(isArrayNotEmpty($transit) == true){
                                    $ticketFlightTransit = null;

                                    if (isset($transit['flight_id']) && !empty($transit['flight_id'])) {
                                        $ticketFlightTransit = TicketFlight::where('user_id', $queryForUserId)
                                                            ->where('ticket_id', $ticket->id)
                                                            ->where('id', $transit['flight_id'])
                                                            ->first();
                                    }

                                    if (empty($ticketFlightTransit)) {
                                        $ticketFlightTransit = new TicketFlight();
                                        $ticketFlightTransit->created_by = Auth::id();
                                    }else{
                                        $ticketFlightTransit->updated_by = Auth::id();
                                    }

                                    $ticketFlightTransit->user_id = $userId;
                                    $ticketFlightTransit->ticket_id = $ticket->id;
                                    $ticketFlightTransit->parent_id = $ticketFlight->id;
                                    $ticketFlightTransit->airline_id = $transit['airline_id'] ?? null;
                                    $ticketFlightTransit->flight_number = $transit['flight_number'] ?? null;
                                    $ticketFlightTransit->leaving_from = $transit['leaving_from'] ?? null;
                                    $ticketFlightTransit->going_to = $transit['going_to'] ?? null;
                                    $ticketFlightTransit->departure_date_time = $transit['departure_date_time'] ?? null;
                                    $ticketFlightTransit->arrival_date_time = $transit['arrival_date_time'] ?? null;
                                    $ticketFlightTransit->total_fly_time = $transit['total_fly_time'] ?? null;
                                    $ticketFlightTransit->total_transit_time = $transit['total_transit_time'] ?? null;
                                    $ticketFlightTransit->is_transit = $transit['is_transit'] ?? 0;
                                    $ticketFlightTransit->save();

                                    $transit_ids[] = $ticketFlightTransit->id;
                                }
                            }
                        }
                    }
                }
            }

            $all_flight_ids = array_merge($flight_ids, $transit_ids);
            // Remove TicketFlight not included in the request
            $flightToDelete = TicketFlight::where('user_id', $queryForUserId)
                        ->where('ticket_id', $ticket->id)
                        ->whereNotIn('id', $all_flight_ids)
                        ->get();
            foreach ($flightToDelete as $flight) {
                $flight->deleted_by = Auth::id();
                $flight->save();
                $flight->delete(); // use delete() if using SoftDeletes
            }


            // TicketPassenger
            $passengers_ids = [];
            $passenger_flight_ids = [];

            if (isset($request->passenger_info) && !empty($request->passenger_info)) {
                foreach ($request->passenger_info as $passenger) {
                    if (isArrayNotEmpty($passenger) == true && isset($passenger['name']) && !empty($passenger['name'])) {
                        $ticketPassenger = null;

                        if (isset($passenger['passenger_id']) && !empty($passenger['passenger_id'])) {
                            $ticketPassenger = TicketPassenger::where('user_id', $queryForUserId)
                                                              ->where('ticket_id', $ticket->id)
                                                              ->where('id', $passenger['passenger_id'])
                                                              ->first();
                        }

                        if (empty($ticketPassenger)) {
                            $ticketPassenger = new TicketPassenger();
                            $ticketPassenger->created_by = Auth::id();
                        } else {
                            $ticketPassenger->updated_by = Auth::id();
                        }

                        $ticketPassenger->user_id = $userId;
                        $ticketPassenger->ticket_id = $ticket->id;
                        $ticketPassenger->name = $passenger['name'] ?? null;
                        $ticketPassenger->phone = $passenger['phone'] ?? null;
                        $ticketPassenger->email = $passenger['email'] ?? null;
                        $ticketPassenger->pax_type = $passenger['pax_type'] ?? null;
                        //$ticketPassenger->ticket_number = $passenger['ticket_number'] ?? null;
                        $ticketPassenger->ticket_price = $passenger['ticket_price'] ?? null;
                        $ticketPassenger->baggage_allowance = $passenger['baggage_allowance'] ?? null;
                        $ticketPassenger->save();

                        $passengers_ids[] = $ticketPassenger->id;

                        if (isset($passenger['flight']) && !empty($passenger['flight'])) {
                            foreach ($passenger['flight'] as $flight) {
                                if (isArrayNotEmpty($flight) == true) {
                                    $ticketPassengerFlight = null;

                                    if (isset($flight['passenger_flight_id']) && !empty($flight['passenger_flight_id'])) {
                                        $ticketPassengerFlight = TicketPassengerFlight::where('user_id', $queryForUserId)
                                                ->where('ticket_id', $ticket->id)
                                                ->where('passenger_id', $ticketPassenger->id)
                                                ->where('id', $flight['passenger_flight_id'])
                                                ->first();
                                    }

                                    if (empty($ticketPassengerFlight)) {
                                        $ticketPassengerFlight = new TicketPassengerFlight();
                                        $ticketPassengerFlight->created_by = Auth::id();
                                    } else {
                                        $ticketPassengerFlight->updated_by = Auth::id();
                                    }

                                    $ticketPassengerFlight->user_id = $userId;
                                    $ticketPassengerFlight->ticket_id = $ticket->id;
                                    $ticketPassengerFlight->passenger_id = $ticketPassenger->id;
                                    $ticketPassengerFlight->airlines_pnr = $flight['airlines_pnr'] ?? null;
                                    $ticketPassengerFlight->flight_number = $flight['flight_number'] ?? null;
                                    $ticketPassengerFlight->ticket_number = $flight['ticket_number'] ?? null;
                                    $ticketPassengerFlight->save();

                                    $passenger_flight_ids[] = $ticketPassengerFlight->id;
                                }
                            }
                        }
                    }
                }
            }

            // Remove TicketPassenger not included in the request
            $passengersToDelete = TicketPassenger::where('user_id', $queryForUserId)
                ->where('ticket_id', $ticket->id)
                ->whereNotIn('id', $passengers_ids)
                ->get();

            foreach ($passengersToDelete as $passenger) {
                $passenger->deleted_by = Auth::id();
                $passenger->save();
                $passenger->delete(); // use delete() if using SoftDeletes
            }

            // Remove TicketPassengerFlight not included in the request
            $passengerFlightsToDelete = TicketPassengerFlight::where('user_id', $queryForUserId)
                ->where('ticket_id', $ticket->id)
                ->whereNotIn('id', $passenger_flight_ids)
                ->get();

            foreach ($passengerFlightsToDelete as $flight) {
                $flight->deleted_by = Auth::id();
                $flight->save();
                $flight->delete(); // use delete() if using SoftDeletes
            }


            // Fare summary
            $fare_summary_ids = [];
            if (isset($request->fare_summary) && !empty($request->fare_summary)) {
                foreach ($request->fare_summary as $fare) {
                    if(isNotEmptyFare($fare) == true){
                        $ticketFareSummary = null;
                        if (isset($fare['fare_summary_id']) && !empty($fare['fare_summary_id'])) {
                            $ticketFareSummary = TicketFareSummary::where('user_id', $queryForUserId)
                                                            ->where('ticket_id', $ticket->id)
                                                            ->where('id', $fare['fare_summary_id'])
                                                            ->first();
                        }
                        if (empty($ticketFareSummary)) {
                            $ticketFareSummary = new TicketFareSummary();
                            $ticketFareSummary->created_by = Auth::id();
                        }else{
                            $ticketFareSummary->updated_by = Auth::id();
                        }

                        $ticketFareSummary->user_id = $userId;
                        $ticketFareSummary->ticket_id = $ticket->id;
                        $ticketFareSummary->pax_type = $fare['pax_type'] ?? null;
                        $ticketFareSummary->unit_price = $fare['unit_price'] ?? 0;
                        $ticketFareSummary->pax_count = $fare['pax_count'] ?? 0;
                        $ticketFareSummary->total = $fare['total'] ?? 0;
                        $ticketFareSummary->subtotal = $request->subtotal ?? 0;
                        $ticketFareSummary->discount = $request->discount ?? 0;
                        $ticketFareSummary->grandtotal = $request->grandtotal ?? 0;
                        $ticketFareSummary->save();

                        $fare_summary_ids[] = $ticketFareSummary->id;
                    }
                }
            }
            // Remove TicketFareSummary not included in the request
            $fareSummaryToDelete = TicketFareSummary::where('user_id', $queryForUserId)
                    ->where('ticket_id', $ticket->id)
                    ->whereNotIn('id', $fare_summary_ids)
                    ->get();
            foreach ($fareSummaryToDelete as $fare) {
                $fare->deleted_by = Auth::id();
                $fare->save();
                $fare->delete(); // use delete() if using SoftDeletes
            }


            // ✅ For Existing Payment Data Update
            $ticketData = Ticket::with([
                'flights',
                'flights.transits',
                'passengers',
                'passengers.flights',
                'fareSummary',
                'user',
                'user.company',
                'creator'
            ])->where('id', $ticket->id)->first();

            $ticketPassengers = TicketPassenger::where('ticket_id', $ticket->id)->first();

            $invoiceDate = $ticketData->invoice_date ?? null;
            $tripType = $ticketData->trip_type ?? null;
            $airlineId = optional($ticketData->flights->first())->airline_id;

            $departureCity = $ticketData->departure_city ?? null;
            $destinationCity = $ticketData->destination_city ?? null;
            $flightRoute = $ticketData->flight_route ?? null;
            $departureDateTime = $ticketData->departure_datetime ?? null;
            $returnDateTime = $ticketData->return_datetime ?? null;

            $customerName  = optional($ticketData->passengers->first())->name;
            $customerEmail = optional($ticketData->passengers->first())->email;
            $customerPhone = optional($ticketData->passengers->first())->phone;

            $paymentData = Payment::where('ticket_id', $ticket->id)->get();

            foreach ($paymentData as $payment) {
                $payment->invoice_date = $invoiceDate;
                $payment->ticket_id = $ticket->id;
                $payment->client_name = $customerName;
                $payment->client_phone = $customerPhone;
                $payment->client_email = $customerEmail;
                $payment->trip_type = $tripType;
                $payment->departure_date_time = $departureDateTime;
                $payment->return_date_time = $returnDateTime;
                $payment->departure = $departureCity;
                $payment->destination = $destinationCity;
                $payment->flight_route = $flightRoute;
                $payment->airline_id = $airlineId;
                $payment->save();
            }

            // foreach ($paymentData as $payment) {
            //     $payment->invoice_date = $payment->invoice_date ?: $invoiceDate;
            //     $payment->ticket_id = $payment->ticket_id ?: $ticket->id;
            //     $payment->client_name = $payment->client_name ?: $customerName;
            //     $payment->client_phone = $payment->client_phone ?: $customerPhone;
            //     $payment->client_email = $payment->client_email ?: $customerEmail;
            //     $payment->trip_type = $payment->trip_type ?: $tripType;
            //     $payment->departure_date_time = $payment->departure_date_time ?: $departureDateTime;
            //     $payment->return_date_time = $payment->return_date_time ?: $returnDateTime;
            //     $payment->departure = $payment->departure ?: $departureCity;
            //     $payment->destination = $payment->destination ?: $destinationCity;
            //     $payment->flight_route = $payment->flight_route ?: $flightRoute;
            //     $payment->airline_id = $payment->airline_id ?: $airlineId;
            //     $payment->save();
            // }




            DB::commit();
            return [
                'is_success' => 1,
                'icon' => 'success',
                'message' => getCurrentTranslation()['data_saved'] ?? 'data_saved'
            ];
        // } catch (\Exception $e) {
        //     DB::rollBack();
        //     \Log::error('Ticket store error', ['error' => $e->getMessage()]);

        //     return [
        //         'is_success' => 0,
        //         'icon' => 'error',
        //         'message' => getCurrentTranslation()['data_saving_error'] ?? 'data_saving_error'
        //     ];
        // }
    }







    public function mail($id)
    {
        if (!hasPermission('ticket.mail')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        $user = Auth::user();

        $listRoute = hasPermission('ticket.index') ? route('ticket.index') : '';
        $saveRoute = hasPermission('ticket.edit') ? route('ticket.update', $id) : '';

        $editData = Ticket::with('flights', 'flights.transits', 'passengers', 'passengers.flights', 'fareSummary', 'user', 'user.company', 'creator')->where('id', $id)->first();
        if(empty($editData)){
            abort(404);
        }
        if($user->user_type != 'admin' && $editData->user_id != $user->business_id){
            abort(404);
        }

        //$airlines = Airline::where('status', 1)->orderBy('name', 'asc')->get();

        //dd($editData);
        return view('common.ticket.sendMailForm', get_defined_vars());
    }


    public function mailContentLoad(Request $request, $id)
    {
        $user = Auth::user();
        
        //dd($request->all());

        
        $messages = getCurrentTranslation();
        $dateFormat = 'Y-m-d';
        $dateTimeFormat = 'Y-m-d H:i';

        // Base rules for passengers array and id
        $rules = [
            'passengers' => 'nullable|array',
            'passengers.*.id' => 'nullable|integer|exists:ticket_passengers,id',
            'passengers.*.email' => 'nullable|email|max:100',
        ];

        $validator = Validator::make($request->all(), $rules, [
            'required' => $messages['required_message'] ?? 'This field is required.',
            'array' => $messages['array_message'] ?? 'This field must be an array.',
            'string' => $messages['string_message'] ?? 'This field must be a string.',
            'integer' => $messages['integer_message'] ?? 'This field must be an integer.',
            'numeric' => $messages['numeric_message'] ?? 'This field must be numeric.',
            'unique' => $messages['unique_message'] ?? 'This value has already been taken.',
            'exists' => $messages['exists_message'] ?? 'The selected value is invalid.',
            'date_format' => $messages['date_format_message'] ?? 'The date format is invalid.',
            'in' => $messages['in_message'] ?? 'The selected value is invalid.',

            // Max length messages
            'passengers.*.email.email' => ($messages['enter_valid_email_address'] ?? 'Please enter a valid email address.'),
            'passengers.*.email.max' => ($messages['max_string_message'] ?? 'This field allowed maximum character length is: ') . '100',
        ]);

        // $validator->after(function ($validator) use ($request, $messages) {
        //     if (isset($request->passengers) && is_array($request->passengers)) {
        //         foreach ($request->passengers as $index => $passenger) {
        //             if (isset($passenger['id'])) {
        //                 // Check required
        //                 if (empty($passenger['email'])) {
        //                     $validator->errors()->add(
        //                         "passengers.$index.email",
        //                         $messages['required_message'] ?? 'This field is required.'
        //                     );
        //                 }
        //                 // // Check format if not empty
        //                 // elseif (!filter_var($passenger['email'], FILTER_VALIDATE_EMAIL)) {
        //                 //     $validator->errors()->add(
        //                 //         "passengers.$index.email",
        //                 //         $messages['enter_valid_email_address'] ?? 'enter_valid_email_address'
        //                 //     );
        //                 // }
        //             }
        //         }
        //     }
        // });

        if ($validator->fails()) {
            return response()->json([
                'is_success' => 0,
                'icon' => 'error',
                'errors' => $validator->errors()
            ]);
        }

        $passengers = $request->input('passengers', []);
        $filtered = array_values(array_filter($passengers, fn($p) => isset($p['id']) && $p['id'] !== ''));
        $request->merge(['passengers' => $filtered]);

        $passengers = $request->input('passengers', []);

        $passengerIds = array_column(
            array_filter($passengers, fn($p) => isset($p['id']) && $p['id'] !== ''),
            'id'
        );

        $mailData = Ticket::with('flights', 'flights.transits', 'passengers', 'passengers.flights', 'fareSummary', 'user', 'user.company', 'creator')->where('id', $id)->first();

        if(empty($mailData)){
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['data_not_found'] ?? 'data_not_found'
            ];
        }
        if($user->user_type != 'admin' && $mailData->user_id != $user->business_id){
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['data_not_found'] ?? 'data_not_found'
            ];
        }
        
        $passengers = TicketPassenger::where('ticket_id', $id)->whereIn('id', $passengerIds)->get();
        
        $viewData = view('common.ticket.sendMailContent', get_defined_vars())->render();

        return [
            'is_success' => 1,
            'icon' => 'success',
            'message' => getCurrentTranslation()['mail_content_updated'] ?? 'mail_content_updated',
            'mail_content' => $viewData
        ];
        //dd($mailData);
        //dd($passengers);
    }


    public function mailSend(Request $request, $id, PdfService $pdfService)
    {
        $user = Auth::user();
        
        //dd($request->all());

        
        $messages = getCurrentTranslation();
        $dateFormat = 'Y-m-d';
        $dateTimeFormat = 'Y-m-d H:i';

        // Base rules for passengers array and id
        $rules = [
            'passengers' => 'nullable|array',
            'passengers.*.id' => 'nullable|integer|exists:ticket_passengers,id',
            'passengers.*.email' => 'nullable|email|max:100',
            'ticket_layout' => 'required|in:1,2,3,4,5',
        ];

        $validator = Validator::make($request->all(), $rules, [
            'required' => $messages['required_message'] ?? 'This field is required.',
            'array' => $messages['array_message'] ?? 'This field must be an array.',
            'string' => $messages['string_message'] ?? 'This field must be a string.',
            'integer' => $messages['integer_message'] ?? 'This field must be an integer.',
            'numeric' => $messages['numeric_message'] ?? 'This field must be numeric.',
            'unique' => $messages['unique_message'] ?? 'This value has already been taken.',
            'exists' => $messages['exists_message'] ?? 'The selected value is invalid.',
            'date_format' => $messages['date_format_message'] ?? 'The date format is invalid.',
            'in' => $messages['in_message'] ?? 'The selected value is invalid.',

            // Max length messages
            'passengers.*.email.email' => ($messages['enter_valid_email_address'] ?? 'Please enter a valid email address.'),
            'passengers.*.email.max' => ($messages['max_string_message'] ?? 'This field allowed maximum character length is: ') . '100',
        ]);

        // Custom rule: At least one passenger with both id and email
        $validator->after(function ($validator) use ($request, $messages) {
            $hasValidPassenger = false;

            if (isset($request->passengers) && is_array($request->passengers)) {
                foreach ($request->passengers as $passenger) {
                    if (!empty($passenger['id']) && !empty($passenger['email'])) {
                        $hasValidPassenger = true;
                        break;
                    }
                }
            }

            if (!$hasValidPassenger) {
                // Attach error under first passenger's email field
                $validator->errors()->add(
                    'passengers.0.email',
                    $messages['at_least_one_passenger_and_mail_required'] ?? 'at_least_one_passenger_and_mail_required'
                );
            }
        });


        if ($validator->fails()) {
            return response()->json([
                'is_success' => 0,
                'icon' => 'error',
                'errors' => $validator->errors()
            ]);
        }

        $passengers = $request->input('passengers', []);
        $filtered = array_values(array_filter($passengers, fn($p) => isset($p['id']) && $p['id'] !== ''));
        $request->merge(['passengers' => $filtered]);

        $passengers = $request->input('passengers', []);

        $passengerIds = array_column(
            array_filter($passengers, fn($p) => isset($p['id']) && $p['id'] !== ''),
            'id'
        );

        $mailData = Ticket::where('id', $id)->first();

        if(empty($mailData)){
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['data_not_found'] ?? 'data_not_found'
            ];
        }
        if($user->user_type != 'admin' && $mailData->user_id != $user->business_id){
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['data_not_found'] ?? 'data_not_found'
            ];
        }

        $totalMail = 0;
        foreach($passengers as $item){
            $passengerModel = TicketPassenger::where('ticket_id', $id)->where('id', $item['id'])->first();
            $passengerModel->email = $item['email'];
            $passengerModel->save();
        }

        $mailData = Ticket::with('flights', 'flights.transits', 'passengers', 'passengers.flights', 'fareSummary', 'user', 'user.company', 'creator')->where('id', $id)->first();
        $editData = $mailData;
        
        $passengers = TicketPassenger::where('ticket_id', $id)->whereIn('id', $passengerIds)->get();
        $attachPassengers = 1; 
        foreach ($passengers as $key => $passengerItem) {
            $invoice_passengers = (isset($request->send_individually)) && $request->send_individually == 1 ? collect([$passengerItem]) : $passengers;
            $ticket_passengers = (isset($request->send_individually)) && $request->send_individually == 1 ? collect([$passengerItem]) : $passengers;

            $passengers = (isset($request->send_individually)) && $request->send_individually == 1 ? collect([$passengerItem]) : $passengers;
            //$mailContent = view('common.ticket.sendMailContent', get_defined_vars())->render();
            $mailContent = $request->mail_content;
            $getPassengers = getPassengerDataForMail($passengers);
            $mailContent = str_replace('{passenger_automatic_name_here}', $passengerItem->name ?? 'N/A', $mailContent);
            $mailContent = str_replace('{passenger_automatic_data_here}', $getPassengers, $mailContent);


            $attachments = []; // Store file paths for this passenger

            // ---------------- Generate Ticket PDF ----------------
            if (isset($request->document_type_ticket) && $request->document_type_ticket == 1) {
                $passenger = (isset($request->send_individually)) && $request->send_individually == 1 ? $passengerItem : null;
                $withPrice = isset($request->ticket_with_price) && $request->ticket_with_price == 1 ? 1 : 0;
                $isTicket = 1;
                $isInvoice = 0;

                $ticketLayout = 'common.ticket.includes.ticket-1';
                $ticketLayoutId = 1;
                if (isset($request->ticket_layout) && filter_var($request->ticket_layout, FILTER_VALIDATE_INT) !== false) {
                    $ticketLayout = 'common.ticket.includes.ticket-' . $request->ticket_layout;
                    $ticketLayoutId = $request->ticket_layout;

                    if (!View::exists($ticketLayout)) {
                        $ticketLayout = 'common.ticket.includes.ticket-1';
                        $ticketLayoutId = 1;
                    }
                }

                if (!hasPermission('ticket.multiLayout')) {
                    $ticketLayout = 'common.ticket.includes.ticket-1';
                    $ticketLayoutId = 1;
                }

                $html = view($ticketLayout, compact('editData', 'passenger', 'ticket_passengers', 'withPrice', 'isTicket', 'isInvoice'))->render();

                // Generate safe filename
                $rawFilename = 'Reservation-' . $editData->reservation_number . '.pdf';
                $filename = preg_replace('/[\/\\\\?%*:|"<>]/', '_', $rawFilename);

                // Ensure temp_pdfs directory exists
                if (!file_exists(public_path('temp_pdfs'))) {
                    mkdir(public_path('temp_pdfs'), 0777, true);
                }

                $filePath = public_path('temp_pdfs/' . $filename);

                $pdfService->generatePdf($editData, $html, $filePath, 'F', 'ticket'); 
                $attachments[] = $filePath;
            }

            // ---------------- Generate Invoice PDF ----------------
            if (isset($request->document_type_invoice) && $request->document_type_invoice == 1) {
                $editData = Ticket::with('flights', 'flights.transits', 'passengers', 'passengers.flights', 'fareSummary', 'user', 'user.company', 'creator')->where('id', $id)->first();
                $withPrice = isset($request->invoice_with_price) && $request->invoice_with_price == 1 ? 1 : 0;
                $isTicket = 0;
                $isInvoice = 1;
                $blade = 'common.ticket.includes.invoice';
                $html = view($blade, compact('editData', 'invoice_passengers', 'withPrice', 'isTicket', 'isInvoice'))->render();

                // Generate safe filename
                $rawFilename = 'Invoice-' . $editData->invoice_id . '.pdf';
                $filename = preg_replace('/[\/\\\\?%*:|"<>]/', '_', $rawFilename);

                // Ensure temp_pdfs directory exists
                if (!file_exists(public_path('temp_pdfs'))) {
                    mkdir(public_path('temp_pdfs'), 0777, true);
                }

                $filePath = public_path('temp_pdfs/' . $filename);

                $pdfService->generatePdf($editData, $html, $filePath, 'F', 'invoice');
                $attachments[] = $filePath;
            }


            // ---------------- Send Email ----------------
            $mailPayload = [
                'passengers'  => $ticket_passengers,
                'mailData'    => $mailData,
                'mailContent' => $mailContent,
            ];

            $filesToAttach = !empty($attachments) ? $attachments : [];
            
            try {
                if (!empty($passengerItem->email)) {
                    $mail = Mail::to($passengerItem->email, $passengerItem->name);

                    // ✅ Add CC if provided
                    if ($request->filled('cc_emails')) {
                        $ccEmails = array_filter($request->cc_emails); // remove empty values
                        if (!empty($ccEmails)) {
                            $mail->cc($ccEmails);
                        }
                    }

                    // ✅ Add BCC if provided
                    if ($request->filled('bcc_emails')) {
                        $bccEmails = array_filter($request->bcc_emails);
                        if (!empty($bccEmails)) {
                            $mail->bcc($bccEmails);
                        }
                    }

                    // ✅ Finally send the mail
                    $mail->send(new TicketInvoiceMail(
                        $mailPayload,
                        $filesToAttach
                    ));

                    // Update cc & bcc emails after mail send
                    $companyData = UserCompany::where('user_id', Auth::user()->business_id)->first();
                    $companyData->cc_emails = $request->cc_emails ?? [];
                    $companyData->bcc_emails = $request->bcc_emails ?? [];
                    $companyData->save();

                    $totalMail++;
                }
            } catch (\Throwable $e) {
                \Log::error('Failed to send ticket invoice confirmation email', [
                    'error'     => $e->getMessage(),
                    'passenger' => $passengerItem->email ?? null,
                ]);
            }


            // Delete temp files
            try {
                if (!empty($attachments)) {
                    foreach ($attachments as $filePath) {
                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }
                    }
                }
            } catch (\Throwable $e) {
                \Log::error('Failed to delete temp files', [
                    'error' => $e->getMessage(),
                    'files' => $attachments ?? [],
                ]);
            }

        }

        if($totalMail > 0){
            $ticketData = Ticket::where('id', $id)->first();
            $ticketData->mail_sent_count += 1;
            $ticketData->save();
        }
        
        return [
            'is_success' => 1,
            'icon' => 'success',
            'message' => getCurrentTranslation()['mail_sent_successfully'] ?? 'mail_sent_successfully',
            'mail_content' => $mailContent
        ];

       
    }
    
}