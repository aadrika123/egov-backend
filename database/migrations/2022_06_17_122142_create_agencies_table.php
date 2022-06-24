<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAgenciesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('agencies', function (Blueprint $table) {
            $table->id();
            $table->string('RenewalID', 15)->nullable();
            $table->string('UniqueID', 15)->nullable();
            $table->smallInteger('Renewal')->nullable();
            $table->date('ApplicationDate')->nullable();
            $table->mediumText('RegistrationNo')->nullable();
            $table->mediumText('EntityName')->nullable();
            $table->mediumText('EntityType')->nullable();
            $table->mediumText('Address')->nullable();
            $table->mediumText('MobileNo')->nullable();
            $table->mediumText('Telephone')->nullable();
            $table->mediumText('Fax')->nullable();
            $table->mediumText('Email')->nullable();
            $table->mediumText('Director1Name')->nullable();
            $table->mediumText('Director2Name')->nullable();
            $table->mediumText('Director3Name')->nullable();
            $table->mediumText('Director4Name')->nullable();
            $table->mediumText('Director1Mobile')->nullable();
            $table->mediumText('Director2Mobile')->nullable();
            $table->mediumText('Director3Mobile')->nullable();
            $table->mediumText('Director4Mobile')->nullable();
            $table->mediumText('Director1Email')->nullable();
            $table->mediumText('Director2Email')->nullable();
            $table->mediumText('Director3Email')->nullable();
            $table->mediumText('Director4Email')->nullable();
            $table->mediumText('PANNo')->nullable();
            $table->mediumText('GSTNo')->nullable();
            $table->integer('RegistrationFee')->nullable();
            $table->smallInteger('Blacklisted')->nullable();
            $table->decimal('PendingAmount', $precision = 18, $scale = 2)->nullable();
            $table->smallInteger('PendingCourtCase')->nullable();
            $table->mediumText('Proceeding1Path')->nullable();
            $table->mediumText('Proceeding2Path')->nullable();
            $table->mediumText('Proceeding3Path')->nullable();
            $table->mediumText('ExtraDoc1')->nullable();
            $table->mediumText('ExtraDoc2')->nullable();
            $table->mediumText('GSTPath')->nullable();
            $table->mediumText('ITReturnPath1')->nullable();
            $table->mediumText('ITReturnPath2')->nullable();
            $table->mediumText('OfficeAddressPath')->nullable();
            $table->mediumText('PANNoPath')->nullable();
            $table->mediumText('Director1AadharPath')->nullable();
            $table->mediumText('Director2AadharPath')->nullable();
            $table->mediumText('Director3AadharPath')->nullable();
            $table->mediumText('Director4AadharPath')->nullable();
            $table->mediumText('AffidavitPath')->nullable();
            $table->integer('WorkflowID')->nullable();
            $table->mediumText('CurrentUser')->nullable();
            $table->mediumText('Initiator')->nullable();
            $table->mediumText('Approver')->nullable();
            $table->smallInteger('Pending')->nullable();
            $table->smallInteger('Approved')->nullable();
            $table->smallInteger('Rejected')->nullable();
            $table->date('ApprovalDate')->nullable();
            $table->smallInteger('Paid')->nullable();
            $table->mediumText('RejectionReason')->nullable();
            $table->mediumText('ApplicationStatus')->nullable();
            $table->string('LicenseFrom')->nullable();
            $table->smallInteger('Active')->nullable();
            $table->decimal('LicenseFee', $precision = 18, $scale = 2)->nullable();
            $table->decimal('Amount', $precision = 18, $scale = 2)->nullable();
            $table->decimal('GST', $precision = 18, $scale = 2)->nullable();
            $table->decimal('NetAmount', $precision = 18, $scale = 2)->nullable();
            $table->integer('OnlinePaymentID')->nullable();
            $table->mediumText('PmtMode')->nullable();
            $table->mediumText('Bank')->nullable();
            $table->mediumText('MRNo')->nullable();
            $table->mediumText('DraftNo')->nullable();
            $table->dateTime('PaymentDate')->nullable();
            $table->dateTime('DraftDate')->nullable();
            $table->mediumText('CreatedOn')->nullable();
            $table->integer('ModifiedBy')->nullable();
            $table->string('ModifiedOn', 29)->nullable();
            $table->mediumText('SignaturePath')->nullable();
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
        Schema::dropIfExists('agencies');
    }
}
