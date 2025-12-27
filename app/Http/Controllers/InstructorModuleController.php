<?php
// ...existing code...
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use App\Models\Module;
use App\Models\Course;
use Exception;

class InstructorModuleController extends BaseController
{
    /**
     * List modules for the authenticated instructor (optional).
     */
    public function index(Request $request)
    {
        $instructorId = auth()->id();

        $modules = Module::whereIn('course_id', function ($q) use ($instructorId) {
            $q->select('id')->from('courses')->where('instructor_id', $instructorId);
        })->orderBy('position')->get();

        return response()->json(['ok' => true, 'data' => $modules], 200);
    }

    /**
     * Store a new module. Validates ownership of the course.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|integer|exists:courses,id',
            'title'     => 'required|string|max:255',
            'position'  => 'sometimes|integer',
        ]);

        try {
            $instructorId = auth()->id();

            // ensure the authenticated user owns the course
            $owns = Course::where('id', $validated['course_id'])
                          ->where('instructor_id', $instructorId)
                          ->exists();

            if (! $owns) {
                return response()->json(['ok' => false, 'error' => 'You do not own this course.'], 403);
            }

            // if position not provided, place module at end (next position)
            if (! isset($validated['position'])) {
                $max = Module::where('course_id', $validated['course_id'])->max('position');
                $validated['position'] = is_null($max) ? 1 : ($max + 1);
            }

            $module = Module::create([
                'course_id' => $validated['course_id'],
                'title'     => $validated['title'],
                'position'  => $validated['position'],
            ]);

            return response()->json([
                'ok' => true,
                'created' => true,
                'data' => $module,
            ], 201);
        } catch (Exception $ex) {
            logger()->error('Error creating module: '.$ex->getMessage(), ['trace' => $ex->getTraceAsString()]);

            return response()->json([
                'ok' => false,
                'error' => 'Server error creating module.'
            ], 500);
        }
    }
}