<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWorkflowTracksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('workflow_tracks', function (Blueprint $table) {
            $table->id();
            $table->mediumText('RenewalID')->nullable();
            $table->dateTime('TrackDate')->nullable();
            $table->mediumText('UserID')->nullable();
            $table->mediumText('Remarks')->nullable();
            $table->smallInteger('IsSMS')->nullable();
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
        Schema::dropIfExists('workflow_tracks');
    }
}
