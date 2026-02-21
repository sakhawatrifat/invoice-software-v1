<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Queue\SerializesModels;

class StickyNoteReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public $note;
    public $noteUrl;
    public $recipientName;

    public function __construct($note, $noteUrl, $recipientName = '')
    {
        $this->note = $note;
        $this->noteUrl = $noteUrl;
        $this->recipientName = $recipientName;
    }

    public function envelope(): Envelope
    {
        $subject = 'Sticky Note Reminder: ' . ($this->note->note_title ?? 'Action required');
        return new Envelope(
            subject: $subject,
            from: new Address(config('mail.from.address'), config('mail.from.name')),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mails.system.sticky-note-reminder',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
