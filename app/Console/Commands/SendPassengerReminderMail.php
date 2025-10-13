<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Mail\FlightReminderMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

use App\Models\User;
use App\Models\UserCompany;
use App\Models\Airline;

use App\Models\Ticket;
use App\Models\TicketFlight;
use App\Models\TicketPassenger;
use App\Models\TicketPassengerFlight;
use App\Models\TicketFareSummary;

class SendPassengerReminderMail extends Command
{
    protected $signature = 'passenger:send-reminder';
    protected $description = 'Send reminder emails to passengers 2 days before their flight';

    public function handle()
    {
        $today = Carbon::today();
        // before 2 days
        $targetDate = $today->copy()->addDays(2)->toDateString();

        $subscribedUserIds = User::where('is_staff', 0)->whereJsonContains('permissions', 'ticket.reminder')->pluck('id')->toArray();

        // $subQuery = TicketFlight::select(
        //         'ticket_id',
        //         DB::raw('MIN(departure_date_time) as first_departure')
        //     )
        //     ->whereIn('user_id', $subscribedUserIds)
        //     ->whereNull('parent_id')
        //     ->groupBy('ticket_id');

        // $upcommingFlightIds = DB::table(DB::raw("({$subQuery->toSql()}) as tf"))
        //     ->mergeBindings($subQuery->getQuery()) // merge bindings for Laravel
        //     ->whereDate('first_departure', $targetDate) // filter AFTER min is found
        //     ->pluck('ticket_id')
        //     ->toArray();

        $upcommingFlightIds = TicketFlight::whereNull('parent_id')
            ->whereDate('departure_date_time', $targetDate)
            ->whereIn('user_id', $subscribedUserIds)
            ->pluck('ticket_id')
            ->unique()
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
    }
}
