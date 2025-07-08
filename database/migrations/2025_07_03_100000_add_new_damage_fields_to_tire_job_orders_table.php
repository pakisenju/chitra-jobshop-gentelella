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
            $table->integer('spot')->default(0);
            $table->integer('patch')->default(0);
            $table->integer('area_curing_sw')->default(0);
            $table->integer('area_curing_tread')->default(0);
            $table->integer('bead')->default(0);
            $table->integer('chaffer')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tire_job_orders', function (Blueprint $table) {
            $table->dropColumn(['spot', 'patch', 'area_curing_sw', 'area_curing_tread', 'bead', 'chaffer']);
        });
    }
};