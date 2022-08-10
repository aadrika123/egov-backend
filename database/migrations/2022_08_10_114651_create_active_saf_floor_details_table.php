<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActiveSafFloorDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('active_saf_floor_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('saf_dtl_id');
            $table->bigInteger('floor_mstr_id')->nullable();
            $table->bigInteger('usage_type_mstr_id')->nullable();
            $table->bigInteger('const_type_mstr_id')->nullable();
            $table->bigInteger('occupancy_type_mstr_id')->nullable();
            $table->decimal('builtup_area', 18)->nullable();
            $table->date('date_from')->nullable();
            $table->date('date_upto')->nullable();
            $table->bigInteger('emp_details_id')->nullable();
            $table->timestamp('created_on')->nullable();
            $table->integer('status')->nullable()->default(1);
            $table->bigInteger('verified_floor_mstr_id')->nullable();
            $table->bigInteger('verified_usage_type_mstr_id')->nullable();
            $table->bigInteger('verified_const_type_mstr_id')->nullable();
            $table->bigInteger('verified_occupancy_type_mstr_id')->nullable();
            $table->decimal('verified_builtup_area', 18)->nullable();
            $table->date('verified_date_from')->nullable();
            $table->date('verified_date_upto')->nullable();
            $table->bigInteger('verified_emp_details_id')->nullable();
            $table->timestamp('verified_created_on')->nullable();
            $table->decimal('carpet_area', 18)->nullable();
            $table->bigInteger('rmc_saf_dtl_id')->nullable();
            $table->bigInteger('rmc_saf_floor_dtl_id')->nullable();
            $table->bigInteger('prop_floor_details_id')->nullable();
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
        Schema::dropIfExists('active_saf_floor_details');
    }
}
