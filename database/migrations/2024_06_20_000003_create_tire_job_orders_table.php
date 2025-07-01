<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTireJobOrdersTable extends Migration
{
    public function up()
    {
        Schema::create('tire_job_orders', function (Blueprint $table) {
            $table->id();
            $table->string('sn_tire')->unique();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('tire_job_orders');
    }
}
