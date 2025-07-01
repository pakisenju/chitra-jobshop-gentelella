<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Dashboard Penjadwalan</h1>
        <div class="flex space-x-4">
            <a href="/manage-tools" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded">Manage
                Tools</a>
            <a href="/manage-tasks" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded">Manage
                Tasks</a>
            <a href="/manage-customers"
                class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded">Manage Customers</a>
            <a href="/manage-tire-job-orders"
                class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded">Manage Job Orders</a>
        </div>
    </div>

    <div class="bg-white shadow-md rounded p-4">
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
