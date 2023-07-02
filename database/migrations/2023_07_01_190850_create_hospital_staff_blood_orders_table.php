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
        Schema::create('hospital_staff_blood_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hospital_staff')->constrained('hospital_staff');
            $table->foreignId('patient_id')->constrained('patients');
            $table->foreignId('blood_product')->constrained('blood_products');
            $table->foreignId('blood_group')->constrained('blood_groups');
            $table->foreignId('status_id')->constrained('statuses');
            $table->foreignId('approved_by')->constrained('users');
            $table->string('amount');
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
        Schema::dropIfExists('hospital_staff_blood_orders');
    }
};
