<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->enum('recipient_type', ['parent', 'doctor']);
            $table->unsignedBigInteger('recipient_id');
            $table->unsignedBigInteger('child_id')->nullable();
            $table->enum('type', ['auto', 'manual'])->default('auto');
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->timestamp('sent_at')->useCurrent();

            $table->foreign('child_id', 'fk_notif_child')->references('id')->on('children')->nullOnDelete();
            $table->index(['recipient_type', 'recipient_id'], 'idx_recipient');
            $table->index('child_id', 'idx_notif_child');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};