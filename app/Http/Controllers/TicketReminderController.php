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
use App\Mail\FlightReminderMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

use App\Models\User;
use App\Models\UserCompany;
use App\Models\Airline;

use App\Models\Ticket;
use App\Models\TicketFlight;
use App\Models\TicketPassenger;
use App\Models\TicketPassengerFlight;
use App\Models\TicketFareSummary;

use PDF;
use App\Services\PdfService;

class TicketReminderController extends Controller
{
    public function index()
    {   
        if (!hasPermission('ticket.reminder')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        $dataTableRoute = hasPermission('ticket.reminder') ? route('ticket.reminder.datatable') : '';

        return view('common.ticket.reminder.index', get_defined_vars());
    }

    public function datatable()
    {   
        $user = Auth::user();
        $today = Carbon::now();

        // $subQuery = TicketFlight::select(
        //         'ticket_id',
        //         DB::raw('MIN(departure_date_time) as first_departure')
        //     )
        //     ->whereNull('parent_id')
        //     ->groupBy('ticket_id');

        // $upcommingFlightIds = DB::table(DB::raw("({$subQuery->toSql()}) as tf"))
        //     ->mergeBindings($subQuery->getQuery()) // merge bindings for Laravel
        //     ->where('first_departure', '>', now()->endOfDay()) // filter AFTER min is found
        //     ->pluck('ticket_id')
        //     ->toArray();

        $upcommingFlightIds = TicketFlight::whereNull('parent_id')
            ->where('departure_date_time', '>', now()->endOfDay())
            ->pluck('ticket_id')
            ->unique()
            ->toArray();

        //dd($upcommingFlightIds);

        $query = TicketPassenger::with('ticket', 'ticket.flights', 'flights', 'user', 'creator')->whereIn('ticket_id', $upcommingFlightIds)->latest();

        if($user->user_type != 'admin'){
            $query->where('user_id', $user->business_id);
        }

        //$query->whereNotNull('email');

        // Properly grouped global search
        if (request()->has('search') && $search = request('search')['value']) {
            //Main fields search
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('passport_number', 'like', "%{$search}%")
                    ->orWhere('pax_type', 'like', "%{$search}%");
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


        $currentTranslation = getCurrentTranslation();
        return DataTables::of($query)
            ->addIndexColumn()
            // ->addColumn('user_id', function ($row) {
            //     return $row->user?->name ?? 'N/A';
            // })
            ->addColumn('user_id', function ($row) {
                $company = $row->user?->company_data;

                if ($company) {
                    $logo = $company->dark_logo_url
                        ? '<img src="' . e($company->dark_logo_url) . '" alt="Logo" height="30" style="margin-right: 10px;"><br>'
                        : '';
                    $name = $company->company_name ?? 'N/A';

                    return '<div class="text-center">' . $logo . '<span>' . e($name) . '</span></div>';
                }

                return 'N/A';
            })
            ->addColumn('phone', function ($row) {
                return $row->phone ?? 'N/A';
            })
            ->addColumn('phone', function ($row) {
                return $row->phone ?? 'N/A';
            })
            // ->addColumn('flight_date_time', function ($row) {
            //     $flight = $row->ticket->flights[0] ?? null;

            //     return $flight?->departure_date_time 
            //         ? date('Y-m-d H:i', strtotime($flight->departure_date_time)) 
            //         : 'N/A';
            // })
            ->addColumn('flight_date_time', function ($row) {
                $flights = $row->ticket->flights
                    ->where('departure_date_time', '>', now())   // only future flights
                    ->sortBy('departure_date_time');             // soonest first

                return $flights->isNotEmpty()
                    ? $flights->map(function ($flight) {
                        return date('Y-m-d H:i', strtotime($flight->departure_date_time));
                    })->implode('<br>')   // use break instead of comma
                    : 'N/A';
            })

            ->addColumn('created_by', function ($row) {
                return $row->creator ? $row->creator->name : 'N/A';
            })
            ->editColumn('created_at', function ($row) {
                return Carbon::parse($row->created_at)->format('Y-m-d, H:i');
            })
            ->addColumn('reminder_status', function ($row) {
                if (!hasPermission('ticket.reminder')) {
                    // Just return plain text without toggle
                    return '
                        <span class="' . ($row->reminder_status == 1 ? 'text-success' : 'text-danger') . '">'
                            . ($row->reminder_status == 1 ? 'Enabled' : 'Disabled') .
                        '</span>';
                }

                // Toggle status: if 1 then 0 else 1
                $newStatus = $row->reminder_status == 1 ? 0 : 1;

                // Generate URL with GET parameters (passenger_id and new status)
                $statusUrl = route('ticket.reminder.status', ['passenger_id' => $row->id, 'status' => $newStatus]);

                return '
                    <div class="form-check form-switch">
                        <input type="checkbox" class="form-check-input toggle-table-data-status"
                            data-id="' . $row->id . '"
                            data-url="' . $statusUrl . '"
                            ' . ($row->reminder_status == 1 ? 'checked' : '') . '>
                    </div>';
            })
            ->addColumn('action', function ($row) {
                $editUrl = route('ticket.edit', $row->ticket_id);

                $buttons = '';

                // Edit button (requires permission)
                if (hasPermission('ticket.edit')) {
                    $buttons .= '
                        <a href="' . $editUrl . '" class="btn btn-sm btn-primary" title="Edit">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </a>
                    ';
                }

                return !empty(trim($buttons)) ? '
                    <div class="d-flex align-items-center gap-2">
                        ' . $buttons . '
                    </div>' : 'N/A';
            })
            ->rawColumns(['user_id', 'flight_date_time', 'reminder_status', 'action']) // Allow HTML rendering
            ->make(true);
    }


    public function status($passenger_id, $status)
    {
        if (!hasPermission('ticket.reminder')) {
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
        $passenger = TicketPassenger::where('user_id', $user->business_id)->where('id', $passenger_id)->first();
        if(empty($passenger)){
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['data_not_found'] ?? 'data_not_found'
            ];
        }
        // if($user->user_type != 'admin' && $airline->user_id != $user->id){
        //     return [
        //         'is_success' => 0,
        //         'icon' => 'error',
        //         'message' => getCurrentTranslation()['data_not_found'] ?? 'data_not_found'
        //     ];
        // }

        $passenger->reminder_status = $status;
        $passenger->updated_by = $user->id;
        $passenger->save();
        
        $statusName = $status == 1 ? 'Active' : 'Inactive';
        return [
            'is_success' => 1,
            'icon' => 'success',
            'message' => (getCurrentTranslation()['status_updated'] ?? 'status_updated') . ' (' . $statusName . ')',
        ];
    }


    public function reminderMailForm(Request $request)
    {
        if (!hasPermission('ticket.reminder')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        $listRoute = hasPermission('ticket.reminder') ? route('ticket.reminder.index') : '';
        $saveRoute = hasPermission('ticket.reminder') ? route('ticket.reminder.save') : '';

        return view('common.ticket.reminder.reminderMailForm', get_defined_vars());
    }


    public function saveReminderInformation(Request $request)
    {
        if (!hasPermission('ticket.reminder')) {
            return [
                'is_success' => 0,
                'icon' => 'error',
                'message' => getCurrentTranslation()['permission_denied'] ?? 'Permission denied',
            ];
        }

        $authUser = Auth::user();
        $userCompany = UserCompany::where('user_id', $authUser->business_id)->first();
        $userCompany->reminder_mail_content = $request->reminder_mail_content;
        $userCompany->save();


        return [
            'is_success' => 1,
            'icon' => 'success',
            'message' => getCurrentTranslation()['mail_content_updated'] ?? 'mail_content_updated',
        ];
    }


    public function sendReminderMail()
    {
        $today = Carbon::today();
        // before 2 days
        $targetDate = $today->copy()->addDays(2)->toDateString();

        $subscribedUserIds = User::where('is_staff', 0)->whereJsonContains('permissions', 'ticket.reminder')->pluck('id')->toArray();

        $subQuery = TicketFlight::select(
                'ticket_id',
                DB::raw('MIN(departure_date_time) as first_departure')
            )
            ->whereIn('user_id', $subscribedUserIds)
            ->whereNull('parent_id')
            ->groupBy('ticket_id');

        $upcommingFlightIds = DB::table(DB::raw("({$subQuery->toSql()}) as tf"))
            ->mergeBindings($subQuery->getQuery()) // merge bindings for Laravel
            ->whereDate('first_departure', $targetDate) // filter AFTER min is found
            ->pluck('ticket_id')
            ->toArray();

        $passengers = TicketPassenger::with('ticket', 'ticket.flights', 'user')->whereIn('ticket_id', $upcommingFlightIds)
        ->whereNotNull('email')
        ->where('reminder_status', 1)
        ->where('mail_sent_status', 0)
        ->get();
        
        // dd($upcommingFlightIds);
        // dd($passengers);

        foreach ($passengers as $passenger) {
            $company = $passenger->user->company_data ?? null;
            $message = getTravelReminderEmailContent();

            // Replace variables
            $message = str_replace('{passenger_name_here}', $passenger->name, $message);
            $message = str_replace('{company_name_here}', $company->company_name, $message);
            $message = str_replace('{company_website_url_here}', $company->website_url, $message);
            $message = str_replace('{company_mail_here}', $company->email_1, $message);

            DB::beginTransaction();
            try {
                if (!empty($passenger->email) && $company != null) {
                    Mail::to($passenger->email)->send(
                        new FlightReminderMail($company, $passenger, $message)
                    );

                    $passenger->mail_sent_status = 1;
                    $passenger->save();
                }

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();

                \Log::error('Reminder mail sending failed', [
                    'passenger_id' => $passenger->id,
                    'email' => $passenger->email,
                    'error' => $e->getMessage(),
                ]);
            }

        }


        return 'Reminder Test Mail Sent';
    }
    
    
}