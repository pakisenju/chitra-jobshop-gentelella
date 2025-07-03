<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TireJobOrderTaskDetail;
use App\Models\Tool;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ScheduleTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schedule:tasks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Schedules pending tasks based on tool availability.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 1. Reset all scheduled tasks to pending and clear start_time, end_time
        TireJobOrderTaskDetail::where('status', 'scheduled')
            ->update([
                'start_time' => null,
                'end_time' => null,
                'status' => 'pending',
            ]);

        // 2. Initialize toolAvailability: Tracks when each specific tool instance becomes free
        $toolAvailability = $this->initializeToolAvailability();

        // 3. Get all pending tasks
        $allPendingTasks = TireJobOrderTaskDetail::where('status', 'pending')
            ->with(['task.tools', 'tireJobOrder'])
            ->get();

        // 4. Track jobOrderLastEndTime: end time of the last scheduled task for each job order
        $jobOrderLastEndTime = [];

        $scheduledCount = 0;
        $maxIterations = count($allPendingTasks) * 2; // Safety break to prevent infinite loops

        DB::transaction(function () use (&$allPendingTasks, &$toolAvailability, &$jobOrderLastEndTime, &$scheduledCount, $maxIterations) {
            for ($i = 0; $i < $maxIterations && $allPendingTasks->count() > 0; $i++) {
                $bestCandidate = null;
                $bestCandidateStartTime = null;
                $bestCandidateKey = null;

                // Find the best task to schedule in this iteration (earliest possible start time)
                foreach ($allPendingTasks as $key => $taskDetail) {
                    $task = $taskDetail->task;
                    $requiredTools = $task->tools;
                    $taskDuration = $taskDetail->total_duration_calculated;

                    // Determine earliestConsideredStart for this task (respecting previous tasks in same job order)
                    $earliestConsideredStart = Carbon::now();
                    if (isset($jobOrderLastEndTime[$taskDetail->tire_job_order_id])) {
                        $earliestConsideredStart = $earliestConsideredStart->max($jobOrderLastEndTime[$taskDetail->tire_job_order_id]);
                    }

                    $candidateStartTime = null;
                    if ($requiredTools->isEmpty()) {
                        // Task does not require any tools, can be scheduled immediately after earliestConsideredStart
                        $candidateStartTime = $earliestConsideredStart;
                    } else {
                        // Find the actual start time considering tool availability
                        // Create a deep copy of toolAvailability for this check to avoid premature updates
                        $tempToolAvailability = [];
                        foreach ($toolAvailability as $toolName => $instances) {
                            foreach ($instances as $instanceKey => $carbonTime) {
                                $tempToolAvailability[$toolName][$instanceKey] = $carbonTime->copy();
                            }
                        }

                        $candidateStartTime = $this->findEarliestAvailableTime(
                            $requiredTools,
                            $tempToolAvailability, // Use deep copy for checking
                            $taskDuration,
                            $earliestConsideredStart
                        );
                    }

                    if ($candidateStartTime !== null) {
                        // This task is a candidate. Check if it's the best so far.
                        if ($bestCandidateStartTime === null || $candidateStartTime->lessThan($bestCandidateStartTime)) {
                            $bestCandidate = $taskDetail;
                            $bestCandidateStartTime = $candidateStartTime;
                            $bestCandidateKey = $key;
                        }
                    }
                }

                // If a best candidate was found, schedule it
                if ($bestCandidate) {
                    $taskDetail = $bestCandidate;
                    $task = $taskDetail->task;
                    $requiredTools = $task->tools;
                    $taskDuration = $taskDetail->total_duration_calculated;
                    $startTime = $bestCandidateStartTime;
                    $endTime = $startTime->copy()->addMinutes($taskDuration);

                    // Update tool availability for the chosen task (this time, update the actual $toolAvailability)
                    if (!$requiredTools->isEmpty()) {
                        $this->findEarliestAvailableTime(
                            $requiredTools,
                            $toolAvailability, // Pass actual $toolAvailability by reference to update
                            $taskDuration,
                            $startTime // Pass the confirmed start time
                        );
                    }

                    $taskDetail->start_time = $startTime;
                    $taskDetail->end_time = $endTime;
                    $taskDetail->status = 'scheduled';
                    $taskDetail->save();

                    $this->info("Task {$task->name} (Job Order: {$taskDetail->tireJobOrder->sn_tire}) scheduled from {$taskDetail->start_time} to {$taskDetail->end_time}.");

                    // Update the last end time for this job order
                    $jobOrderLastEndTime[$taskDetail->tire_job_order_id] = $endTime;

                    // Remove the scheduled task from the pending list
                    $allPendingTasks->forget($bestCandidateKey);
                    $scheduledCount++;
                } else {
                    // No more tasks can be scheduled in this iteration
                    break;
                }
            }
        });

        $this->info("Task scheduling complete. Scheduled {$scheduledCount} tasks.");
        if ($allPendingTasks->count() > 0) {
            $this->warn("{$allPendingTasks->count()} tasks remain pending due to unavailability or scheduling constraints.");
        }
    }

    /**
     * Initializes the tool availability array.
     * Each tool instance is tracked by its name, and its value is the Carbon timestamp
     * when that specific instance becomes free.
     *
     * @return array
     */
    protected function initializeToolAvailability(): array
    {
        $tools = Tool::all();
        $availability = [];
        foreach ($tools as $tool) {
            // Each instance of a tool starts as free now
            for ($i = 0; $i < $tool->quantity; $i++) {
                $availability[$tool->name][] = Carbon::now();
            }
        }
        return $availability;
    }

    /**
     * Finds the earliest possible start time for a task given required tools and their availability.
     *
     * @param \Illuminate\Database\Eloquent\Collection $requiredTools
     * @param array $toolAvailability (passed by reference to update chosen instances)
     * @param int $taskDuration
     * @param \Carbon\Carbon $earliestConsideredStart The earliest time this task can possibly start (e.g., after previous task in same job order).
     * @return \Carbon\Carbon|null The earliest possible start time, or null if no slot found.
     */
    protected function findEarliestAvailableTime(
        $requiredTools,
        &$toolAvailability, // Passed by reference to update it
        $taskDuration,
        $earliestConsideredStart
    ): ?Carbon {
        $currentSearchTime = $earliestConsideredStart->copy();
        $maxSearchMinutes = 7 * 24 * 60; // Search up to 7 days in minutes to prevent infinite loops
        $searchLimit = Carbon::now()->addMinutes($maxSearchMinutes);

        while ($currentSearchTime->lessThan($searchLimit)) {
            $canScheduleAtCurrentTime = true;
            $chosenToolInstances = []; // To store which specific instance of each tool is chosen for this potential start time

            // For each required tool, try to find an available instance that can start at $currentSearchTime
            foreach ($requiredTools as $tool) {
                $foundInstanceForTool = false;
                $bestInstanceKey = null;

                // Sort the instances of the current tool by their available time
                // This ensures we always pick the earliest available instance for this tool
                // (or one that is free by currentSearchTime)
                asort($toolAvailability[$tool->name]); // Sort by value (Carbon time)

                foreach ($toolAvailability[$tool->name] as $instanceKey => $availableTime) {
                    if ($availableTime->lessThanOrEqualTo($currentSearchTime)) {
                        // This instance is free at or before currentSearchTime, so it can be used.
                        $foundInstanceForTool = true;
                        $bestInstanceKey = $instanceKey;
                        break; // Found a suitable instance for this tool, move to next required tool
                    }
                }

                if (!$foundInstanceForTool) {
                    $canScheduleAtCurrentTime = false;
                    break; // Cannot schedule at currentSearchTime, a required tool instance is not available
                }
                $chosenToolInstances[$tool->name] = $bestInstanceKey;
            }

            if ($canScheduleAtCurrentTime) {
                // All required tools have an available instance that can start at $currentSearchTime.
                // Update the availability of the chosen instances for this task.
                foreach ($requiredTools as $tool) {
                    $instanceKey = $chosenToolInstances[$tool->name];
                    $toolAvailability[$tool->name][$instanceKey] = $currentSearchTime->copy()->addMinutes($taskDuration);
                }
                return $currentSearchTime; // Found a valid start time
            }

            // If not all tools are available at $currentSearchTime, find the next relevant time to check.
            $nextRelevantTime = $currentSearchTime->copy()->addMinute(); // Default to next minute

            foreach ($requiredTools as $tool) {
                // Find the earliest time any instance of this tool becomes available *after* currentSearchTime
                // This helps to jump ahead in time more efficiently than just adding a minute.
                if (isset($toolAvailability[$tool->name])) {
                    foreach ($toolAvailability[$tool->name] as $availableTime) {
                        if ($availableTime->greaterThan($currentSearchTime)) {
                            $nextRelevantTime = $nextRelevantTime->min($availableTime);
                        }
                    }
                }
            }
            $currentSearchTime = $nextRelevantTime;
        }

        return null; // No suitable time found within the search duration
    }
}