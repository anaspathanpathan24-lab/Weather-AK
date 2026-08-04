<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class PasswordResetOtpMail extends Mailable
{
    public function __construct(public string $otp, public string $email)
    {
    }

    public function build(): self
    {
        return $this->subject('Weather Application - Password Reset OTP')
            ->html(view('emails.password-reset-otp', ['otp' => $this->otp, 'email' => $this->email])->render());
    }
}
