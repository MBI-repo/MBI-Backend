<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public ?string $otp = null,
        public string $type = 'otp',
    ) {
    }

    public function build(): self
    {
        $subject = match ($this->type) {

            'otp' =>
                'Password Reset OTP — My Bridge International',

            'verified' =>
                'OTP Verified — My Bridge International',

            'password_changed' =>
                'Password Changed — My Bridge International',

            default =>
                'Password Reset — My Bridge International',
        };

        return $this
            ->subject($subject)
            ->view('emails.password_reset_otp');
    }
}
