<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMenuMastersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('menu_masters', function (Blueprint $table) {
            $table->id();
            $table->integer('Serial')->nullable();
            $table->mediumText('Description')->nullable();
            $table->mediumText('MenuString')->nullable();
            $table->integer('ParentSerial')->nullable();
            $table->mediumText('ControllerName')->nullable();
            $table->mediumText('ViewName')->nullable();
            $table->mediumText('Icon')->nullable();
            $table->smallInteger('TopLevel')->nullable();
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
        Schema::dropIfExists('menu_masters');
    }
}
