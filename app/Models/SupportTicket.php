<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    use HasFactory;

    protected $table = 'support_tickets';
    protected $primaryKey = 'ticketId';

    protected $fillable = [
        'userId',
        'subject',
        'message',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'userId', 'id');
    }

    // public function replies()
    // {
    //     return $this->hasMany(SupportReply::class, 'ticketId', 'ticketId');
    // }

    public function replies()
{
    return $this->hasMany(SupportReply::class, 'ticketId', 'ticketId')
                ->orderBy('created_at', 'asc'); // Always oldest first
}

    /**
     * Scope to find tickets inactive for more than 48 hours
     */
    public function scopeInactiveFor48Hours($query)
    {
        return $query->whereIn('status', ['open', 'in_progress'])
                     ->where('updated_at', '<', Carbon::now()->subHours(48));
    }

    
};