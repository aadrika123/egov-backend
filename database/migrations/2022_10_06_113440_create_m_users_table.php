<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('m_users', function (Blueprint $table) {
            $table->id();
            $table->integer('ulb_id');
            $table->integer('citizen_id');
            $table->integer('employee_id');
            $table->integer('vendor_id');
            $table->integer('agency_id');
            $table->boolean('is_admin');
            $table->boolean('is_psudo');
            $table->text('email');
            $table->text('user_name');
            $table->text('full_name');
            $table->mediumText('description');
            $table->boolean('is_suspended');
            $table->boolean('is_deleted');
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
        Schema::dropIfExists('m_users');
    }
}
