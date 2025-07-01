<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Customer;
use Livewire\WithPagination;

class ManageCustomers extends Component
{
    use WithPagination;

    public $name;
    public $customerId;
    public $isEdit = false;

    protected $rules = [
        'name' => 'required|unique:customers,name'
    ];

    public function render()
    {
        $customers = Customer::paginate(10);
        return view('livewire.manage-customers', ['customers' => $customers]);
    }

    public function save()
    {
        $this->validate();

        if ($this->isEdit) {
            $customer = Customer::find($this->customerId);
            $customer->update(['name' => $this->name]);
            session()->flash('message', 'Customer berhasil diupdate.');
        } else {
            Customer::create(['name' => $this->name]);
            session()->flash('message', 'Customer berhasil ditambahkan.');
        }

        $this->resetInput();
    }

    public function edit($id)
    {
        $customer = Customer::find($id);
        $this->customerId = $id;
        $this->name = $customer->name;
        $this->isEdit = true;
    }

    public function delete($id)
    {
        Customer::find($id)->delete();
        session()->flash('message', 'Customer berhasil dihapus.');
    }

    private function resetInput()
    {
        $this->name = '';
        $this->customerId = '';
        $this->isEdit = false;
    }
}
