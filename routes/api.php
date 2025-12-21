<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CancerController;
use App\Http\Controllers\BeneficiariesController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\StateController;
use App\Http\Controllers\LgaController;
use App\Http\Controllers\ProductsController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\MinistryController;
use App\Http\Controllers\AgentsController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SuggestedFollowersController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\ProductRequestController;
use App\Http\Controllers\TransactionsController;
use App\Http\Controllers\ProjectsController;
use App\Http\Controllers\HubsController;
use App\Http\Controllers\MSPsController;
use App\Http\Controllers\FarmersController;
use App\Http\Controllers\MembershipController;
use App\Http\Controllers\ServicesController;
use App\Http\Controllers\CommodityController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\RecruitmentJobApplicationsController;
use App\Http\Controllers\LearningController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\InstructorCourseController;
use App\Http\Controllers\InstructorModuleController;
use App\Http\Controllers\InstructorLessonController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\InvoicePdfController;
use App\Http\Controllers\TenantsController;
use App\Models\Currency;
use App\Models\PaymentGateway;
use Tymon\JWTAuth\Claims\Custom;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::post('/resend-otp', [OtpController::class, 'resendOtp']);
Route::post('/verify-otp', [OtpController::class, 'verifyOtp']);
Route::post('/setup-password', [AuthController::class, 'setupPassword']);

Route::post('/signup', [AuthController::class, 'signup2']);
Route::post('/signin', [AuthController::class, 'signin']);
Route::post('/logout', [AuthController::class, 'logout']);
Route::post('/refresh', [AuthController::class, 'refresh']);
Route::get('/users/profile', [AuthController::class, 'profile'])->middleware('auth.jwt');
Route::get('/roles', [RolesController::class, 'index']);

Route::get('/currencies', function(){
    $currencies = Currency::orderBy('currencyId')->get()->makeHidden([ 'created_at', 'updated_at', 'deleted_at']);
    return response()->json($currencies);
});

Route::get('/payment-gateways', function(){
    $gateways = PaymentGateway::orderBy('gatewayId')->get()->makeHidden([ 'created_at', 'updated_at', 'deleted_at']);
    return response()->json($gateways);
});

// Stripe webhook (public)
Route::post('stripe/webhook', [StripeWebhookController::class, 'handle']);

Route::middleware(['auth.jwt', 'tenant'])->group(function () {

    Route::get('/user', function () {
        $user = auth()->user(); // Use the 'api' guard for JWT

        return response()->json([
            'user' => [
                // 'id' => (string) $user->id,
                'id' => $user->id,
                'full_name' => trim($user->firstName . ' ' . $user->lastName . ' ' . ($user->otherNames ?? '')),
                'role' => $user->user_role->roleName ?? null,
                'phoneNumber' => $user->phoneNumber,
                'email' => $user->email,
                'default_tenant' => $user->default_tenant,
                'tenantId' => $user->currently_active_tenant->tenantId ?? '',
            ]
        ]);
    });

    Route::patch('/tenants/{tenantId}/set-default', [TenantsController::class, 'setDefaultTenant']);

    // User profile routes
    Route::get('profile/biodata', [UsersController::class, 'userBiodataProfile']);
    Route::get('profile/education', [UsersController::class, 'userEducationProfile']);
    Route::get('profile/experience', [UsersController::class, 'userExperienceProfile']);
    Route::get('profile/skills', [UsersController::class, 'userSkillsProfile']);
    Route::get('profile/drivers-license', [UsersController::class, 'userDriversLicenseProfile']);

    Route::post('profile/biodata', [UsersController::class, 'storeUserBiodata']);
    Route::post('profile/education', [UsersController::class, 'storeUserEducation']);
    Route::post('profile/experience', [UsersController::class, 'storeUserExperience']);
    Route::post('profile/skills', [UsersController::class, 'storeUserSkills']);
    Route::post('profile/drivers-license', [UsersController::class, 'storeUserDriversLicense']);

    Route::delete('profile/education/{id}', [UsersController::class, 'deleteUserEducation']);
    Route::delete('profile/experience/{id}', [UsersController::class, 'deleteUserExperience']);
    Route::delete('profile/skills/{id}', [UsersController::class, 'deleteUserSkills']);
    Route::delete('profile/drivers-license/{id}', [UsersController::class, 'deleteUserDriversLicense']);

    Route::post('profile/upload-image', [UsersController::class, 'uploadProfileImage']);
    Route::post('profile/upload-cover-image', [UsersController::class, 'uploadCoverImage']);

    // Tenants
    Route::get('tenants', [TenantsController::class, 'index']);
    Route::post('tenants', [TenantsController::class, 'store']);
    Route::get('tenants/user-tenants', [TenantsController::class, 'myTenants']);

    Route::get('customers/tenant', [CustomerController::class, 'getTenantCustomers']);
    Route::post('customers/tenant', [CustomerController::class, 'storeTenantCustomer']);



    // Create a new invoice
    Route::get('/invoices/latest', [InvoiceController::class, 'getLast5UserInvoices']);
    Route::get('/invoices/summary', [InvoiceController::class, 'invoiceSummary']);

    Route::post('/invoices', [InvoiceController::class, 'store']);

    // Get all invoices for logged-in user
    Route::get('/invoices', [InvoiceController::class, 'getUserInvoices']);

    // Get a single invoice by tenant ID
    Route::get('/invoices/tenant/{tenantId}', [InvoiceController::class, 'getInvoiceByTenant']);

    // Get a single invoice by Invoice ID
    Route::get('/invoices/{invoiceId}', [InvoiceController::class, 'getInvoiceByInvoiceId']);
    Route::patch('/invoices/{invoiceId}/status', [InvoiceController::class, 'updateInvoiceStatus']);


    // Route::get('/invoices/{invoiceId}', [InvoiceController::class, 'show'])
    // ->where('invoiceId', '[A-Za-z0-9\-]+');


    // PDF routes
// Route::prefix('invoices')->group(function () {
//     Route::get('/{id}/pdf', [InvoicePdfController::class, 'download']);
//     Route::get('/{id}/stream-pdf', [InvoicePdfController::class, 'stream']);
//     Route::get('/{id}/generate-pdf', [InvoicePdfController::class, 'generate']);
//     Route::post('/{id}/send-email', [InvoicePdfController::class, 'sendEmail']);
// });

    Route::put('applications/{applicantId}/status', [RecruitmentJobApplicationsController::class, 'updateApplicationStatus']);

    // Posts
    Route::post('posts', [PostController::class, 'store']);
    Route::get('posts', [PostController::class, 'index']);
    Route::get('posts/{id}', [PostController::class, 'show']);
    Route::put('posts/{id}', [PostController::class, 'update']);
    Route::delete('posts/{id}', [PostController::class, 'destroy']);
    Route::post('posts/{id}/like', [PostController::class, 'likePost']);
    Route::post('posts/{id}/unlike', [PostController::class, 'unlikePost']);
    Route::post('posts/{id}/share', [PostController::class, 'sharePost']);
    Route::post('posts/{id}/unshare', [PostController::class, 'unsharePost']);
    Route::post('posts/{id}/comment', [PostController::class, 'commentPost']);
    Route::post('posts/{id}/uncomment', [PostController::class, 'uncommentPost']);
    Route::get('posts/{id}/comments', [PostController::class, 'getComments']);
    Route::get('posts/{id}/likes', [PostController::class, 'getLikes']);
    Route::get('posts/{id}/shares', [PostController::class, 'getShares']);

    // Companies
    Route::get('companies', [CompanyController::class, 'index']);
    Route::post('companies', [CompanyController::class, 'store']);
    Route::get('companies/{id}', [CompanyController::class, 'show']);
    Route::post('companies/{id}', [CompanyController::class, 'update']);
    Route::delete('companies/{id}', [CompanyController::class, 'destroy']);
    Route::get('my-companies', [CompanyController::class, 'myCompanies']);

    // Jobs
    Route::post('jobs', [JobController::class, 'store']);
    Route::get('jobs', [JobController::class, 'index']);
    Route::get('jobs/{id}', [JobController::class, 'show']);
    Route::post('jobs/{id}', [JobController::class, 'update']);
    Route::delete('jobs/{id}', [JobController::class, 'destroy']);
    Route::get('my-jobs', [JobController::class, 'myJobs']);
    Route::post('jobs/{id}/apply', [RecruitmentJobApplicationsController::class, 'store']);
    Route::get('jobs/{id}/application-status', [RecruitmentJobApplicationsController::class, 'checkApplicationStatus']);
    Route::get('jobs/{id}/applications', [RecruitmentJobApplicationsController::class, 'getJobApplications']);
    Route::get('my-applications', [JobController::class, 'myApplications']);

    // Learning endpoints (authenticated)
    Route::get('learning', [LearningController::class, 'index']);
    Route::get('learning/{id}', [LearningController::class, 'show']);
    Route::post('learning/{id}/checkout', [LearningController::class, 'createCheckoutSession']);

    // **Added payment verification route**
    Route::get('learning/{id}/payment-verify', [LearningController::class, 'paymentVerify']);

    // Instructor API - courses / modules / lessons
    Route::get('instructor/courses', [InstructorCourseController::class, 'index']);
    Route::post('instructor/courses', [InstructorCourseController::class, 'store']);
    Route::get('instructor/courses/{id}', [InstructorCourseController::class, 'show']);
    Route::put('instructor/courses/{id}', [InstructorCourseController::class, 'update']);
    Route::patch('instructor/courses/{id}', [InstructorCourseController::class, 'update']);
    Route::delete('instructor/courses/{id}', [InstructorCourseController::class, 'destroy']);

    Route::get('instructor/modules', [InstructorModuleController::class, 'index']);
    Route::post('instructor/modules', [InstructorModuleController::class, 'store']);

    Route::get('instructor/lessons', [InstructorLessonController::class, 'index']);
    Route::post('instructor/lessons', [InstructorLessonController::class, 'store']);
    Route::get('instructor/lessons/{id}', [InstructorLessonController::class, 'show']);
    Route::put('instructor/lessons/{id}', [InstructorLessonController::class, 'update']);
    Route::patch('instructor/lessons/{id}', [InstructorLessonController::class, 'update']);
    Route::delete('instructor/lessons/{id}', [InstructorLessonController::class, 'destroy']);

    // Fetch a single lesson for a course
    Route::get('learning/{courseId}/lessons/{lessonId}', [LessonController::class, 'show']);

      // Chat routes
    Route::prefix('chat')->group(function () {
        Route::get('/users', [ChatController::class, 'getChatUsers']);
        Route::get('/messages/{userId}', [ChatController::class, 'getMessages']);
        Route::post('/send', [ChatController::class, 'sendMessage']);
        Route::post('/mark-read', [ChatController::class, 'markAsRead']);
        Route::get('/unread-count', [ChatController::class, 'getUnreadCount']);
        Route::post('/update-last-seen', [ChatController::class, 'updateLastSeen']);
    });



    Route::get('/users/{id}/profile', [UsersController::class, 'profile']);
});

Route::prefix('invoices')->group(function () {
    Route::get('/{id}/pdf', [InvoicePdfController::class, 'download']);
    Route::get('/{id}/stream-pdf', [InvoicePdfController::class, 'stream']);
    Route::get('/{id}/generate-pdf', [InvoicePdfController::class, 'generate']);
    Route::post('/{id}/send-email', [InvoicePdfController::class, 'sendEmail']);
});
