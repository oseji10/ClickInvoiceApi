<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportReply extends Model
{
    use HasFactory;

    protected $table = 'support_replies';
    protected $primaryKey = 'replyId';

    protected $fillable = [
        'ticketId',
        'userId',
        'message',
        'is_admin',
    ];

    public function ticket()
    {
        return $this->belongsTo(SupportTicket::class, 'ticketId', 'ticketId');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'userId', 'id');
    }
};