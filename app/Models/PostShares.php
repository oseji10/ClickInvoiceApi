<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostShares extends Model
{
    protected $table = 'post_shares';
    protected $primaryKey = 'postShareId';

    protected $fillable = [
        'userId',
        'postId',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'userId', 'id');
    }
}
