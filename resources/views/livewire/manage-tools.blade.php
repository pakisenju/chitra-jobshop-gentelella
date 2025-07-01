<div class="container mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-6">Manajemen Tools</h1>

    <!-- Form Input -->
    <div class="p-6 rounded-lg shadow-md mb-8">
        <h2 class="text-xl font-semibold mb-4">{{ $editMode ? 'Edit Tool' : 'Tambah Tool' }}</h2>

        <form wire:submit.prevent="saveTool">
            <div class="mb-4">
                <label for="name" class="block mb-2">Nama Tool</label>
                <input type="text" id="name" wire:model="name" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring focus:border-blue-300">
                @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <div class="mb-4">
                <label for="quantity" class="block mb-2">Jumlah</label>
                <input type="number" id="quantity" wire:model="quantity" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring focus:border-blue-300">
                @error('quantity') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <div class="flex space-x-4">
                <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-300">
                    {{ $editMode ? 'Update' : 'Simpan' }}
                </button>
                <button type="button" wire:click="resetForm" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-300">
                    Batal
                </button>
            </div>
        </form>
    </div>

    <!-- Tabel Data -->
    <div class="p-6 rounded-lg shadow-md">
        <h2 class="text-xl font-semibold mb-4">Daftar Tools</h2>

        @if(session()->has('message'))
            <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg">
                {{ session('message') }}
            </div>
        @endif

        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr>
                        <th class="py-2 px-4 border">ID</th>
                        <th class="py-2 px-4 border">Nama</th>
                        <th class="py-2 px-4 border">Jumlah</th>
                        <th class="py-2 px-4 border">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tools as $tool)
                        <tr>
                            <td class="py-2 px-4 border">{{ $tool->id }}</td>
                            <td class="py-2 px-4 border">{{ $tool->name }}</td>
                            <td class="py-2 px-4 border">{{ $tool->quantity }}</td>
                            <td class="py-2 px-4 border">
                                <div class="flex space-x-2">
                                    <button wire:click="editTool({{ $tool->id }})" class="px-3 py-1 bg-yellow-500 text-white rounded hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-yellow-300">
                                        Edit
                                    </button>
                                    <button wire:click="deleteTool({{ $tool->id }})" class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-300" onclick="return confirm('Apakah Anda yakin ingin menghapus tool ini?')">
                                        Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
