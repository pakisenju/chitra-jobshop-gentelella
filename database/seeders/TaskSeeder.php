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

        $tasksData = [
            ['name' => 'inspeksi awal', 'duration' => 15, 'tools' => []],
            ['name' => 'skiving sidewall', 'duration' => 30, 'tools' => [$airbuffingHighToolId]],
            ['name' => 'skiving tread', 'duration' => 90, 'tools' => [$airbuffingLowToolId]],
            ['name' => 'skiving spot', 'duration' => 1, 'tools' => [$airbuffingHighToolId]],
            ['name' => 'skiving bead', 'duration' => 10, 'tools' => [$airbuffingHighToolId]],
            ['name' => 'skiving chaffer', 'duration' => 10, 'tools' => [$airbuffingHighToolId]],
            ['name' => 'inspeksi after skiving', 'duration' => 30, 'tools' => []],
            ['name' => 'buffing sidewall', 'duration' => 30, 'tools' => [$airbuffingLowToolId]],
            ['name' => 'buffing tread', 'duration' => 120, 'tools' => [$airbuffingLowToolId]],
            ['name' => 'buffing spot', 'duration' => 1, 'tools' => [$airbuffingLowToolId]],
            ['name' => 'buffing bead', 'duration' => 10, 'tools' => [$airbuffingLowToolId]],
            ['name' => 'buffing chaffer', 'duration' => 15, 'tools' => [$airbuffingLowToolId]],
            ['name' => 'cementing 1 sidewall', 'duration' => 3, 'tools' => []],
            ['name' => 'cementing 1 tread', 'duration' => 3, 'tools' => []],
            ['name' => 'cementing 1 spot', 'duration' => 3, 'tools' => []],
            ['name' => 'cement 1 bead', 'duration' => 3, 'tools' => []],
            ['name' => 'cement 1 chaffer', 'duration' => 3, 'tools' => []],
            ['name' => 'dryup cement 1', 'duration' => 30, 'tools' => []],
            ['name' => 'cementing 2 sidewall', 'duration' => 5, 'tools' => []],
            ['name' => 'cementing 2 tread', 'duration' => 5, 'tools' => []],
            ['name' => 'cementing 2 spot', 'duration' => 3, 'tools' => []],
            ['name' => 'cementing 2 bead', 'duration' => 3, 'tools' => []],
            ['name' => 'cementing 2 chaffer', 'duration' => 3, 'tools' => []],
            ['name' => 'dryup cementing 2', 'duration' => 45, 'tools' => []],
            ['name' => 'builtup tread', 'duration' => 15, 'tools' => [$extruderToolId]],
            ['name' => 'builtup spot', 'duration' => 25, 'tools' => [$extruderToolId]],
            ['name' => 'builtup bead', 'duration' => 15, 'tools' => [$extruderToolId]],
            ['name' => 'install patch', 'duration' => 15, 'tools' => [$extruderToolId]],
            ['name' => 'builtup chaffer', 'duration' => 90, 'tools' => [$extruderToolId]],
            ['name' => 'prepare curing', 'duration' => 15, 'tools' => []],
            ['name' => 'curing sidewall', 'duration' => 60, 'tools' => [$monarToolId]],
            ['name' => 'curing tread', 'duration' => 300, 'tools' => [$monarToolId]],
            ['name' => 'curing patch', 'duration' => 600, 'tools' => [$monarToolId]],
            ['name' => 'curing bead', 'duration' => 240, 'tools' => [$monarToolId]],
            ['name' => 'curing chaffer', 'duration' => 300, 'tools' => [$monarToolId]],
            ['name' => 'finishing sidewall', 'duration' => 300, 'tools' => [$airbuffingLowToolId]],
            ['name' => 'finishing tread', 'duration' => 30, 'tools' => [$airbuffingLowToolId]],
            ['name' => 'finishing spot', 'duration' => 120, 'tools' => [$airbuffingLowToolId]],
            ['name' => 'finishing bead', 'duration' => 1, 'tools' => [$airbuffingLowToolId]],
            ['name' => 'finishing chaffer', 'duration' => 15, 'tools' => [$airbuffingLowToolId]],
            ['name' => 'final inspection(pumping)', 'duration' => 15, 'tools' => [$airbuffingLowToolId]]
        ];

        foreach ($tasksData as $taskData) {
            $task = Task::create([
                'name' => $taskData['name'],
                'duration' => $taskData['duration'],
            ]);
            $task->tools()->sync($taskData['tools']);
        }
    }
}
