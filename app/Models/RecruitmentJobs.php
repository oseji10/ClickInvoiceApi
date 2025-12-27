<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecruitmentJobs extends Model
{
    protected $table = 'recruitment_jobs';

    protected $primaryKey = 'jobId';
    protected $fillable = [
        'jobId',
        'companyId',
        'jobTitle',
        'jobDescription',
        'jobLocation',
        'jobType',
        'salary',
        'applicationDeadline',
        'jobStatus',
        'jobImage',
        'postedBy',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'companyId', 'companyId');
    }

    public function applications()
    {
        return $this->hasMany(RecruitmentJobApplications::class, 'jobId', 'jobId');
    }
}
