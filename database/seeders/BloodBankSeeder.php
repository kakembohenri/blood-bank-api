<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BloodBankSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // $user = User::create([
        //     'email' => 'bloodbank@mail.com',
        //     'phone' => '0712804062',
        //     'status_id' => 3,
        //     'password' => md5("admin123"),
        //     'sdsds' => 
        // ]);

        $date = '"' . date('Y:m:d H:i:s', time()) . '"';
        $password = '"' . md5('admin123') . '"';

        DB::statement('INSERT INTO `users` (`email`,`phone`,`role_id`,`status_id`,`password`, `created_at`) VALUES ("bloodbank@mail.com", "0712804062", 1, 1,' . $password . ',' . $date . ')');
    }
}
