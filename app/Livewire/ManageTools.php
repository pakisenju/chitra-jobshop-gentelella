<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Tool;

class ManageTools extends Component
{
    public $tools = [];
    public $name = '';
    public $quantity = 1;
    public $editMode = false;
    public $editId = null;

    protected $rules = [
        'name' => 'required|string|unique:tools,name',
        'quantity' => 'required|integer|min:1'
    ];

    public function mount()
    {
        $this->loadTools();
    }

    public function loadTools()
    {
        $this->tools = Tool::all();
    }

    public function saveTool()
    {
        $this->validate();

        if ($this->editMode) {
            $tool = Tool::find($this->editId);
            $tool->update([
                'name' => $this->name,
                'quantity' => $this->quantity
            ]);
            session()->flash('message', 'Tool berhasil diperbarui.');
        } else {
            Tool::create([
                'name' => $this->name,
                'quantity' => $this->quantity
            ]);
            session()->flash('message', 'Tool berhasil ditambahkan.');
        }

        $this->resetForm();
        $this->loadTools();
    }

    public function editTool($id)
    {
        $tool = Tool::find($id);
        $this->name = $tool->name;
        $this->quantity = $tool->quantity;
        $this->editMode = true;
        $this->editId = $id;
    }

    public function deleteTool($id)
    {
        Tool::find($id)->delete();
        session()->flash('message', 'Tool berhasil dihapus.');
        $this->loadTools();
    }

    public function resetForm()
    {
        $this->name = '';
        $this->quantity = 1;
        $this->editMode = false;
        $this->editId = null;
    }

    public function render()
    {
        return view('livewire.manage-tools');
    }
}
