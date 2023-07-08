<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bulk_orders', function () {
            DB::statement('ALTER TABLE `bulk_orders` CHANGE `approved_by` `approved_by` BIGINT(20) UNSIGNED NULL');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bulk_orders', function (Blueprint $table) {
            //
        });
    }
};
