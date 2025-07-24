<div>
    <div class="page-title">
        <div class="title_left">
            <h3>Dashboard Penjadwalan</h3>
        </div>
    </div>

    <div class="clearfix"></div>

    <div class="row">
        <div class="col-md-12 col-sm-12 ">
            <div class="x_panel">
                {{-- <div class="x_title">
                    <h2>Dashboard Penjadwalan</h2>
                    <ul class="nav navbar-right panel_toolbox">
                        <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a></li>
                        <li><a class="close-link"><i class="fa fa-close"></i></a></li>
                    </ul>
                    <div class="clearfix"></div>
                </div> --}}
                <div class="x_content">
                    @if (session()->has('message'))
                        <div class="alert alert-success alert-dismissible " role="alert">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span
                                    aria-hidden="true">×</span></button>
                            {{ session('message') }}
                        </div>
                    @endif
                    <div class="row" style="margin-bottom: 20px;">
                        <div class="col-md-4">
                            <select wire:model.live="selectedJobOrderId" class="form-control">
                                <option value="">Semua Job Order</option>
                                @foreach ($jobOrders as $jobOrder)
                                    <option value="{{ $jobOrder->id }}">{{ $jobOrder->sn_tire }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <select wire:model.live="selectedToolId" class="form-control">
                                <option value="">Semua Tool</option>
                                @foreach ($tools as $tool)
                                    <option value="{{ $tool->id }}">{{ $tool->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <button wire:click="runScheduler" class="btn btn-success">
                                <i class="fa fa-cogs"></i> Run Scheduler
                            </button>
                        </div>
                    </div>
                    <div wire:ignore id='calendar' style="min-height: 500px; max-height: 575px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Task Detail Modal -->
    <div class="modal fade" id="taskDetailModal" tabindex="-1" role="dialog" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Detail Task</h4>
                </div>
                <div class="modal-body">
                    @if ($selectedTaskDetail)
                        <p><strong>Customer:</strong> {{ $selectedTaskDetail->tireJobOrder->customer->name ?? 'N/A' }}
                        </p>
                        <p><strong>Job Order SN:</strong> {{ $selectedTaskDetail->tireJobOrder->sn_tire }}</p>
                        <p><strong>Task Name:</strong> {{ $selectedTaskDetail->task->name }}</p>
                        <p><strong>Duration:</strong> {{ $selectedTaskDetail->total_duration_calculated }} menit</p>
                        <p><strong>Tools Required:</strong>
                            @forelse ($selectedTaskDetail->task->tools as $tool)
                                <span class="label label-default">{{ $tool->name }}</span>
                            @empty
                                None
                            @endforelse
                        </p>
                        <p><strong>Start Time:</strong>
                            {{ $selectedTaskDetail->start_time ? $selectedTaskDetail->start_time->format('Y-m-d H:i') : 'N/A' }}
                        </p>
                        <p><strong>End Time:</strong>
                            {{ $selectedTaskDetail->end_time ? $selectedTaskDetail->end_time->format('Y-m-d H:i') : 'N/A' }}
                        </p>
                        <p><strong>Status:</strong> {{ $selectedTaskDetail->status }}</p>
                    @else
                        <p>Detail task tidak ditemukan.</p>
                    @endif
                </div>
                <div class="modal-footer">
                    @if ($selectedTaskDetail && $selectedTaskDetail->status !== 'done')
                        <button
                            wire:click="markTaskAsDone({{ $selectedTaskDetail->tire_job_order_id }}, {{ $selectedTaskDetail->task_id }})"
                            class="btn btn-success">
                            Mark as Done
                        </button>
                    @endif
                    <button type="button" class="btn btn-primary" data-dismiss="modal">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Shift Selection Modal -->
    <div class="modal fade" id="shiftSelectionModal" tabindex="-1" role="dialog" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Pilih Shift</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="shift_select">Shift:</label>
                        <select wire:model="selectedShift" id="shift_select" class="form-control">
                            <option value="">Pilih Shift</option>
                            <option value="pagi">Pagi (08:00 - 17:00)</option>
                            <option value="malam">Malam (20:00 - 05:00)</option>
                        </select>
                        @error('selectedShift')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button wire:click="startScheduling" class="btn btn-primary">Mulai Penjadwalan</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Schedule Detail Modal -->
    <div class="modal fade" id="scheduleDetailModal" tabindex="-1" role="dialog" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Jadwal untuk {{ $selectedDate }}</h4>
                </div>
                <div class="modal-body" style="max-height: 60vh; overflow-y: auto;">
                    <div class="accordion" id="scheduleAccordion">
                        @foreach ($scheduleData as $shift => $tasks)
                            <div class="panel">
                                <a class="panel-heading" role="tab" id="heading{{ $shift }}"
                                    data-toggle="collapse" data-parent="#scheduleAccordion"
                                    href="#collapse{{ $shift }}" aria-expanded="true"
                                    aria-controls="collapse{{ $shift }}">
                                    <h4 class="panel-title">{{ ucfirst($shift) }} Shift ({{ count($tasks) }} tasks)
                                    </h4>
                                </a>
                                <div id="collapse{{ $shift }}" class="panel-collapse collapse in"
                                    role="tabpanel" aria-labelledby="heading{{ $shift }}">
                                    <div class="panel-body">
                                        <div class="row" style="margin-bottom: 10px;">
                                            <div class="col-md-12 text-right">
                                                <button wire:click.stop="exportSchedule('{{ $shift }}')"
                                                    class="btn btn-success btn-xs">
                                                    <i class="fa fa-file-excel-o"></i> Export Excel
                                                </button>
                                                <button wire:click.stop="exportPdf('{{ $shift }}')"
                                                    class="btn btn-danger btn-xs">
                                                    <i class="fa fa-file-pdf-o"></i> Export PDF
                                                </button>
                                            </div>
                                        </div>
                                        @if (count($tasks) > 0)
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th><input type="checkbox" wire:model.live="selectAll.{{ $shift }}"></th>
                                                        <th>Job Order SN</th>
                                                        <th>Task</th>
                                                        <th>Tools</th>
                                                        <th>Start</th>
                                                        <th>End</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($tasks as $task)
                                                        <tr>
                                                            <td>
                                                                @if ($task->status === 'scheduled')
                                                                    <input type="checkbox" wire:model.live="tasksToMarkAsDone" value="{{ $task->id }}">
                                                                @endif
                                                            </td>
                                                            <td>{{ $task->tireJobOrder->sn_tire }}</td>
                                                            <td>{{ $task->task->name }}</td>
                                                            <td>{{ $task->task->tools->pluck('name')->implode(', ') }}
                                                            </td>
                                                            <td>{{ $task->start_time ? $task->start_time->format('H:i') : 'N/A' }}
                                                            </td>
                                                            <td>{{ $task->end_time ? $task->end_time->format('H:i') : 'N/A' }}
                                                            </td>
                                                            <td>{{ $task->status }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        @else
                                            <p>No tasks scheduled for this shift.</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-success" wire:click="markSelectedTasksAsDone"
                        :disabled="tasksToMarkAsDone.length === 0">Mark Selected as Done</button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <style>
        /* Styling untuk time indicator */
        .fc-timegrid-now-indicator-line {
            border-color: #5db6e4 !important;
            border-width: 2px !important;
        }

        .fc-timegrid-now-indicator-arrow {
            border-color: #5db6e4 !important;
            border-width: 5px !important;
        }
    </style>
    <script>
        document.addEventListener('livewire:initialized', function() {
            console.log('Livewire Initialized in dashboard.blade.php');

            // Initialize modals first
            $('#taskDetailModal').modal({
                show: false
            });
            $('#shiftSelectionModal').modal({
                show: false
            });
            $('#scheduleDetailModal').modal({
                show: false
            });

            Livewire.on('showTaskDetail', (event) => {
                console.log('Livewire event: showTaskDetail', event);
                $('#taskDetailModal').modal('show');
            });

            Livewire.on('hideTaskDetailModal', () => {
                console.log('Livewire event: hideTaskDetailModal');
                $('#taskDetailModal').modal('hide');
            });

            Livewire.on('showShiftSelection', () => {
                console.log('Livewire event: showShiftSelection');
                $('#shiftSelectionModal').modal('show');
            });

            Livewire.on('hideShiftSelectionModal', () => {
                console.log('Livewire event: hideShiftSelectionModal');
                $('#shiftSelectionModal').modal('hide');
            });

            Livewire.on('showSchedule', () => {
                console.log('Livewire event: showSchedule');
                $('#scheduleDetailModal').modal('show');
            });

            Livewire.on('hideScheduleModal', () => {
                console.log('Livewire event: hideScheduleModal');
                $('#scheduleDetailModal').modal('hide');
            });

            // FullCalendar initialization
            window.dashboardCalendar = window.dashboardCalendar || null;

            const initializeDashboardCalendar = () => {
                const calendarEl = document.getElementById('calendar');

                if (!calendarEl || window.dashboardCalendar) {
                    return;
                }

                console.log('Initializing FullCalendar for dashboard...');

                window.dashboardCalendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,timeGridDay'
                    },
                    slotMinTime: '00:00:00',
                    slotMaxTime: '23:59:00',
                    slotDuration: '00:05:00',
                    slotLabelInterval: {
                        minutes: 5
                    },
                    slotLabelFormat: {
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: false
                    },
                    dayMaxEventRows: false, // Show all events in a day cell
                    dayMaxEvents: false, // Show all events in a day cell
                    eventContent: function(arg) {
                        let event = arg.event;
                        // Basic event display: title and time (if not all-day)
                        let content = `<div class="fc-event-main">${event.title}</div>`;
                        return {
                            html: content
                        };
                    },
                    eventDidMount: function(arg) {
                        // Apply custom styling to events
                        arg.el.style.whiteSpace = 'normal'; // Allow text to wrap
                    },
                    nowIndicator: true, // Menambahkan time indicator
                    scrollTime: new Date().toTimeString().slice(0, 8), // Scroll ke waktu sekarang
                    events: (fetchInfo, successCallback, failureCallback) => {
                        const componentEl = calendarEl.closest('[wire\\:id]');
                        if (componentEl) {
                            const component = Livewire.find(componentEl.getAttribute('wire:id'));
                            if (component) {
                                component.getEvents(fetchInfo.startStr, fetchInfo.endStr).then(
                                    events => successCallback(events));
                                return;
                            }
                        }
                        failureCallback(new Error('Livewire component not found'));
                    },
                    eventClick: function(info) {
                        Livewire.dispatch('showTaskDetail', {
                            jobOrderId: info.event.extendedProps.jobOrderId,
                            taskId: info.event.extendedProps.taskId
                        });
                    },
                    dateClick: function(info) {
                        Livewire.dispatch('dateClicked', [info.dateStr]);
                    },
                    viewDidMount: function(viewInfo) {
                        if (viewInfo.view.type === 'dayGridMonth') {
                            // Adjust cell rendering in month view (example: add padding)
                            let dayCells = viewInfo.el.querySelectorAll('.fc-daygrid-day');
                            dayCells.forEach(cell => cell.style.padding = '5px');
                        }
                    },

                });

                window.dashboardCalendar.render();
                console.log('FullCalendar rendered.');
            };

            const destroyDashboardCalendar = () => {
                if (window.dashboardCalendar) {
                    console.log('Destroying FullCalendar instance.');
                    window.dashboardCalendar.destroy();
                    window.dashboardCalendar = null;
                }
            };

            // Add event listeners for Livewire navigation events.
            document.addEventListener('livewire:navigated', initializeDashboardCalendar);
            document.addEventListener('livewire:navigating', destroyDashboardCalendar);

            // Ensure the refresh listener is only set up once.
            if (!window.isCalendarRefreshListenerSet) {
                Livewire.on('refreshCalendar', () => {
                    console.log('refreshCalendar event fired.');
                    if (window.dashboardCalendar) {
                        window.dashboardCalendar.refetchEvents();
                    }
                });
                window.isCalendarRefreshListenerSet = true;
            }

            window.addEventListener('load', () => {
                if (window.dashboardCalendar) {
                    destroyDashboardCalendar();
                    initializeDashboardCalendar();
                }
            });
        });
    </script>
@endpush
