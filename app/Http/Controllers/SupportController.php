<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\SupportTicketCreated;
use App\Models\SupportTicket;
use App\Mail\SupportTicketConfirmation;
use App\Mail\SupportReplyReceived;
use App\Models\SupportReply;

class SupportController extends Controller
{
    // app/Http/Controllers/SupportController.php



public function index()
{
    $user = Auth::user();

    $tickets = SupportTicket::where('userId', $user->id)
        ->with(['replies' => fn($q) => $q->latest()->take(1)])
        ->orderBy('updated_at', 'desc')
        ->get()
        ->map(function ($ticket) {
            $lastReply = $ticket->replies->first();
            $ticket->last_reply = $lastReply?->message;
            $ticket->last_reply_by_admin = $lastReply?->is_admin ?? false;
            return $ticket;
        });

    return response()->json(['tickets' => $tickets]);
}

public function store(Request $request)
{
    $validated = $request->validate([
        'subject' => 'required|string|max:255',
        'message' => 'required|string',
    ]);

    $user = Auth::user();

    $ticket = SupportTicket::create([
        'userId' => $user->id,
        'subject' => $validated['subject'],
        'message' => $validated['message'],
        'status' => 'open',
    ]);

    // Notify support team
    Mail::to('support@clickinvoice.app')->send(new SupportTicketCreated($ticket, $user));

    // Send confirmation to user
    Mail::to($user->email)->send(new SupportTicketConfirmation($ticket));

    return response()->json([
        'message' => 'Support ticket created successfully',
        'ticket' => $ticket->load('replies'),
    ], 201);
}

public function reply(Request $request, $ticketId)
{
    $ticket = SupportTicket::where('ticketId', $ticketId)
        ->where('userId', Auth::id())
        ->firstOrFail();

    $validated = $request->validate([
        'message' => 'required|string',
    ]);

    $reply = SupportReply::create([
        'ticketId' => $ticket->ticketId,
        'userId' => Auth::id(),
        'message' => $validated['message'],
        'is_admin' => false,
    ]);

    // Update ticket status & timestamp
    $ticket->update([
        'status' => 'open',
        'updated_at' => now(),
    ]);

    // Notify support team of new reply
    Mail::to('support@clickinvoice.app')->send(new SupportReplyReceived($ticket, $reply, Auth::user()));

    return response()->json([
        'message' => 'Reply sent successfully',
        'reply' => $reply,
    ]);
}
}