<div class="container">
    @if ($isCalculating)
        <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 9999; display: flex; justify-content: center; align-items: center;" x-data x-init="$wire.executeCalculation()">
            <div class="text-center">
                <i class="fa fa-spinner fa-spin fa-3x fa-fw" style="color: white;"></i>
                <p style="color: white; margin-top: 10px;">Menjalankan Perhitungan, mohon tunggu...</p>
            </div>
        </div>
    @endif

    <h1>Repair Time Calculator</h1>

    <div class="form-group">
        <label for="numberOfTires">Number of Tires:</label>
        <input type="number" id="numberOfTires" wire:model.live="numberOfTires" min="1" class="form-control">
    </div>

    <div class="form-group mt-3">
        <label>Damage Quantities:</label>
        @foreach ($damageFields as $field)
            <div class="form-group">
                <label for="{{ $field }}">{{ ucwords(str_replace('_', ' ', $field)) }}:</label>
                <input type="number" id="{{ $field }}" wire:model.live="damageTypes.{{ $field }}" min="0" class="form-control">
            </div>
        @endforeach
    </div>

    <button wire:click="calculate" class="btn btn-primary mt-4">Calculate Estimated Days</button>

    @if ($estimatedDays > 0)
        <div class="alert alert-info mt-4">
            Estimated days to complete: <strong>{{ $estimatedDays }} working days</strong>
        </div>
    @endif

    {{-- <div class="mt-5">
        <h2>Simulated Schedule</h2>
        <div id='calendar'></div>
    </div> --}}
</div>

@push('scripts')
<script>
    document.addEventListener('livewire:initialized', () => {
        var calendarEl = document.getElementById('calendar');
        if (calendarEl) {
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'timeGridWeek',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: @json($simulatedSchedule),
                eventDidMount: function(info) {
                    // Optional: Add tooltips or custom styling
                },
                eventClick: function(info) {
                    // Optional: Display task details on click
                    alert('Task: ' + info.event.title + '\nStart: ' + info.event.start.toLocaleString() + '\nEnd: ' + info.event.end.toLocaleString());
                }
            });
            calendar.render();

            Livewire.on('scheduleUpdated', (events) => {
                calendar.removeAllEvents();
                calendar.addEventSource(events);
                calendar.render();
            });
        }
    });
</script>
@endpush
