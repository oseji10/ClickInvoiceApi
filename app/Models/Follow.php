<?php
// app/Models/Follow.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Follow extends Model
{
    use HasFactory;

    protected $fillable = [
        'followerId',
        'followingId',
        'followedAt'
    ];

    protected $casts = [
        'followedAt' => 'datetime'
    ];

    // Don't use Laravel's timestamps
    public $timestamps = false;

    /**
     * Get the follower (user who is following)
     */
    public function follower(): BelongsTo
    {
        return $this->belongsTo(User::class, 'followerId', 'id');
    }

    /**
     * Get the following (user being followed)
     */
    public function following(): BelongsTo
    {
        return $this->belongsTo(User::class, 'followingId', 'id');
    }

    /**
     * Scope for checking if a user follows another
     */
    public function scopeFollowing($query, $followerId, $followingId)
    {
        return $query->where('followerId', $followerId)
                    ->where('followingId', $followingId);
    }

    /**
     * Scope for getting followers of a user
     */
    public function scopeFollowersOf($query, $userId)
    {
        return $query->where('followingId', $userId);
    }

    /**
     * Scope for getting who a user is following
     */
    public function scopeFollowingBy($query, $userId)
    {
        return $query->where('followerId', $userId);
    }
}