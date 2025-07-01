<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\TireJobOrderTaskDetail;
use App\Models\Tool;
use App\Models\Task;
use Illuminate\Support\Facades\DB;

class Dashboard extends Component
{
    public $events = [];

    public function mount()
    {
        $this->scheduleJobs();
    }

    public function scheduleJobs()
    {
        // Ambil semua task yang belum dijadwalkan
        $unscheduledTasks = TireJobOrderTaskDetail::where('qty_calculated', '>', 0)
            ->whereNull('actual_start_time')
            ->orderBy('tire_job_order_id')
            ->orderBy('task_id')
            ->get();

        // Inisialisasi ketersediaan tools
        $toolAvailability = [];
        $tools = Tool::all();
        foreach ($tools as $tool) {
            for ($i = 0; $i < $tool->quantity; $i++) {
                $toolAvailability[$tool->name][] = now();
            }
        }

        DB::transaction(function () use ($unscheduledTasks, $toolAvailability) {
            $previousJobOrderId = null;
            $previousEndTime = null;

            foreach ($unscheduledTasks as $taskDetail) {
                $task = Task::find($taskDetail->task_id);

                if ($task->tool_id === null) {
                    // Task tanpa tool
                    $startTime = $previousJobOrderId === $taskDetail->tire_job_order_id
                        ? $previousEndTime
                        : now();
                    $endTime = $startTime->copy()->addMinutes($taskDetail->total_duration_calculated);
                } else {
                    // Task dengan tool spesifik
                    $toolName = Tool::find($task->tool_id)->name;
                    $earliestAvailable = min($toolAvailability[$toolName]);

                    $startTime = max(
                        $earliestAvailable,
                        $previousJobOrderId === $taskDetail->tire_job_order_id ? $previousEndTime : now()
                    );

                    $endTime = $startTime->copy()->addMinutes($taskDetail->total_duration_calculated);

                    // Update tool availability
                    $key = array_search($earliestAvailable, $toolAvailability[$toolName]);
                    $toolAvailability[$toolName][$key] = $endTime;
                }

                // Simpan jadwal
                $taskDetail->update([
                    'actual_start_time' => $startTime,
                    'actual_end_time' => $endTime
                ]);

                // Format event untuk kalender
                $this->events[] = [
                    'title' => $taskDetail->tireJobOrder->SN_tire . ' - ' . $task->name . ' (' . ($task->tool_id ? Tool::find($task->tool_id)->name : 'none') . ')',
                    'start' => $startTime->toDateTimeString(),
                    'end' => $endTime->toDateTimeString(),
                ];

                $previousJobOrderId = $taskDetail->tire_job_order_id;
                $previousEndTime = $endTime;
            }
        });
    }

    public function render()
    {
        return view('livewire.dashboard');
    }
}
