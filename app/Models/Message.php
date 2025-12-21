<?php
// app/Models/Message.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'senderId',
        'receiverId',
        'message',
        'is_read'
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Relationship to sender
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'senderId', 'id');
    }

    /**
     * Relationship to receiver
     */
    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiverId', 'id');
    }

    /**
     * Scope for unread messages
     */
    public function scopeUnread($query, $userId)
    {
        return $query->where('receiverId', $userId)
                    ->where('is_read', false);
    }

    /**
     * Scope for conversation between two users
     */
    public function scopeConversation($query, $user1Id, $user2Id)
    {
        return $query->where(function($q) use ($user1Id, $user2Id) {
            $q->where('senderId', $user1Id)
              ->where('receiverId', $user2Id);
        })->orWhere(function($q) use ($user1Id, $user2Id) {
            $q->where('senderId', $user2Id)
              ->where('receiverId', $user1Id);
        });
    }

    /**
     * Get formatted timestamp
     */
   /**
 * Get formatted timestamp
 */
public function getFormattedTimeAttribute()
{
    if (!$this->created_at) {
        return 'Recently';
    }
    
    try {
        $now = now();
        $created = \Carbon\Carbon::parse($this->created_at);
        $diffMinutes = $created->diffInMinutes($now);

        if ($diffMinutes < 1) {
            return 'Just now';
        } elseif ($diffMinutes < 60) {
            return $diffMinutes . 'm ago';
        } elseif ($diffMinutes < 1440) {
            return floor($diffMinutes / 60) . 'h ago';
        } elseif ($diffMinutes < 10080) {
            return floor($diffMinutes / 1440) . 'd ago';
        } else {
            return $created->format('M d, Y');
        }
    } catch (\Exception $e) {
        return 'Recently';
    }
}
}