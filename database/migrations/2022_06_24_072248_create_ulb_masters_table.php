<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUlbMastersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ulb_masters', function (Blueprint $table) {
            $table->id();
            $table->mediumText('UlbName')->nullable();
            $table->mediumText('UlbType')->nullable();
            $table->mediumText('Description')->nullable();
            $table->date('IncorporationDate')->nullable();
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
        Schema::dropIfExists('ulb_masters');
    }
}
