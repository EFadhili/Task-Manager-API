<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $e)
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return $this->handleApiException($e);
        }

        return parent::render($request, $e);
    }

    private function handleApiException(Throwable $e): JsonResponse
    {
        // Validation errors (422)
        if ($e instanceof ValidationException) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }

        // Model not found (404)
        if ($e instanceof ModelNotFoundException) {
            $model = class_basename($e->getModel());
            return response()->json([
                'success' => false,
                'message' => "{$model} not found",
                'error' => 'The requested resource does not exist'
            ], 404);
        }

        // Business rule violations (403)
        if ($e instanceof BusinessRuleException) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'details' => $e->getDetails()
            ], 403);
        }

        // Database errors (500)
        if ($e instanceof \Illuminate\Database\QueryException) {
            return response()->json([
                'success' => false,
                'message' => 'Database error',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }

        // Generic errors (500)
        return response()->json([
            'success' => false,
            'message' => 'Server error',
            'error' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred'
        ], 500);
    }
}
