<div>
    <div class="page-title">
        <div class="title_left">
            <h3>Manage Tasks</h3>
        </div>
    </div>

    <div class="clearfix"></div>

    <div class="row">
        <div class="col-md-12 col-sm-12 ">
            <div class="x_panel">
                <div class="x_title">
                    <h2>Task List</h2>
                    <ul class="nav navbar-right panel_toolbox">
                        <li>
                            <button wire:click="create" class="btn btn-success btn-sm">Tambah Task</button>
                        </li>
                        {{-- <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a></li>
                        <li><a class="close-link"><i class="fa fa-close"></i></a></li> --}}
                    </ul>
                    <div class="clearfix"></div>
                </div>
                <div class="x_content">
                    @if (session()->has('message'))
                        <div class="alert alert-success alert-dismissible " role="alert">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span
                                    aria-hidden="true">×</span></button>
                            {{ session('message') }}
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-striped jambo_table bulk_action">
                            <thead>
                                <tr class="headings">
                                    <th class="column-title">No.</th>
                                    <th class="column-title">Nama Task</th>
                                    <th class="column-title">Durasi</th>
                                    <th class="column-title">Tools</th>
                                    <th class="column-title no-link last"><span class="nobr">Aksi</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($tasks as $index => $task)
                                    <tr class="{{ $loop->even ? 'even' : 'odd' }} pointer">
                                        <td class=" ">{{ $tasks->firstItem() + $index }}</td>
                                        <td class=" ">{{ $task->name }}</td>
                                        <td class=" ">{{ $task->duration }} menit</td>
                                        <td class=" ">
                                            @forelse ($task->tools as $tool)
                                                <span class="label label-default">{{ $tool->name }}</span>
                                            @empty
                                                None
                                            @endforelse
                                        </td>
                                        <td class=" last">
                                            <button wire:click="edit({{ $task->id }})"
                                                class="btn btn-info btn-xs"><i class="fa fa-pencil"></i> Edit </button>
                                            <button wire:click="prepareDelete({{ $task->id }})"
                                                class="btn btn-danger btn-xs"><i class="fa fa-trash-o"></i> Delete
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{ $tasks->links('vendor.pagination.bootstrap-4') }}
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Form -->
    <div class="modal fade" id="taskModal" tabindex="-1" role="dialog" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form wire:submit.prevent="store">
                    <div class="modal-header">
                        <h4 class="modal-title">{{ $taskId ? 'Edit' : 'Tambah' }} Task</h4>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="name">Nama Task <span class="required">*</span></label>
                            <input wire:model="name" type="text" id="name" class="form-control">
                            @error('name')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="duration">Durasi (menit) <span class="required">*</span></label>
                            <input wire:model="duration" type="number" min="0" id="duration"
                                class="form-control">
                            @error('duration')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="selectedTools">Tools</label>
                            <select wire:model="selectedTools" multiple id="selectedTools" class="form-control">
                                @foreach ($tools as $tool)
                                    <option value="{{ $tool->id }}">{{ $tool->name }}</option>
                                @endforeach
                            </select>
                            @error('selectedTools')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                            @error('selectedTools.*')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
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
    <div class="modal fade" id="deleteConfirmationModal" tabindex="-1" role="dialog" aria-hidden="true"
        wire:ignore.self>
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Konfirmasi Hapus</h4>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menghapus data ini?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"
                        wire:click="cancelDelete()">Batal</button>
                    <button type="button" class="btn btn-danger" wire:click="confirmDelete()">Hapus</button>
                </div>
            </div>
        </div>
    </div>
</div>


@push('scripts')
    <script>
        const setupTaskEventListeners = () => {
            Livewire.on('showTaskModal', () => {
                $('#taskModal').modal('show');
            });

            Livewire.on('hideTaskModal', () => {
                $('#taskModal').modal('hide');
            });

            Livewire.on('showDeleteConfirmationModal', () => {
                $('#deleteConfirmationModal').modal('show');
            });

            Livewire.on('hideDeleteConfirmationModal', () => {
                $('#deleteConfirmationModal').modal('hide');
            });
        };

        const cleanupTaskEventListeners = () => {
            Livewire.off('showTaskModal');
            Livewire.off('hideTaskModal');
            Livewire.off('showDeleteConfirmationModal');
            Livewire.off('hideDeleteConfirmationModal');
        };

        document.addEventListener('livewire:navigated', setupTaskEventListeners);
        document.addEventListener('livewire:navigating', cleanupTaskEventListeners);

        // Initial setup
        setupTaskEventListeners();
    </script>
@endpush
