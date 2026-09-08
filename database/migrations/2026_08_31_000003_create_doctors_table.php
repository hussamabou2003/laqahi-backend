<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('email', 150)->unique();
            $table->string('password');
            $table->string('phone', 20)->nullable();
            $table->string('specialization', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('national_id', 20)->unique();
            $table->unsignedBigInteger('center_id');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('center_id', 'fk_doctor_center')->references('id')->on('health_centers')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctors');
    }
};