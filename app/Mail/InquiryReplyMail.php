<?php
namespace App\Mail;
use App\Models\Inquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
class InquiryReplyMail extends Mailable
{
    use Queueable, SerializesModels;
    public function __construct(
        public Inquiry $inquiry,
        public string $responseText,
    ) {}
    public function envelope(): Envelope
    {
        $subjectLabel = Inquiry::SUBJECTS[$this->inquiry->subject] ?? $this->inquiry->subject;
        return new Envelope(
            subject: 'Re: ' . $subjectLabel,
        );
    }
    public function content(): Content
    {
        return new Content(view: 'mail.inquiry-reply');
    }
}
