<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Exceptions\BusinessRuleException;

class Task extends Model
{
    protected $fillable = [
        'title',
        'due_date',
        'priority',
        'status',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    private const STATUS_TRANSITIONS = [
        'pending' => ['in_progress'],
        'in_progress' => ['done'],
        'done' => [],
    ];

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

    public static function isDuplicate(string $title, string $dueDate): bool
    {
        return self::where('title', $title)
                   ->where('due_date', $dueDate)
                   ->exists();
    }
}
