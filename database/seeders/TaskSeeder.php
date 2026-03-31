<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Task;
use Carbon\Carbon;

class TaskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing tasks (optional)
        Task::truncate();

        // Sample tasks for testing
        $tasks = [
            [
                'title' => 'Complete Cytonn coding challenge',
                'due_date' => Carbon::now()->addDays(1)->format('Y-m-d'),
                'priority' => 'high',
                'status' => 'pending',
            ],
            [
                'title' => 'Review project requirements',
                'due_date' => Carbon::now()->format('Y-m-d'),
                'priority' => 'high',
                'status' => 'in_progress',
            ],
            [
                'title' => 'Setup MySQL database',
                'due_date' => Carbon::now()->subDays(1)->format('Y-m-d'),
                'priority' => 'medium',
                'status' => 'done',
            ],
            [
                'title' => 'Write API documentation',
                'due_date' => Carbon::now()->addDays(2)->format('Y-m-d'),
                'priority' => 'medium',
                'status' => 'pending',
            ],
            [
                'title' => 'Deploy to Railway',
                'due_date' => Carbon::now()->addDays(3)->format('Y-m-d'),
                'priority' => 'low',
                'status' => 'pending',
            ],
            [
                'title' => 'Test all endpoints',
                'due_date' => Carbon::now()->addDays(1)->format('Y-m-d'),
                'priority' => 'high',
                'status' => 'in_progress',
            ],
        ];

        foreach ($tasks as $task) {
            Task::create($task);
        }

        $this->command->info('Created ' . count($tasks) . ' sample tasks');
    }
}
