<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\TireJobOrder;
use App\Models\Task;
use App\Models\TireJobOrderTaskDetail;
use App\Models\Customer;

class ManageTireJobOrders extends Component
{
    public $sn_tire;
    public $tread;
    public $sidewall;
    public $customer_id;
    public $jobOrderId;
    public $isOpen = false;

    protected $rules = [
        'sn_tire' => 'required|string|max:255',
        'tread' => 'nullable|integer|min:0',
        'sidewall' => 'nullable|integer|min:0',
        'customer_id' => 'required|exists:customers,id',
    ];

    public function render()
    {
        $jobOrders = TireJobOrder::with(['tireJobOrderTaskDetails', 'customer'])->paginate(10);
        $customers = Customer::all();

        return view('livewire.manage-tire-job-orders', [
            'jobOrders' => $jobOrders,
            'isOpen' => $this->isOpen,
            'customers' => $customers,
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
        $this->customer_id = null;
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

        $tireJobOrder = TireJobOrder::updateOrCreate(['id' => $this->jobOrderId], [
            'sn_tire' => $this->sn_tire,
            'tread' => $this->tread,
            'sidewall' => $this->sidewall,
            'customer_id' => $this->customer_id,
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
                    'status' => 'pending',
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
        $this->customer_id = $jobOrder->customer_id;

        $this->openModal();
    }

    public function delete($id)
    {
        TireJobOrder::find($id)->delete();
        session()->flash('message', 'Tire Job Order berhasil dihapus.');
    }
}
