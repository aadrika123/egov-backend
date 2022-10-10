<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMUlbWardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('m_ulb_wards', function (Blueprint $table) {
            $table->id();
            $table->integer('ulb_id');
            $table->string('ward_name');
            $table->string('old_ward_name');
            $table->integer('user_id');
            $table->smallInteger('status')->nullable()->default(1);
            $table->dateTime('stamp_date_time');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('m_ulb_wards');
    }
}
