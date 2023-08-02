<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
        Schema::create('approved_by_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bulk_order_id')->constrained('bulk_orders')->onDelete('cascade');
            $table->string('name');
            $table->string('designation');
            $table->text('signature');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('approved_by_orders');
    }
};
