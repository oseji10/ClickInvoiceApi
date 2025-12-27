<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Course;
use App\Models\User;
use App\Models\Lesson;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

/**
 * Combined controllers file containing:
 * - LearningController (handles courses, checkout, enrollment, payment verification)
 * - LessonController (handles fetching a single lesson by course + lesson id)
 */

/**
 * LearningController
 */
class LessonController extends Controller
{
    public function index(): JsonResponse
    {
        $courses = Course::with('instructor')
            ->withCount('modules')
            ->where('published', 1)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($courses);
    }

    // public function show($id): JsonResponse
    // {
    //     try {
    //         $course = Course::with([
    //             'modules.lessons' => fn($q) => $q->orderBy('position', 'asc'),
    //             'modules' => fn($q) => $q->orderBy('position', 'asc'),
    //             'instructor'
    //         ])->findOrFail($id);

    //         // Ensure lessons is always a collection
    //         $course->modules = $course->modules->map(
    //             fn($module) => tap($module, fn($m) => $m->lessons = $m->lessons ?? collect([]))
    //         );

    //         $user = Auth::user();
    //         $course->enrolled = $user ? $user->enrolledCourses()->where('course_id', $id)->exists() : false;

    //         return response()->json($course);
    //     } catch (\Throwable $e) {
    //         \Log::error('LearningController::show error', [
    //             'courseId' => $id,
    //             'error' => $e->getMessage(),
    //             'trace' => $e->getTraceAsString(),
    //         ]);

    //         return response()->json([
    //             'message' => 'Failed to load course.',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    public function createCheckoutSession(Request $request, $id): JsonResponse
    {
        $user = Auth::user();
        if (!$user) return response()->json(['message' => 'Unauthorized'], 401);

        $course = Course::findOrFail($id);

        if (!$course->price || $course->price <= 0) {
            return response()->json(['message' => 'Course is free. No payment needed.']);
        }

        $paystackSecret = env('PAYSTACK_SECRET_KEY');
        if (!$paystackSecret) return response()->json(['message' => 'Payment gateway not configured.'], 500);

        $callbackUrl = rtrim(env('FRONTEND_URL'), '/') . "/dashboard/learning/{$id}/payment-verify";

        try {
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
                return response()->json(['authorization_url' => $data['data']['authorization_url']]);
            }

            return response()->json([
                'message' => 'Failed to initiate payment.',
                'error' => $response->body(),
            ], 500);
        } catch (\Throwable $e) {
            \Log::error('LearningController::createCheckoutSession error', [
                'courseId' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Payment initialization error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function enroll(Request $request, $id): JsonResponse
    {
        $user = Auth::user();
        if (!$user) return response()->json(['message' => 'Unauthorized'], 401);

        $course = Course::findOrFail($id);

        if ($user->enrolledCourses()->where('course_id', $course->id)->exists()) {
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
    }

    public function verifyPaymentApi(Request $request, $id): JsonResponse
    {
        $reference = $request->query('reference') ?? $request->query('trxref');

        if (!$reference) return response()->json(['enrolled' => false, 'message' => 'Payment reference missing.'], 400);

        $paystackSecret = env('PAYSTACK_SECRET_KEY');
        if (!$paystackSecret) return response()->json(['enrolled' => false, 'message' => 'Payment gateway not configured.'], 500);

        try {
            $response = Http::withToken($paystackSecret)->get("https://api.paystack.co/transaction/verify/{$reference}");

            if (!$response->successful()) {
                return response()->json(['enrolled' => false, 'message' => 'Payment verification failed.'], 500);
            }

            $data = $response->json();

            if (($data['data']['status'] ?? null) === 'success') {
                $userId = $data['data']['metadata']['user_id'] ?? null;
                $courseId = $data['data']['metadata']['course_id'] ?? $id;

                if (!$userId) return response()->json(['enrolled' => false, 'message' => 'User not resolved from payment metadata.'], 400);

                $user = User::find($userId);
                if (!$user) return response()->json(['enrolled' => false, 'message' => 'User not found.'], 404);

                $course = Course::with([
                    'modules' => fn($q) => $q->orderBy('position', 'asc'),
                    'modules.lessons' => fn($q) => $q->orderBy('position', 'asc'),
                ])->findOrFail($courseId);

                if (!$user->enrolledCourses()->where('course_id', $course->id)->exists()) {
                    $user->enrolledCourses()->create([
                        'course_id' => $course->id,
                        'status' => 'active',
                        'started_at' => now(),
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
            }

            return response()->json(['enrolled' => false, 'message' => 'Payment was not successful.'], 400);

        } catch (\Throwable $e) {
            \Log::error('LearningController::verifyPaymentApi error', [
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

    public function show(Request $request): JsonResponse
    {
        \Log::info('LessonController::show called', ['courseId' => $request->courseId, 'lessonId' => $request->lessonId]);

        try {
            // Load lesson with module relationship
            $lesson = Lesson::with(['module:id,course_id'])->where('id', $request->lessonId)->first();
            // $lesson = Lesson::with(['module:id,course_id'])->find($lessonId);

            if (!$lesson) {
                \Log::warning('Lesson not found', ['lessonId' => $request->lessonId]);
                return response()->json(['message' => 'Lesson not found.'], 404);
            }

            if (!$lesson->module) {
                \Log::warning('Lesson has no module', ['lessonId' => $request->lessonId]);
                return response()->json(['message' => 'Lesson is not assigned to any module.'], 404);
            }

            if ((int)$lesson->module->course_id !== (int)$request->courseId) {
                \Log::warning('Lesson does not belong to course', [
                    'lessonId' => $request->lessonId,
                    'moduleCourseId' => $lesson->module->course_id,
                    'requestedCourseId' => $request->courseId
                ]);
                return response()->json(['message' => 'Lesson not found for this course.'], 404);
            }

            \Log::info('Lesson fetched successfully', ['lessonId' => $request->lessonId]);

            return response()->json([
                'id' => $lesson->id,
                'title' => $lesson->title,
                'content' => $lesson->content,
                'content_type' => $lesson->content_type,
                'content_url' => $lesson->content_url,
                'position' => $lesson->position,
                'duration_seconds' => $lesson->duration_seconds,
                'module_id' => $lesson->module_id,
                'course_id' => $lesson->module->course_id,
                'created_at' => $lesson->created_at,
                'updated_at' => $lesson->updated_at,
            ]);

        } catch (\Throwable $e) {
            \Log::error('LessonController::show exception', [
                'courseId' => $request->courseId,
                'lessonId' => $request->lessonId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'SERVER ERROR',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
