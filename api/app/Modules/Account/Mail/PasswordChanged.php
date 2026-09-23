<?php

namespace App\Modules\Account\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent after a password is reset with a code. If the owner did not do it,
 * this is how they find out - so it says what happened and what to do.
 */
class PasswordChanged extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $name,
        public string $lang = 'en',
        public ?string $ip = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->lang === 'ar'
                ? 'تم تغيير كلمة المرور - Fruga'
                : 'Your password was changed - Fruga',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.password-changed');
    }
}
