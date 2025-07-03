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
        Schema::table('tire_job_orders', function (Blueprint $table) {
            $table->integer('tread')->nullable()->after('sn_tire');
            $table->integer('sidewall')->nullable()->after('tread');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tire_job_orders', function (Blueprint $table) {
            $table->dropColumn(['tread', 'sidewall']);
        });
    }
};