<?php

namespace App\Http\Livewire;

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
        'name' => 'required|unique:tools,name',
        'quantity' => 'required|integer|min:1'
    ];

    public function mount()
    {
        $this->tools = Tool::all();
    }

    public function render()
    {
        return view('livewire.manage-tools');
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
        $this->tools = Tool::all();
    }

    public function editTool($id)
    {
        $tool = Tool::find($id);
        $this->editMode = true;
        $this->editId = $id;
        $this->name = $tool->name;
        $this->quantity = $tool->quantity;
    }

    public function deleteTool($id)
    {
        Tool::find($id)->delete();
        session()->flash('message', 'Tool berhasil dihapus.');
        $this->tools = Tool::all();
    }

    public function resetForm()
    {
        $this->editMode = false;
        $this->editId = null;
        $this->name = '';
        $this->quantity = 1;
    }
}
