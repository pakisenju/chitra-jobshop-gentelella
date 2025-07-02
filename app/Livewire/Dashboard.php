<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\TireJobOrderTaskDetail;
use App\Models\Tool;
use App\Models\Task;
use App\Models\TireJobOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class Dashboard extends Component
{
    public $events = [];
    public $simulatedTasks = [];
    protected $toolAvailability = [];

    public function mount()
    {
        $this->initializeToolAvailability();
        $this->scheduleJobs();
    }

    private function initializeToolAvailability()
    {
        $this->toolAvailability = [];
        $tools = Tool::all();
        foreach ($tools as $tool) {
            for ($i = 0; $i < $tool->quantity; $i++) {
                $this->toolAvailability[$tool->name][] = Carbon::now('Asia/Makassar');
            }
        }
    }

    public function scheduleJobs()
    {
        // Reset penjadwalan sebelumnya
        TireJobOrderTaskDetail::where('qty_calculated', '>', 0)
            ->update([
                'actual_start_time' => null,
                'actual_end_time' => null,
                'tool_id_used' => null
            ]);

        // Inisialisasi ulang ketersediaan tools
        $this->initializeToolAvailability();

        // Ambil semua task yang akan dijadwalkan
        $unscheduledTasks = TireJobOrderTaskDetail::with(['task', 'tireJobOrder'])
            ->where('qty_calculated', '>', 0)
            ->orderBy('tire_job_order_id')
            ->orderBy('id')
            ->get();

        $previousJobOrderEndTimes = [];

        DB::transaction(function () use ($unscheduledTasks) {
            $previousJobOrderEndTimes = []; // Track end time for each job order

            foreach ($unscheduledTasks as $taskDetail) {
                $task = Task::find($taskDetail->task_id);

                $earliestPossibleStartTime = Carbon::now();

                // Consider previous task in the same job order
                if (isset($previousJobOrderEndTimes[$taskDetail->tire_job_order_id])) {
                    $earliestPossibleStartTime = $earliestPossibleStartTime->max($previousJobOrderEndTimes[$taskDetail->tire_job_order_id]);
                }

                $selectedToolInstanceKey = null;
                if ($task->tool_id !== null) {
                    $toolName = Tool::find($task->tool_id)->name;
                    if (!isset($this->toolAvailability[$toolName])) {
                        // This tool is not initialized, which means its quantity is 0 or it doesn't exist.
                        // Handle this case, e.g., skip task or log an error.
                        continue;
                    }

                    $earliestToolAvailableTime = null;
                    foreach ($this->toolAvailability[$toolName] as $key => $availableTime) {
                        if ($earliestToolAvailableTime === null || $availableTime->lt($earliestToolAvailableTime)) {
                            $earliestToolAvailableTime = $availableTime;
                            $selectedToolInstanceKey = $key;
                        }
                    }

                    if ($earliestToolAvailableTime !== null) {
                        $earliestPossibleStartTime = $earliestPossibleStartTime->max($earliestToolAvailableTime);
                    } else {
                        // No tool instance available, skip this task for now
                        continue;
                    }
                }

                $startTime = $earliestPossibleStartTime;
                $endTime = $startTime->copy()->addMinutes($taskDetail->total_duration_calculated);

                // Update tool availability
                if ($task->tool_id !== null && $selectedToolInstanceKey !== null) {
                    $toolName = Tool::find($task->tool_id)->name;
                    $this->toolAvailability[$toolName][$selectedToolInstanceKey] = $endTime;
                }

                // Simpan jadwal
                $taskDetail->update([
                    'actual_start_time' => $startTime,
                    'actual_end_time' => $endTime
                ]);

                // Update previous job order end time
                $previousJobOrderEndTimes[$taskDetail->tire_job_order_id] = $endTime;

                // Format event untuk kalender
                $this->events[] = [
                    'title' => $taskDetail->tireJobOrder->sn_tire . ' - ' . $task->name . ' (' . ($task->tool_id ? Tool::find($task->tool_id)->name : 'none') . ')',
                    'start' => $startTime->toDateTimeString(),
                    'end' => $endTime->toDateTimeString(),
                ];
            }
        });
    }

    private function loadScheduledEvents()
    {
        $this->events = [];

        $scheduledTasks = TireJobOrderTaskDetail::with(['task', 'tireJobOrder', 'toolUsed'])
            ->where('qty_calculated', '>', 0)
            ->whereNotNull('actual_start_time')
            ->whereNotNull('actual_end_time')
            ->get();

        foreach ($scheduledTasks as $taskDetail) {
            $toolName = $taskDetail->toolUsed ? $taskDetail->toolUsed->name : 'none';

            $this->events[] = [
                'title' => $taskDetail->tireJobOrder->sn_tire . ' - ' . $taskDetail->task->name . ' (' . $toolName . ')',
                'start' => $taskDetail->actual_start_time->toIso8601String(),
                'end' => $taskDetail->actual_end_time->toIso8601String(),
                'backgroundColor' => $this->getEventColor($toolName),
                'borderColor' => $this->getEventColor($toolName),
                'extendedProps' => [
                    'jobOrderId' => $taskDetail->tire_job_order_id,
                    'taskId' => $taskDetail->task_id
                ]
            ];
        }
    }

    private function getEventColor(string $toolName): string
    {
        $colors = [
            'airbuffing high' => '#ff0000',
            'extruder' => '#0000ff',
            'none' => '#808080'
        ];

        return $colors[$toolName] ?? '#' . substr(md5($toolName), 0, 6);
    }

    public function simulateScheduling()
    {
        $this->simulatedTasks = [];

        $jobOrders = TireJobOrder::with('taskDetails.task')->get();

        foreach ($jobOrders as $jobOrder) {
            foreach ($jobOrder->taskDetails as $taskDetail) {
                if ($taskDetail->total_duration_calculated > 0) {
                    $this->simulatedTasks[] = [
                        'job_order_sn' => $jobOrder->sn_tire,
                        'task_name' => $taskDetail->task->name,
                        'calculated_duration' => $taskDetail->total_duration_calculated,
                    ];
                }
            }
        }
    }

    public function render()
    {
        $this->loadScheduledEvents();
        return view('livewire.dashboard', [
            'simulatedTasks' => $this->simulatedTasks,
        ]);
    }
}
