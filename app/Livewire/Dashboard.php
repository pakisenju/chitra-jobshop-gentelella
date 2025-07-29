<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\TireJobOrderTaskDetail;
use App\Models\Tool;
use App\Models\Task;
use App\Models\TireJobOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\File;

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
    public $showScheduleModal = false;
    public $selectedDate;
    public $scheduleData = [];
    public $tasksToMarkAsDone = [];
    public $selectAll = []; // Changed to array
    public $isScheduling = false;

    public function updatedSelectAll($value, $shift)
    {
        if ($value) {
            $this->tasksToMarkAsDone = array_merge(
                $this->tasksToMarkAsDone,
                collect($this->scheduleData[$shift] ?? [])
                    ->filter(fn($task) => $task->status === 'scheduled')
                    ->pluck('id')
                    ->toArray()
            );
        } else {
            // Remove tasks from this specific shift from the selection
            $tasksInShift = collect($this->scheduleData[$shift] ?? [])->pluck('id')->toArray();
            $this->tasksToMarkAsDone = array_diff($this->tasksToMarkAsDone, $tasksInShift);
        }
        // Ensure unique values and re-index array
        $this->tasksToMarkAsDone = array_values(array_unique($this->tasksToMarkAsDone));
    }


    protected $listeners = ['showTaskDetail', 'refreshCalendar', 'dateClicked'];

    public function mount()
    {
        $this->jobOrders = TireJobOrder::all();
        $this->tools = Tool::all();
    }

    public function runScheduler()
    {
        $this->dispatch('showShiftSelection');
    }

    public function prepareAndSchedule()
    {
        $this->validate([
            'selectedShift' => 'required|in:pagi,malam',
        ]);
        $this->isScheduling = true;
    }

    public function executeScheduling()
    {
        \Illuminate\Support\Facades\Artisan::call('schedule:tasks', [
            '--shift' => $this->selectedShift,
        ]);

        session()->flash('message', 'Scheduler command executed for ' . $this->selectedShift . ' shift.');
        $this->dispatch('refreshCalendar');
        $this->dispatch('hideShiftSelectionModal');
        $this->closeShiftSelectionModal();
        $this->isScheduling = false;
    }

    public function closeShiftSelectionModal()
    {
        $this->showShiftSelectionModal = false;
        $this->selectedShift = '';
    }

    public function dateClicked($date)
    {
        $this->selectedDate = $date;
        $this->loadScheduleData();
        $this->dispatch('showSchedule');
    }

    public function closeScheduleModal()
    {
        $this->showScheduleModal = false;
        $this->dispatch('hideScheduleModal');
        $this->selectedDate = null;
        $this->scheduleData = [];
        $this->tasksToMarkAsDone = [];
        $this->selectAll = []; // Reset as array
    }

    public function loadScheduleData()
    {
        if (!$this->selectedDate) {
            return;
        }

        $this->tasksToMarkAsDone = [];
        $this->selectAll = []; // Reset as array

        $date = Carbon::parse($this->selectedDate);

        // Morning Shift
        $startPagi = $date->copy()->setTime(8, 0, 0);
        $endPagi = $date->copy()->setTime(17, 0, 0);
        $this->scheduleData['pagi'] = TireJobOrderTaskDetail::with(['task.tools', 'tireJobOrder'])
            ->whereIn('status', ['scheduled', 'done'])
            ->whereBetween('start_time', [$startPagi, $endPagi])
            ->orderBy('start_time')->get();

        // Night Shift
        $startMalam = $date->copy()->setTime(20, 0, 0);
        $endMalam = $date->copy()->addDay()->setTime(5, 0, 0);
        $this->scheduleData['malam'] = TireJobOrderTaskDetail::with(['task.tools', 'tireJobOrder'])
            ->whereIn('status', ['scheduled', 'done'])
            ->whereBetween('start_time', [$startMalam, $endMalam])
            ->orderBy('start_time')->get();
    }

    public function exportSchedule($shift)
    {
        if (!isset($this->scheduleData[$shift])) {
            return;
        }

        $tasks = $this->scheduleData[$shift];
        $date = Carbon::parse($this->selectedDate);
        $fileName = 'schedule_' . $date->format('Y-m-d') . '_' . $shift . '.csv';
        $filePath = 'exports/' . $fileName;

        // Using a temporary stream to build CSV content
        $handle = fopen('php://temp', 'r+');

        // Add headers
        fputcsv($handle, ['Job Order SN', 'Task Name', 'Tools', 'Start Time', 'End Time', 'Status']);

        // Add data
        foreach ($tasks as $task) {
            fputcsv($handle, [
                $task->tireJobOrder->sn_tire,
                $task->task->name,
                $task->task->tools->pluck('name')->implode(', '),
                $task->start_time ? $task->start_time->format('Y-m-d H:i') : 'N/A',
                $task->end_time ? $task->end_time->format('Y-m-d H:i') : 'N/A',
                $task->status,
            ]);
        }

        rewind($handle);
        $csvContent = stream_get_contents($handle);
        fclose($handle);

        Storage::disk('local')->put($filePath, $csvContent);

        return Storage::disk('local')->download($filePath, $fileName);
    }

    public function exportPdf($shift)
    {
        if (!isset($this->scheduleData[$shift])) {
            return;
        }

        $tasks = $this->scheduleData[$shift];
        $date = Carbon::parse($this->selectedDate)->format('Y-m-d');

        $pdf = Pdf::loadView('pdf.schedule', [
            'tasks' => $tasks,
            'date' => $date,
            'shift' => $shift,
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'schedule_' . $date . '_' . $shift . '.pdf');
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

    public function getEvents($start, $end)
    {
        $events = [];

        $query = TireJobOrderTaskDetail::with(['task.tools', 'tireJobOrder'])
            ->whereIn('status', ['scheduled', 'done'])
            ->where('total_duration_calculated', '>', 0)
            ->whereNotNull('start_time')
            ->whereNotNull('end_time')
            ->where(function ($q) use ($start, $end) {
                $q->where('start_time', '<', Carbon::parse($end))
                    ->where('end_time', '>', Carbon::parse($start));
            });

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

    public function markSelectedTasksAsDone()
    {
        TireJobOrderTaskDetail::whereIn('id', $this->tasksToMarkAsDone)->update(['status' => 'done']);

        $this->tasksToMarkAsDone = [];
        $this->loadScheduleData(); // Refresh the data in the modal
        $this->dispatch('refreshCalendar'); // Refresh the calendar
        session()->flash('message', 'Selected tasks have been marked as done.');
    }
}
