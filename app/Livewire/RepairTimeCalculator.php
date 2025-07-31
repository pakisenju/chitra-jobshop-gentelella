<?php

namespace App\Livewire;

use Livewire\Component as LivewireComponent;
use App\Models\Tool; // Import the Tool model with an alias
use App\Models\Task; // Import the Task model
use Carbon\Carbon; // Import Carbon

class RepairTimeCalculator extends LivewireComponent
{
    public $numberOfTires = 1;
    public $damageTypes = []; // Will store quantities for each damage type
    public $estimatedDays = 0;
    public $tools = [];
    public $simulatedSchedule = []; // Property to store simulated schedule data for table display
    public $isCalculating = false;

    // Define damage fields based on TireJobOrder properties that represent damage quantities
    public $damageFields = [
        'tread',
        'sidewall',
        'spot',
        'patch',
        'area_curing_sw',
        'area_curing_tread',
        'bead',
        'chaffer',
    ];

    // Task mappings from ManageTireJobOrders.php
    protected $taskMappings = [
        'tread' => ['skiving tread', 'buffing tread', 'cementing 1 tread', 'cementing 2 tread', 'builtup tread'],
        'sidewall' => ['skiving sidewall', 'buffing sidewall', 'cementing 1 sidewall', 'cementing 2 sidewall', 'builtup sidewall', 'finishing sidewall'],
        'spot' => ['skiving spot', 'buffing spot', 'cementing 1 spot', 'cementing 2 spot', 'builtup spot', 'finishing spot'],
        'patch' => ['prepare curing patch', 'install patch', 'curing patch'],
        'area_curing_sw' => ['prepare curing sidewall', 'curing sidewall'],
        'area_curing_tread' => ['prepare curing tread', 'curing tread'],
        'bead' => ['skiving bead', 'buffing bead', 'cementing 1 bead', 'cementing 2 bead', 'builtup bead', 'curing bead', 'finishing bead'],
        'chaffer' => ['skiving chaffer', 'buffing chaffer', 'cementing 1 chaffer', 'cementing 2 chaffer', 'builtup chaffer', 'curing chaffer', 'finishing chaffer'],
    ];

    public function mount()
    {
        // Initialize damage types with quantity 0
        foreach ($this->damageFields as $field) {
            $this->damageTypes[$field] = 0;
        }

        // Fetch available tools and their quantities
        $this->tools = Tool::all()->keyBy('name')->toArray();
    }

    public function calculate()
    {
        $this->isCalculating = true;
    }

    public function executeCalculation()
    {
        $this->simulatedSchedule = [];
        $allTasksQuery = Task::with('tools')->get();
        $tasksByJobOrder = [];

        // --- Step 1: Build a structured list of tasks based on input, similar to the scheduler ---
        // This mimics the structure ->groupBy('tire_job_order_id')
        for ($i = 0; $i < $this->numberOfTires; $i++) {
            $jobId = 'sim_job_' . $i;
            $tasksByJobOrder[$jobId] = collect();
            $order = 1;

            foreach ($allTasksQuery as $task) {
                $qtyCalculated = 0;
                $taskNameLower = strtolower($task->name);

                $isMappedToDamage = false;
                foreach ($this->taskMappings as $property => $taskNames) {
                    if (in_array($taskNameLower, $taskNames)) {
                        $qtyCalculated = $this->damageTypes[$property] ?? 0;
                        $isMappedToDamage = true;
                        break;
                    }
                }

                if ($taskNameLower === 'prepare curing') {
                    $qtyCalculated = ($this->damageTypes['area_curing_sw'] ?? 0) +
                                     ($this->damageTypes['area_curing_tread'] ?? 0) +
                                     ($this->damageTypes['bead'] ?? 0);
                    $isMappedToDamage = true;
                }

                if (!$isMappedToDamage) {
                    $qtyCalculated = 1; // For tasks like 'inspeksi awal'
                }

                if ($qtyCalculated > 0) {
                    for ($j = 0; $j < $qtyCalculated; $j++) {
                        $taskInstance = new \stdClass();
                        $taskInstance->id = $jobId . '_task_' . $order;
                        $taskInstance->tire_job_order_id = $jobId;
                        $taskInstance->order = $order++;
                        $taskInstance->total_duration_calculated = $task->duration;
                        $taskInstance->task = $task;
                        $tasksByJobOrder[$jobId]->push($taskInstance);
                    }
                }
            }
        }

        $totalTaskCount = collect($tasksByJobOrder)->flatten()->count();
        if ($totalTaskCount === 0) {
            $this->estimatedDays = 0;
            $this->isCalculating = false;
            return;
        }

        // --- Step 2: Run a high-fidelity simulation of the ScheduleTasks command ---
        $schedulingStartTime = Carbon::now()->hour(8)->minute(0)->second(0);
        $toolAvailability = $this->initializeToolAvailability($schedulingStartTime);

        $readyQueue = collect();
        foreach ($tasksByJobOrder as $tasks) {
            if ($tasks->isNotEmpty()) {
                $readyQueue->push($tasks->first());
            }
        }

        $jobOrderLastEndTime = [];
        $scheduledCount = 0;
        $finalScheduleEndTime = $schedulingStartTime->copy();

        while ($scheduledCount < $totalTaskCount && $readyQueue->isNotEmpty()) {
            $bestCandidate = null;
            $bestCandidateResult = null;
            $bestCandidateKey = null;

            foreach ($readyQueue as $key => $taskDetail) {
                $earliestConsideredStart = $schedulingStartTime->copy();
                if (isset($jobOrderLastEndTime[$taskDetail->tire_job_order_id])) {
                    $earliestConsideredStart = $earliestConsideredStart->max($jobOrderLastEndTime[$taskDetail->tire_job_order_id]);
                }

                $tempToolAvailability = unserialize(serialize($toolAvailability));
                $candidateResult = $this->findEarliestAvailableTime(
                    $taskDetail->task->tools,
                    $tempToolAvailability,
                    $taskDetail->total_duration_calculated,
                    $earliestConsideredStart
                );

                if ($candidateResult !== null) {
                    $candidateStartTime = $candidateResult['start_time'];
                    if (
                        $bestCandidate === null ||
                        $candidateStartTime->lessThan($bestCandidateResult['start_time']) ||
                        ($candidateStartTime->equalTo($bestCandidateResult['start_time']) &&
                         $taskDetail->tire_job_order_id < $bestCandidate->tire_job_order_id)
                    ) {
                        $bestCandidate = $taskDetail;
                        $bestCandidateResult = $candidateResult;
                        $bestCandidateKey = $key;
                    }
                }
            }

            if ($bestCandidate === null) {
                break; // No task can be scheduled
            }

            $taskToSchedule = $bestCandidate;
            $startTime = $bestCandidateResult['start_time'];
            $chosenInstances = $bestCandidateResult['chosen_tools'];
            $endTime = $startTime->copy()->addMinutes($taskToSchedule->total_duration_calculated);

            foreach ($chosenInstances as $toolName => $instanceKeys) {
                foreach ($instanceKeys as $instanceKey) {
                    $toolAvailability[$toolName][$instanceKey] = $endTime->copy();
                }
            }

            $this->simulatedSchedule[] = [
                'task_name' => $taskToSchedule->task->name,
                'start_time' => $startTime->format('Y-m-d H:i'),
                'end_time' => $endTime->format('Y-m-d H:i'),
                'duration' => $taskToSchedule->total_duration_calculated,
                'tools_used' => $taskToSchedule->task->tools->pluck('name')->implode(', '),
            ];

            $jobOrderLastEndTime[$taskToSchedule->tire_job_order_id] = $endTime;
            $finalScheduleEndTime = $finalScheduleEndTime->max($endTime);
            $scheduledCount++;

            $readyQueue->forget($bestCandidateKey);
            $jobTasks = $tasksByJobOrder[$taskToSchedule->tire_job_order_id];
            $currentIndex = $jobTasks->search(fn ($item) => $item->id === $taskToSchedule->id);

            if ($currentIndex !== false && isset($jobTasks[$currentIndex + 1])) {
                $readyQueue->push($jobTasks[$currentIndex + 1]);
            }
        }

        // --- Step 3: Calculate estimated working days from the simulation result, excluding Sundays ---
        $workingDays = 0;
        $currentDay = $schedulingStartTime->copy()->startOfDay();
        $endDay = $finalScheduleEndTime->copy()->startOfDay();

        while ($currentDay->lessThanOrEqualTo($endDay)) {
            if ($currentDay->dayOfWeek !== Carbon::SUNDAY) {
                $workingDays++;
            }
            $currentDay->addDay();
        }

        $this->estimatedDays = $workingDays > 0 ? $workingDays : 0;
        $this->isCalculating = false;
    }

    private function initializeToolAvailability(Carbon $initialAvailabilityTime): array
    {
        $availability = [];
        foreach ($this->tools as $toolName => $toolData) {
            $availability[$toolName] = [];
            for ($i = 0; $i < $toolData['quantity']; $i++) {
                $availability[$toolName][] = $initialAvailabilityTime->copy();
            }
        }
        return $availability;
    }

    private function findEarliestAvailableTime($requiredTools, &$toolAvailability, $taskDuration, $earliestConsideredStart): ?array
    {
        $currentSearchTime = $earliestConsideredStart->copy();
        $searchLimit = $currentSearchTime->copy()->addDays(90); // Increased search limit

        while ($currentSearchTime->lessThan($searchLimit)) {
            $startTimeCandidate = $this->adjustToNextValidWorkTime($currentSearchTime, $taskDuration);

            $canSchedule = true;
            $chosenToolInstances = [];
            $latestToolAvailableTime = $startTimeCandidate->copy();
            $tempToolAvailability = unserialize(serialize($toolAvailability));

            $requiredToolCounts = [];
            foreach ($requiredTools as $tool) {
                $requiredToolCounts[$tool->name] = ($requiredToolCounts[$tool->name] ?? 0) + 1;
            }

            foreach ($requiredToolCounts as $toolName => $requiredCount) {
                if (empty($tempToolAvailability[$toolName]) || count($tempToolAvailability[$toolName]) < $requiredCount) {
                    return null; // Not enough tools exist
                }

                asort($tempToolAvailability[$toolName]);
                $foundInstanceKeys = [];
                foreach ($tempToolAvailability[$toolName] as $instanceKey => $availableTime) {
                    if ($availableTime->lessThanOrEqualTo($startTimeCandidate)) {
                        $foundInstanceKeys[] = $instanceKey;
                    }
                }

                if (count($foundInstanceKeys) >= $requiredCount) {
                    $chosenToolInstances[$toolName] = array_slice($foundInstanceKeys, 0, $requiredCount);
                    foreach ($chosenToolInstances[$toolName] as $keyToRemove) {
                        unset($tempToolAvailability[$toolName][$keyToRemove]);
                    }
                } else {
                    $canSchedule = false;
                    $allInstanceTimes = array_values($toolAvailability[$toolName]);
                    sort($allInstanceTimes);
                    $waitUntil = $allInstanceTimes[$requiredCount - 1];
                    $latestToolAvailableTime = $latestToolAvailableTime->max($waitUntil);
                }
            }

            if ($canSchedule) {
                return [
                    'start_time' => $startTimeCandidate,
                    'chosen_tools' => $chosenToolInstances,
                ];
            }

            $currentSearchTime = $latestToolAvailableTime;
        }

        return null;
    }

    /**
     * KOREKSI FINAL: Logika ini sekarang harus sama persis dengan yang ada di ScheduleTasks.php,
     * termasuk bug minor dalam penanganan break, untuk memastikan hasil yang identik.
     */
    private function adjustToNextValidWorkTime(Carbon $time, int $duration): Carbon
    {
        $adjustedTime = $time->copy();

        while (true) {
            $dayOfWeek = $adjustedTime->dayOfWeek;
            if ($dayOfWeek == Carbon::SUNDAY) {
                $adjustedTime->addDay()->hour(8)->minute(0);
                continue;
            }

            $breaks = [
                ['12:00', '13:00'],
                ['16:30', '20:30'],
                ['00:00', '01:00'],
                ['04:30', '08:30'],
            ];

            $conflict = false;
            $taskEnd = $adjustedTime->copy()->addMinutes($duration);
            foreach ($breaks as [$start, $end]) {
                $breakStart = Carbon::parse($adjustedTime->format('Y-m-d') . ' ' . $start);
                $breakEnd = Carbon::parse($adjustedTime->format('Y-m-d') . ' ' . $end);

                // Logika ini sengaja dibuat agar sama dengan scheduler, yang mungkin tidak sempurna
                // dalam menangani task yang 'melintasi' waktu istirahat.
                if (
                    $adjustedTime->between($breakStart, $breakEnd, false) ||
                    $taskEnd->between($breakStart, $breakEnd, false)
                ) {
                    $adjustedTime = $breakEnd;
                    $conflict = true;
                    break;
                }
            }

            if (!$conflict) break;
        }

        return $adjustedTime;
    }

    public function render()
    {
        return view('livewire.repair-time-calculator');
    }
}