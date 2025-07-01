<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tool;

class ToolSeeder extends Seeder
{
    public function run()
    {
        $tools = [
            ['name' => 'airbuffing high', 'quantity' => 1],
            ['name' => 'airbuffing low', 'quantity' => 1],
            ['name' => 'extruder', 'quantity' => 1],
            ['name' => 'monar', 'quantity' => 1],
            ['name' => 'none', 'quantity' => 999]
        ];

        foreach ($tools as $tool) {
            Tool::create($tool);
        }
    }
}
