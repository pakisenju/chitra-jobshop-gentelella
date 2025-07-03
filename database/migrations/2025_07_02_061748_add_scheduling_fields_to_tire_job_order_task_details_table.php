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
        Schema::table('tire_job_order_task_details', function (Blueprint $table) {
            $table->timestamp('start_time')->nullable()->after('task_id');
            $table->timestamp('end_time')->nullable()->after('start_time');
            $table->string('status')->default('pending')->after('end_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tire_job_order_task_details', function (Blueprint $table) {
            $table->dropColumn(['start_time', 'end_time', 'status']);
        });
    }
};