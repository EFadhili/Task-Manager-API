<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    /**
     * GET /api/tasks
     * List all tasks with optional status filter
     *
     * Sorting: priority (high → medium → low), then due_date ascending
     */
    public function index(Request $request): JsonResponse
    {
        $query = Task::query();

        // Apply status filter if provided
        if ($request->has('status')) {
            $validStatuses = ['pending', 'in_progress', 'done'];
            $status = $request->status;

            // Validate the status parameter
            if (!in_array($status, $validStatuses)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid status filter',
                    'allowed_statuses' => $validStatuses
                ], 400);
            }

            $query->where('status', $status);
        }

        // Apply sorting: custom priority order, then due date
        $tasks = $query->orderByRaw("FIELD(priority, 'high', 'medium', 'low')")
                       ->orderBy('due_date', 'asc')
                       ->get();

        return response()->json([
            'success' => true,
            'data' => $tasks,
            'count' => $tasks->count()
        ]);
    }

    /**
     * POST /api/tasks
     * Create a new task with validation
     *
     * Rules:
     * - Title cannot duplicate with same due_date
     * - due_date must be today or later
     * - Priority must be low, medium, or high
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => [
                'required',
                'string',
                'max:255',
                // Custom validation to prevent duplicate tasks on same date
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

        // Return validation errors if any
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

    /**
     * GET /api/tasks/{id}
     * Retrieve a single task by ID
     */
    public function show($id): JsonResponse
    {
        $task = Task::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $task
        ]);
    }

    /**
     * PATCH /api/tasks/{id}/status
     * Update task status with progression rules
     *
     * Rules: pending → in_progress → done (cannot skip or revert)
     */
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

        // Business logic for status transition is in the Task model
        $task->updateStatus($request->status);

        return response()->json([
            'success' => true,
            'message' => "Task status updated from '{$oldStatus}' to '{$task->status}'",
            'data' => $task
        ]);
    }

    /**
     * DELETE /api/tasks/{id}
     * Delete a task (only allowed if status is 'done')
     *
     * Returns 403 if task is not completed
     */
    public function destroy($id): JsonResponse
    {
        $task = Task::findOrFail($id);
        $taskTitle = $task->title;

        // Business logic for deletion is in the Task model
        $task->deleteIfAllowed();

        return response()->json(null, 204);
    }

    /**
     * BONUS: GET /api/tasks/report?date=YYYY-MM-DD
     * Generate daily report with counts per priority and status
     *
     * Returns summary statistics for tasks due on the specified date
     */
    public function dailyReport(Request $request): JsonResponse
    {
        // Validate date format
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

        // Additional validation to ensure date is valid (e.g., not 2026-13-45)
        if (!strtotime($date)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid date',
                'example' => '2026-04-01'
            ], 400);
        }

        // Get all tasks for the specified date
        $tasks = Task::where('due_date', $date)->get();

        // Initialize summary structure for all priority levels
        $summary = [
            'high' => ['pending' => 0, 'in_progress' => 0, 'done' => 0],
            'medium' => ['pending' => 0, 'in_progress' => 0, 'done' => 0],
            'low' => ['pending' => 0, 'in_progress' => 0, 'done' => 0],
        ];

        // Count tasks by priority and status
        foreach ($tasks as $task) {
            $summary[$task->priority][$task->status]++;
        }

        // Calculate overall statistics
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
