<?php

namespace App\Modules\User\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * 6-digit code for password reset / password change / email change.
 */
class VerificationCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $code,
        public string $purpose = 'password',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->purpose === 'email'
                ? 'Confirm your new email - رمز تأكيد البريد الإلكتروني'
                : 'Your verification code - رمز التحقق',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.verification-code',
            with: ['code' => $this->code, 'purpose' => $this->purpose],
        );
    }
}
