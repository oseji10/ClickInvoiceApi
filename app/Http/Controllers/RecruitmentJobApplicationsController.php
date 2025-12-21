<?php

namespace App\Http\Controllers;

use App\Models\RecruitmentJobApplications;
use App\Models\RecruitmentJobs;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class RecruitmentJobApplicationsController extends Controller
{
    public function index()
    {
        $applications = RecruitmentJobApplications::with('job', 'job.company', 'applicant', 'education', 'workExperience', 'driversLicense', 'skills')->get();
        return response()->json($applications);
       
    }

   

    public function store(Request $request)
    {
        // Directly get the data from the request
        $data = $request->all();
    
         $validated = $request->validate([
            'jobId' => 'required|integer|exists:recruitment_jobs,jobId',
            'coverLetter' => 'required|string',
            'resume' => 'nullable|file|mimes:pdf,doc,docx|max:2048', // 2MB max
            'applicationStatus' => 'required|string',
        ]);

        $validated['applicantId'] = auth()->id();
        $validated['applicationDate'] = now();
        $applications = RecruitmentJobApplications::create($validated);

        $job = RecruitmentJobs::findOrFail($validated['jobId']);
         if ($applications->applicantId !== auth()->id()) {
        NotificationService::notifyJobApplication(auth()->user(), $job, $applications);
    }
    
        // Return a response, typically JSON
        return response()->json($applications, 201); // HTTP status code 201: Created
    }



    public function checkApplicationStatus($jobId)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'hasApplied' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $job = RecruitmentJobs::find($jobId);
            
            if (!$job) {
                return response()->json([
                    'hasApplied' => false,
                    'message' => 'Job not found'
                ], 404);
            }

            $application = RecruitmentJobApplications::where('jobId', $jobId)
                ->where('applicantId', $user->id)
                ->first();

            return response()->json([
                'hasApplied' => !is_null($application),
                'application' => $application,
                'applicationStatus' => $application ? $application->status : null
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'hasApplied' => false,
                'message' => 'Error checking application status',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function updateApplicationStatus(Request $request, $applicantId)
    {
        $request->validate([
            'applicationStatus' => 'required|string',
        ]);

        $application = RecruitmentJobApplications::find($applicantId);
        if (!$application) {
            return response()->json(['message' => 'Application not found'], 404);
        }

        $application->applicationStatus = $request->applicationStatus;
        $application->save();

        return response()->json($application);
    }


    public function getJobApplications($jobId)
    {
        $job = RecruitmentJobs::find($jobId);
        if (!$job) {
            return response()->json(['message' => 'Job not found'], 404);
        }

        $applications = RecruitmentJobApplications::with('applicant')
            ->where('jobId', $jobId)
            ->get();

        return response()->json($applications);
    }
    
}
