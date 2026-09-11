<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('parents', function (Blueprint $table) {
            $table->string('province', 100)->nullable()->after('national_id');
            $table->foreignId('center_id')->nullable()->constrained('health_centers')->nullOnDelete()->after('province');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parents', function (Blueprint $table) {
            $table->dropForeign(['center_id']);
            $table->dropColumn(['province', 'center_id']);
        });
    }
};
