<?php
    $isOpen = $isOpen ?? false;
?>
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Manage Tire Job Orders</h1>
        <button wire:click="create" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded cursor-pointer transition-colors">
            Tambah Job Order
        </button>
    </div>

    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline">{{ session('message') }}</span>
        </div>
    @endif

    <!-- Modal Form -->
    @if ($isOpen)
        <div class="fixed inset-0 bg-[#FDFDFC] dark:bg-[#0a0a0a] flex items-center justify-center" wire:ignore.self>
            <div class="rounded-lg p-6 w-full max-w-4xl">
                <h2 class="text-xl font-semibold mb-4">{{ $jobOrderId ? 'Edit' : 'Tambah' }} Tire Job Order</h2>

                <form wire:submit.prevent="store">
                    <div class="mb-4">
                        <label class="block text-sm font-bold mb-2" for="sn_tire">
                            SN Tire <span class="text-red-500">*</span>
                        </label>
                        <input wire:model="sn_tire" type="text"
                            class="shadow appearance-none border rounded w-full py-2 px-3 leading-tight focus:outline-none focus:shadow-outline"
                            id="sn_tire">
                        @error('sn_tire')
                            <span class="text-red-500 text-xs">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Inspection Inputs -->
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div>
                            <label class="block text-sm font-bold mb-2" for="tread">
                                Tread
                            </label>
                            <input wire:model.live="tread" type="number" min="0"
                                class="shadow appearance-none border rounded w-full py-2 px-3 leading-tight focus:outline-none focus:shadow-outline"
                                id="tread">
                        </div>

                        <div>
                            <label class="block text-sm font-bold mb-2" for="sidewall">
                                Sidewall
                            </label>
                            <input wire:model.live="sidewall" type="number" min="0"
                                class="shadow appearance-none border rounded w-full py-2 px-3 leading-tight focus:outline-none focus:shadow-outline"
                                id="sidewall">
                        </div>

                        <!-- Add other inspection inputs similarly -->
                        <!-- ... -->
                    </div>

                    <!-- Task Summary Table -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold mb-2">Ringkasan Task</h3>
                        <div class="shadow-md rounded">
                            <table class="min-w-full border-collapse">
                                <thead>
                                    <tr>
                                        <th class="py-3 px-6 font-semibold text-sm text-left">Nama Task</th>
                                        <th class="py-3 px-6 font-semibold text-sm text-left">Durasi Master
                                        </th>
                                        <th class="py-3 px-6 font-semibold text-sm text-left">Qty Calculated
                                        </th>
                                        <th class="py-3 px-6 font-semibold text-sm text-left">Total Duration
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($calculatedTaskDetails as $taskDetail)
                                        <tr class="border-b">
                                            <td class="py-4 px-6">{{ $taskDetail['task_name'] }}</td>
                                            <td class="py-4 px-6">{{ $taskDetail['duration_master'] }}</td>
                                            <td class="py-4 px-6">{{ $taskDetail['qty_calculated'] }}</td>
                                            <td class="py-4 px-6">{{ $taskDetail['total_duration_calculated'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-2">
                        <button type="button" wire:click="closeModal"
                            class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded cursor-pointer transition-colors">
                            Batal
                        </button>
                        <button type="submit"
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded cursor-pointer transition-colors">
                            Simpan Job Order
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Job Orders Table -->
    <div class="shadow-md rounded my-6">
        <table class="min-w-full border-collapse">
            <thead>
                <tr>
                    <th class="py-3 px-6 font-semibold text-sm text-left">SN Tire</th>
                    <th class="py-3 px-6 font-semibold text-sm text-left">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($jobOrders as $jobOrder)
                    <tr class="border-b">
                        <td class="py-4 px-6">{{ $jobOrder->sn_tire }}</td>
                        <td class="py-4 px-6">
                            <button wire:click="edit({{ $jobOrder->id }})"
                                class="text-yellow-500 hover:text-yellow-700 mr-2 cursor-pointer transition-colors"><i class="fa fa-edit"></i></button>
                            <button wire:click="delete({{ $jobOrder->id }})"
                                onclick="return confirm('Apakah Anda yakin ingin menghapus job order ini?')"
                                class="text-red-500 hover:text-red-700 cursor-pointer transition-colors"><i class="fa fa-trash"></i></button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{ $jobOrders->links() }}
</div
