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
            $table->integer('order')->after('task_id')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tire_job_order_task_details', function (Blueprint $table) {
            $table->dropColumn('order');
        });
    }
};
