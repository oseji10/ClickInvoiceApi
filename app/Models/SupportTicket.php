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

    public function replies()
    {
        return $this->hasMany(SupportReply::class, 'ticketId', 'ticketId');
    }

    
};