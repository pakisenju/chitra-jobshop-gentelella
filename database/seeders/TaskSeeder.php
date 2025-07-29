<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Task;
use App\Models\Tool;

class TaskSeeder extends Seeder
{
    public function run()
    {
        $tools = [
            'airbuffing high' => Tool::where('name', 'airbuffing high')->first()?->id,
            'airbuffing low' => Tool::where('name', 'airbuffing low')->first()?->id,
            'extruder' => Tool::where('name', 'extruder')->first()?->id,
            'monar' => Tool::where('name', 'monar')->first()?->id,
            'lampu' => Tool::where('name', 'lampu')->first()?->id,
            'washing tool' => Tool::where('name', 'washing tool')->first()?->id,
            'none' => Tool::where('name', 'none')->first()?->id,
        ];

        $tasksData = [
            ['Inspeksi Awal', 30, 'none'],
            ['Washing', 30, 'washing tool'],
            ['Skiving Sidewall', 15, 'airbuffing high'],
            ['Skiving Tread', 90, 'airbuffing high'],
            ['Skiving Spot', 0.34, 'airbuffing high'],
            ['Skiving Bead', 10, 'airbuffing high'],
            ['Skiving Chaffer', 10, 'airbuffing high'],
            ['Inspeksi After Skiving', 30, 'none'],
            ['Buffing Sidewall', 30, 'airbuffing low'],
            ['Buffing Tread', 120, 'airbuffing low'],
            ['Buffing Spot', 0.34, 'airbuffing low'],
            ['Buffing Bead', 10, 'airbuffing low'],
            ['Buffing Chaffer', 15, 'airbuffing low'],
            ['Cementing 1 Sidewall', 3, 'none'],
            ['Cementing 1 Tread', 3, 'none'],
            ['Cementing 1 Spot', 3, 'none'],
            ['Cement 1 Bead', 3, 'none'],
            ['Cement 1 Chaffer', 3, 'none'],
            ['Dryup Cement 1', 30, 'none'],
            ['Cementing 2 Sidewall', 5, 'none'],
            ['Cementing 2 Tread', 5, 'none'],
            ['Cementing 2 Spot', 3, 'none'],
            ['Cementing 2 Bead', 3, 'none'],
            ['Cementing 2 Chaffer', 5, 'none'],
            ['Dryup Cementing 2', 45, 'none'],
            ['Builtup Sidewall', 15, 'extruder'],
            ['Builtup Tread', 25, 'extruder'],
            ['Builtup Spot', 5, 'extruder'],
            ['Builtup Bead', 15, 'extruder'],
            ['Install Patch', 90, 'extruder'],
            ['Builtup Chaffer', 15, 'extruder'],
            ['Prepare Curing Sidewall', 60, 'monar'],
            ['Curing Sidewall', 300, 'monar'],
            ['Prepare Curing Tread', 60, 'monar'],
            ['Curing Tread', 600, 'monar'],
            ['Prepare Curing Patch', 60, 'monar'],
            ['Curing Patch', 240, 'monar'],
            ['Curing Bead', 300, 'lampu'],
            ['Curing Chaffer', 300, 'lampu'],
            ['Finishing Sidewall', 30, 'airbuffing low'],
            ['Finishing Tread', 120, 'airbuffing low'],
            ['Finishing Spot', 0.34, 'airbuffing low'],
            ['Finishing Bead', 15, 'airbuffing low'],
            ['Finishing Chaffer', 15, 'airbuffing low'],
            ['Final Inspection(Pumping)', 180, 'none'],
        ];

        foreach ($tasksData as [$name, $duration, $toolName]) {
            $task = Task::create([
                'name' => ucwords($name),
                'duration' => $duration,
            ]);

            $toolId = $toolName && isset($tools[$toolName]) ? [$tools[$toolName]] : [];
            $task->tools()->sync($toolId);
        }
    }
}
