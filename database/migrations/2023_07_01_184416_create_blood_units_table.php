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
        Schema::create('blood_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blood_group')->constrained('blood_groups');
            $table->foreignId('blood_product')->constrained('blood_products');
            $table->foreignId('status_id')->constrained('statuses');
            $table->timestamp('date_of_expiry');
            AuditTrail::UserForeignKeys(($table));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('blood_units');
    }
};
