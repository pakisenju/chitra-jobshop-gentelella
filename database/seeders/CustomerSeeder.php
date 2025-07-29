<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $customers = [
            ['name' => 'PT. Cipta Kridatama'],
            ['name' => 'PT. Putra Perkasa Abadi'],
        ];

        foreach ($customers as $customer) {
            Customer::create($customer);
        }
    }
}
