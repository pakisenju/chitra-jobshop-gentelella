
<div>
    <div class="page-title">
        <div class="title_left">
            <h3>Manage Tire Job Orders</h3>
        </div>
    </div>

    <div class="clearfix"></div>

    <div class="row">
        <div class="col-md-12 col-sm-12 ">
            <div class="x_panel">
                <div class="x_title">
                    <h2>Tire Job Order List</h2>
                    <ul class="nav navbar-right panel_toolbox">
                        <li>
                            <button wire:click="create" class="btn btn-success btn-sm">Tambah Job Order</button>
                        </li>
                        {{-- <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a></li>
                        <li><a class="close-link"><i class="fa fa-close"></i></a></li> --}}
                    </ul>
                    <div class="clearfix"></div>
                </div>
                <div class="x_content">
                    @if (session()->has('message'))
                        <div class="alert alert-success alert-dismissible " role="alert">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>
                            {{ session('message') }}
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-striped jambo_table bulk_action">
                            <thead>
                                <tr class="headings">
                                    <th class="column-title">Customer</th>
                                    <th class="column-title">SN Tire</th>
                                    <th class="column-title">Tread</th>
                                    <th class="column-title">Sidewall</th>
                                    <th class="column-title">Spot</th>
                                    <th class="column-title">Patch</th>
                                    <th class="column-title">Area Curing SW</th>
                                    <th class="column-title">Area Curing Tread</th>
                                    <th class="column-title">Bead</th>
                                    <th class="column-title">Chaffer</th>
                                    <th class="column-title">Tasks</th>
                                    <th class="column-title no-link last"><span class="nobr">Aksi</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($jobOrders as $jobOrder)
                                    <tr class="{{ $loop->even ? 'even' : 'odd' }} pointer">
                                        <td class=" ">{{ $jobOrder->customer->name ?? 'N/A' }}</td>
                                        <td class=" ">{{ $jobOrder->sn_tire }}</td>
                                        <td class=" ">{{ $jobOrder->tread }}</td>
                                        <td class=" ">{{ $jobOrder->sidewall }}</td>
                                        <td class=" ">{{ $jobOrder->spot }}</td>
                                        <td class=" ">{{ $jobOrder->patch }}</td>
                                        <td class=" ">{{ $jobOrder->area_curing_sw }}</td>
                                        <td class=" ">{{ $jobOrder->area_curing_tread }}</td>
                                        <td class=" ">{{ $jobOrder->bead }}</td>
                                        <td class=" ">{{ $jobOrder->chaffer }}</td>
                                        <td class=" ">
                                            @php
                                                $scheduledCount = $jobOrder->tireJobOrderTaskDetails->where('status', 'scheduled')->count();
                                                $doneCount = $jobOrder->tireJobOrderTaskDetails->where('status', 'done')->count();
                                                $totalTasks = $jobOrder->tireJobOrderTaskDetails->where('start_time', '!=', null)->count();
                                            @endphp
                                            Scheduled: {{ $scheduledCount }} / {{ $totalTasks }}<br>
                                            Done: {{ $doneCount }} / {{ $totalTasks }}
                                        </td>
                                        <td class=" last">
                                            <button wire:click="edit({{ $jobOrder->id }})" class="btn btn-info btn-xs"><i class="fa fa-pencil"></i> Edit </button>
                                            <button wire:click="prepareDelete({{ $jobOrder->id }})" class="btn btn-danger btn-xs"><i class="fa fa-trash-o"></i> Delete </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{ $jobOrders->links('vendor.pagination.bootstrap-4') }}
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Form -->
    <div class="modal fade" id="tireJobOrderModal" tabindex="-1" role="dialog" aria-hidden="true" wire:ignore.self>
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <form wire:submit.prevent="store">
                        <div class="modal-header">
                            <h4 class="modal-title">{{ $jobOrderId ? 'Edit' : 'Tambah' }} Tire Job Order</h4>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="customer_id">Customer <span class="required">*</span></label>
                                <select wire:model="customer_id" id="customer_id" class="form-control">
                                    <option value="">Pilih Customer</option>
                                    @foreach ($customers as $customer)
                                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                    @endforeach
                                </select>
                                @error('customer_id') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="form-group">
                                <label for="sn_tire">SN Tire <span class="required">*</span></label>
                                <input wire:model="sn_tire" type="text" id="sn_tire" class="form-control">
                                @error('sn_tire') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="row">
                                <div class="col-md-6 col-sm-6 form-group">
                                    <label for="tread">Tread</label>
                                    <input wire:model.live="tread" type="number" min="0" id="tread" class="form-control">
                                </div>
                                <div class="col-md-6 col-sm-6 form-group">
                                    <label for="sidewall">Sidewall</label>
                                    <input wire:model.live="sidewall" type="number" min="0" id="sidewall" class="form-control">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 col-sm-6 form-group">
                                    <label for="spot">Spot</label>
                                    <input wire:model.live="spot" type="number" min="0" id="spot" class="form-control">
                                </div>
                                <div class="col-md-6 col-sm-6 form-group">
                                    <label for="patch">Patch</label>
                                    <input wire:model.live="patch" type="number" min="0" id="patch" class="form-control">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 col-sm-6 form-group">
                                    <label for="area_curing_sw">Area Curing SW</label>
                                    <input wire:model.live="area_curing_sw" type="number" min="0" id="area_curing_sw" class="form-control">
                                </div>
                                <div class="col-md-6 col-sm-6 form-group">
                                    <label for="area_curing_tread">Area Curing Tread</label>
                                    <input wire:model.live="area_curing_tread" type="number" min="0" id="area_curing_tread" class="form-control">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 col-sm-6 form-group">
                                    <label for="bead">Bead</label>
                                    <input wire:model.live="bead" type="number" min="0" id="bead" class="form-control">
                                </div>
                                <div class="col-md-6 col-sm-6 form-group">
                                    <label for="chaffer">Chaffer</label>
                                    <input wire:model.live="chaffer" type="number" min="0" id="chaffer" class="form-control">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">Simpan Job Order</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteConfirmationModal" tabindex="-1" role="dialog" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Konfirmasi Hapus</h4>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menghapus data ini?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal" wire:click="cancelDelete()">Batal</button>
                    <button type="button" class="btn btn-danger" wire:click="confirmDelete()">Hapus</button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        const setupTireJobOrderEventListeners = () => {
            Livewire.on('showTireJobOrderModal', () => {
                $('#tireJobOrderModal').modal('show');
            });

            Livewire.on('hideTireJobOrderModal', () => {
                $('#tireJobOrderModal').modal('hide');
            });

            Livewire.on('showDeleteConfirmationModal', () => {
                $('#deleteConfirmationModal').modal('show');
            });

            Livewire.on('hideDeleteConfirmationModal', () => {
                $('#deleteConfirmationModal').modal('hide');
            });
        };

        const cleanupTireJobOrderEventListeners = () => {
            Livewire.off('showTireJobOrderModal');
            Livewire.off('hideTireJobOrderModal');
            Livewire.off('showDeleteConfirmationModal');
            Livewire.off('hideDeleteConfirmationModal');
        };

        document.addEventListener('livewire:navigated', setupTireJobOrderEventListeners);
        document.addEventListener('livewire:navigating', cleanupTireJobOrderEventListeners);

        // Initial setup
        setupTireJobOrderEventListeners();
    </script>
@endpush
