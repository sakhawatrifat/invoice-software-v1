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

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;

class DashboardController extends Controller
{
    public function dashboard(){
        $user = Auth::user();

        $totalUser = User::get();
        $totalAirline = Airline::get();
        $allTicket = Ticket::get();
        $allPassengers = TicketPassenger::get();

        return view('admin.dashboard.index', get_defined_vars());
    }

    public function permission(){
        $globalData = User::with('company')->find(Auth::id());
        
        if($globalData->is_staff != 1){
            $userType = $globalData->user_type;
            $allPermissions = getPermissionList();
            $permissions = collect($allPermissions)->filter(function ($item) use ($userType) {
                return $userType === 'admin' || $item['for'] === 'all_user';
            });

            $permissions = $permissions
                ->pluck('permissions')
                ->flatten(1)
                ->pluck('key')
                ->toArray();
            
            if($globalData->id == 1){
                $newPermission = [
                    'user.index', 'user.create', 'user.edit', 'user.status', 'user.delete'
                ];

                $permissions = array_merge($permissions, $newPermission);
            }
                
            $globalData->permissions = $permissions;
            $globalData->save();
        }

        return 'Admin Permissions Updated...';
    }

}