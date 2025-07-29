<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tool;

class ToolSeeder extends Seeder
{
    public function run()
    {
        $tools = [
            ['name' => 'Airbuffing High', 'quantity' => 1],
            ['name' => 'Airbuffing Low', 'quantity' => 1],
            ['name' => 'Extruder', 'quantity' => 1],
            ['name' => 'Monar', 'quantity' => 1],
            ['name' => 'Lampu', 'quantity' => 1],
            ['name' => 'Washing Tool', 'quantity' => 1],
            ['name' => 'None', 'quantity' => 2]
        ];

        foreach ($tools as $tool) {
            Tool::create($tool);
        }
    }
}
