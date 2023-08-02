<?php

use App\AuditTrail\AuditTrail;
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
        Schema::create('bulk_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bulk_order')->constrained('bulk_orders')->cascadeOnDelete();
            $table->foreignId('bloodGroup_id')->constrained('blood_groups')->cascadeOnDelete();
            $table->foreignId('bloodComponent_id')->constrained('blood_products')->cascadeOnDelete();
            $table->integer('quantity');
            AuditTrail::UserForeignKeys($table);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bulk_order_items');
    }
};
