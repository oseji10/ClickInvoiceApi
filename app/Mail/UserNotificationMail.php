<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class UserNotificationMail extends Mailable
{
    use Queueable, SerializesModels; // Queueable is still here but not used

    public $user;
    public $subjectLine;
    public $messageBody;

    public function __construct(User $user, string $subjectLine, string $messageBody)
    {
        $this->user = $user;
        $this->subjectLine = $subjectLine;
        $this->messageBody = $messageBody;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.user-notification',
            with: [
                'user' => $this->user,
                'messageBody' => $this->messageBody,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}