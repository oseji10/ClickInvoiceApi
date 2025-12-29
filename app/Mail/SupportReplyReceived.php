<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\SupportTicket;
use App\Models\SupportReply;
use App\Models\User;

class SupportReplyReceived extends Mailable
{
    use Queueable, SerializesModels;

    public $ticket;
    public $reply;
    public $user;

    /**
     * Create a new message instance.
     */
    public function __construct(SupportTicket $ticket, SupportReply $reply, User $user)
    {
        $this->ticket = $ticket;
        $this->reply = $reply;
        $this->user = $user;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Reply on Ticket #' . $this->ticket->ticketId . ': ' . $this->ticket->subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.support_reply_received', // your blade template for internal notification
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}