<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;

//class HotelInvoiceMail extends Mailable implements ShouldQueue
class HotelInvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $mailPayload;
    public array $fileAttachments;

    public function __construct(array $mailPayload, array $fileAttachments = [])
    {
        $this->mailPayload     = $mailPayload;
        $this->fileAttachments = $fileAttachments;
    }

    public function envelope(): Envelope
    {
        $reservationNumber = $this->mailPayload['mailData']['booking_number'] ?? '';
        $subject = 'Hotel Confirmation – Booking ID: ' . $reservationNumber;

        $fromName  = Auth::user()->company_data->company_name ?? env('APP_NAME');
        $fromEmail = env('MAIL_FROM_ADDRESS');
        //$fromEmail = Auth::user()->company_data->email_1 ?? env('MAIL_FROM_ADDRESS');

        return new Envelope(
            from: new Address($fromEmail, $fromName),
            subject: $subject
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mails.system.ticket-invoice',
            with: [
                'guests' => $this->mailPayload['guests'] ?? [],
                'mailData'   => $this->mailPayload['mailData'] ?? [],
                'mailContent'=> $this->mailPayload['mailContent'] ?? [],
            ]
        );
    }

    public function attachments(): array
    {
        $attachments = [];

        foreach ($this->fileAttachments as $filePath) {
            if (file_exists($filePath)) {
                $attachments[] = Attachment::fromPath($filePath);
            }
        }

        return $attachments;
    }
}
