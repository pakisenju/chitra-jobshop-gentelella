<div>
    <div class="page-title">
        <div class="title_left">
            <h3>Manage Customers</h3>
        </div>
    </div>

    <div class="clearfix"></div>

    <div class="row">
        <div class="col-md-12 col-sm-12 ">
            <div class="x_panel">
                <div class="x_title">
                    <h2>Customer List</h2>
                    <ul class="nav navbar-right panel_toolbox">
                        <li>
                            <button wire:click="create" class="btn btn-success btn-sm">Tambah Customer</button>
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
                                    <th class="column-title">ID</th>
                                    <th class="column-title">Nama Customer</th>
                                    <th class="column-title no-link last"><span class="nobr">Aksi</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($customers as $index => $customer)
                                    <tr class="{{ $loop->even ? 'even' : 'odd' }} pointer">
                                        <td class=" ">{{ $customers->firstItem() + $index }}</td>
                                        <td class=" ">{{ $customer->name }}</td>
                                        <td class=" last">
                                            <button wire:click="edit({{ $customer->id }})" class="btn btn-info btn-xs"><i class="fa fa-pencil"></i> Edit </button>
                                            <button wire:click="prepareDelete({{ $customer->id }})" class="btn btn-danger btn-xs"><i class="fa fa-trash-o"></i> Delete </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{ $customers->links('vendor.pagination.bootstrap-4') }}
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Form -->
    <div class="modal fade" id="customerModal" tabindex="-1" role="dialog" aria-hidden="true" wire:ignore.self>
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <form wire:submit.prevent="store">
                        <div class="modal-header">
                            <h4 class="modal-title">{{ $customerId ? 'Edit' : 'Tambah' }} Customer</h4>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="name">Nama Customer <span class="required">*</span></label>
                                <input wire:model="name" type="text" id="name" class="form-control">
                                @error('name') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">Simpan</button>
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
        const setupCustomerEventListeners = () => {
            Livewire.on('showCustomerModal', () => {
                $('#customerModal').modal('show');
            });

            Livewire.on('hideCustomerModal', () => {
                $('#customerModal').modal('hide');
            });

            Livewire.on('showDeleteConfirmationModal', () => {
                $('#deleteConfirmationModal').modal('show');
            });

            Livewire.on('hideDeleteConfirmationModal', () => {
                $('#deleteConfirmationModal').modal('hide');
            });
        };

        const cleanupCustomerEventListeners = () => {
            Livewire.off('showCustomerModal');
            Livewire.off('hideCustomerModal');
            Livewire.off('showDeleteConfirmationModal');
            Livewire.off('hideDeleteConfirmationModal');
        };

        document.addEventListener('livewire:navigated', setupCustomerEventListeners);
        document.addEventListener('livewire:navigating', cleanupCustomerEventListeners);

        // Initial setup
        setupCustomerEventListeners();
    </script>
@endpush
