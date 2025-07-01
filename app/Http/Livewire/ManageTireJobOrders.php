<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\TireJobOrder;
use App\Models\Task;
use App\Models\TireJobOrderTaskDetail;

class ManageTireJobOrders extends Component
{
    public $isOpen = false;
    public $jobOrderId;
    public $SN_tire = '';

    // Inspection inputs
    public $tread = 0;
    public $sidewall = 0;
    public $spot = 0;
    public $patch = 0;
    public $area_curing_sw = 0;
    public $area_curing_tread = 0;
    public $bead = 0;
    public $chaffer = 0;

    // Calculated values
    public $tasks = [];

    protected $rules = [
        'SN_tire' => 'required|unique:tire_job_orders,SN_tire',
    ];

    public function render()
    {
        $jobOrders = TireJobOrder::all();
        return view('livewire.manage-tire-job-orders', ['jobOrders' => $jobOrders]);
    }

    public function create()
    {
        $this->resetInputFields();
        $this->isOpen = true;
    }

    public function edit($id)
    {
        $jobOrder = TireJobOrder::findOrFail($id);

        $this->jobOrderId = $id;
        $this->SN_tire = $jobOrder->SN_tire;

        // Load inspection results from task details
        $this->loadInspectionResults($jobOrder);

        $this->isOpen = true;
    }

    public function store()
    {
        $this->validate();

        $jobOrder = TireJobOrder::updateOrCreate(
            ['id' => $this->jobOrderId],
            ['SN_tire' => $this->SN_tire]
        );

        // Save task details
        $this->saveTaskDetails($jobOrder);

        session()->flash(
            'message',
            $this->jobOrderId ? 'Job Order berhasil diupdate.' : 'Job Order berhasil dibuat.'
        );

        $this->closeModal();
    }

    public function delete($id)
    {
        TireJobOrder::find($id)->delete();
        session()->flash('message', 'Job Order berhasil dihapus.');
    }

    public function closeModal()
    {
        $this->isOpen = false;
    }

    public function resetInputFields()
    {
        $this->jobOrderId = null;
        $this->SN_tire = '';
        $this->tread = 0;
        $this->sidewall = 0;
        $this->spot = 0;
        $this->patch = 0;
        $this->area_curing_sw = 0;
        $this->area_curing_tread = 0;
        $this->bead = 0;
        $this->chaffer = 0;
    }

    protected function loadInspectionResults($jobOrder)
    {
        // Implement loading of inspection results from task details
    }

    protected function saveTaskDetails($jobOrder)
    {
        // Implement saving of task details
    }

    protected function calculateTasks()
    {
        // Implement task calculation logic based on inspection inputs
    }
}
