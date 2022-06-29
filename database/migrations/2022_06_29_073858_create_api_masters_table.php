<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApiMastersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('api_masters', function (Blueprint $table) {
            $table->id();
            $table->mediumText('Description')->nullable();
            $table->mediumText('Category')->nullable();
            $table->mediumText('EndPoint')->nullable();
            $table->mediumText('Usage')->nullable();
            $table->mediumText('PreCondition')->nullable();
            $table->mediumText('RequestPayload')->nullable();         // IN JSON
            $table->mediumText('ResponsePayload')->nullable();        // IN JSON
            $table->mediumText('PostCondition')->nullable();
            $table->mediumText('Version')->nullable();
            $table->dateTime('CreatedOn')->nullable();
            $table->mediumText('CreatedBy')->nullable();
            $table->smallInteger('RevisionNo')->nullable();
            $table->boolean('Discontinued')->nullable();
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
        Schema::dropIfExists('api_masters');
    }
}
