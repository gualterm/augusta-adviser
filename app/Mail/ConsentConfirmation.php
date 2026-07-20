<?php
namespace App\Mail;

use App\Models\ClientConsent;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ConsentConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ClientConsent $consent) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirmação de Consentimento — Augusta Beauty Adviser',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.consent-confirmation',
        );
    }
}