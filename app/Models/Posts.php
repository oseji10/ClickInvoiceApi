<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Posts extends Model
{
    protected $table = 'posts';

    protected $primaryKey = 'postId';

    protected $fillable = [
        'userId',
        'title',
        'body',
        'uploadUrl',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'userId');
    }

    public function likes()
    {
        return $this->hasMany(PostLikes::class, 'postId');
    }

    public function shares()
    {
        return $this->hasMany(PostShares::class, 'postId');
    }

    public function comments()
    {
        return $this->hasMany(PostComments::class, 'postId');
    }
}
