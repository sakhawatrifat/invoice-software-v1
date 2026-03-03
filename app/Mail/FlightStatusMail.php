<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;

class FlightStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $subjectLine;
    public string $htmlContent;
    /** @var array<int, string> */
    public array $fileAttachments;

    /**
     * @param string $subjectLine
     * @param string $htmlContent
     * @param array<int, string> $fileAttachments Paths to files to attach (e.g. PDF)
     */
    public function __construct(string $subjectLine, string $htmlContent, array $fileAttachments = [])
    {
        $this->subjectLine = $subjectLine;
        $this->htmlContent = $htmlContent;
        $this->fileAttachments = $fileAttachments;
    }

    public function envelope(): Envelope
    {
        $fromName = (Auth::check() && isset(auth()->user()->company_data)) ? auth()->user()->company_data->company_name : env('APP_NAME');
        $fromEmail = env('MAIL_FROM_ADDRESS');

        return new Envelope(
            from: new Address($fromEmail, $fromName),
            subject: $this->subjectLine
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mails.system.flight-status',
            with: ['mailContent' => $this->htmlContent]
        );
    }

    public function attachments(): array
    {
        $attachments = [];
        foreach ($this->fileAttachments as $filePath) {
            if (is_string($filePath) && file_exists($filePath)) {
                $attachments[] = Attachment::fromPath($filePath);
            }
        }
        return $attachments;
    }
}
