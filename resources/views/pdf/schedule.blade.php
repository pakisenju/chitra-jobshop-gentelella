<!DOCTYPE html>
<html>
<head>
    <title>Schedule for {{ $date }} - {{ ucfirst($shift) }} Shift</title>
    <style>
        body {
            font-family: sans-serif;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        .table th, .table td {
            border: 1px solid #ddd;
            padding: 8px;
        }
        .table th {
            background-color: #f2f2f2;
            text-align: left;
        }
    </style>
</head>
<body>
    <h2>Schedule for {{ $date }} - {{ ucfirst($shift) }} Shift</h2>
    <table class="table">
        <thead>
            <tr>
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
                    <td>{{ $task->tireJobOrder->sn_tire }}</td>
                    <td>{{ $task->task->name }}</td>
                    <td>{{ $task->task->tools->pluck('name')->implode(', ') }}</td>
                    <td>{{ $task->start_time ? $task->start_time->format('H:i') : 'N/A' }}</td>
                    <td>{{ $task->end_time ? $task->end_time->format('H:i') : 'N/A' }}</td>
                    <td>{{ $task->status }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
