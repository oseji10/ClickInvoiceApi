<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecruitmentJobApplications extends Model
{
     protected $table = 'recruitment_job_applications';

    protected $primaryKey = 'applicationId';
    protected $fillable = [
        'applicationId',
        'jobId',
        'applicantId',
        'applicationDate',
        'applicationStatus',
        'coverLetter',
        'resumePath',
        
    ];

    public function job()
    {
        return $this->belongsTo(RecruitmentJobs::class, 'jobId', 'jobId');
    }

public function applicant()
    {
        return $this->belongsTo(User::class, 'applicantId', 'id');
    }

    public function education()
    {
        return $this->hasMany(Education::class, 'userId', 'applicantId');
    }

    public function workExperience()
    {
        return $this->hasMany(WorkExperience::class, 'userId', 'applicantId');
    }

    public function driversLicense()
    {
        return $this->hasMany(DriversLicense::class, 'userId', 'applicantId');
    }

    public function skills(){
        return $this->hasMany(Skills::class, 'userId', 'applicantId');
    }

   
}
