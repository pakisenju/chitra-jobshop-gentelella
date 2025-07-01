<div class="container mx-auto px-4 py-8">
    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('message') }}
        </div>
    @endif

    <div class="rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-xl font-semibold mb-4">{{ $isEdit ? 'Edit Customer' : 'Tambah Customer' }}</h2>

        <form wire:submit.prevent="save">
            <div class="mb-4">
                <label for="name" class="block mb-2">Nama Customer</label>
                <input type="text" id="name" wire:model="name"
                    class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring focus:border-blue-300">
                @error('name')
                    <span class="text-red-500 text-sm">{{ $message }}</span>
                @enderror
            </div>

            <div class="flex space-x-2">
                <button type="submit"
                    class="px-4 py-2 bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    Simpan
                </button>
                <button type="button" wire:click="resetInput"
                    class="px-4 py-2 bg-gray-600 rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500">
                    Batal
                </button>
            </div>
        </form>
    </div>

    <div class="rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold mb-4">Daftar Customers</h2>

        <div class="overflow-x-auto">
            <table class="min-w-full ">
                <thead>
                    <tr>
                        <th
                            class="py-2 px-4 border-b  text-left text-xs font-semibold uppercase tracking-wider">
                            ID</th>
                        <th
                            class="py-2 px-4 border-b  text-left text-xs font-semibold uppercase tracking-wider">
                            Nama Customer</th>
                        <th
                            class="py-2 px-4 border-b  text-left text-xs font-semibold uppercase tracking-wider">
                            Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($customers as $customer)
                        <tr>
                            <td class="py-2 px-4 border-b border-gray-200">{{ $customer->id }}</td>
                            <td class="py-2 px-4 border-b border-gray-200">{{ $customer->name }}</td>
                            <td class="py-2 px-4 border-b border-gray-200">
                                <button wire:click="edit({{ $customer->id }})"
                                    class="px-2 py-1 bg-yellow-500 text-white rounded-md hover:bg-yellow-600 mr-2">Edit</button>
                                <button
                                    onclick="confirm('Apakah Anda yakin ingin menghapus customer ini?') || event.stopImmediatePropagation()"
                                    wire:click="delete({{ $customer->id }})"
                                    class="px-2 py-1 bg-red-500 text-white rounded-md hover:bg-red-600">Hapus</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $customers->links() }}
        </div>
    </div>
</div>
