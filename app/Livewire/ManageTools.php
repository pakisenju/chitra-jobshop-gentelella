<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Tool;
use Livewire\WithPagination;
use Illuminate\Validation\Rule;

class ManageTools extends Component
{
    use WithPagination;

    public $name;
    public $quantity;
    public $toolId;
    public $isOpen = false;

    public function render()
    {
        $tools = Tool::paginate(10);

        return view('livewire.manage-tools', [
            'tools' => $tools,
        ]);
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
        $this->quantity = 1;
        $this->toolId = '';
    }

    public function store()
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('tools')->ignore($this->toolId)],
            'quantity' => 'required|integer|min:1'
        ]);

        Tool::updateOrCreate(['id' => $this->toolId], [
            'name' => $this->name,
            'quantity' => $this->quantity
        ]);

        session()->flash(
            'message',
            $this->toolId ? 'Tool berhasil diupdate.' : 'Tool berhasil dibuat.'
        );

        $this->closeModal();
        $this->resetInputFields();
    }

    public function edit($id)
    {
        $tool = Tool::findOrFail($id);

        $this->toolId = $id;
        $this->name = $tool->name;
        $this->quantity = $tool->quantity;

        $this->openModal();
    }

    public function delete($id)
    {
        Tool::find($id)->delete();
        session()->flash('message', 'Tool berhasil dihapus.');
    }
}
