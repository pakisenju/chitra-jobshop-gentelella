<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TireJobOrder;
use App\Models\TireJobOrderTaskDetail;
use App\Models\Task;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class TireJobOrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Disable foreign key checks to avoid issues with truncating.
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        // Truncate the tables to start with a clean slate.
        TireJobOrder::truncate();
        TireJobOrderTaskDetail::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Get the first customer, or create one if none exist.
        $customer = Customer::first();
        if (!$customer) {
            $customer = Customer::factory()->create();
        }

        // Get all tasks.
        $tasks = Task::all();

        if ($tasks->isEmpty()) {
            $this->command->info('No tasks found in the database. Please seed the tasks table first.');
            return;
        }

        // Define the task order for sorting.
        $taskOrder = [
            'inspeksi awal' => 1,
            'washing' => 2,
            'skiving sidewall' => 3,
            'skiving tread' => 4,
            'skiving spot' => 5,
            'skiving bead' => 6,
            'skiving chaffer' => 7,
            'inspeksi after skiving' => 8,
            'buffing sidewall' => 9,
            'buffing tread' => 10,
            'buffing spot' => 11,
            'buffing bead' => 12,
            'buffing chaffer' => 13,
            'cementing 1 sidewall' => 14,
            'cementing 1 tread' => 15,
            'cementing 1 spot' => 16,
            'cementing 1 bead' => 17,
            'cementing 1 chaffer' => 18,
            'dryup cement 1' => 19,
            'cementing 2 sidewall' => 20,
            'cementing 2 tread' => 21,
            'cementing 2 spot' => 22,
            'cementing 2 bead' => 23,
            'cementing 2 chaffer' => 24,
            'dryup cementing 2' => 25,
            'builtup tread' => 26,
            'builtup spot' => 27,
            'builtup bead' => 28,
            'install patch' => 29,
            'builtup chaffer' => 30,
            'prepare curing sidewall' => 31,
            'curing sidewall' => 32,
            'prepare curing tread' => 33,
            'curing tread' => 34,
            'prepare curing patch' => 35,
            'curing patch' => 36,
            'curing bead' => 37,
            'curing chaffer' => 38,
            'finishing sidewall' => 39,
            'finishing tread' => 40,
            'finishing spot' => 41,
            'finishing bead' => 42,
            'finishing chaffer' => 43,
            'final inspection(pumping)' => 44,
        ];

        for ($i = 1; $i <= 10; $i++) {
            $jobOrder = TireJobOrder::create([
                'sn_tire' => 'sn' . $i,
                'tread' => 1,
                'sidewall' => 1,
                'spot' => 1,
                'patch' => 1,
                'area_curing_sw' => 1,
                'area_curing_tread' => 1,
                'bead' => 1,
                'chaffer' => 1,
                'customer_id' => $customer->id,
            ]);

            foreach ($tasks as $task) {
                $taskNameLower = strtolower($task->name);
                $order = $taskOrder[$taskNameLower] ?? 999;

                TireJobOrderTaskDetail::create([
                    'tire_job_order_id' => $jobOrder->id,
                    'task_id' => $task->id,
                    'qty_calculated' => 1,
                    'total_duration_calculated' => $task->duration,
                    'status' => 'pending',
                    'order' => $order,
                ]);
            }
        }
    }
}
