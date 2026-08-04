<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VerifyEmailMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public string $verificationUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, string $verificationUrl)
    {
        $this->user = $user;
        $this->verificationUrl = $verificationUrl;
    }

    /**
     * Build the message.
     */
    public function build(): self
    {
        return $this->subject('Verify Your Email Address — My Bridge International')
            ->view('emails.verify_email')
            ->with([
                'user'            => $this->user,
                'verificationUrl' => $this->verificationUrl,
            ]);
    }
}
