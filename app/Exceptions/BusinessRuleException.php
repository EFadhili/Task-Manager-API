<?php

namespace App\Exceptions;

use Exception;

/**
 * Custom exception for business rule violations
 *
 * Used when a user attempts an operation that violates business rules,
 * such as invalid status transitions or deleting non-completed tasks.
 * Returns HTTP 403 Forbidden with contextual details.
 */
class BusinessRuleException extends Exception
{
    /**
     * Additional context about the violation
     * @var array
     */
    protected array $details;

    /**
     * Create a new business rule exception
     *
     * @param string $message Human-readable error message
     * @param array $details Additional context (current status, allowed transitions, etc.)
     * @param int $code HTTP status code (default 403 Forbidden)
     */
    public function __construct(string $message, array $details = [], int $code = 403)
    {
        parent::__construct($message, $code);
        $this->details = $details;
    }

    /**
     * Get the additional context details
     *
     * @return array
     */
    public function getDetails(): array
    {
        return $this->details;
    }
}
