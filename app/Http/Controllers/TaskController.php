<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Task::query();

        if ($request->has('status')) {
            $validStatuses = ['pending', 'in_progress', 'done'];
            $status = $request->status;

            if (!in_array($status, $validStatuses)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid status filter',
                    'allowed_statuses' => $validStatuses
                ], 400);
            }

            $query->where('status', $status);
        }

        $tasks = $query->orderByRaw("FIELD(priority, 'high', 'medium', 'low')")
                       ->orderBy('due_date', 'asc')
                       ->get();

        return response()->json([
            'success' => true,
            'data' => $tasks,
            'count' => $tasks->count()
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($request) {
                    if (Task::isDuplicate($value, $request->due_date)) {
                        $fail('A task with this title already exists for this due date.');
                    }
                },
            ],
            'due_date' => [
                'required',
                'date',
                'after_or_equal:today',
            ],
            'priority' => [
                'required',
                Rule::in(['low', 'medium', 'high']),
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $task = Task::create($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Task created successfully',
            'data' => $task
        ], 201);
    }

    public function show($id): JsonResponse
    {
        $task = Task::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $task
        ]);
    }

    public function updateStatus(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => [
                'required',
                Rule::in(['pending', 'in_progress', 'done']),
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $task = Task::findOrFail($id);
        $oldStatus = $task->status;
        $task->updateStatus($request->status);

        return response()->json([
            'success' => true,
            'message' => "Task status updated from '{$oldStatus}' to '{$task->status}'",
            'data' => $task
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $task = Task::findOrFail($id);
        $taskTitle = $task->title;
        $task->deleteIfAllowed();

        return response()->json(null, 204);
    }

    public function dailyReport(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid date format',
                'example' => '/api/tasks/report?date=2026-04-01'
            ], 422);
        }

        $date = $request->date;

        if (!strtotime($date)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid date',
                'example' => '2026-04-01'
            ], 400);
        }

        $tasks = Task::where('due_date', $date)->get();

        $summary = [
            'high' => ['pending' => 0, 'in_progress' => 0, 'done' => 0],
            'medium' => ['pending' => 0, 'in_progress' => 0, 'done' => 0],
            'low' => ['pending' => 0, 'in_progress' => 0, 'done' => 0],
        ];

        foreach ($tasks as $task) {
            $summary[$task->priority][$task->status]++;
        }

        $totalTasks = $tasks->count();
        $completedTasks = $tasks->where('status', 'done')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $date,
                'summary' => $summary,
                'statistics' => [
                    'total_tasks' => $totalTasks,
                    'completed' => $completedTasks,
                    'completion_rate' => $totalTasks > 0
                        ? round(($completedTasks / $totalTasks) * 100, 2) . '%'
                        : '0%'
                ]
            ]
        ]);
    }
}
