<?php

namespace App\Modules\Account\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** The one-time code that confirms an account's email, in the page's language. */
class EmailVerificationCode extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $code,
        public string $name,
        public string $lang = 'en',
        public int $minutes = 15,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->lang === 'ar'
                ? 'رمز تأكيد بريدك الإلكتروني - Fruga'
                : 'Your email confirmation code - Fruga',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.email-verification-code');
    }
}
