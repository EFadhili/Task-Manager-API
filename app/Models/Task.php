<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Exceptions\BusinessRuleException;

class Task extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'due_date',
        'priority',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'due_date' => 'date',
    ];

    /**
     * Status transition rules.
     * Defines which status can transition to which.
     */
    private const STATUS_TRANSITIONS = [
        'pending' => ['in_progress'],
        'in_progress' => ['done'],
        'done' => [],
    ];

    /**
     * Update task status with validation.
     * Throws BusinessRuleException if transition is not allowed.
     *
     * @param string $newStatus The requested new status
     * @throws BusinessRuleException
     */
    public function updateStatus(string $newStatus): void
    {
        $allowed = self::STATUS_TRANSITIONS[$this->status] ?? [];

        if (!in_array($newStatus, $allowed)) {
            throw new BusinessRuleException(
                "Cannot change status from '{$this->status}' to '{$newStatus}'",
                [
                    'current_status' => $this->status,
                    'allowed_statuses' => $allowed ?: ['none - task is completed'],
                    'requested_status' => $newStatus
                ]
            );
        }

        $this->status = $newStatus;
        $this->save();
    }

    /**
     * Delete task if allowed (only when status is 'done').
     * Throws BusinessRuleException if deletion is not allowed.
     *
     * @throws BusinessRuleException
     */
    public function deleteIfAllowed(): void
    {
        if ($this->status !== 'done') {
            throw new BusinessRuleException(
                "Cannot delete task with status '{$this->status}'",
                [
                    'current_status' => $this->status,
                    'required_status' => 'done',
                    'message' => 'Only completed tasks can be deleted'
                ]
            );
        }

        $this->delete();
    }

    /**
     * Check if a task with the same title and due date already exists.
     * Used for duplicate validation during task creation.
     *
     * @param string $title
     * @param string $dueDate
     * @return bool
     */
    public static function isDuplicate(string $title, string $dueDate): bool
    {
        return self::where('title', $title)
                   ->where('due_date', $dueDate)
                   ->exists();
    }
}
