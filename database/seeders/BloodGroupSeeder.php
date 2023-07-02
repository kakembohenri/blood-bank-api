<?php

namespace Database\Seeders;

use App\Models\BloodGroup;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BloodGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        BloodGroup::insert([
            [
                "name" => "A"
            ],
            [
                "name" => "A-"
            ],
            [
                "name" => "B"
            ],
            [
                "name" => "B-"
            ],
            [
                "name" => "AB"
            ],
            [
                "name" => "AB-"
            ],
            [
                "name" => "O"
            ],
            [
                "name" => "O-"
            ]
        ]);
    }
}
