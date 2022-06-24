<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWorkflowCandidatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('workflow_candidates', function (Blueprint $table) {
            $table->id();
            $table->integer('WorkflowID')->nullable();
            $table->integer('EmployeeID')->nullable();
            $table->mediumText('JobDescription')->nullable();
            $table->mediumText('UserID')->nullable();
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
        Schema::dropIfExists('workflow_candidates');
    }
}
