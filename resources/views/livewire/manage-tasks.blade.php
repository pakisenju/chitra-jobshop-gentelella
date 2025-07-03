<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Manage Tasks</h1>
        <button wire:click="create" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded cursor-pointer transition-colors">
            Tambah Task
        </button>
    </div>

    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline">{{ session('message') }}</span>
        </div>
    @endif

    <!-- Modal Form -->
    @if ($isOpen)
        <div class="fixed inset-0 bg-[#FDFDFC] dark:bg-[#0a0a0a] flex items-center justify-center">
            <div class=" rounded-lg p-6 w-full max-w-md">
                <h2 class="text-xl font-semibold mb-4">{{ $taskId ? 'Edit' : 'Tambah' }} Task</h2>

                <form wire:submit.prevent="store">
                    <div class="mb-4">
                        <label class="block text-sm font-bold mb-2" for="name">
                            Nama Task <span class="text-red-500">*</span>
                        </label>
                        <input wire:model="name" type="text"
                            class="shadow appearance-none border rounded w-full py-2 px-3 leading-tight focus:outline-none focus:shadow-outline"
                            id="name">
                        @error('name')
                            <span class="text-red-500 text-xs">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-bold mb-2" for="duration">
                            Durasi (menit) <span class="text-red-500">*</span>
                        </label>
                        <input wire:model="duration" type="number" min="0"
                            class="shadow appearance-none border rounded w-full py-2 px-3 leading-tight focus:outline-none focus:shadow-outline"
                            id="duration">
                        @error('duration')
                            <span class="text-red-500 text-xs">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-bold mb-2" for="selectedTools">
                            Tools
                        </label>
                        <select wire:model="selectedTools" multiple
                            class="shadow bg-[#FDFDFC] dark:bg-[#0a0a0a] appearance-none border rounded w-full py-2 px-3 leading-tight focus:outline-none focus:shadow-outline"
                            id="selectedTools">
                            @foreach ($tools as $tool)
                                <option value="{{ $tool->id }}">{{ $tool->name }}</option>
                            @endforeach
                        </select>
                        @error('selectedTools')
                            <span class="text-red-500 text-xs">{{ $message }}</span>
                        @enderror
                        @error('selectedTools.*')
                            <span class="text-red-500 text-xs">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="flex justify-end space-x-2">
                        <button type="button" wire:click="closeModal"
                            class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded cursor-pointer transition-colors">
                            Batal
                        </button>
                        <button type="submit"
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded cursor-pointer transition-colors">
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Tabel Tasks -->
    <div class=" shadow-md rounded my-6">
        <table class="min-w-full border-collapse">
            <thead>
                <tr>
                    <th class="py-3 px-6 font-semibold text-sm text-left">No.</th>
                    <th class="py-3 px-6 font-semibold text-sm text-left">Nama Task</th>
                    <th class="py-3 px-6 font-semibold text-sm text-left">Durasi</th>
                    <th class="py-3 px-6 font-semibold text-sm text-left">Tools</th>
                    <th class="py-3 px-6 font-semibold text-sm text-left">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($tasks as $task)
                    <tr class="border-b ">
                        <td class="py-4 px-6">{{ $loop->iteration }}</td>
                        <td class="py-4 px-6">{{ $task->name }}</td>
                        <td class="py-4 px-6">{{ $task->duration }} menit</td>
                        <td class="py-4 px-6">
                            @forelse ($task->tools as $tool)
                                <span class="inline-block bg-gray-200 rounded-full px-3 py-1 text-sm font-semibold text-gray-700 mr-2 mb-2">{{ $tool->name }}</span>
                            @empty
                                None
                            @endforelse
                        </td>
                        <td class="py-4 px-6">
                            <button wire:click="edit({{ $task->id }})"
                                class="text-yellow-500 hover:text-yellow-700 mr-2 cursor-pointer transition-colors"><i class="fa fa-edit"></i></button>
                            <button wire:click="delete({{ $task->id }})"
                                onclick="return confirm('Apakah Anda yakin ingin menghapus task ini?')"
                                class="text-red-500 hover:text-red-700 cursor-pointer transition-colors"><i class="fa fa-trash"></i></button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="px-6 py-3">
            {{ $tasks->links() }}
        </div>
    </div>
</div>
