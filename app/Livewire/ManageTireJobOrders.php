<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\TireJobOrder;
use App\Models\Task;
use App\Models\TireJobOrderTaskDetail;

class ManageTireJobOrders extends Component
{
    public $sn_tire;
    public $tread;
    public $sidewall;
    public $jobOrderId;
    public $isOpen = false;
    public $calculatedTaskDetails = [];

    protected $rules = [
        'sn_tire' => 'required|string|max:255|unique:tire_job_orders,sn_tire',
        'tread' => 'nullable|integer|min:0',
        'sidewall' => 'nullable|integer|min:0',
    ];

    public function mount()
    {
        $this->calculateTaskDetails();
    }

    public function updatedTread()
    {
        $this->calculateTaskDetails();
    }

    public function updatedSidewall()
    {
        $this->calculateTaskDetails();
    }

    public function calculateTaskDetails()
    {
        $this->calculatedTaskDetails = [];
        $tasks = Task::all();

        foreach ($tasks as $task) {
            $qtyCalculated = 1;
            if (stripos($task->name, 'tread') !== false) {
                $qtyCalculated = $this->tread ?? 0;
            } elseif (stripos($task->name, 'sidewall') !== false) {
                $qtyCalculated = $this->sidewall ?? 0;
            }
            $totalDurationCalculated = $task->duration * $qtyCalculated;

            $this->calculatedTaskDetails[] = [
                'task_name' => $task->name,
                'duration_master' => $task->duration,
                'qty_calculated' => $qtyCalculated,
                'total_duration_calculated' => $totalDurationCalculated,
            ];
        }
    }

    public function render()
    {
        $jobOrders = TireJobOrder::paginate(10);

        return view('livewire.manage-tire-job-orders', [
            'jobOrders' => $jobOrders,
            'isOpen' => $this->isOpen,
            'calculatedTaskDetails' => $this->calculatedTaskDetails,
        ]);
    }

    public function create()
    {
        $this->resetInputFields();
        $this->openModal();
    }

    public function resetInputFields()
    {
        $this->sn_tire = '';
        $this->tread = null;
        $this->sidewall = null;
        $this->jobOrderId = '';
    }

    public function openModal()
    {
        $this->isOpen = true;
    }

    public function closeModal()
    {
        $this->isOpen = false;
    }

    public function store()
    {
        $this->validate();

        TireJobOrder::updateOrCreate(['id' => $this->jobOrderId], [
            'sn_tire' => $this->sn_tire,
            'tread' => $this->tread,
            'sidewall' => $this->sidewall,
        ]);

        $tireJobOrder = TireJobOrder::updateOrCreate(['id' => $this->jobOrderId], [
            'sn_tire' => $this->sn_tire,
            'tread' => $this->tread,
            'sidewall' => $this->sidewall,
        ]);

        $tasks = Task::all();

        foreach ($tasks as $task) {
            $qtyCalculated = 1;
            if (stripos($task->name, 'tread') !== false) {
                $qtyCalculated = $this->tread ?? 0;
            } elseif (stripos($task->name, 'sidewall') !== false) {
                $qtyCalculated = $this->sidewall ?? 0;
            }
            $totalDurationCalculated = $task->duration * $qtyCalculated;

            TireJobOrderTaskDetail::updateOrCreate(
                [
                    'tire_job_order_id' => $tireJobOrder->id,
                    'task_id' => $task->id
                ],
                [
                    'qty_calculated' => $qtyCalculated,
                    'total_duration_calculated' => $totalDurationCalculated,
                    'tool_id_used' => $task->tool_id, // Assuming tool_id_used is the same as task's tool_id
                ]
            );
        }

        session()->flash(
            'message',
            $this->jobOrderId ? 'Tire Job Order berhasil diupdate.' : 'Tire Job Order berhasil dibuat.'
        );

        $this->closeModal();
        $this->resetInputFields();
    }

    public function edit($id)
    {
        $jobOrder = TireJobOrder::findOrFail($id);

        $this->jobOrderId = $id;
        $this->sn_tire = $jobOrder->sn_tire;
        $this->tread = $jobOrder->tread;
        $this->sidewall = $jobOrder->sidewall;

        $this->calculateTaskDetails();

        $this->openModal();
    }

    public function delete($id)
    {
        TireJobOrder::find($id)->delete();
        session()->flash('message', 'Tire Job Order berhasil dihapus.');
    }
}
