<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostComments extends Model
{
    protected $table = 'post_comments';
    protected $primaryKey = 'postCommentId';

    protected $fillable = [
        'userId',
        'postId',
        'comment',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'userId', 'id');
    }
}
