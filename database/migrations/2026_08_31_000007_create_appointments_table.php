<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('child_id');
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->unsignedBigInteger('center_id');
            $table->unsignedBigInteger('vaccine_id');
            $table->dateTime('appointment_date');
            $table->enum('status', ['booked', 'completed', 'cancelled'])->default('booked');
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('child_id', 'fk_appt_child')->references('id')->on('children')->cascadeOnDelete();
            $table->foreign('doctor_id', 'fk_appt_doctor')->references('id')->on('doctors')->nullOnDelete();
            $table->foreign('center_id', 'fk_appt_center')->references('id')->on('health_centers')->restrictOnDelete();
            $table->foreign('vaccine_id', 'fk_appt_vaccine')->references('id')->on('vaccines')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};