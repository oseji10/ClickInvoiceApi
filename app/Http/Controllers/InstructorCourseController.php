<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Course;
use Illuminate\Support\Str;

class InstructorCourseController extends Controller
{
    /**
     * GET /instructor/courses
     * Return courses created by the authenticated instructor.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Return ONLY the array of courses – frontend expects [] not { data: [] }
        return Course::where('instructor_id', $user->id)
            ->withCount('modules')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * POST /instructor/courses
     * Create a new course.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric',
            'thumbnail' => 'nullable|string',
            'published' => 'sometimes|boolean',
        ]);

        $course = Course::create([
            'instructor_id' => $user->id,
            'title' => $data['title'],
            'slug' => Str::slug($data['title']) . '-' . Str::random(6),
            'description' => $data['description'] ?? null,
            'price' => $data['price'] ?? 0,
            'thumbnail' => $data['thumbnail'] ?? null,
            'published' => $data['published'] ?? false,
        ]);

        return response()->json($course, 201);
    }

    /**
     * GET /instructor/courses/{id}
     * Show a course with modules + lessons.
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();

        $course = Course::with('modules.lessons')
            ->where('instructor_id', $user->id)
            ->findOrFail($id);

        return response()->json($course);
    }

    /**
     * PUT/PATCH /instructor/courses/{id}
     * Update a course.
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();

        $course = Course::where('instructor_id', $user->id)->findOrFail($id);

        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric',
            'thumbnail' => 'nullable|string',
            'published' => 'sometimes|boolean',
        ]);

        // Normalize boolean values
        if (array_key_exists('published', $data)) {
            $data['published'] = filter_var(
                $data['published'],
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE
            );
        }

        $course->fill($data);
        $course->save();

        return response()->json($course);
    }

    /**
     * DELETE /instructor/courses/{id}
     * Delete a course.
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        $course = Course::where('instructor_id', $user->id)->findOrFail($id);
        $course->delete();

        return response()->json(['message' => 'deleted']);
    }
}
