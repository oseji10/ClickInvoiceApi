<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use App\Models\Lesson;
use App\Models\Module;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class InstructorLessonController extends BaseController
{

    /**
     * List lessons for the authenticated instructor.
     * Optional query param: module_id to restrict to a single module.
     */
    public function index(Request $request)
    {
        $instructorId = auth()->id();
        $moduleId = $request->query('module_id');

        try {
            if ($moduleId) {
                $module = Module::with('course')->findOrFail($moduleId);
                if (! $module->course || $module->course->instructor_id !== $instructorId) {
                    return response()->json(['ok' => false, 'error' => 'You do not own this module.'], 403);
                }

                $lessons = Lesson::where('module_id', $module->id)
                    ->orderBy('position')
                    ->get();
            } else {
                // all lessons belonging to instructor's courses
                $lessons = Lesson::whereHas('module.course', function ($q) use ($instructorId) {
                    $q->where('instructor_id', $instructorId);
                })->with('module')->orderBy('position')->get();
            }

            return response()->json(['ok' => true, 'data' => $lessons], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['ok' => false, 'error' => 'Module not found.'], 404);
        } catch (Exception $ex) {
            logger()->error('InstructorLessonController@index error: '.$ex->getMessage());
            return response()->json(['ok' => false, 'error' => 'Server error.'], 500);
        }
    }

    /**
     * Show a single lesson (only if it belongs to instructor).
     */
    public function show($id)
    {
        $instructorId = auth()->id();

        try {
            $lesson = Lesson::with('module.course')->findOrFail($id);

            if (! $lesson->module || ! $lesson->module->course || $lesson->module->course->instructor_id !== $instructorId) {
                return response()->json(['ok' => false, 'error' => 'You do not own this lesson.'], 403);
            }

            return response()->json(['ok' => true, 'data' => $lesson], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['ok' => false, 'error' => 'Lesson not found.'], 404);
        } catch (Exception $ex) {
            logger()->error('InstructorLessonController@show error: '.$ex->getMessage());
            return response()->json(['ok' => false, 'error' => 'Server error.'], 500);
        }
    }

    /**
     * Create a new lesson under a module.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'module_id'        => 'required|integer|exists:modules,id',
            'title'            => 'required|string|max:255',
            'content'          => 'nullable|string',
            'content_type'     => 'nullable|string|in:video,pdf,html,text',
            'content_url'      => 'nullable|string|max:255',
            'position'         => 'sometimes|integer',
            'duration_seconds' => 'nullable|integer',
        ]);

        try {
            $instructorId = auth()->id();

            $module = Module::with('course')->findOrFail($validated['module_id']);

            if (! $module->course || $module->course->instructor_id !== $instructorId) {
                return response()->json(['ok' => false, 'error' => 'You do not own this module/course.'], 403);
            }

            // default position: append to end
            if (! isset($validated['position'])) {
                $max = Lesson::where('module_id', $module->id)->max('position');
                $validated['position'] = is_null($max) ? 1 : ($max + 1);
            }

            $lesson = Lesson::create([
                'module_id'        => $module->id,
                'title'            => $validated['title'],
                'content'          => $validated['content'] ?? null,
                'content_type'     => $validated['content_type'] ?? 'video',
                'content_url'      => $validated['content_url'] ?? null,
                'position'         => $validated['position'],
                'duration_seconds' => $validated['duration_seconds'] ?? null,
            ]);

            // return created lesson (with id) so frontend can update without redirect
            return response()->json(['ok' => true, 'created' => true, 'data' => $lesson], 201);
        } catch (ModelNotFoundException $e) {
            return response()->json(['ok' => false, 'error' => 'Module not found.'], 404);
        } catch (Exception $ex) {
            logger()->error('InstructorLessonController@store error: '.$ex->getMessage(), ['trace' => $ex->getTraceAsString()]);
            return response()->json(['ok' => false, 'error' => 'Server error creating lesson.'], 500);
        }
    }

    /**
     * Update a lesson. Ownership validated.
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'module_id'        => 'sometimes|integer|exists:modules,id',
            'title'            => 'sometimes|required|string|max:255',
            'content'          => 'nullable|string',
            'content_type'     => 'nullable|string|in:video,pdf,html,text',
            'content_url'      => 'nullable|string|max:255',
            'position'         => 'sometimes|integer',
            'duration_seconds' => 'nullable|integer',
        ]);

        try {
            $instructorId = auth()->id();

            $lesson = Lesson::findOrFail($id);
            $module = Module::with('course')->findOrFail($lesson->module_id);

            if (! $module->course || $module->course->instructor_id !== $instructorId) {
                return response()->json(['ok' => false, 'error' => 'You do not own this lesson.'], 403);
            }

            // If moving to a different module ensure ownership of target module too
            if (isset($validated['module_id']) && $validated['module_id'] !== $lesson->module_id) {
                $targetModule = Module::with('course')->findOrFail($validated['module_id']);
                if (! $targetModule->course || $targetModule->course->instructor_id !== $instructorId) {
                    return response()->json(['ok' => false, 'error' => 'You do not own the target module.'], 403);
                }
            }

            $lesson->fill($validated);
            $lesson->save();

            return response()->json(['ok' => true, 'updated' => true, 'data' => $lesson], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['ok' => false, 'error' => 'Resource not found.'], 404);
        } catch (Exception $ex) {
            logger()->error('InstructorLessonController@update error: '.$ex->getMessage(), ['trace' => $ex->getTraceAsString()]);
            return response()->json(['ok' => false, 'error' => 'Server error updating lesson.'], 500);
        }
    }

    /**
     * Delete a lesson. Ownership validated.
     */
    public function destroy($id)
    {
        try {
            $instructorId = auth()->id();

            $lesson = Lesson::findOrFail($id);
            $module = Module::with('course')->findOrFail($lesson->module_id);

            if (! $module->course || $module->course->instructor_id !== $instructorId) {
                return response()->json(['ok' => false, 'error' => 'You do not own this lesson.'], 403);
            }

            $lesson->delete();

            return response()->json(['ok' => true, 'deleted' => true], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['ok' => false, 'error' => 'Resource not found.'], 404);
        } catch (Exception $ex) {
            logger()->error('InstructorLessonController@destroy error: '.$ex->getMessage(), ['trace' => $ex->getTraceAsString()]);
            return response()->json(['ok' => false, 'error' => 'Server error deleting lesson.'], 500);
        }
    }
}