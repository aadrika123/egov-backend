<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSelfAdvertisementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('self_advertisements', function (Blueprint $table) {
            $table->id();
            $table->string('RenewalID', 15)->nullable();
            $table->string('UniqueID', 15)->nullable();
            $table->string('OldRenewalID', 15)->nullable();
            $table->smallInteger('Renewal')->nullable();
            $table->string('LicenseYear', 7)->nullable();

            $table->mediumText('Applicant')->nullable();
            $table->mediumText('Father')->nullable();
            $table->mediumText('Email')->nullable();
            $table->mediumText('ResidenceAddress')->nullable();
            $table->mediumText('WardNo')->nullable();
            $table->mediumText('PermanentAddress')->nullable();
            $table->mediumText('WardNo1')->nullable();
            $table->mediumText('EntityName')->nullable();
            $table->mediumText('EntityAddress')->nullable();
            $table->mediumText('EntityWard')->nullable();
            $table->string('MobileNo', 10)->nullable();
            $table->mediumText('AadharNo')->nullable();
            $table->mediumText('TradeLicenseNo')->nullable();
            $table->mediumText('HoldingNo')->nullable();
            $table->mediumText('GSTNo')->nullable();
            $table->mediumText('Longitude')->nullable();
            $table->mediumText('Latitude')->nullable();
            $table->integer('DisplayArea')->nullable();
            $table->mediumText('DisplayType')->nullable();
            $table->mediumText('InstallationLocation')->nullable();
            $table->mediumText('BrandDisplayName')->nullable();
            $table->mediumText('AadharPath')->nullable();
            $table->mediumText('TradeLicensePath')->nullable();
            $table->mediumText('HoldingNoPath')->nullable();
            $table->mediumText('GPSPhotoPath')->nullable();
            $table->mediumText('GSTPath')->nullable();
            $table->string('Zone', 10)->nullable();
            $table->mediumText('Proceeding1Photo')->nullable();
            $table->mediumText('Proceeding2Photo')->nullable();
            $table->mediumText('Proceeding3Photo')->nullable();
            $table->mediumText('ExtraDoc1')->nullable();
            $table->mediumText('ExtraDoc2')->nullable();
            $table->integer('WorkflowID')->nullable();
            $table->mediumText('CurrentUser')->nullable();
            $table->mediumText('Initiator')->nullable();
            $table->mediumText('Approver')->nullable();
            $table->integer('InspectorID')->nullable();
            $table->mediumText('InspectionRemarks')->nullable();
            $table->smallInteger('Pending')->nullable();
            $table->smallInteger('Approved')->nullable();
            $table->smallInteger('Rejected')->nullable();
            $table->dateTime('ApprovalDate')->nullable();
            $table->smallInteger('Paid')->nullable();
            $table->mediumText('RejectionReason')->nullable();
            $table->mediumText('ApplicationStatus')->nullable();

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
            $table->string('CreatedOn', 29)->nullable();
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
        Schema::dropIfExists('self_advertisements');
    }
}
