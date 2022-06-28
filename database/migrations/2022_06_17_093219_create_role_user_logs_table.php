<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoleUserLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('role_user_logs', function (Blueprint $table) {
            $table->id();
            $table->integer('RoleID')->nullable();
            $table->integer('UserID')->nullable();
            $table->boolean('Flag')->nullable();
            $table->mediumText('CreatedBy')->nullable();
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
        Schema::dropIfExists('role_user_logs');
    }
}
