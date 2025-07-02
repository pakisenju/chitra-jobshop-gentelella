<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Dashboard Penjadwalan</h1>
        <button wire:click="simulateScheduling" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded cursor-pointer transition-colors">
            Simulate Scheduling
        </button>
    </div>

    @if (!empty($simulatedTasks))
        <div class="shadow-md rounded p-4 mb-6">
            <h2 class="text-xl font-bold mb-4">Simulated Tasks</h2>
            <table class="min-w-full border-collapse">
                <thead>
                    <tr>
                        <th class="py-3 px-6 font-semibold text-sm text-left">Job Order SN</th>
                        <th class="py-3 px-6 font-semibold text-sm text-left">Task Name</th>
                        <th class="py-3 px-6 font-semibold text-sm text-left">Calculated Duration (minutes)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($simulatedTasks as $task)
                        <tr class="border-b">
                            <td class="py-4 px-6">{{ $task['job_order_sn'] }}</td>
                            <td class="py-4 px-6">{{ $task['task_name'] }}</td>
                            <td class="py-4 px-6">{{ $task['calculated_duration'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="shadow-md rounded p-4">
        <div wire:ignore id='calendar'></div>
    </div>
</div>

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
    <script>
        document.addEventListener('livewire:initialized', () => {
            const calendarEl = document.getElementById('calendar');
            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: @json($events)
            });
            calendar.render();

            Livewire.on('refreshCalendar', () => {
                calendar.refetchEvents();
            });
        });
    </script>
@endpush
