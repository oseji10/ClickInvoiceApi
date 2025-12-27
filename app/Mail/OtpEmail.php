<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OtpEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $firstName;
    public $lastName;
    public $otp;

    /**
     * Create a new message instance.
     */
    public function __construct($firstName, $lastName, $otp)
    {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->otp = $otp;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Verify Your ClickInvoice Account')
                    ->view('emails.otp-verification')
                    ->with([
                        'firstName' => $this->firstName,
                        'lastName' => $this->lastName,
                        'otp' => $this->otp,
                    ]);
    }
}
