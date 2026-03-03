<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Queue\SerializesModels;

class FlightChangeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $payment;
    public $livePayload;

    public function __construct($payment, array $livePayload = [])
    {
        $this->payment = $payment;
        $this->livePayload = $livePayload;
    }

    public function envelope(): Envelope
    {
        $subject = 'Flight schedule update – ' . ($this->payment->payment_invoice_id ?? 'Booking');
        $fromEmail = env('MAIL_FROM_ADDRESS');
        $fromName = auth()->check() && isset(auth()->user()->company_data) ? auth()->user()->company_data->company_name : env('APP_NAME');

        return new Envelope(
            from: new Address($fromEmail, $fromName),
            subject: $subject
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mails.system.flight-change',
            with: [
                'payment' => $this->payment,
                'livePayload' => $this->livePayload,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
