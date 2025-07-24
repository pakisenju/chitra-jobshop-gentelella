<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTireJobOrderTaskDetailsTable extends Migration
{
    public function up()
    {
        Schema::create('tire_job_order_task_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tire_job_order_id');
            $table->unsignedBigInteger('task_id');
            $table->integer('qty_calculated')->default(0);
            $table->float('total_duration_calculated')->default(0);
            $table->dateTime('actual_start_time')->nullable();
            $table->dateTime('actual_end_time')->nullable();
            $table->unsignedBigInteger('tool_id_used')->nullable();
            $table->timestamps();

            $table->foreign('tire_job_order_id')->references('id')->on('tire_job_orders');
            $table->foreign('task_id')->references('id')->on('tasks');
            $table->foreign('tool_id_used')->references('id')->on('tools');
        });
    }

    public function down()
    {
        Schema::dropIfExists('tire_job_order_task_details');
    }
}
