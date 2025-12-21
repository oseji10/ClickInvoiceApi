<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostLikes extends Model
{
    protected $table = 'post_likes';
    protected $primaryKey = 'postLikeId';

    protected $fillable = [
        'userId',
        'postId',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'userId', 'id');
    }
}
