<?php
// app/Http/Controllers/ChatController.php
namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ChatController extends Controller
{
    /**
     * Get all users for chat (excluding current user)
     */
    public function getChatUsers()
    {
        // $currentUserId = 1;
        $currentUserId = Auth::id();
        
        // Get users with their last message and unread count
        $users = User::where('id', '!=', $currentUserId)
            ->select(['id', 'firstName', 'lastName', 'otherNames', 'avatar as profile_picture', 'last_seen'])
            ->withCount(['receivedMessages as unread_count' => function($query) use ($currentUserId) {
                $query->where('receiverId', $currentUserId)
                      ->where('is_read', false);
            }])
            ->with(['latestMessage' => function($query) use ($currentUserId) {
                $query->where(function($q) use ($currentUserId) {
                    $q->where('senderId', $currentUserId)
                      ->orWhere('receiverId', $currentUserId);
                });
            }])
            ->orderBy('last_seen', 'desc')
            ->get()
            ->map(function($user) {
                return [
                    'id' => $user->id,
                    'firstName' => $user->firstName,
                    'lastName' => $user->lastName,
                    'otherNames' => $user->otherNames,
                    'fullName' => $user->fullName,
                    'profile_picture' => $user->profile_picture,
                    'lastSeen' => $user->last_seen,
                    'unread_count' => $user->unread_count,
                    'last_message' => $user->latestMessage ? $user->latestMessage->message : null,
                    'last_message_time' => $user->latestMessage ? $user->latestMessage->formatted_time : null,
                    'isOnline' => $user->isOnline()
                ];
            });

        return response()->json(['users' => $users]);
    }

    /**
     * Get messages between current user and another user
     */
    public function getMessages($userId)
    {
        $currentUserId = Auth::id();
        
        // Validate user exists
        $user = User::find($userId);
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $messages = Message::with(['sender', 'receiver'])
            ->conversation($currentUserId, $userId)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function($message) {
                return [
                    'id' => $message->id,
                    'senderId' => $message->senderId,
                    'receiverId' => $message->receiverId,
                    'message' => $message->message,
                    'timestamp' => $message->created_at->toISOString(),
                    'formatted_time' => $message->formatted_time,
                    'isRead' => $message->is_read,
                    'sender' => [
                        'id' => $message->sender->id,
                        'firstName' => $message->sender->firstName,
                        'lastName' => $message->sender->lastName,
                        'otherNames' => $message->sender->otherNames,
                        'fullName' => $message->sender->fullName,
                        'avatar' => $message->sender->avatar
                    ]
                ];
            });

        // Mark messages as read
        Message::where('senderId', $userId)
               ->where('receiverId', $currentUserId)
               ->where('is_read', false)
               ->update(['is_read' => true]);

        return response()->json([
            'messages' => $messages,
            'otherUser' => [
                'id' => $user->id,
                'firstName' => $user->firstName,
                'lastName' => $user->lastName,
                'otherNames' => $user->otherNames,
                'fullName' => $user->fullName,
                'avatar' => $user->avatar,
                'isOnline' => $user->isOnline()
            ]
        ]);
    }

    /**
     * Send a message
     */
    public function sendMessage(Request $request)
    {
        $request->validate([
            'receiverId' => 'required|exists:users,id',
            'message' => 'required|string|max:2000'
        ]);

        $message = Message::create([
            'senderId' => Auth::id(),
            'receiverId' => $request->receiverId,
            'message' => $request->message,
            'is_read' => false
        ]);

        $message->load(['sender', 'receiver']);

        // Update current user's last_seen
        $currentUser = Auth::user();
        $currentUser->last_seen = now();
        $currentUser->save();

        return response()->json([
            'success' => true,
            'message' => [
                'id' => $message->id,
                'senderId' => $message->senderId,
                'receiverId' => $message->receiverId,
                'message' => $message->message,
                'timestamp' => $message->created_at->toISOString(),
                'formatted_time' => $message->formatted_time,
                'isRead' => $message->is_read,
                'sender' => [
                    'id' => $message->sender->id,
                    'firstName' => $message->sender->firstName,
                    'lastName' => $message->sender->lastName,
                    'otherNames' => $message->sender->otherNames,
                    'fullName' => $message->sender->fullName,
                    'avatar' => $message->sender->avatar
                ]
            ]
        ], 201);
    }

    /**
     * Mark messages as read
     */
    public function markAsRead(Request $request)
    {
        $request->validate([
            'senderId' => 'required|exists:users,id'
        ]);

        $updated = Message::where('senderId', $request->senderId)
               ->where('receiverId', Auth::id())
               ->where('is_read', false)
               ->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'updated_count' => $updated
        ]);
    }

    /**
     * Get unread message count
     */
    public function getUnreadCount()
    {
        $count = Message::where('receiverId', Auth::id())
                       ->where('is_read', false)
                       ->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Update user's last seen timestamp
     */
    public function updateLastSeen()
    {
        $user = Auth::user();
        $user->last_seen = now();
        $user->save();

        return response()->json(['success' => true]);
    }

    /**
     * Search users for chat
     */
    public function searchUsers(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:2'
        ]);

        $currentUserId = Auth::id();
        $query = $request->query;

        $users = User::where('id', '!=', $currentUserId)
            ->where(function($q) use ($query) {
                $q->where('firstName', 'LIKE', "%{$query}%")
                  ->orWhere('lastName', 'LIKE', "%{$query}%")
                  ->orWhere('otherNames', 'LIKE', "%{$query}%")
                  ->orWhere('email', 'LIKE', "%{$query}%");
            })
            ->select(['id', 'firstName', 'lastName', 'otherNames', 'avatar', 'last_seen'])
            ->limit(20)
            ->get()
            ->map(function($user) {
                return [
                    'id' => $user->id,
                    'firstName' => $user->firstName,
                    'lastName' => $user->lastName,
                    'otherNames' => $user->otherNames,
                    'fullName' => $user->fullName,
                    'avatar' => $user->avatar,
                    'isOnline' => $user->isOnline()
                ];
            });

        return response()->json(['users' => $users]);
    }
}