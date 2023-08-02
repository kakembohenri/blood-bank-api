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
            $table->foreignId('hospital_staff_id')->constrained('hospital_staff')->cascadeOnDelete();
            $table->foreignId('hospital_id')->constrained('hospitals')->cascadeOnDelete();
            $table->string('patient_name');
            $table->integer('patient_age');
            $table->string('patient_email')->nullable();
            $table->string('patient_phone');
            $table->foreignId('blood_group_id')->constrained('blood_groups')->cascadeOnDelete();
            $table->foreignId('blood_component_id')->constrained('blood_products')->cascadeOnDelete();
            $table->integer('quantity');
            $table->foreignId('status_id')->constrained('statuses');
            $table->foreignId('approved_by')->nullable()->constrained('users');
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
