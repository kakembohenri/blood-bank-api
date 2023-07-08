<?php

namespace Database\Seeders;

use App\Models\OrderTypes;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OrderTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        OrderTypes::insert([
            [
                'id' => 1,
                'name' => 'Routine'
            ],
            [
                'id' => 2,
                'name' => 'Emergency'
            ]
        ]);
    }
}
