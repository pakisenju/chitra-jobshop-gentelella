<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Task;
use App\Models\Tool;

class TaskSeeder extends Seeder
{
    public function run()
    {
        $airbuffingHighToolId = Tool::where('name', 'airbuffing high')->first()->id;
        $airbuffingLowToolId = Tool::where('name', 'airbuffing low')->first()->id;
        $extruderToolId = Tool::where('name', 'extruder')->first()->id;
        $monarToolId = Tool::where('name', 'monar')->first()->id;

        $tasks = [
            ['name' => 'inspeksi awal', 'duration' => 15, 'tool_id' => null],
            ['name' => 'skiving sidewall', 'duration' => 30, 'tool_id' => $airbuffingHighToolId],
            ['name' => 'skiving tread', 'duration' => 90, 'tool_id' => $airbuffingLowToolId],
            ['name' => 'skiving spot', 'duration' => 0, 'tool_id' => $airbuffingHighToolId],
            ['name' => 'skiving bead', 'duration' => 10, 'tool_id' => $airbuffingHighToolId],
            ['name' => 'skiving chaffer', 'duration' => 10, 'tool_id' => $airbuffingHighToolId],
            ['name' => 'inspeksi after skiving', 'duration' => 30, 'tool_id' => null],
            ['name' => 'buffing sidewall', 'duration' => 30, 'tool_id' => $airbuffingLowToolId],
            ['name' => 'buffing tread', 'duration' => 120, 'tool_id' => $airbuffingLowToolId],
            ['name' => 'buffing spot', 'duration' => 0, 'tool_id' => $airbuffingLowToolId],
            ['name' => 'buffing bead', 'duration' => 10, 'tool_id' => $airbuffingLowToolId],
            ['name' => 'buffing chaffer', 'duration' => 15, 'tool_id' => $airbuffingLowToolId],
            ['name' => 'cementing 1 sidewall', 'duration' => 3, 'tool_id' => null],
            ['name' => 'cementing 1 tread', 'duration' => 3, 'tool_id' => null],
            ['name' => 'cementing 1 spot', 'duration' => 3, 'tool_id' => null],
            ['name' => 'cement 1 bead', 'duration' => 3, 'tool_id' => null],
            ['name' => 'cement 1 chaffer', 'duration' => 3, 'tool_id' => null],
            ['name' => 'dryup cement 1', 'duration' => 30, 'tool_id' => null],
            ['name' => 'cementing 2 sidewall', 'duration' => 5, 'tool_id' => null],
            ['name' => 'cementing 2 tread', 'duration' => 5, 'tool_id' => null],
            ['name' => 'cementing 2 spot', 'duration' => 3, 'tool_id' => null],
            ['name' => 'cementing 2 bead', 'duration' => 3, 'tool_id' => null],
            ['name' => 'cementing 2 chaffer', 'duration' => 3, 'tool_id' => null],
            ['name' => 'dryup cementing 2', 'duration' => 45, 'tool_id' => null],
            ['name' => 'builtup tread', 'duration' => 15, 'tool_id' => $extruderToolId],
            ['name' => 'builtup spot', 'duration' => 25, 'tool_id' => $extruderToolId],
            ['name' => 'builtup bead', 'duration' => 15, 'tool_id' => $extruderToolId],
            ['name' => 'install patch', 'duration' => 15, 'tool_id' => $extruderToolId],
            ['name' => 'builtup chaffer', 'duration' => 90, 'tool_id' => $extruderToolId],
            ['name' => 'prepare curing', 'duration' => 15, 'tool_id' => null],
            ['name' => 'curing sidewall', 'duration' => 60, 'tool_id' => $monarToolId],
            ['name' => 'curing tread', 'duration' => 300, 'tool_id' => $monarToolId],
            ['name' => 'curing patch', 'duration' => 600, 'tool_id' => $monarToolId],
            ['name' => 'curing bead', 'duration' => 240, 'tool_id' => $monarToolId],
            ['name' => 'curing chaffer', 'duration' => 300, 'tool_id' => $monarToolId],
            ['name' => 'finishing sidewall', 'duration' => 300, 'tool_id' => $airbuffingLowToolId],
            ['name' => 'finishing tread', 'duration' => 30, 'tool_id' => $airbuffingLowToolId],
            ['name' => 'finishing spot', 'duration' => 120, 'tool_id' => $airbuffingLowToolId],
            ['name' => 'finishing bead', 'duration' => 0, 'tool_id' => $airbuffingLowToolId],
            ['name' => 'finishing chaffer', 'duration' => 15, 'tool_id' => $airbuffingLowToolId],
            ['name' => 'final inspection(pumping)', 'duration' => 15, 'tool_id' => $airbuffingLowToolId]
        ];

        foreach ($tasks as $task) {
            Task::create($task);
        }
    }
}
