<?php

namespace App\Mail;

use App\Models\Donor;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmailDonorMail extends Mailable
{
    use Queueable, SerializesModels;

    public Donor $donor;
    public string $email;

    /**
     * Create a new message instance.
     */
    public function __construct(Donor $donor, string $email)
    {
        $this->donor = $donor;
        $this->email = $email;
    }

    /**
     * Build the message.
     */
    public function build(): self
    {
        return $this->subject('Email Verification — My Bridge International')
            ->view('emails.email_donor')
            ->with([
                'donor'=> $this->donor,
                'email' => $this->email,
            ]);
    }
}
