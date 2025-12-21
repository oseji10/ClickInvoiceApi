<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Education extends Model
{
    protected $table = 'education';

    protected $primaryKey = 'educationId';

    protected $fillable = [
        'userId',
        'institutionName',
        'degree',
        'fieldOfStudy',
        'startDate',
        'endDate',
        'description',
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
}
