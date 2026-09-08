<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->enum('actor_type', ['admin', 'doctor', 'parent']);
            $table->unsignedBigInteger('actor_id');
            $table->string('action', 100);
            $table->string('target_table', 100);
            $table->unsignedBigInteger('target_id')->nullable();
            $table->json('old_value')->nullable();
            $table->json('new_value')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['actor_type', 'actor_id'], 'idx_audit_actor');
            $table->index(['target_table', 'target_id'], 'idx_audit_target');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};