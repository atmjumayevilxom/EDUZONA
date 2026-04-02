<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\ClassroomStudent;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ClassroomController extends Controller
{
    /** List teacher's classrooms */
    public function index(Request $request)
    {
        $classrooms = Classroom::where('user_id', $request->user()->id)
            ->withCount('students')
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['data' => $classrooms]);
    }

    /** Create a new classroom */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'    => ['required', 'string', 'max:100'],
            'subject' => ['nullable', 'string', 'max:80'],
        ]);

        $classroom = Classroom::create([
            'user_id'   => $request->user()->id,
            'name'      => $validated['name'],
            'subject'   => $validated['subject'] ?? null,
            'join_code' => $this->generateCode(),
        ]);

        return response()->json(['data' => $classroom->loadCount('students')], 201);
    }

    /** Delete classroom */
    public function destroy(Request $request, int $id)
    {
        $classroom = Classroom::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $classroom->delete();
        return response()->json(['message' => 'Sinf o\'chirildi.']);
    }

    /** Get classroom info by join_code (public) */
    public function byCode(string $code)
    {
        $classroom = Classroom::where('join_code', strtoupper($code))
            ->withCount('students')
            ->firstOrFail();

        return response()->json([
            'data' => [
                'id'             => $classroom->id,
                'name'           => $classroom->name,
                'subject'        => $classroom->subject,
                'students_count' => $classroom->students_count,
            ],
        ]);
    }

    /** Student joins classroom (public) */
    public function join(Request $request, string $code)
    {
        $classroom = Classroom::where('join_code', strtoupper($code))->firstOrFail();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $name = trim($validated['name']);

        // Duplicate check
        $exists = ClassroomStudent::where('classroom_id', $classroom->id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->exists();

        if ($exists) {
            return response()->json(['message' => "Bu nom allaqachon ro'yxatda: {$name}"], 422);
        }

        $student = ClassroomStudent::create([
            'classroom_id' => $classroom->id,
            'name'         => $name,
            'joined_at'    => now(),
        ]);

        return response()->json(['data' => $student, 'classroom' => ['name' => $classroom->name]], 201);
    }

    /** List students in a classroom (teacher only) */
    public function students(Request $request, int $id)
    {
        $classroom = Classroom::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->with('students')
            ->firstOrFail();

        return response()->json([
            'data' => [
                'classroom' => $classroom->only(['id', 'name', 'subject', 'join_code']),
                'students'  => $classroom->students,
            ],
        ]);
    }

    /** Remove student (teacher only) */
    public function removeStudent(Request $request, int $classroomId, int $studentId)
    {
        $classroom = Classroom::where('id', $classroomId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        ClassroomStudent::where('id', $studentId)
            ->where('classroom_id', $classroom->id)
            ->delete();

        return response()->json(['message' => "O'quvchi o'chirildi."]);
    }

    private function generateCode(): string
    {
        do {
            $code = strtoupper(Str::random(6));
        } while (Classroom::where('join_code', $code)->exists());

        return $code;
    }
}
