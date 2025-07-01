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
    public $isOpen = false;

    protected $rules = [
        'name' => 'required|string|max:255|unique:customers,name',
    ];

    public function render()
    {
        $customers = Customer::paginate(10);
        return view('livewire.manage-customers', ['customers' => $customers]);
    }

    public function create()
    {
        $this->resetInputFields();
        $this->openModal();
    }

    public function openModal()
    {
        $this->isOpen = true;
    }

    public function closeModal()
    {
        $this->isOpen = false;
    }

    public function resetInputFields()
    {
        $this->name = '';
        $this->customerId = '';
    }

    public function store()
    {
        $this->validate();

        Customer::updateOrCreate(['id' => $this->customerId], [
            'name' => $this->name,
        ]);

        session()->flash(
            'message',
            $this->customerId ? 'Customer berhasil diupdate.' : 'Customer berhasil ditambahkan.'
        );

        $this->closeModal();
        $this->resetInputFields();
    }

    public function edit($id)
    {
        $customer = Customer::findOrFail($id);

        $this->customerId = $id;
        $this->name = $customer->name;

        $this->openModal();
    }

    public function delete($id)
    {
        Customer::find($id)->delete();
        session()->flash('message', 'Customer berhasil dihapus.');
    }
}
