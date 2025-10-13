<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address; // <-- import this
use Illuminate\Queue\SerializesModels;

class FlightReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public $passenger;
    public $mailContent;
    public $company; // <-- add this

    /**
     * Create a new message instance.
     */
    public function __construct($company, $passenger, $mailContent)
    {
        $this->company     = $company;     // company info
        $this->passenger   = $passenger;
        $this->mailContent = $mailContent;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = 'Have a Safe Flight! Important Reminders Before Departure';

        //$fromEmail = $this->company->email_1 ?? env('MAIL_FROM_ADDRESS');
        $fromEmail = env('MAIL_FROM_ADDRESS');
        $fromName  = $this->company->company_name ?? env('APP_NAME');

        return new Envelope(
            subject: $subject,
            from: new Address(
                $fromEmail,
                $fromName
            ),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mails.system.flight-reminder',
            with: [
                'passenger'   => $this->passenger,
                'mailContent' => $this->mailContent,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
