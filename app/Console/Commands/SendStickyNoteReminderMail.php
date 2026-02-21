<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Models\StickyNote;
use App\Mail\StickyNoteReminderMail;

class SendStickyNoteReminderMail extends Command
{
    protected $signature = 'sticky-note:send-reminder 
                            {--test : Send reminder for one note with reminder due (for testing mail)}';

    protected $description = 'Send reminder emails for sticky notes when reminder_datetime has passed';

    public function handle()
    {
        $isTest = $this->option('test');

        $query = StickyNote::with('user', 'assignedUsers')
            ->whereNotNull('reminder_datetime')
            ->where('reminder_datetime', '<=', Carbon::now())
            ->whereNull('reminder_mail_sent_at')
            ->whereNotIn('status', ['Cancelled', 'Completed']);

        if ($isTest) {
            $notes = $query->limit(1)->get();
            if ($notes->isEmpty()) {
                $this->warn('No sticky note with due reminder found. Create a note with reminder_datetime in the past to test.');
                return 0;
            }
            $this->info('Test mode: processing ' . $notes->count() . ' note(s).');
        } else {
            $notes = $query->get();
        }

        $sent = 0;

        foreach ($notes as $note) {
            $noteUrl = route('sticky_note.show', $note->id);

            $recipients = collect();

            if ($note->created_by) {
                $creator = \App\Models\User::find($note->created_by);
                if ($creator && $creator->email) {
                    $recipients->push((object)['user' => $creator, 'name' => $creator->name]);
                }
            }

            foreach ($note->assignedUsers as $u) {
                if ($u->email && !$recipients->contains(fn ($r) => $r->user->id === $u->id)) {
                    $recipients->push((object)['user' => $u, 'name' => $u->name]);
                }
            }

            foreach ($recipients as $r) {
                try {
                    Mail::to($r->user->email)->send(
                        new StickyNoteReminderMail($note, $noteUrl, $r->name)
                    );
                    $sent++;
                    $this->line('Sent to: ' . $r->user->email);
                } catch (\Exception $e) {
                    $this->error('Failed to send to ' . $r->user->email . ': ' . $e->getMessage());
                    \Log::error('Sticky note reminder mail failed', [
                        'note_id' => $note->id,
                        'email' => $r->user->email,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if ($sent > 0 && !$isTest) {
                $note->reminder_mail_sent_at = Carbon::now();
                $note->save();
            }
        }

        $this->info('Done. Emails sent: ' . $sent);
        return 0;
    }
}
