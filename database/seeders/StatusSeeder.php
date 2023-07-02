<?php

namespace Database\Seeders;

use App\Models\Status;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Status::insert([
            [
                'name' => 'verified'
            ],
            [
                'name' => 'unverified'
            ],
            [
                'name' => 'new'
            ],
            [
                'name' => 'pending'
            ],
            [
                'name' => 'approved'
            ],
            [
                'name' => 'delivered'
            ],
            [
                'name' => 'rejected'
            ],
        ]);
    }
}
