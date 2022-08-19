<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWardMstrsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ward_mstrs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('ward_no');
            $table->integer('status')->default(1);
            $table->bigInteger('ulb_mstr_id');
            $table->bigInteger('sspl_ward_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ward_mstrs');
    }
}
