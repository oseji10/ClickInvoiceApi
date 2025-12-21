<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'userId',
        'type',
        'title',
        'message',
        'data',
        'readAt'
    ];

    protected $casts = [
        'data' => 'array',
        'readAt' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeUnread($query)
    {
        return $query->whereNull('readAt');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('userId', $userId);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Helper methods
    public function markAsRead()
    {
        if (!$this->readAt) {
            $this->update(['readAt' => now()]);
        }
        return $this;
    }

    public function isRead(): bool
    {
        return !is_null($this->readAt);
    }

    public function isUnread(): bool
    {
        return is_null($this->readAt);
    }

    public function getSenderAttribute()
    {
        if (isset($this->data['senderId'])) {
            return User::find($this->data['senderId']);
        }
        return null;
    }

    public function getPostAttribute()
    {
        if (isset($this->data['postId'])) {
            return Posts::find($this->data['postId']);
        }
        return null;
    }

    public function getJobAttribute()
    {
        if (isset($this->data['jobId'])) {
            return RecruitmentJobs::find($this->data['jobId']);
        }
        return null;
    }
}