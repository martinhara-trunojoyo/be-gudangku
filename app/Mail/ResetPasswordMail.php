<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public $token;
    public $email;

    public function __construct($token, $email)
    {
        $this->token = $token;
        $this->email = $email;
    }

    public function build()
    {
        return $this->subject('Reset Password Notification')
                    ->view('emails.reset-password')
                    ->with([
                        'token' => $this->token,
                        'email' => $this->email,
                        'resetUrl' => env('FRONTEND_URL', 'http://localhost:5174') . '/reset-password?token=' . $this->token . '&email=' . $this->email
                    ]);
    }
} 