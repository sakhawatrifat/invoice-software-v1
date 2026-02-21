<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use App\Models\StickyNote;
use App\Models\User;

class StickyNoteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::whereNull('deleted_at')->take(5)->get();
        if ($users->isEmpty()) {
            return;
        }

        $owner = $users->first();
        $businessId = $owner->is_staff ? $owner->parent_id : $owner->id;

        $notes = [
            [
                'note_title' => 'Follow up with client – Proposal',
                'note_description' => 'Send revised proposal and schedule a call by end of week.',
                'deadline' => Carbon::now()->addDays(5),
                'reminder_datetime' => Carbon::now()->addDays(3),
                'status' => 'Pending',
            ],
            [
                'note_title' => 'Submit monthly report',
                'note_description' => 'Complete and submit the monthly sales report to management.',
                'deadline' => Carbon::now()->addDays(2),
                'reminder_datetime' => Carbon::now()->addDay(),
                'status' => 'In Progress',
            ],
            [
                'note_title' => 'Test reminder (past) – Action required',
                'note_description' => 'This note has a reminder in the past so it will show in the popup and can be used to test the reminder email via: php artisan sticky-note:send-reminder --test',
                'deadline' => Carbon::now()->addDays(7),
                'reminder_datetime' => Carbon::now()->subHour(),
                'status' => 'Pending',
            ],
            [
                'note_title' => 'Team meeting – Agenda',
                'note_description' => 'Prepare agenda and send calendar invite for Friday team sync.',
                'deadline' => Carbon::now()->addDays(6),
                'reminder_datetime' => Carbon::now()->addDays(4),
                'status' => 'Pending',
            ],
            [
                'note_title' => 'Review contract draft',
                'note_description' => 'Legal review of the new vendor contract.',
                'deadline' => Carbon::now()->addDays(10),
                'reminder_datetime' => Carbon::now()->addDays(7),
                'status' => 'Pending',
            ],
        ];

        foreach ($notes as $i => $data) {
            $note = new StickyNote();
            $note->user_id = $businessId;
            $note->note_title = $data['note_title'];
            $note->note_description = $data['note_description'];
            $note->deadline = $data['deadline'];
            $note->reminder_datetime = $data['reminder_datetime'];
            $note->status = $data['status'];
            $note->created_by = $owner->id;
            $note->updated_by = $owner->id;
            $note->save();

            if ($users->count() > 1 && $i % 2 === 0) {
                $note->assignedUsers()->sync($users->skip(1)->take(2)->pluck('id')->toArray());
            }
        }
    }
}
