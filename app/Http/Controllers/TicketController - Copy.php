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

use App\Models\User;
use App\Models\Airline;

use App\Models\Ticket;
use App\Models\TicketFlight;
use App\Models\TicketPassenger;

class TicketController extends Controller
{
    public function index()
    {   
        $pageTitle = 'Ticket';
        $queryFor = 'List';
        $createRoute = route('ticket.create');
        $dataTableRoute = route('ticket.datatable');

        return view('common.ticket.index', get_defined_vars());
    }

    public function datatable()
    {   
        $user = Auth::user();
        $query = Ticket::with('user', 'creator')->latest();

        if($user->user_type != 'admin'){
            $query->where('user_id', $user->id);
        }

        // Properly grouped global search
        if (request()->has('search') && $search = request('search')['value']) {
            // Main fields search
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('gender', 'like', "%{$search}%")
                ->orWhere('date_of_birth', 'like', "%{$search}%")
                // ->orWhere(function ($q2) use ($search) {
                //     // Try parsing to date for safer comparison
                //     if (strtotime($search)) {
                //         $q2->orWhereDate('date_of_birth', '=', date('Y-m-d', strtotime($search)));
                //     }
                // })
                ->orWhere('marital_status', 'like', "%{$search}%")
                ->orWhere('education', 'like', "%{$search}%")
                ->orWhere('occupation', 'like', "%{$search}%")
                ->orWhere('location', 'like', "%{$search}%")
                ->orWhere('internet_type', 'like', "%{$search}%")
                ->orWhere('lifestyle_notes', 'like', "%{$search}%");
            });

            // Users by name
            $userIds = User::where('name', 'like', "%{$search}%")->pluck('id');
            if (!empty($userIds)) {
                $query->orWhereIn('user_id', $userIds);
            }

            // Creators by name
            $creatorIds = User::where('name', 'like', "%{$search}%")->pluck('id');
            if (!empty($creatorIds)) {
                $query->orWhereIn('created_by', $creatorIds);
            }
            
            
        }


        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('date_of_birth', function ($row) {
                return Carbon::parse($row->date_of_birth)->format('Y-m-d');
            })
            ->editColumn('gender', function ($row) {
                return ucfirst($row->gender);
            })
            ->addColumn('status', function ($row) {
                // Toggle status: if 1 then 0 else 1
                $newStatus = $row->status == 1 ? 0 : 1;

                // Generate URL with GET parameters (id and new status)
                $statusUrl = route('ticket.status', ['id' => $row->id, 'status' => $newStatus]);

                return '
                    <div class="form-check form-switch">
                        <input type="checkbox" class="form-check-input toggle-table-data-status"
                            data-id="' . $row->id . '"
                            data-url="' . $statusUrl . '"
                            ' . ($row->status == 1 ? 'checked' : '') . '>
                    </div>';
            })
            ->addColumn('action', function ($row) {
                $editUrl = route('ticket.edit', $row->id);
                $deleteUrl = route('ticket.destroy', $row->id);
                $detailsUrl = route('ticket.show', $row->id);

                return '
                    <div class="d-flex align-items-center gap-2">
                        <a href="' . $detailsUrl . '" class="btn btn-sm btn-info" title="View Details">
                            <i class="fa-solid fa-pager"></i>
                        </a>
                        <a href="' . $editUrl . '" class="btn btn-sm btn-primary" title="Edit">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </a>
                        <button class="btn btn-sm btn-danger delete-table-data-btn" title="Delete"
                            data-id="' . $row->id . '"
                            data-url="' . $deleteUrl . '">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>';
            })

            ->editColumn('created_at', function ($row) {
                return Carbon::parse($row->created_at)->format('Y-m-d, H:i');
            })
            ->rawColumns(['status', 'action']) // Allow HTML rendering
            ->make(true);
    }


    public function create()
    {
        $pageTitle = 'Ticket';
        $queryFor = 'Create';
        $listRoute = route('ticket.index');
        $saveRoute = route('ticket.store');

        $airlines = Airline::where('status', 1)->orderBy('name', 'asc')->get();
        //dd($ticketAccountPlatforms);
        return view('common.ticket.addEdit', get_defined_vars());
    }

    public function store(Request $request)
    {
        return $this->saveTicketData($request);
    }

    public function status($id, $status)
    {
        if(!in_array($status, [0,1])){
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => 'Ticket status is incorrect.'
            ];
        }
        
        $user = Auth::user();
        $ticket = Ticket::where('id', $id)->first();
        if(empty($ticket)){
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => 'Ticket not found.'
            ];
        }
        if($user->user_type != 'admin' && $ticket->user_id != $user->id){
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => 'Ticket not found.'
            ];
        }

        $ticket->status = $status;
        $ticket->updated_by = $user->id;
        $ticket->save();
        
        $statusName = $status == 1 ? 'Active' : 'Inactive';
        return [
            'is_success' => 1,
            'icon' => 'success',
            'message' => 'Ticket status updated to '.$statusName.')'
        ];
    }

    public function show($id)
    {
        $user = Auth::user();
        $pageTitle = 'Ticket';
        $queryFor = 'Details';
        $listRoute = route('ticket.index');
        $saveRoute = route('ticket.update', $id);

        $editData = Ticket::with('user', 'creator')->where('id', $id)->first();
        //$questionAnswers = 
        if(empty($editData)){
            abort(404);
        }
        if($user->user_type != 'admin' && $editData->user_id != $user->id){
            abort(404);
        }

        $ticketAccountPlatforms = TicketAccountPlatform::where('status', 1)->orderBy('name', 'asc')->get();

        //dd($editData);
        return view('common.ticket.details', get_defined_vars());
    }

    public function edit($id)
    {
        $user = Auth::user();
        $pageTitle = 'Ticket';
        $queryFor = 'Edit';
        $listRoute = route('ticket.index');
        $saveRoute = route('ticket.update', $id);

        $editData = Ticket::with('user', 'creator')->where('id', $id)->first();
        if(empty($editData)){
            abort(404);
        }
        if($user->user_type != 'admin' && $editData->user_id != $user->id){
            abort(404);
        }

        $airlines = Airline::where('status', 1)->orderBy('name', 'asc')->get();

        //dd($editData);
        return view('common.ticket.addEdit', get_defined_vars());
    }

    public function update(Request $request, $id)
    {
        return $this->saveTicketData($request, $id);
    }

    public function destroy($id)
    {   
        $user = Auth::user();
        $ticket = Ticket::where('id', $id)->first();
        if(empty($ticket)){
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => 'Ticket not found.'
            ];
        }
        if($user->user_type != 'admin' && $ticket->user_id != $user->id){
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => 'Ticket not found.'
            ];
        }

        $forUserId = $ticket->user_id;

        DB::beginTransaction();
        try {
            // Update deleted_by for TicketChildren and delete
            TicketChildren::where('user_id', $forUserId)->where('ticket_id', $ticket->id)
                ->each(function($child) use ($userId) {
                    $child->deleted_by = $userId;
                    $child->save();
                });
            TicketChildren::where('user_id', $forUserId)->where('ticket_id', $ticket->id)->delete();

            // Update deleted_by for TicketDevice and delete
            TicketDevice::where('user_id', $forUserId)->where('ticket_id', $ticket->id)
                ->each(function($device) use ($userId) {
                    $device->deleted_by = $userId;
                    $device->save();
                });
            TicketDevice::where('user_id', $forUserId)->where('ticket_id', $ticket->id)->delete();

            // Get ticket platforms
            $ticketPlatforms = TicketPlatform::where('user_id', $forUserId)->where('ticket_id', $ticket->id)->get();

            // Update deleted_by for TicketPlatformAccount and delete
            $ticketPlatformIds = $ticketPlatforms->pluck('id');
            TicketPlatformAccount::where('user_id', $forUserId)->whereIn('ticket_platform_id', $ticketPlatformIds)
                ->each(function($account) use ($userId) {
                    $account->deleted_by = $userId;
                    $account->save();
                });
            TicketPlatformAccount::where('user_id', $forUserId)->whereIn('ticket_platform_id', $ticketPlatformIds)->delete();

            // Update deleted_by for TicketPlatform and delete
            $ticketPlatforms->each(function($platform) use ($userId) {
                $platform->deleted_by = $userId;
                $platform->save();
            });
            TicketPlatform::where('user_id', $forUserId)->where('ticket_id', $ticket->id)->delete();

            // Update deleted_by for Ticket itself and delete
            $ticket->deleted_by = $userId;
            $ticket->save();
            $ticket->delete();

            DB::commit();

            return [
                'is_success' => 1,
                'icon' => 'success',
                'message' => 'Ticket data deleted successfully.'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => 'An error occurred while deleting the ticket.',
                'error' => $e->getMessage()
            ];
        }
    }




    public function saveTicketData(Request $request, $id = null)
    {
        $validator = Validator::make($request->all(), [
            'invoice_date' => 'required|date_format:Y-m-d H:i',
            'invoice_id' => 'required|string|max:255',
            'reference_number' => 'required|string|max:255',
            'reservation_pnr' => 'required|string|max:255',
            'trip_type' => 'required|in:One Way,Round Trip,Multi City',
            'ticket_type' => 'required|in:Economy,Premium Economy,Business Class,First Class',
            'flight_number' => 'required|string|max:255',
            'booking_status' => 'required|in:On Hold,Processing,Confirmed,Cancelled',

            'ticket_flight_info' => 'required|array',
            'ticket_flight_info.*.airline_id' => 'required|integer|exists:airlines,id',
            'ticket_flight_info.*.leaving_from' => 'required|string|max:255',
            'ticket_flight_info.*.going_to' => 'required|string|max:255',
            'ticket_flight_info.*.departure_date_time' => 'required|date_format:Y-m-d H:i',
            'ticket_flight_info.*.arrival_date_time' => 'required|date_format:Y-m-d H:i',
            'ticket_flight_info.*.is_transit' => 'nullable|in:0,1',
            'ticket_flight_info.*.transit' => 'nullable|array',
            'ticket_flight_info.*.transit.*.airline_id' => 'nullable|integer|exists:airlines,id',
            'ticket_flight_info.*.transit.*.leaving_from' => 'nullable|string|max:255',
            'ticket_flight_info.*.transit.*.going_to' => 'nullable|string|max:255',
            'ticket_flight_info.*.transit.*.departure_date_time' => 'nullable|date_format:Y-m-d H:i',
            'ticket_flight_info.*.transit.*.arrival_date_time' => 'nullable|date_format:Y-m-d H:i',

            'passenger_info' => 'required|array|min:1',
            'passenger_info.*.name' => 'required|string|max:255',
            'passenger_info.*.date_of_birth' => 'required|date',
            'passenger_info.*.gender' => 'required|in:Male,Female,Other',
            'passenger_info.*.nationality' => 'required|string|max:255',
            'passenger_info.*.type' => 'required|in:Adult,Child,Infant',
            'passenger_info.*.passport_number' => 'required|string|max:255',
            'passenger_info.*.baggage_allowance' => 'nullable|string|max:255',

            'footer_title' => 'nullable|string|max:255',
            'footer_text' => 'nullable|string',
        ]);


        if ($validator->fails()) {
            return response()->json([
                'is_success' => 0,
                'icon' => 'error',
                'errors' => $validator->errors()
            ]);
        }

        //dd($request->all());


        DB::beginTransaction();
        try {
            
            $user = Auth::user();
            $ticket = null;
            if (isset($id)) {
                $ticket = Ticket::where('id', $id)->first();
                if(empty($ticket)){
                    return [
                        'is_success' => 0,
                        'icon' => 'error',
                        'message' => 'Ticket not found.'
                    ];
                }
                if($user->user_type != 'admin' && $ticket->user_id != $user->id){
                    return [
                        'is_success' => 0,
                        'icon' => 'error',
                        'message' => 'Ticket not found.'
                    ];
                }
            }

            $queryForUserId = $request->user_id ?? Auth::id();
            $userId = $request->user_id ?? Auth::id();

            if (empty($ticket)) {
                $ticket = new Ticket();
                $ticket->created_by = Auth::id();
            }else{
                $ticket->updated_by = Auth::id();
            }

            $ticket->user_id = $userId;
            $ticket->invoice_date = $request->invoice_date ?? null;
            $ticket->invoice_id = $request->invoice_id ?? null;
            $ticket->reference_number = $request->reference_number ?? null;
            $ticket->reservation_pnr = $request->reservation_pnr ?? null;
            $ticket->trip_type = $request->trip_type ?? null;
            $ticket->ticket_type = $request->ticket_type ?? null;
            $ticket->flight_number = $request->flight_number ?? null;
            $ticket->booking_status = $request->booking_status ?? null;

            $ticket->save();

            // Flight
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
                        $ticketFlight->leaving_from = $flight['leaving_from'] ?? null;
                        $ticketFlight->going_to = $flight['going_to'] ?? null;
                        $ticketFlight->departure_date_time = $flight['departure_date_time'] ?? null;
                        $ticketFlight->arrival_date_time = $flight['arrival_date_time'] ?? null;
                        $ticketFlight->is_transit = $flight['is_transit'] ?? 0;
                        $ticketFlight->save();

                        $flight_ids[] = $ticketFlight->id;

                        if (isset($flight['transit']) && !empty($flight['transit'])) {
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
                                    $ticketFlightTransit->leaving_from = $transit['leaving_from'] ?? null;
                                    $ticketFlightTransit->going_to = $transit['going_to'] ?? null;
                                    $ticketFlightTransit->departure_date_time = $transit['departure_date_time'] ?? null;
                                    $ticketFlightTransit->arrival_date_time = $transit['arrival_date_time'] ?? null;
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
            // Remove children not included in the request
            $flightToDelete = TicketFlight::where('user_id', $queryForUserId)
                        ->where('ticket_id', $ticket->id)
                        ->whereNotIn('id', $all_flight_ids)
                        ->get();
            foreach ($flightToDelete as $flight) {
                $flight->deleted_by = Auth::id();
                $flight->save();
                $flight->delete(); // use delete() if using SoftDeletes
            }


            // Passengers
            $passengers_ids = [];
            if (isset($request->passenger_info) && !empty($request->passenger_info)) {
                foreach ($request->passenger_info as $passenger) {
                    if(isArrayNotEmpty($passenger) == true){
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
                        }else{
                            $ticketPassenger->updated_by = Auth::id();
                        }

                        $ticketPassenger->user_id = $userId;
                        $ticketPassenger->ticket_id = $ticket->id;
                        $ticketPassenger->name = $passenger['name'] ?? null;
                        $ticketPassenger->date_of_birth = $passenger['date_of_birth'] ?? null;
                        $ticketPassenger->gender = $passenger['gender'] ?? null;
                        $ticketPassenger->nationality = $passenger['nationality'] ?? null;
                        $ticketPassenger->type = $passenger['type'] ?? null;
                        $ticketPassenger->passport_number = $passenger['passport_number'] ?? null;
                        $ticketPassenger->baggage_allowance = $passenger['baggage_allowance'] ?? null;
                        $ticketPassenger->save();

                        $passengers_ids[] = $ticketPassenger->id;
                    }
                }
            }
            // Remove device not included in the request
            $passengersToDelete = TicketPassenger::where('user_id', $queryForUserId)
                    ->where('ticket_id', $ticket->id)
                    ->whereNotIn('id', $passengers_ids)
                    ->get();
            foreach ($passengersToDelete as $passenger) {
                $passenger->deleted_by = Auth::id();
                $passenger->save();
                $passenger->delete(); // use delete() if using SoftDeletes
            }

            DB::commit();
            return [
                'is_success' => 1,
                'icon' => 'success',
                'message' => 'Ticket data saved successfully.'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Ticket store error', ['error' => $e->getMessage()]);

            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => 'Something went wrong while creating the ticket. Please reload the page & try again.'
            ];
        }
    }
}