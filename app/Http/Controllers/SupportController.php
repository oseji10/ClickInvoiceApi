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
use Illuminate\Support\Facades\DB;

class SupportController extends Controller
{
    // app/Http/Controllers/SupportController.php



// public function index()
// {
//     $user = Auth::user();

//     $tickets = SupportTicket::where('userId', $user->id)
//         ->with(['replies' => fn($q) => $q->latest()->take(1)])
//         ->orderBy('updated_at', 'desc')
//         ->get()
//         ->map(function ($ticket) {
//             $lastReply = $ticket->replies->first();
//             $ticket->last_reply = $lastReply?->message;
//             $ticket->last_reply_by_admin = $lastReply?->is_admin ?? false;
//             return $ticket;
//         });

//     return response()->json(['tickets' => $tickets]);
// }


public function index()
{
    $user = Auth::user();

    $tickets = SupportTicket::where('userId', $user->id)
        ->with(['replies']) // Load ALL replies, not just the latest one
        ->orderBy('updated_at', 'desc')
        ->get()
        ->map(function ($ticket) {
            // Sort replies chronologically: oldest → newest
            $sortedReplies = $ticket->replies
                ->sortBy('created_at')
                ->values();

            // Add preview of last reply (optional, for list view)
            $lastReply = $sortedReplies->last();
            $ticket->last_reply = $lastReply?->message;
            $ticket->last_reply_by_admin = $lastReply?->is_admin ?? false;

            // Replace replies with properly sorted collection
            $ticket->replies = $sortedReplies;

            return $ticket;
        });

    return response()->json(['tickets' => $tickets]);
}

/**
 * Get all support tickets (Admin only)
 */
// public function indexAdmin()
// {
//     // Optional: Add policy or middleware to ensure only admins can access this
//     // e.g., $this->authorize('viewAny', SupportTicket::class);

//     $tickets = SupportTicket::with(['user', 'replies']) // Load user and all replies
//         ->orderBy('updated_at', 'desc')
//         ->get()
//         ->map(function ($ticket) {
//             // Include user info (name, email) directly on the ticket for frontend convenience
//             $ticket->user = [
//                 'name' => $ticket->user->name,
//                 'email' => $ticket->user->email,
//             ];

//             // Optional: Add last reply preview (like in user version)
//             $lastReply = $ticket->replies->sortByDesc('created_at')->first();
//             $ticket->last_reply = $lastReply?->message;
//             $ticket->last_reply_by_admin = $lastReply?->is_admin ?? false;

//             // Ensure replies are ordered chronologically (oldest first)
//             $ticket->replies = $ticket->replies->sortBy('created_at')->values();

//             return $ticket;
//         });

//     return response()->json([
//         'tickets' => $tickets
//     ]);
// }


/**
 * Get all support tickets (Admin only)
 */
public function indexAdmin()
{
    $tickets = SupportTicket::with(['user', 'replies'])
        ->orderBy('updated_at', 'desc')
        ->get()
        ->map(function ($ticket) {
            // Extract user info
            $ticket->user = [
                'name' => $ticket->user->name,
                'email' => $ticket->user->email,
            ];

            // Sort replies chronologically: oldest first → newest last
            $sortedReplies = $ticket->replies
                ->sortBy('created_at')
                ->values();

            // Last reply preview (optional)
            $lastReply = $sortedReplies->last();
            $ticket->last_reply = $lastReply?->message ?? null;
            $ticket->last_reply_by_admin = $lastReply?->is_admin ?? false;

            // Critical: Use sorted replies
            $ticket->replies = $sortedReplies;

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


public function updateTicketStatus(Request $request, $ticketId)
    {
        // Validate the input
        $validated = $request->validate([
            'status' => 'required|in:open,in_progress,resolved,closed',
        ]);

        // Update the ticket status and save
        $ticket = SupportTicket::where('ticketId', $ticketId)
        ->firstOrFail();
        $ticket->status = $validated['status'];
        $ticket->updated_at = now(); // Manually update timestamp if needed (Laravel handles automatically)
        $ticket->save();

        return response()->json([
            'message' => 'Ticket status updated successfully',
            'ticket' => $ticket, // Optionally return the updated ticket
        ]);
    }



    public function adminReply(Request $request, $ticketId)
    {
        // Validate the request
        $validated = $request->validate([
            'message' => 'required|string|max:5000|min:1',
        ]);

        $ticket = SupportTicket::where('ticketId', $ticketId)
        ->firstOrFail();

        // Check if ticket is still open for replies
        $allowedStatuses = ['open', 'in_progress'];
        if (!in_array($ticket->status, $allowedStatuses)) {
            return response()->json([
                'error' => 'Cannot reply to a closed or resolved ticket.'
            ], 400);
        }

        // Use database transaction for data consistency
        return DB::transaction(function () use ($validated, $ticket) {
            $user = Auth::user();
            
            // Create the reply
            $reply = SupportReply::create([
                'ticketId' => $ticket->ticketId,
                'userId' => $user->id,
                'message' => $validated['message'],
                'is_admin' => $user->user_role->roleName === 'ADMIN', // Adjust based on your role structure
            ]);

            // Update ticket status and timestamp
            $ticket->update([
                'status' => 'in_progress', // Auto-move to in_progress when admin replies
                'updated_at' => now(),
            ]);

            // Reload ticket with fresh relationships for response
            $ticket->refresh();
            $ticket->load(['user', 'replies']);

            return response()->json([
                'message' => 'Reply added successfully',
                'ticket' => $this->formatTicketForResponse($ticket),
                'reply' => [
                    'replyId' => $reply->replyId,
                    'message' => $reply->message,
                    'is_admin' => $reply->is_admin,
                    'created_at' => $reply->created_at->toISOString(),
                ]
            ]);
        });
    }

    
    private function formatTicketForResponse($ticket)
    {
        return [
            'ticketId' => $ticket->ticketId,
            'subject' => $ticket->subject,
            'message' => $ticket->message,
            'status' => $ticket->status,
            'created_at' => $ticket->created_at->toISOString(),
            'updated_at' => $ticket->updated_at->toISOString(),
            'user' => [
                'name' => $ticket->user->firstName . ' ' . $ticket->user->lastName,
                'email' => $ticket->user->email,
            ],
            'replies' => $ticket->replies->map(function ($reply) {
                return [
                    'replyId' => $reply->replyId,
                    'message' => $reply->message,
                    'is_admin' => $reply->is_admin,
                    'created_at' => $reply->created_at->toISOString(),
                ];
            })->sortBy('created_at')->values(),
        ];
    }
}