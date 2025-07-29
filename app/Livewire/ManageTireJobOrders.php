<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\TireJobOrder;
use App\Models\Task;
use App\Models\TireJobOrderTaskDetail;
use App\Models\Customer;

class ManageTireJobOrders extends Component
{
    public $isOpen = false;
    public $jobOrderId;
    public $jobOrderToDeleteId = null;

    public $sn_tire, $tread, $sidewall, $spot, $patch, $area_curing_sw, $area_curing_tread, $bead, $chaffer, $customer_id;

    protected $rules = [
        'sn_tire' => 'required|string|max:255',
        'tread' => 'nullable|integer|min:0',
        'sidewall' => 'nullable|integer|min:0',
        'spot' => 'nullable|integer|min:0',
        'patch' => 'nullable|integer|min:0',
        'area_curing_sw' => 'nullable|integer|min:0',
        'area_curing_tread' => 'nullable|integer|min:0',
        'bead' => 'nullable|integer|min:0',
        'chaffer' => 'nullable|integer|min:0',
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

        $this->tread = 0;
        $this->sidewall = 0;
        $this->spot = 0;
        $this->patch = 0;
        $this->area_curing_sw = 0;
        $this->area_curing_tread = 0;
        $this->bead = 0;
        $this->chaffer = 0;

        $this->openModal();
    }

    public function resetInputFields()
    {
        $this->sn_tire = '';
        $this->tread = null;
        $this->sidewall = null;
        $this->spot = null;
        $this->patch = null;
        $this->area_curing_sw = null;
        $this->area_curing_tread = null;
        $this->bead = null;
        $this->chaffer = null;
        $this->customer_id = null;
        $this->jobOrderId = null;
        $this->jobOrderToDeleteId = null;
    }

    public function openModal()
    {
        $this->isOpen = true;
        $this->dispatch('showTireJobOrderModal');
    }

    public function closeModal()
    {
        $this->isOpen = false;
        $this->dispatch('hideTireJobOrderModal');
    }

    public function store()
    {
        $this->validate();

        // Custom validation for sn_tire
        $existingJobOrder = TireJobOrder::where('sn_tire', $this->sn_tire)->first();

        if ($existingJobOrder && $existingJobOrder->id != $this->jobOrderId) {
            $allTasksDone = $existingJobOrder->tireJobOrderTaskDetails->every(function ($taskDetail) {
                return $taskDetail->status === 'done';
            });

            if (!$allTasksDone) {
                $this->addError('sn_tire', 'Tire Job Order dengan SN ini masih memiliki task yang aktif.');
                return;
            }
        }

        $tireJobOrder = TireJobOrder::updateOrCreate(['id' => $this->jobOrderId], [
            'sn_tire' => $this->sn_tire,
            'tread' => $this->tread,
            'sidewall' => $this->sidewall,
            'spot' => $this->spot,
            'patch' => $this->patch,
            'area_curing_sw' => $this->area_curing_sw,
            'area_curing_tread' => $this->area_curing_tread,
            'bead' => $this->bead,
            'chaffer' => $this->chaffer,
            'customer_id' => $this->customer_id,
        ]);

        $tasks = Task::all();

        $taskMappings = [
            'tread' => ['skiving tread', 'buffing tread', 'cementing 1 tread', 'cementing 2 tread', 'builtup tread'],
            'sidewall' => ['skiving sidewall', 'buffing sidewall', 'cementing 1 sidewall', 'cementing 2 sidewall', 'builtup sidewall', 'finishing sidewall'],
            'spot' => ['skiving spot', 'buffing spot', 'cementing 1 spot', 'cementing 2 spot', 'builtup spot', 'finishing spot'],
            'patch' => ['prepare curing patch', 'install patch', 'curing patch'],
            'area_curing_sw' => ['prepare curing sidewall', 'curing sidewall'],
            'area_curing_tread' => ['prepare curing tread', 'curing tread'],
            'bead' => ['skiving bead', 'buffing bead', 'cementing 1 bead', 'cementing 2 bead', 'builtup bead', 'curing bead', 'finishing bead'],
            'chaffer' => ['skiving chaffer', 'buffing chaffer', 'cementing 1 chaffer', 'cementing 2 chaffer', 'builtup chaffer', 'curing chaffer', 'finishing chaffer'],
        ];

        // Define the desired order of tasks
        $taskOrder = [
            'inspeksi awal',
            'washing',
            'skiving sidewall',
            'skiving tread',
            'skiving spot',
            'skiving bead',
            'skiving chaffer',
            'inspeksi after skiving',
            'buffing sidewall',
            'buffing tread',
            'buffing spot',
            'buffing bead',
            'buffing chaffer',
            'cementing 1 sidewall',
            'cementing 1 tread',
            'cementing 1 spot',
            'cementing 1 bead',
            'cementing 1 chaffer',
            'dryup cement 1',
            'cementing 2 sidewall',
            'cementing 2 tread',
            'cementing 2 spot',
            'cementing 2 bead',
            'cementing 2 chaffer',
            'dryup cementing 2',
            'builtup tread',
            'builtup spot',
            'builtup bead',
            'install patch',
            'builtup chaffer',
            'prepare curing sidewall',
            'curing sidewall',
            'prepare curing tread',
            'curing tread',
            'prepare curing patch',
            'curing patch',
            'curing bead',
            'curing chaffer',
            'finishing sidewall',
            'finishing tread',
            'finishing spot',
            'finishing bead',
            'finishing chaffer',
            'final inspection(pumping)'
        ];

        // Create a map from task name to its order
        $taskOrderMap = array_flip($taskOrder);

        foreach ($tasks as $task) {
            $qtyCalculated = 0;
            $taskNameLower = strtolower($task->name);
            $isTaskMapped = false;

            foreach ($taskMappings as $property => $taskNames) {
                if (in_array($taskNameLower, $taskNames)) {
                    $qtyCalculated = $this->{$property} ?? 0;
                    $isTaskMapped = true;
                    break;
                }
            }

            if ($taskNameLower === 'prepare curing') {
                $qtyCalculated = ($this->area_curing_sw ?? 0) + ($this->area_curing_tread ?? 0) + ($this->bead ?? 0);
                $isTaskMapped = true;
            }

            if (!$isTaskMapped) {
                $qtyCalculated = 1;
            }

            // skip task if qty is 0
            if ($qtyCalculated === 0) {
                continue;
            }

            $totalDurationCalculated = $task->duration * $qtyCalculated;

            $orderIndex = $taskOrderMap[$taskNameLower] ?? 999;

            // Tambahan logika agar prepare curing selalu tepat sebelum curing
            if (str_starts_with($taskNameLower, 'prepare curing')) {
                $nextCuring = str_replace('prepare ', '', $taskNameLower);
                $orderIndex = $taskOrderMap[$nextCuring] - 1; // tepat sebelum curing
            }

            TireJobOrderTaskDetail::updateOrCreate(
                [
                    'tire_job_order_id' => $tireJobOrder->id,
                    'task_id' => $task->id
                ],
                [
                    'qty_calculated' => $qtyCalculated,
                    'total_duration_calculated' => $totalDurationCalculated,
                    'status' => 'pending',
                    'order' => $orderIndex
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
        $this->spot = $jobOrder->spot;
        $this->patch = $jobOrder->patch;
        $this->area_curing_sw = $jobOrder->area_curing_sw;
        $this->area_curing_tread = $jobOrder->area_curing_tread;
        $this->bead = $jobOrder->bead;
        $this->chaffer = $jobOrder->chaffer;
        $this->customer_id = $jobOrder->customer_id;

        $this->openModal();
    }

    public function prepareDelete($id)
    {
        $this->jobOrderToDeleteId = $id;
        $this->dispatch('showDeleteConfirmationModal');
    }

    public function confirmDelete()
    {
        if ($this->jobOrderToDeleteId) {
            TireJobOrder::find($this->jobOrderToDeleteId)->delete();
            session()->flash('message', 'Tire Job Order berhasil dihapus.');
            $this->dispatch('hideDeleteConfirmationModal');
            $this->resetInputFields();
        }
    }

    public function cancelDelete()
    {
        $this->jobOrderToDeleteId = null;
        $this->dispatch('hideDeleteConfirmationModal');
    }
}
