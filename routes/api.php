<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TaskController;

// Specific routes FIRST to avoid conflicts with CRUD routes
Route::get('/tasks/report', [TaskController::class, 'dailyReport']);

// CRUD routes
Route::get('/tasks', [TaskController::class, 'index']);
Route::post('/tasks', [TaskController::class, 'store']);
Route::get('/tasks/{id}', [TaskController::class, 'show']);
Route::patch('/tasks/{id}/status', [TaskController::class, 'updateStatus']);
Route::delete('/tasks/{id}', [TaskController::class, 'destroy']);
