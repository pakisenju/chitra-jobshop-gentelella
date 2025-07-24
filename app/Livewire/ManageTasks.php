<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Task;
use App\Models\Tool;
use Livewire\WithPagination;
use Illuminate\Validation\Rule;

class ManageTasks extends Component
{
    use WithPagination;

    public $name;
    public $duration;
    public $selectedTools = [];
    public $taskId;
    public $isOpen = false;
    public $taskToDeleteId = null;

    public function render()
    {
        $tasks = Task::with('tools')->paginate(10);
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
        $this->dispatch('showTaskModal');
    }

    public function closeModal()
    {
        $this->isOpen = false;
        $this->dispatch('hideTaskModal');
    }

    public function resetInputFields()
    {
        $this->name = '';
        $this->duration = '';
        $this->selectedTools = [];
        $this->taskId = '';
        $this->taskToDeleteId = null;
    }

    public function store()
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('tasks')->ignore($this->taskId)],
            'duration' => 'required|integer|min:1',
            'selectedTools' => 'nullable|array',
            'selectedTools.*' => 'exists:tools,id',
        ]);

        $task = Task::updateOrCreate(['id' => $this->taskId], [
            'name' => $this->name,
            'duration' => $this->duration,
        ]);

        $task->tools()->sync($this->selectedTools);

        session()->flash(
            'message',
            $this->taskId ? 'Task berhasil diupdate.' : 'Task berhasil dibuat.'
        );

        $this->closeModal();
        $this->resetInputFields();
    }

    public function edit($id)
    {
        $task = Task::with('tools')->findOrFail($id);

        $this->taskId = $id;
        $this->name = $task->name;
        $this->duration = $task->duration;
        $this->selectedTools = $task->tools->pluck('id')->toArray();

        $this->openModal();
    }

    public function prepareDelete($id)
    {
        $this->taskToDeleteId = $id;
        $this->dispatch('showDeleteConfirmationModal');
    }

    public function confirmDelete()
    {
        if ($this->taskToDeleteId) {
            Task::find($this->taskToDeleteId)->delete();
            session()->flash('message', 'Task berhasil dihapus.');
            $this->dispatch('hideDeleteConfirmationModal');
            $this->resetInputFields();
        }
    }

    public function cancelDelete()
    {
        $this->taskToDeleteId = null;
        $this->dispatch('hideDeleteConfirmationModal');
    }
}
