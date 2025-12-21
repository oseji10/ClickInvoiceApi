<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LearningController extends Controller
{
    /**
     * GET /learning
     * List all published courses with enrollment info for the logged-in user.
     */
    public function index(): JsonResponse
    {
        try {
            $user = Auth::user();

            $courses = Course::with('instructor')
                ->withCount('modules')
                ->where('published', 1)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($course) use ($user) {
                    $course->enrolled = $user
                        ? $user->enrolledCourses()
                               ->where('course_id', $course->id)
                               ->whereIn('status', ['active', 'completed'])
                               ->exists()
                        : false;
                    return $course;
                });

            return response()->json($courses);
        } catch (\Throwable $e) {
            Log::error('LearningController::index error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Failed to fetch courses.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /learning/{id}
     * Fetch single course with modules, lessons, and enrollment info.
     */
    public function show(Request $request, $id): JsonResponse
    {
        try {
            $course = Course::with([
                'modules' => fn($q) => $q->orderBy('position', 'asc'),
                'modules.lessons' => fn($q) => $q->orderBy('position', 'asc'),
                'instructor'
            ])->where('published', 1)->findOrFail($id);

            $course->modules = $course->modules->map(function ($module) {
                $module->lessons = $module->lessons ?? collect([]);
                return $module;
            });

            $user = Auth::user();
            $course->enrolled = $user
                ? $user->enrolledCourses()
                       ->where('course_id', $course->id)
                       ->whereIn('status', ['active', 'completed'])
                       ->exists()
                : false;

            return response()->json($course);
        } catch (\Throwable $e) {
            Log::error('LearningController::show error', [
                'courseId' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Failed to load course.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /learning/{id}/checkout
     * Initialize payment for a paid course.
     */
    public function createCheckoutSession(Request $request, $id): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) return response()->json(['message' => 'Unauthorized'], 401);

            $course = Course::findOrFail($id);

            if (!$course->price || $course->price <= 0) {
                return response()->json(['message' => 'Course is free. No payment needed.'], 400);
            }

            $paystackSecret = env('PAYSTACK_SECRET_KEY');
            if (!$paystackSecret) return response()->json(['message' => 'Payment gateway not configured.'], 500);

            $callbackUrl = rtrim(env('FRONTEND_URL'), '/') . "/dashboard/learning/enroll?courseId={$id}";

            $response = Http::withToken($paystackSecret)
                ->post('https://api.paystack.co/transaction/initialize', [
                    'email' => $user->email,
                    'amount' => intval($course->price * 100),
                    'currency' => 'NGN',
                    'callback_url' => $callbackUrl,
                    'metadata' => [
                        'course_id' => $id,
                        'user_id' => $user->id,
                        'course_title' => $course->title,
                    ],
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return response()->json([
                    'authorization_url' => $data['data']['authorization_url'] ?? ($data['data']['url'] ?? null)
                ]);
            }

            return response()->json([
                'message' => 'Failed to initiate payment.',
                'error' => $response->body(),
            ], 500);
        } catch (\Throwable $e) {
            Log::error('LearningController::createCheckoutSession error', [
                'courseId' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Payment initialization error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /learning/{id}/enroll
     * Enroll the authenticated user in a free course.
     */
    public function enroll(Request $request, $id): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) return response()->json(['message' => 'Unauthorized'], 401);

            $course = Course::findOrFail($id);

            $alreadyEnrolled = $user->enrolledCourses()
                ->where('course_id', $course->id)
                ->whereIn('status', ['active', 'completed'])
                ->exists();

            if ($alreadyEnrolled) {
                return response()->json(['enrolled' => true, 'message' => 'Already enrolled']);
            }

            if (!$course->price || $course->price <= 0) {
                $user->enrolledCourses()->create([
                    'course_id' => $course->id,
                    'status' => 'active',
                    'started_at' => now(),
                ]);

                return response()->json(['enrolled' => true, 'message' => 'Successfully enrolled']);
            }

            return response()->json([
                'enrolled' => false,
                'message' => 'Course requires payment. Complete checkout first.',
            ]);
        } catch (\Throwable $e) {
            Log::error('LearningController::enroll error', [
                'courseId' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'enrolled' => false,
                'message' => 'Enrollment failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /learning/{id}/payment-verify
     * Verify Paystack payment and enroll user if successful.
     */
    public function paymentVerify(Request $request, $id): JsonResponse
    {
        $reference = $request->query('reference') ?? $request->query('trxref');
        if (!$reference) return response()->json(['enrolled' => false, 'message' => 'Payment reference missing.'], 400);

        $paystackSecret = env('PAYSTACK_SECRET_KEY');
        if (!$paystackSecret) return response()->json(['enrolled' => false, 'message' => 'Payment gateway not configured.'], 500);

        try {
            $response = Http::withToken($paystackSecret)
                ->get("https://api.paystack.co/transaction/verify/{$reference}");

            if (!$response->successful()) {
                return response()->json(['enrolled' => false, 'message' => 'Payment verification failed.'], 500);
            }

            $data = $response->json();
            if (($data['data']['status'] ?? null) !== 'success') {
                return response()->json(['enrolled' => false, 'message' => 'Payment was not successful.'], 400);
            }

            $userId = $data['data']['metadata']['user_id'] ?? null;
            $courseId = $data['data']['metadata']['course_id'] ?? $id;

            if (!$userId) return response()->json(['enrolled' => false, 'message' => 'User not resolved from payment metadata.'], 400);

            $user = User::find($userId);
            if (!$user) return response()->json(['enrolled' => false, 'message' => 'User not found.'], 404);

            $course = Course::with([
                'modules' => fn($q) => $q->orderBy('position', 'asc'),
                'modules.lessons' => fn($q) => $q->orderBy('position', 'asc'),
            ])->findOrFail($courseId);

            $alreadyEnrolled = $user->enrolledCourses()
                ->where('course_id', $course->id)
                ->whereIn('status', ['active', 'completed'])
                ->exists();

            if (!$alreadyEnrolled) {
                $user->enrolledCourses()->create([
                    'course_id' => $course->id,
                    'status' => 'active',
                    'started_at' => now(),
                    'payment_reference' => $reference,
                ]);
            }

            $firstLessonId = null;
            foreach ($course->modules as $module) {
                if ($module->lessons && $module->lessons->count() > 0) {
                    $firstLessonId = $module->lessons->first()->id;
                    break;
                }
            }

            return response()->json([
                'enrolled' => true,
                'message' => 'Payment successful! Redirecting to your course.',
                'course_id' => $course->id,
                'first_lesson_id' => $firstLessonId,
            ]);
        } catch (\Throwable $e) {
            Log::error('LearningController::paymentVerify error', [
                'courseId' => $id,
                'reference' => $reference,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'enrolled' => false,
                'message' => 'Payment verification error: ' . $e->getMessage(),
            ], 500);
        }
    }
}
