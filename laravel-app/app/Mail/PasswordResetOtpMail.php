<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetOtpMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public string $otp, public string $email)
    {
    }

    public function build(): self
    {
        return $this->subject('Password Reset OTP')
            ->view('emails.password-reset-otp')
            ->with(['otp' => $this->otp, 'email' => $this->email]);
    }
}
