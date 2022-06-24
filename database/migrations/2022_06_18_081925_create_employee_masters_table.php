<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeMastersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_masters', function (Blueprint $table) {
            $table->id();
            $table->integer('RoleID')->nullable();
            $table->mediumText('EmployeeName')->nullable();
            $table->mediumText('Mobile')->nullable();
            $table->mediumText('Email')->nullable();
            $table->mediumText('Address')->nullable();
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
        Schema::dropIfExists('employee_masters');
    }
}
