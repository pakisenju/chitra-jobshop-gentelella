<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Dashboard Penjadwalan</h1>
        <div class="flex items-center space-x-4">
            <div>
                {{-- <label for="job_order_filter" class="block text-sm font-medium text-gray-700">Filter by Job Order:</label> --}}
                <select id="job_order_filter" wire:model.live="selectedJobOrderId" class="shadow bg-[#FDFDFC] dark:bg-[#0a0a0a] appearance-none border rounded w-full py-2 px-3 leading-tight focus:outline-none focus:shadow-outline">
                    <option value="">All Job Orders</option>
                    @foreach ($jobOrders as $jobOrder)
                        <option value="{{ $jobOrder->id }}">{{ $jobOrder->sn_tire }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                {{-- <label for="tool_filter" class="block text-sm font-medium text-gray-700">Filter by Tool:</label> --}}
                <select id="tool_filter" wire:model.live="selectedToolId" class="shadow bg-[#FDFDFC] dark:bg-[#0a0a0a] appearance-none border rounded w-full py-2 px-3 leading-tight focus:outline-none focus:shadow-outline">
                    <option value="">All Tools</option>
                    @foreach ($tools as $tool)
                        <option value="{{ $tool->id }}">{{ $tool->name }}</option>
                    @endforeach
                </select>
            </div>
            <button wire:click="runScheduler" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded cursor-pointer transition-colors">
                Run Scheduler
            </button>
        </div>
    </div>

    <div class="shadow-md rounded p-4">
        <div wire:ignore id='calendar' style="max-height: 575px;"></div>
    </div>

    <!-- Task Detail Modal -->
    @if ($showTaskDetailModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full flex items-center justify-center z-50">
            <div class="relative p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-zinc-800">
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">Detail Task</h3>
                @if ($selectedTaskDetail)
                    <div class="mt-2 text-sm text-gray-500 dark:text-gray-300">
                        <p><strong>Job Order SN:</strong> {{ $selectedTaskDetail->tireJobOrder->sn_tire }}</p>
                        <p><strong>Task Name:</strong> {{ $selectedTaskDetail->task->name }}</p>
                        <p><strong>Duration:</strong> {{ $selectedTaskDetail->total_duration_calculated }} menit</p>
                        <p><strong>Tools Required:</strong>
                            @forelse ($selectedTaskDetail->task->tools as $tool)
                                <span class="inline-block bg-gray-200 rounded-full px-2 py-0.5 text-xs font-semibold text-gray-700 mr-1">{{ $tool->name }}</span>
                            @empty
                                None
                            @endforelse
                        </p>
                        <p><strong>Start Time:</strong> {{ $selectedTaskDetail->start_time ? $selectedTaskDetail->start_time->format('Y-m-d H:i') : 'N/A' }}</p>
                        <p><strong>End Time:</strong> {{ $selectedTaskDetail->end_time ? $selectedTaskDetail->end_time->format('Y-m-d H:i') : 'N/A' }}</p>
                        <p><strong>Status:</strong> {{ $selectedTaskDetail->status }}</p>
                    </div>
                @else
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-300">Detail task tidak ditemukan.</p>
                @endif
                <div class="mt-4 flex justify-end space-x-2">
                    @if ($selectedTaskDetail && $selectedTaskDetail->status !== 'done')
                        <button wire:click="markTaskAsDone({{ $selectedTaskDetail->tire_job_order_id }}, {{ $selectedTaskDetail->task_id }})" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                            Mark as Done
                        </button>
                    @endif
                    <button wire:click="closeTaskDetailModal" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Shift Selection Modal -->
    @if ($showShiftSelectionModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full flex items-center justify-center z-50">
            <div class="relative p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-zinc-800">
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">Pilih Shift</h3>
                <div class="mt-2 text-sm text-gray-500 dark:text-gray-300">
                    <label for="shift_select" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Shift:</label>
                    <select wire:model="selectedShift" id="shift_select" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md dark:bg-zinc-700 dark:border-zinc-600 dark:text-white">
                        <option value="">Pilih Shift</option>
                        <option value="pagi">Pagi (08:00 - 17:00)</option>
                        <option value="malam">Malam (20:00 - 05:00)</option>
                    </select>
                    @error('selectedShift') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div class="mt-4 flex justify-end space-x-2">
                    <button wire:click="closeShiftSelectionModal" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                        Batal
                    </button>
                    <button wire:click="startScheduling" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        Mulai Penjadwalan
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Schedule Detail Modal -->
    @if ($showScheduleModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-75 overflow-y-auto h-full w-full flex items-center justify-center z-50" x-data="{ openAccordion: '' }">
            <div class="relative p-5 border w-full max-w-4xl shadow-lg rounded-md bg-white dark:bg-zinc-800 flex flex-col" style="max-height: 90vh;">
                <div class="flex-shrink-0">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">Jadwal untuk {{ $selectedDate }}</h3>
                        <button wire:click="closeScheduleModal" class="text-gray-400 hover:text-gray-500">
                            <span class="sr-only">Close</span>
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Accordion for Shifts -->
                <div class="flex-grow overflow-y-auto pr-2">
                    <div class="space-y-2">
                        @foreach ($scheduleData as $shift => $tasks)
                            <div class="border rounded-md">
                                <div @click="openAccordion = openAccordion === '{{ $shift }}' ? '' : '{{ $shift }}'" class="cursor-pointer p-4 flex justify-between items-center">
                                    <h4 class="font-semibold text-gray-800 dark:text-gray-200">{{ ucfirst($shift) }} Shift ({{ count($tasks) }} tasks)</h4>
                                    <div class="flex items-center space-x-2">
                                        <button wire:click.stop="exportSchedule('{{ $shift }}')" class="bg-green-500 hover:bg-green-700 text-white font-bold py-1 px-3 rounded text-xs">
                                            Export Excel
                                        </button>
                                        <button wire:click.stop="exportPdf('{{ $shift }}')" class="bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-3 rounded text-xs">
                                            Export PDF
                                        </button>
                                        <span :class="{ 'transform rotate-180': openAccordion === '{{ $shift }}' }">▼</span>
                                    </div>
                                </div>
                                <div x-show="openAccordion === '{{ $shift }}'" class="p-4 border-t">
                                    @if (count($tasks) > 0)
                                        <div class="overflow-x-auto">
                                            <table class="min-w-full divide-y divide-gray-200">
                                                <thead class="bg-gray-50 dark:bg-zinc-700">
                                                    <tr>
                                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Job Order SN</th>
                                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Task</th>
                                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tools</th>
                                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Start</th>
                                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">End</th>
                                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="bg-white divide-y divide-gray-200 dark:bg-zinc-800">
                                                    @foreach ($tasks as $task)
                                                        <tr>
                                                            <td class="px-6 py-4 whitespace-nowrap">{{ $task->tireJobOrder->sn_tire }}</td>
                                                            <td class="px-6 py-4 whitespace-nowrap">{{ $task->task->name }}</td>
                                                            <td class="px-6 py-4 whitespace-nowrap">{{ $task->task->tools->pluck('name')->implode(', ') }}</td>
                                                            <td class="px-6 py-4 whitespace-nowrap">{{ $task->start_time ? $task->start_time->format('H:i') : 'N/A' }}</td>
                                                            <td class="px-6 py-4 whitespace-nowrap">{{ $task->end_time ? $task->end_time->format('H:i') : 'N/A' }}</td>
                                                            <td class="px-6 py-4 whitespace-nowrap">{{ $task->status }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <p>No tasks scheduled for this shift.</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
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
        (function() {
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
                        return { html: content };
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
                                component.getEvents().then(events => successCallback(events));
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
        })();
    </script>
@endpush
