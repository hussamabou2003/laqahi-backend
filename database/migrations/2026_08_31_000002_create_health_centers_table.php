<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('health_centers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('address', 255);
            $table->string('phone', 20)->nullable();
            $table->unsignedBigInteger('admin_id');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('admin_id', 'fk_hc_admin')->references('id')->on('admins')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('health_centers');
    }
};