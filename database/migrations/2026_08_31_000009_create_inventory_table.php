<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('center_id');
            $table->unsignedBigInteger('vaccine_id');
            $table->unsignedInteger('quantity')->default(0);
            $table->unsignedInteger('min_threshold')->default(10);
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['center_id', 'vaccine_id'], 'uq_center_vaccine');
            $table->foreign('center_id', 'fk_inv_center')->references('id')->on('health_centers')->cascadeOnDelete();
            $table->foreign('vaccine_id', 'fk_inv_vaccine')->references('id')->on('vaccines')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory');
    }
};