<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Task;
use App\Models\Tool;
use Livewire\WithPagination;

class ManageTasks extends Component
{
    use WithPagination;

    public $name;
    public $duration;
    public $tool_id;
    public $taskId;
    public $isOpen = false;

    protected $rules = [
        'name' => 'required|string|max:255|unique:tasks,name',
        'duration' => 'required|integer|min:1',
        'tool_id' => 'nullable|exists:tools,id'
    ];

    public function render()
    {
        $tasks = Task::with('tool')->paginate(10);
        $tools = Tool::all();

        return view('livewire.manage-tasks', [
            'tasks' => $tasks,
            'tools' => $tools
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
        $this->duration = '';
        $this->tool_id = null;
        $this->taskId = '';
    }

    public function store()
    {
        $this->validate();

        Task::updateOrCreate(['id' => $this->taskId], [
            'name' => $this->name,
            'duration' => $this->duration,
            'tool_id' => $this->tool_id
        ]);

        session()->flash(
            'message',
            $this->taskId ? 'Task berhasil diupdate.' : 'Task berhasil dibuat.'
        );

        $this->closeModal();
        $this->resetInputFields();
    }

    public function edit($id)
    {
        $task = Task::findOrFail($id);

        $this->taskId = $id;
        $this->name = $task->name;
        $this->duration = $task->duration;
        $this->tool_id = $task->tool_id;

        $this->openModal();
    }

    public function delete($id)
    {
        Task::find($id)->delete();
        session()->flash('message', 'Task berhasil dihapus.');
    }
}
