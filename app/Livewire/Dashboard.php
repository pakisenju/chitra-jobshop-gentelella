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
    public $showTaskDetailModal = false;
    public $selectedTaskDetail;
    public $jobOrders = [];
    public $tools = [];
    public $selectedJobOrderId = '';
    public $selectedToolId = '';
    public $showShiftSelectionModal = false;
    public $selectedShift = '';


    protected $listeners = ['showTaskDetail', 'refreshCalendar'];

    public function mount()
    {
        $this->jobOrders = TireJobOrder::all();
        $this->tools = Tool::all();
    }

    public function runScheduler()
    {
        $this->showShiftSelectionModal = true;
    }

    public function startScheduling()
    {
        $this->validate([
            'selectedShift' => 'required|in:pagi,malam',
        ]);

        \Illuminate\Support\Facades\Artisan::call('schedule:tasks', [
            '--shift' => $this->selectedShift,
        ]);

        session()->flash('message', 'Scheduler command executed for ' . $this->selectedShift . ' shift.');
        $this->dispatch('refreshCalendar');
        $this->closeShiftSelectionModal();
    }

    public function closeShiftSelectionModal()
    {
        $this->showShiftSelectionModal = false;
        $this->selectedShift = '';
    }

    public function markTaskAsDone($jobOrderId, $taskId)
    {
        $taskDetail = TireJobOrderTaskDetail::where('tire_job_order_id', $jobOrderId)
            ->where('task_id', $taskId)
            ->first();

        if ($taskDetail) {
            $taskDetail->status = 'done';
            $taskDetail->save();
            session()->flash('message', 'Task marked as done.');
            $this->dispatch('refreshCalendar');
            $this->closeTaskDetailModal();
        }
    }

    public function showTaskDetail($jobOrderId, $taskId)
    {
        $this->selectedTaskDetail = TireJobOrderTaskDetail::with(['task.tools', 'tireJobOrder'])
            ->where('tire_job_order_id', $jobOrderId)
            ->where('task_id', $taskId)
            ->first();

        $this->showTaskDetailModal = true;
    }

    public function closeTaskDetailModal()
    {
        $this->showTaskDetailModal = false;
        $this->selectedTaskDetail = null;
    }

    public function getEvents()
    {
        $events = [];

        $query = TireJobOrderTaskDetail::with(['task.tools', 'tireJobOrder'])
            ->whereIn('status', ['scheduled', 'done'])
            ->where('total_duration_calculated', '>', 0)
            ->whereNotNull('start_time')
            ->whereNotNull('end_time');

        if ($this->selectedJobOrderId) {
            $query->where('tire_job_order_id', $this->selectedJobOrderId);
        }

        if ($this->selectedToolId) {
            $query->whereHas('task.tools', function ($q) {
                $q->where('tools.id', $this->selectedToolId);
            });
        }

        $scheduledTasks = $query->get()->groupBy('tire_job_order_id');


        foreach ($scheduledTasks as $jobOrderId => $tasksInJobOrder) {
            $sortedTasks = $tasksInJobOrder->sortBy('start_time');
            $taskNumber = 1;
            foreach ($sortedTasks as $taskDetail) {
                $toolNames = $taskDetail->task->tools->pluck('name')->implode(', ');
                if (empty($toolNames)) {
                    $toolNames = 'None';
                }

                $colors = $this->getEventColor($taskDetail->tire_job_order_id, $taskDetail->status);

                $title = $taskNumber . '. ' . $taskDetail->tireJobOrder->sn_tire . ' - ' . $taskDetail->task->name . ' (' . $toolNames . ')';

                $events[] = [
                    'title' => $title,
                    'start' => $taskDetail->start_time->toIso8601String(),
                    'end' => $taskDetail->end_time->toIso8601String(),
                    'backgroundColor' => $colors['backgroundColor'],
                    'borderColor' => $colors['borderColor'],
                    'textColor' => $colors['textColor'],
                    'extendedProps' => [
                        'jobOrderId' => $taskDetail->tire_job_order_id,
                        'taskId' => $taskDetail->task_id,
                        'status' => $taskDetail->status // Add status here
                    ]
                ];
                $taskNumber++;
            }
        }
        return $events;
    }

    public function updatedSelectedJobOrderId()
    {
        $this->dispatch('refreshCalendar');
    }

    public function updatedSelectedToolId()
    {
        $this->dispatch('refreshCalendar');
    }

    private function getEventColor(int $jobOrderId, string $status): array
    {
        if ($status === 'done') {
            return [
                'backgroundColor' => '#d3d3d3', // Light gray for done tasks
                'borderColor' => '#d3d3d3',
                'textColor' => '#000000',
            ];
        }

        // Generate a consistent color based on the job order ID
        $hash = md5((string)$jobOrderId);
        $backgroundColor = '#' . substr($hash, 0, 6);

        // Determine text color based on background brightness
        $hex = str_replace('#', '', $backgroundColor);
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        $brightness = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;

        $textColor = $brightness > 155 ? '#000000' : '#FFFFFF';

        return [
            'backgroundColor' => $backgroundColor,
            'borderColor' => $backgroundColor,
            'textColor' => $textColor,
        ];
    }

    public function render()
    {
        return view('livewire.dashboard');
    }
}
