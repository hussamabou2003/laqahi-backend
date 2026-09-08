<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('children', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->date('birth_date');
            $table->enum('gender', ['male', 'female']);
            $table->string('qr_code', 255)->unique();
            $table->unsignedBigInteger('parent_id');
            $table->unsignedBigInteger('center_id');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('parent_id', 'fk_child_parent')->references('id')->on('parents')->restrictOnDelete();
            $table->foreign('center_id', 'fk_child_center')->references('id')->on('health_centers')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('children');
    }
};