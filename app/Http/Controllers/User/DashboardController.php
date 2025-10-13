<?php

namespace App\Http\Controllers\User;


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

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;

class DashboardController extends Controller
{
    public function dashboard(){
        $user = Auth::user();
        $userId = $user->id;

        $totalUser = User::get();
        $totalAirline = Airline::get();
        $allTicket = Ticket::where('user_id', $userId)->get();
        $allPassengers = TicketPassenger::where('user_id', $userId)->get();

        return view('frontend.dashboard.index', get_defined_vars());
    }

}