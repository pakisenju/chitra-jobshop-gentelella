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
    protected $signature = 'schedule:tasks {--shift= : The shift to schedule tasks for (pagi or malam)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Schedules pending tasks based on tool availability and selected shift.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $shift = $this->option('shift');
        $now = Carbon::now();
        $schedulingStartTime = $now->copy(); // Default to now

        $now = Carbon::now();
        $schedulingStartTime = null;

        if ($shift === 'pagi') {
            $todayPagiStart = $now->copy()->startOfDay()->setHour(8);
            $todayPagiEnd = $now->copy()->startOfDay()->setHour(17);

            if ($now->lessThan($todayPagiStart)) {
                // Before 8 AM today, start at 8 AM today
                $schedulingStartTime = $todayPagiStart;
            } elseif ($now->greaterThanOrEqualTo($todayPagiStart) && $now->lessThanOrEqualTo($todayPagiEnd)) {
                // Within 8 AM - 5 PM today, start at 8 AM today
                $schedulingStartTime = $todayPagiStart;
            } else {
                // After 5 PM today
                $todayMalamStart = $now->copy()->startOfDay()->setHour(20);
                // Check if it's before 8 PM today, if so, start at 8 PM today (malam shift)
                if ($now->lessThan($todayMalamStart)) {
                    $schedulingStartTime = $todayMalamStart;
                } else {
                    // After 8 PM today, start at 8 AM tomorrow
                    $schedulingStartTime = $todayPagiStart->addDay();
                }
            }
        } elseif ($shift === 'malam') {
            $todayMalamStart = $now->copy()->startOfDay()->setHour(20);
            $tomorrowMalamEnd = $now->copy()->startOfDay()->addDay()->setHour(5);

            if ($now->lessThan($todayMalamStart) && $now->greaterThanOrEqualTo($tomorrowMalamEnd->copy()->subDay())) {
                // Between 5 AM today and 8 PM today, start at 8 PM today
                $schedulingStartTime = $todayMalamStart;
            } elseif ($now->greaterThanOrEqualTo($todayMalamStart)) {
                // After 8 PM today, start at 8 PM today
                $schedulingStartTime = $todayMalamStart;
            } else {
                // After 5 AM tomorrow, start at 8 PM tomorrow
                $schedulingStartTime = $todayMalamStart->addDay();
            }
        } else {
            $this->error('Invalid shift specified. Use --shift=pagi or --shift=malam.');
            return Command::FAILURE;
        }

        // 1. Reset all scheduled tasks (that are not 'done') to pending and clear start_time, end_time
        TireJobOrderTaskDetail::where('status', 'scheduled')
            ->where('total_duration_calculated', '>', 0) // Only reset tasks that actually have a duration
            ->update([
                'start_time' => null,
                'end_time' => null,
                'status' => 'pending',
            ]);

        // 2. Initialize toolAvailability: Tracks when each specific tool instance becomes free
        //    Tools become available from the scheduling start time.
        $toolAvailability = $this->initializeToolAvailability($schedulingStartTime);

        // 3. Get all pending tasks (including those just reset from 'scheduled')
        $allPendingTasks = TireJobOrderTaskDetail::where('status', 'pending')
            ->where('total_duration_calculated', '>', 0) // Only consider tasks that have a duration
            ->with(['task.tools', 'tireJobOrder'])
            ->orderBy('tire_job_order_id')
            ->orderBy('order')
            ->get();

        // 4. Track jobOrderLastEndTime: end time of the last scheduled task for each job order
        $jobOrderLastEndTime = [];

        $scheduledCount = 0;
        $maxIterations = count($allPendingTasks) * 2; // Safety break to prevent infinite loops

        DB::transaction(function () use (&$allPendingTasks, &$toolAvailability, &$jobOrderLastEndTime, &$scheduledCount, $maxIterations, $schedulingStartTime, $shift) {
            for ($i = 0; $i < $maxIterations && $allPendingTasks->count() > 0; $i++) {
                $bestCandidate = null;
                $bestCandidateStartTime = null;
                $bestCandidateKey = null;

                // Find the best task to schedule in this iteration (earliest possible start time)
                foreach ($allPendingTasks as $key => $taskDetail) {
                    $task = $taskDetail->task;
                    $requiredTools = $task->tools;
                    $taskDuration = $taskDetail->total_duration_calculated;

                    // Determine earliestConsideredStart for this task (respecting previous tasks in same job order and the overall scheduling start time)
                    $earliestConsideredStart = $schedulingStartTime->copy();
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
                            $earliestConsideredStart,
                            $shift // Pass the shift here
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
                            $startTime, // Pass the confirmed start time
                            $shift // Pass the shift here
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
     * @param Carbon $initialAvailabilityTime The time from which tools are initially available.
     * @return array
     */
    protected function initializeToolAvailability(Carbon $initialAvailabilityTime): array
    {
        $tools = Tool::all();
        $availability = [];
        foreach ($tools as $tool) {
            // Each instance of a tool starts as free from the initialAvailabilityTime
            for ($i = 0; $i < $tool->quantity; $i++) {
                $availability[$tool->name][] = $initialAvailabilityTime->copy();
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
        $earliestConsideredStart,
        string $shift // Added shift parameter
    ): ?Carbon {
        $currentSearchTime = $earliestConsideredStart->copy();
        $maxSearchMinutes = 7 * 24 * 60; // Search up to 7 days in minutes to prevent infinite loops
        $searchLimit = Carbon::now()->addMinutes($maxSearchMinutes);

        // Define shift boundaries
        $shiftStartHour = 0;
        $shiftEndHour = 0;
        if ($shift === 'pagi') {
            $shiftStartHour = 8;
            $shiftEndHour = 17;
        } elseif ($shift === 'malam') {
            $shiftStartHour = 20;
            $shiftEndHour = 5; // Represents 05:00 AM next day
        }

        while ($currentSearchTime->lessThan($searchLimit)) {
            // Adjust currentSearchTime to be within shift boundaries
            $currentHour = $currentSearchTime->hour;
            $currentMinute = $currentSearchTime->minute;

            if ($shift === 'pagi') {
                $pagiShiftStart = $currentSearchTime->copy()->setHour(8)->setMinute(0)->setSecond(0);
                $pagiShiftEnd = $currentSearchTime->copy()->setHour(17)->setMinute(0)->setSecond(0);
                $malamShiftStart = $currentSearchTime->copy()->setHour(20)->setMinute(0)->setSecond(0);

                if ($currentSearchTime->lessThan($pagiShiftStart)) {
                    // Before 8 AM, move to 8 AM today
                    $currentSearchTime = $pagiShiftStart;
                    continue;
                } elseif ($currentSearchTime->greaterThanOrEqualTo($pagiShiftEnd) && $currentSearchTime->lessThan($malamShiftStart)) {
                    // After 5 PM but before 8 PM, move to 8 PM today (malam shift)
                    $currentSearchTime = $malamShiftStart;
                    continue;
                } elseif ($currentSearchTime->greaterThanOrEqualTo($malamShiftStart)) {
                    // After 8 PM, move to 8 AM tomorrow
                    $currentSearchTime->addDay()->setHour(8)->setMinute(0)->setSecond(0);
                    continue;
                }
            } elseif ($shift === 'malam') {
                $malamShiftStartToday = $currentSearchTime->copy()->setHour(20)->setMinute(0)->setSecond(0);
                $malamShiftEndNextDay = $currentSearchTime->copy()->addDay()->setHour(5)->setMinute(0)->setSecond(0);

                // If current time is between 5 AM and 8 PM today (outside malam shift)
                if ($currentSearchTime->greaterThanOrEqualTo($malamShiftEndNextDay->copy()->subDay()) && $currentSearchTime->lessThan($malamShiftStartToday)) {
                    $currentSearchTime = $malamShiftStartToday;
                    continue;
                }
                // If current time is after 5 AM next day (and not yet 8 PM today)
                elseif ($currentSearchTime->greaterThanOrEqualTo($malamShiftEndNextDay)) {
                    $currentSearchTime = $malamShiftStartToday->addDay(); // Move to 8 PM next day
                    continue;
                }
            }

            // NEW LOGIC: Check for breaks/rest times
            $adjustedForBreak = false;
            $breaks = [];
            if ($shift === 'pagi') {
                $breaks = [
                    // Coffee Break 1: 10:00 - 10:15
                    ['start' => $currentSearchTime->copy()->setHour(10)->setMinute(0)->setSecond(0),
                     'end' => $currentSearchTime->copy()->setHour(10)->setMinute(15)->setSecond(0)],
                    // Rest Time: 12:00 - 13:00
                    ['start' => $currentSearchTime->copy()->setHour(12)->setMinute(0)->setSecond(0),
                     'end' => $currentSearchTime->copy()->setHour(13)->setMinute(0)->setSecond(0)],
                    // Coffee Break 2: 15:00 - 15:15
                    ['start' => $currentSearchTime->copy()->setHour(15)->setMinute(0)->setSecond(0),
                     'end' => $currentSearchTime->copy()->setHour(15)->setMinute(15)->setSecond(0)],
                ];
            } elseif ($shift === 'malam') {
                $nightShiftDay = $currentSearchTime->copy();
                // Adjust nightShiftDay to be the day the night shift *started*
                if ($currentSearchTime->hour < 20 && $currentSearchTime->hour >= 0 && $currentSearchTime->hour < 5) { // If currentSearchTime is in the morning part of night shift (00:00-05:00)
                    $nightShiftDay->subDay(); // The night shift started on the previous day
                }

                $breaks = [
                    // Coffee Break 1: 22:00 - 22:15
                    ['start' => $nightShiftDay->copy()->setHour(22)->setMinute(0)->setSecond(0),
                     'end' => $nightShiftDay->copy()->setHour(22)->setMinute(15)->setSecond(0)],
                    // Rest Time: 00:00 - 01:00 (next day relative to nightShiftDay)
                    ['start' => $nightShiftDay->copy()->addDay()->startOfDay(), // 00:00 next day
                     'end' => $nightShiftDay->copy()->addDay()->setHour(1)->setMinute(0)->setSecond(0)],
                    // Coffee Break 2: 03:00 - 03:15 (next day relative to nightShiftDay)
                    ['start' => $nightShiftDay->copy()->addDay()->setHour(3)->setMinute(0)->setSecond(0),
                     'end' => $nightShiftDay->copy()->addDay()->setHour(3)->setMinute(15)->setSecond(0)],
                ];
            }

            foreach ($breaks as $break) {
                $breakStart = $break['start'];
                $breakEnd = $break['end'];

                // If currentSearchTime is within a break, move it past the break
                if ($currentSearchTime->greaterThanOrEqualTo($breakStart) && $currentSearchTime->lessThan($breakEnd)) {
                    $currentSearchTime = $breakEnd->copy();
                    $adjustedForBreak = true;
                    break; // Re-evaluate from the new currentSearchTime
                }

                // If a task starting at currentSearchTime would end within or after a break
                $potentialEndTime = $currentSearchTime->copy()->addMinutes($taskDuration);
                if ($potentialEndTime->greaterThan($breakStart) && $currentSearchTime->lessThan($breakEnd)) {
                    $currentSearchTime = $breakEnd->copy();
                    $adjustedForBreak = true;
                    break; // Re-evaluate from the new currentSearchTime
                }
            }

            if ($adjustedForBreak) {
                continue; // Go to the next iteration of the while loop with the adjusted currentSearchTime
            }
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
                // Check if the task duration fits within the current shift segment
                $potentialEndTime = $currentSearchTime->copy()->addMinutes($taskDuration);
                $fitsInShift = false;

                if ($shift === 'pagi') {
                    $shiftEndToday = $currentSearchTime->copy()->setHour(17)->setMinute(0)->setSecond(0);
                    if ($potentialEndTime->lessThanOrEqualTo($shiftEndToday)) {
                        $fitsInShift = true;
                    }
                } elseif ($shift === 'malam') {
                    $shiftStartToday = $currentSearchTime->copy()->setHour(20)->setMinute(0)->setSecond(0);
                    $shiftEndNextDay = $currentSearchTime->copy()->addDay()->setHour(5)->setMinute(0)->setSecond(0);

                    if ($currentSearchTime->greaterThanOrEqualTo($shiftStartToday)) {
                        // Task starts in the evening part of the night shift
                        if ($potentialEndTime->lessThanOrEqualTo($shiftEndNextDay)) {
                            $fitsInShift = true;
                        }
                    } else {
                        // Task starts in the morning part of the night shift (after midnight)
                        if ($potentialEndTime->lessThanOrEqualTo($shiftEndNextDay)) {
                            $fitsInShift = true;
                        }
                    }
                }

                if ($fitsInShift) {
                    // Update the availability of the chosen instances for this task.
                    foreach ($requiredTools as $tool) {
                        $instanceKey = $chosenToolInstances[$tool->name];
                        $toolAvailability[$tool->name][$instanceKey] = $potentialEndTime;
                    }
                    return $currentSearchTime; // Found a valid start time
                } else {
                    // Task does not fit within the current shift segment.
                    // Advance currentSearchTime to the start of the next logical shift.
                    if ($shift === 'pagi') {
                        // If it's pagi shift, and task doesn't fit, move to malam shift today (20:00)
                        $currentSearchTime->setHour(20)->setMinute(0)->setSecond(0);
                    } elseif ($shift === 'malam') {
                        // If it's malam shift, and task doesn't fit, move to pagi shift next day (08:00)
                        $currentSearchTime->addDay()->setHour(8)->setMinute(0)->setSecond(0);
                    }
                    continue; // Re-evaluate with new currentSearchTime
                }
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
