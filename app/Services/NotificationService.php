<?php

namespace App\Services;

use App\Models\User;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Create a new notification
     */
    public static function create(
        User $user,
        string $type,
        string $title,
        string $message,
        array $data = []
    ): Notification {
        try {
            $notification = Notification::create([
                'userId' => $user->id,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'data' => $data,
                'readAt' => null
            ]);

            // Optionally send real-time notification via WebSocket or Pusher
            self::sendRealTimeNotification($notification);

            return $notification;
        } catch (\Exception $e) {
            Log::error('Failed to create notification: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Notify when someone follows a user
     */
    public static function notifyNewFollower(User $follower, User $followed): Notification
    {
        return self::create(
            $followed,
            'follow',
            'New Follower',
            $follower->full_name . ' started following you',
            [
                'senderId' => $follower->id,
                'senderName' => $follower->fullName,
                'senderAvatar' => $follower->profileImage,
                'senderRole' => $follower->role
            ]
        );
    }

    /**
     * Notify when someone likes a post
     */
    public static function notifyPostLike(User $liker, $post): Notification
    {
        return self::create(
            $post->user,
            'post_like',
            'Post Liked',
            $liker->fullName . ' liked your post: "' . substr($post->content, 0, 50) . '..."',
            [
                'senderId' => $liker->id,
                'senderName' => $liker->fullName,
                'senderAvatar' => $liker->profileImage,
                'postId' => $post->id,
                'postTitle' => $post->title,
                'postExcerpt' => substr($post->content, 0, 100)
            ]
        );
    }

    /**
     * Notify when someone comments on a post
     */
    public static function notifyPostComment(User $commenter, $post, $comment): Notification
    {
        return self::create(
            $post->user,
            'post_comment',
            'New Comment',
            $commenter->fullName . ' commented on your post: "' . substr($comment, 0, 50) . '..."',
            [
                'senderId' => $commenter->id,
                'senderName' => $commenter->fullName,
                'senderAvatar' => $commenter->profileImage,
                'postId' => $post->id,
                'postTitle' => $post->title,
                'commentId' => $comment->id,
                'commentExcerpt' => substr($comment, 0, 100)
            ]
        );
    }

    /**
     * Notify when a new job is posted (for relevant users)
     */
    public static function notifyNewJobPost($job, array $relevantUserIds): array
    {
        $notifications = [];
        
        foreach ($relevantUserIds as $userId) {
            $user = User::find($userId);
            if ($user) {
                $notification = self::create(
                    $user,
                    'job_post',
                    'New Job Post',
                    'New job posted: ' . $job->jobTitle,
                    [
                        'jobId' => $job->jobId,
                        'jobTitle' => $job->jobTitle,
                        'companyName' => $job->company->companyName,
                        'jobLocation' => $job->jobLocation
                    ]
                );
                $notifications[] = $notification;
            }
        }
        
        return $notifications;
    }

    /**
     * Notify when someone applies for a job
     */
    public static function notifyJobApplication(User $applicant, $job, $application): Notification
    {
        return self::create(
            $job->user, // Job poster
            'job_application',
            'New Job Application',
            $applicant->full_name . ' applied for your job: ' . $job->jobTitle,
            [
                'senderId' => $applicant->id,
                'senderName' => $applicant->full_name,
                'senderAvatar' => $applicant->profile_image,
                'jobId' => $job->jobId,
                'jobTitle' => $job->jobTitle,
                'applicationId' => $application->id
            ]
        );
    }

    /**
     * Notify when someone sends a message
     */
    public static function notifyNewMessage(User $sender, User $receiver, $message): Notification
    {
        return self::create(
            $receiver,
            'message',
            'New Message',
            $sender->fullName . ' sent you a message',
            [
                'senderId' => $sender->id,
                'senderName' => $sender->fullName,
                'senderAvatar' => $sender->profileImage,
                'messageId' => $message->id,
                'messageExcerpt' => substr($message->content, 0, 100),
                'conversationId' => $message->conversationId
            ]
        );
    }

    /**
     * Send system notification
     */
    public static function notifySystem(User $user, string $title, string $message, array $data = []): Notification
    {
        return self::create(
            $user,
            'system',
            $title,
            $message,
            $data
        );
    }

    /**
     * Send real-time notification via WebSocket/Pusher
     */
    private static function sendRealTimeNotification(Notification $notification): void
    {
        try {
            // You can use Laravel Echo, Pusher, or any other real-time service
            // Example with Pusher:
            // event(new NewNotificationEvent($notification));
            
            // For now, we'll just log it
            Log::info('Real-time notification would be sent for notification: ' . $notification->id);
        } catch (\Exception $e) {
            Log::error('Failed to send real-time notification: ' . $e->getMessage());
        }
    }

    /**
     * Get notification statistics for a user
     */
    public static function getStats(User $user): array
    {
        $total = Notification::forUser($user->id)->count();
        $unread = Notification::forUser($user->id)->unread()->count();
        
        $byType = [
            'follow' => Notification::forUser($user->id)->byType('follow')->count(),
            'postLike' => Notification::forUser($user->id)->byType('post_like')->count(),
            'postComment' => Notification::forUser($user->id)->byType('post_comment')->count(),
            'postShare' => Notification::forUser($user->id)->byType('post_share')->count(),
            'jobPost' => Notification::forUser($user->id)->byType('job_post')->count(),
            'jobApplication' => Notification::forUser($user->id)->byType('job_application')->count(),
            'message' => Notification::forUser($user->id)->byType('message')->count(),
            'system' => Notification::forUser($user->id)->byType('system')->count(),
        ];
        
        return [
            'total' => $total,
            'unread' => $unread,
            'by_type' => $byType
        ];
    }
}