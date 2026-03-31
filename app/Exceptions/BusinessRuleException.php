<?php

namespace App\Exceptions;

use Exception;

class BusinessRuleException extends Exception
{
    protected array $details;

    public function __construct(string $message, array $details = [], int $code = 403)
    {
        parent::__construct($message, $code);
        $this->details = $details;
    }

    public function getDetails(): array
    {
        return $this->details;
    }
}
