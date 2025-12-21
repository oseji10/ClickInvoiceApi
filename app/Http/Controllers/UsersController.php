<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Staff; 
use App\Models\StaffType;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Mail\WelcomeEmail;
use Illuminate\Support\Facades\Mail;
use DB;
use Illuminate\Http\JsonResponse;
use App\Models\Lgas;
use App\Models\StateCoordinators;
use App\Models\CommunityLead;
use App\Models\DriversLicense;
use App\Models\Education;
use App\Models\Skills;
use App\Models\WorkExperience;
use Illuminate\Queue\Worker;
use Illuminate\Support\Facades\Auth;

class UsersController extends Controller
{
    // public function index()
    // {
    //     $users = User::with('staff.staff_type')->get();
    //     return response()->json($users);
       
    // }






 



    
        public function update(Request $request, $id)  
{
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->update($request->all());
        return response()->json($user);
    }

   public function destroy($id): JsonResponse
    {
        return DB::transaction(function () use ($id) {
            // Find the user
            $user = User::find($id);
            if (!$user) {
                return response()->json(['message' => 'Staff not found'], 404);
            }

            // Find the associated staff record
            // $staff = Staff::where('userId', $id)->first();
            // if (!$staff) {
            //     return response()->json(['message' => 'Associated staff record not found'], 404);
            // }

            // Delete both records
            // $staff->delete();
            $user->delete();

            return response()->json(['message' => 'User deleted successfully']);
        }, 5);
    }

    public function userBiodataProfile(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        return response()->json([
            'firstName' => $user->firstName,
            'lastName' => $user->lastName,
            'otherNames' => $user->otherNames,
            'email' => $user->email,
            'phoneNumber' => $user->phoneNumber,
            'role' => $user->user_role->roleName ?? null,
            'profileImage' => $user->profileImage ?? null,
            'coverImage' => $user->coverImage ?? null,
            'location' => $user->location,
            'bio' => $user->bio,
            'created_at' => $user->created_at,
       
        ]);
    }


 public function userEducationProfile(Request $request)
{
    $user = auth()->user();

    if (!$user) {
        return response()->json([
            'message' => 'User not authenticated'
        ], 401);
    }

    // Fetch ALL education records
    $education = Education::where('userId', $user->id)->get();

    // If empty, return safe empty array
    if ($education->isEmpty()) {
        return response()->json([
            'message' => 'No education records found',
            'education' => []
        ], 200);
    }

    // Return all records
    return response()->json([
        'education' => $education
    ], 200);
}



    public function userExperienceProfile(Request $request)
{
    $user = auth()->user();

    if (!$user) {
        return response()->json([
            'message' => 'User not authenticated'
        ], 401);
    }

    $workExperience = WorkExperience::where('userId', $user->id)->get();

    

    // If found, return the actual fields
    return response()->json([
        'workExperience' => $workExperience
    ], 200);
}



    public function userSkillsProfile(Request $request)
{
    $user = auth()->user();

    if (!$user) {
        return response()->json([
            'message' => 'User not authenticated'
        ], 401);
    }

    $skills = Skills::where('userId', $user->id)->get();

   

     return response()->json([
        'skills' => $skills
    ], 200);
}


public function userDriversLicenseProfile(Request $request)
{
    $user = auth()->user();

    if (!$user) {
        return response()->json([
            'message' => 'User not authenticated'
        ], 401);
    }

    $driversLicense = DriversLicense::where('userId', $user->id)->first();

   

     return response()->json([
        'driversLicense' => $driversLicense
    ], 200);
}

public function storeUserBiodata(Request $request)
{
    $user = auth()->user();

    if (!$user) {
        return response()->json(['message' => 'User not authenticated'], 401);
    }

    $validated = $request->validate([
        'firstName' => 'nullable|string|max:255',
        'lastName' => 'nullable|string|max:255',
        'otherNames' => 'nullable|string|max:255',
        'phoneNumber' => 'nullable|string|max:20',
        'location' => 'nullable|string|max:255',
        'bio' => 'nullable|string'
    ]);

    // Update user biodata
    $user->update($validated);

    return response()->json([
        'message' => 'Biodata updated successfully',
        'data' => $user
    ]);
}

public function storeUserEducation(Request $request)
{
    $user = auth()->user();

    if (!$user) {
        return response()->json(['message' => 'User not authenticated'], 401);
    }

    $validated = $request->validate([
        'institutionName' => 'nullable|string|max:255',
        'degree' => 'nullable|string|max:255',
        'fieldOfStudy' => 'nullable|string|max:255',
        'startDate' => 'nullable|date',
        'endDate' => 'nullable|date',
        'description' => 'nullable|string'
    ]);

    // Force userId into the data
    $validated['userId'] = $user->id;

    // CREATE always — never update existing
    $education = Education::create($validated);

    return response()->json([
        'message' => 'Education added successfully',
        'data' => $education
    ]);
}


public function deleteUserEducation(Request $request, $id)
{
    $user = auth()->user();

    if (!$user) {
        return response()->json(['message' => 'User not authenticated'], 401);
    }

    $education = Education::where('educationId', $id)
        ->where('userId', $user->id)
        ->first();

    if (!$education) {
        return response()->json(['message' => 'Education record not found'], 404);
    }

    $education->delete();

    return response()->json(['message' => 'Education record deleted successfully']);
}


public function storeUserExperience(Request $request)
{
    $user = auth()->user();

    if (!$user) {
        return response()->json(['message' => 'User not authenticated'], 401);
    }

    $validated = $request->validate([
        'companyName' => 'nullable|string|max:255',
        'position' => 'nullable|string|max:255',
        'location' => 'nullable|string|max:255',
        'startDate' => 'nullable|date',
        'endDate' => 'nullable|date',
        'description' => 'nullable|string'
    ]);

     $validated['userId'] = $user->id;

    // CREATE always — never update existing
    $experience = WorkExperience::create($validated);

    return response()->json([
        'message' => 'Work experience saved successfully',
        'data' => $experience
    ]);
}

public function deleteUserExperience(Request $request, $id)
{
    $user = auth()->user();

    if (!$user) {
        return response()->json(['message' => 'User not authenticated'], 401);
    }

    $experience = WorkExperience::where('workExperienceId', $id)
        ->where('userId', $user->id)
        ->first();

    if (!$experience) {
        return response()->json(['message' => 'Work experience record not found'], 404);
    }

    $experience->delete();

    return response()->json(['message' => 'Work experience record deleted successfully']);
}


public function storeUserSkills(Request $request)
{
    $user = auth()->user();

    if (!$user) {
        return response()->json(['message' => 'User not authenticated'], 401);
    }

    $validated = $request->validate([
        'skillName'  => 'nullable|string|max:255',
        'skillLevel' => 'nullable|string|max:100'
    ]);

      $validated['userId'] = $user->id;
      $skills = Skills::create($validated);

  

    return response()->json([
        'message' => 'Skills saved successfully',
        'data' => $skills
    ]);
}


public function deleteUserSkills(Request $request, $id)
{
    $user = auth()->user();
    if (!$user) {
        return response()->json(['message' => 'User not authenticated'], 401);
    }
    $skills = Skills::where('skillId', $id)
        ->where('userId', $user->id)
        ->first();
    if (!$skills) {
        return response()->json(['message' => 'Skills record not found'], 404);
    }
    $skills->delete();
    return response()->json(['message' => 'Skills record deleted successfully']);
}


public function storeUserDriversLicense(Request $request)
{
    $user = auth()->user();

    if (!$user) {
        return response()->json(['message' => 'User not authenticated'], 401);
    }

    $validated = $request->validate([
        'licenseId' => 'nullable|string|max:255',
        'issueDate' => 'nullable|date',
        'expiryDate' => 'nullable|date',
        'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
    ]);

    // Handle uploaded image
    if ($request->hasFile('image')) {
        $image = $request->file('image');
        $imageName = time() . '_' . $user->id . '.' . $image->getClientOriginalExtension();
        $image->move(public_path('drivers-license'), $imageName);

        // Add image path to validated array
        $validated['image'] = 'drivers-license/' . $imageName;
    }

    // Store or update driver's license
    $driversLicense = DriversLicense::updateOrCreate(
        ['userId' => $user->id],
        $validated
    );

    return response()->json([
        'message' => "Driver's license saved successfully",
        'data' => $driversLicense
    ]);
}



public function deleteUserDriversLicense(Request $request, $id)
{
    $user = auth()->user();
    if (!$user) {
        return response()->json(['message' => 'User not authenticated'], 401);
    }
    $driversLicense = DriversLicense::where('id', $id)
        ->where('userId', $user->id)
        ->first();
    if (!$driversLicense) {
        return response()->json(['message' => 'Driver\'s license record not found'], 404);
    }
    $driversLicense->delete();
    return response()->json(['message' => 'Driver\'s license record deleted successfully']);
}


    public function uploadProfileImage(Request $request)
    {
        $request->validate([
            'profileImage' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // max 2MB
        ]);

        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        // Handle file upload
       if ($request->hasFile('profileImage')) {
    $image = $request->file('profileImage');
    $imageName = time() . '_' . $user->id . '.' . $image->getClientOriginalExtension();

    // Store using Laravel storage
    $path = $image->storeAs('profile-images', $imageName, 'public');

    // Save path
    $user->profileImage = $path; // this becomes "profile-images/xxxx.jpg"
    $user->save();

    return response()->json([
        'message' => 'Profile image uploaded successfully',
        'profileImage' => $user->profileImage
    ]);
}


        return response()->json(['message' => 'No image uploaded'], 400);
    }



    public function uploadCoverImage(Request $request)
{
    $request->validate([
        'coverImage' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // max 2MB
    ]);

    $user = auth()->user();

    if (!$user) {
        return response()->json(['message' => 'User not authenticated'], 401);
    }

    if ($request->hasFile('coverImage')) {

        $image = $request->file('coverImage');
        $imageName = time() . '_' . $user->id . '.' . $image->getClientOriginalExtension();

        // Store using Laravel storage (correct way)
        $path = $image->storeAs('cover-images', $imageName, 'public');

        // Save the DB path
        $user->coverImage = $path; // e.g. cover-images/123_1.jpg
        $user->save();

        return response()->json([
            'message' => 'Cover image uploaded successfully',
            'coverImage' => $user->coverImage
        ]);
    }

    return response()->json(['message' => 'No image uploaded'], 400);
}



    public function currentUser(Request $request)
{
    $user = Auth::user();

    return response()->json([
        'user' => [
            'id' => $user->id,
            'full_name' => $user->firstName . ' ' . $user->lastName,
            'role' => $user->role,
            'phoneNumber' => $user->phoneNumber,
            'email' => $user->email,
        ]
    ]);
}


public function profile($id, Request $request)
{
    $authUser = Auth::user(); // logged-in user
    $user = User::with('user_role')->find($authUser->id); // Eager load role

    if (!$user) {
        return response()->json([
            'message' => 'User not found'
        ], 404);
    }

    // Check if authenticated user is following this profile
    $isFollowing = false;
    if ($authUser && $authUser->id !== $user->id) {
        $isFollowing = $authUser->followings()->where('followedId', $user->id)->exists();
    }

    return response()->json([
        'id' => $user->id,
        'firstName' => $user->firstName,
        'lastName' => $user->lastName,
        'bio' => $user->bio,
        'profileImage' => $user->profileImage,
        'coverImage' => $user->coverImage,
        'role' => $user->user_role->roleName ?? 'Member', // Get role name with fallback
        // 'roleId' => $user->role_id, // Also include roleId if needed
        'followersCount' => $user->followers()->count(),
        'followingCount' => $user->followings()->count(),
        'isFollowing' => $isFollowing,
    ]);
}

}
