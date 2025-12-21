<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    /**
     * Get user notifications
     */
    public function index(Request $request)
    {
        try {
            $user = auth()->user();
            
            $perPage = $request->get('per_page', 20);
            $page = $request->get('page', 1);
            $filter = $request->get('filter', '');
            
            $query = Notification::where('userId', $user->id)
                ->with(['user'])
                ->orderBy('created_at', 'desc');
            
            // Apply filters
            if ($filter === 'unread') {
                $query->whereNull('readAt');
            } elseif ($filter && in_array($filter, [
                'follow', 'post_like', 'post_comment', 'post_share', 
                'job_post', 'job_application', 'message', 'system'
            ])) {
                $query->where('type', $filter);
            }
            
            $notifications = $query->paginate($perPage, ['*'], 'page', $page);
            
            // Transform notifications for frontend
            $transformedNotifications = $notifications->map(function ($notification) {
                $data = $notification->data ?? [];
                
                $result = [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'readAt' => $notification->readAt?->toISOString(),
                    'created_at' => $notification->created_at->toISOString(),
                    'data' => $data
                ];
                
                // Add sender info if available
                if (isset($data['senderId'])) {
                    $sender = \App\Models\User::find($data['senderId']);
                    if ($sender) {
                        $result['sender'] = [
                            'id' => $sender->id,
                            'name' => $data['senderName'] ?? $sender->full_name,
                            'avatar' => $data['senderAvatar'] ?? $sender->profile_image,
                            'role' => $sender->role
                        ];
                    }
                }
                
                // Add post info if available
                if (isset($data['postId'])) {
                    $post = \App\Models\Posts::find($data['postId']);
                    if ($post) {
                        $result['post'] = [
                            'id' => $post->id,
                            'title' => $data['postTitle'] ?? $post->title,
                            'excerpt' => $data['postExcerpt'] ?? substr($post->content, 0, 100)
                        ];
                    }
                }
                
                // Add job info if available
                if (isset($data['jobId'])) {
                    $job = \App\Models\RecruitmentJobs::find($data['jobId']);
                    if ($job) {
                        $result['job'] = [
                            'id' => $job->id,
                            'title' => $data['jobTitle'] ?? $job->job_title,
                            'company' => $data['companyName'] ?? $job->company->company_name
                        ];
                    }
                }
                
                return $result;
            });
            
            return response()->json([
                'success' => true,
                'data' => $transformedNotifications,
                'meta' => [
                    'current_page' => $notifications->currentPage(),
                    'per_page' => $notifications->perPage(),
                    'total' => $notifications->total(),
                    'last_page' => $notifications->lastPage(),
                    'from' => $notifications->firstItem(),
                    'to' => $notifications->lastItem(),
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Get notifications error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load notifications'
            ], 500);
        }
    }
    
    /**
     * Get notification statistics
     */
    public function stats()
    {
        try {
            $user = auth()->user();
            $stats = NotificationService::getStats($user);
            
            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
            
        } catch (\Exception $e) {
            Log::error('Get notification stats error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load notification statistics'
            ], 500);
        }
    }
    
    /**
     * Mark notification as read
     */
    public function markAsRead($id)
    {
        try {
            $user = auth()->user();
            
            $notification = Notification::where('userId', $user->id)
                ->where('id', $id)
                ->firstOrFail();
            
            $notification->markAsRead();
            
            return response()->json([
                'success' => true,
                'message' => 'Notification marked as read'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Mark notification as read error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark notification as read'
            ], 500);
        }
    }
    
    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        try {
            $user = auth()->user();
            
            Notification::where('userId', $user->id)
                ->whereNull('readAt')
                ->update(['readAt' => now()]);
            
            return response()->json([
                'success' => true,
                'message' => 'All notifications marked as read'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Mark all notifications as read error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark notifications as read'
            ], 500);
        }
    }
    
    /**
     * Delete a notification
     */
    public function destroy($id)
    {
        try {
            $user = auth()->user();
            
            $notification = Notification::where('userId', $user->id)
                ->where('id', $id)
                ->firstOrFail();
            
            $notification->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Notification deleted successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Delete notification error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete notification'
            ], 500);
        }
    }
    
    /**
     * Clear all notifications
     */
    public function clearAll()
    {
        try {
            $user = auth()->user();
            
            Notification::where('userId', $user->id)->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'All notifications cleared'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Clear all notifications error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear notifications'
            ], 500);
        }
    }
    
    /**
     * Get unread notifications count (for header/badge)
     */
    public function unreadCount()
    {
        try {
            $user = auth()->user();
            
            $count = Notification::where('userId', $user->id)
                ->whereNull('readAt')
                ->count();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'count' => $count
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Get unread count error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get unread count'
            ], 500);
        }
    }
}