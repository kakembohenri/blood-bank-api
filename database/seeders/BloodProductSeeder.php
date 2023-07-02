<?php

namespace Database\Seeders;

use App\Models\BloodProduct;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BloodProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        BloodProduct::insert([
            [
                'name' => 'WB'
            ],
            [
                'name' => 'PRBCs'
            ],
            [
                'name' => 'FFP'
            ],
            [
                'name' => 'FP'
            ],
            [
                'name' => 'PLT'
            ],
            [
                'name' => 'CRYO'
            ],
        ]);
    }
}
