<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkExperience extends Model
{
    protected $table = 'work_experience';

    protected $primaryKey = 'workExperienceId';

    protected $fillable = [
        'userId',
        'companyName',
        'position',
        'location',
        'startDate',
        'endDate',
        'description',
        'isCurrentlyWorking',

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
