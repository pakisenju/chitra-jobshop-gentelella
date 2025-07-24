<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Customer;
use Livewire\WithPagination;
use Illuminate\Validation\Rule;

class ManageCustomers extends Component
{
    use WithPagination;

    public $name;
    public $customerId;
    public $isOpen = false;
    public $customerToDeleteId = null;

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
        $this->dispatch('showCustomerModal');
    }

    public function closeModal()
    {
        $this->isOpen = false;
        $this->dispatch('hideCustomerModal');
    }

    public function resetInputFields()
    {
        $this->name = '';
        $this->customerId = '';
        $this->customerToDeleteId = null;
    }

    public function store()
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('customers')->ignore($this->customerId)],
        ]);

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

    public function prepareDelete($id)
    {
        $this->customerToDeleteId = $id;
        $this->dispatch('showDeleteConfirmationModal');
    }

    public function confirmDelete()
    {
        if ($this->customerToDeleteId) {
            Customer::find($this->customerToDeleteId)->delete();
            session()->flash('message', 'Customer berhasil dihapus.');
            $this->dispatch('hideDeleteConfirmationModal');
            $this->resetInputFields();
        }
    }

    public function cancelDelete()
    {
        $this->customerToDeleteId = null;
        $this->dispatch('hideDeleteConfirmationModal');
    }
}
