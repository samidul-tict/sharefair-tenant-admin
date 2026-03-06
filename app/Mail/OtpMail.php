<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $otpCode;
    public string $email;

    public function __construct(string $otpCode, string $email)
    {
        $this->otpCode = $otpCode;
        $this->email = $email;
    }

    public function build()
    {
        return $this->subject('Your login code')
            ->view('emails.otp')
            ->with([
                'otpCode' => $this->otpCode,
                'email' => $this->email,
            ]);
    }
}
