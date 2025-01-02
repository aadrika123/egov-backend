<?php

namespace App\Models\Water;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class WaterConsumerActiveRequest extends Model
{
    use HasFactory;
    protected $connection = 'pgsql_water';

    /**
     * | Save request details 
     */
    public function saveRequestDetails($req, $consumerDetails, $refRequest, $applicationNo)
    {
        $mWaterConsumerActiveRequest = new WaterConsumerActiveRequest();
        $mWaterConsumerActiveRequest->consumer_id               = $consumerDetails->id;
        $mWaterConsumerActiveRequest->apply_date                = Carbon::now();
        $mWaterConsumerActiveRequest->citizen_id                = $refRequest['citizenId'] ?? null;
        $mWaterConsumerActiveRequest->created_at                = Carbon::now();
        $mWaterConsumerActiveRequest->emp_details_id            = $refRequest['empId'] ?? null;
        $mWaterConsumerActiveRequest->ward_mstr_id              = $consumerDetails->ward_mstr_id;
        $mWaterConsumerActiveRequest->reason                    = $req['reason'] ?? null;
        $mWaterConsumerActiveRequest->amount                    = $refRequest['amount'];
        $mWaterConsumerActiveRequest->remarks                   = $req['remarks'];
        // $mWaterConsumerActiveRequest->apply_from                = $refRequest['applyFrom'];
        $mWaterConsumerActiveRequest->initiator                 = $refRequest['initiatorRoleId'];
        $mWaterConsumerActiveRequest->workflow_id               = $refRequest['ulbWorkflowId'];
        $mWaterConsumerActiveRequest->ulb_id                    = $refRequest['ulbId'];
        $mWaterConsumerActiveRequest->finisher                  = $refRequest['finisherRoleId'];
        // $mWaterConsumerActiveRequest->user_type                 = $refRequest['userType'];
        $mWaterConsumerActiveRequest->application_no            = $applicationNo;
        $mWaterConsumerActiveRequest->charge_catagory_id        = $refRequest['chargeCategoryId'];
        $mWaterConsumerActiveRequest->corresponding_mobile_no   = $req->mobileNo ?? null;
        $mWaterConsumerActiveRequest->save();
        return [
            "id" => $mWaterConsumerActiveRequest->id
        ];
    }

    /**
     * | Get Active appication by consumer Id
     */
    public function getRequestByConId($consumerId)
    {
        return WaterConsumerActiveRequest::where('consumer_id', $consumerId)
            ->where('status', 1)
            ->orderByDesc('id');
    }
    /**
     * | Get Active appication by consumer Id
     */
    public function getRequestByAppId($applicationId)
    {
        return WaterConsumerActiveRequest::where('id', $applicationId)
            ->where('status', 1)
            ->orderByDesc('id');
    }

    /**
     * | Get application by ID
     */
    public function getRequestById($id)
    {
        return WaterConsumerActiveRequest::select(
            'water_consumer_active_requests.*',
            'water_consumer_charge_categories.amount',
            'water_consumer_charge_categories.charge_category AS charge_category_name'
        )
            ->join('water_consumer_charge_categories', 'water_consumer_charge_categories.id', 'water_consumer_active_requests.charge_catagory_id')
            ->where('water_consumer_active_requests.status', 1)
            ->where('water_consumer_active_requests.id', $id);
    }

    /**
     * | Get active request by request id 
     */
    public function getActiveReqById($id)
    {
        return WaterConsumerActiveRequest::where('id', $id)
            ->where('status', 1);
    }




    /**
     * | Update the payment status and the current role for payment 
     * | After the payment is done the data are update in active table
     */
    public function updateDataForPayment($applicationId, $req)
    {
        WaterConsumerActiveRequest::where('id', $applicationId)
            ->where('status', 1)
            ->update($req);
    }

    /**
     * | Get the Application according to user details 
     */
    public function getApplicationByUser($userId)
    {
        return WaterConsumerActiveRequest::select(
            'water_consumer_active_requests.id',
            'water_consumer_active_requests.reason',
            'water_consumer_active_requests.remarks',
            'water_consumer_active_requests.amount',
            'water_consumer_active_requests.application_no',
            DB::raw('REPLACE(water_consumer_charges.charge_category, \'_\', \' \') as charge_category'),
            "water_consumer_active_requests.corresponding_address",
            "water_consumer_active_requests.corresponding_mobile_no",
            "water_consumers.consumer_no",
            "water_consumer_active_requests.ward_mstr_id",
            "water_consumer_active_requests.apply_date",
            "water_consumer_active_requests.payment_status",
            "ulb_ward_masters.ward_name",
            "ulb_name"
        )
            ->join('ulb_masters', 'ulb_masters.id', 'water_consumer_active_requests.ulb_id')
            ->join('ulb_ward_masters', 'ulb_ward_masters.id', 'water_consumer_active_requests.ward_mstr_id')
            ->join('water_consumer_charges', 'water_consumer_charges.related_id', 'water_consumer_active_requests.id')
            ->leftjoin('water_consumers', 'water_consumers.id', 'water_consumer_active_requests.consumer_id')
            ->where('water_consumer_active_requests.citizen_id', $userId)
            ->where('water_consumer_active_requests.status', 1)
            ->orderByDesc('water_consumer_active_requests.id');
    }

    //written by prity pandey
    public function getConsumerByApplication($applicationId)
    {
        return WaterConsumerActiveRequest::select(
            'water_consumer_active_requests.id',
            'water_consumer_active_requests.reason',
            'water_consumer_active_requests.remarks',
            'water_consumer_active_requests.amount',
            'water_consumer_active_requests.application_no',
            "water_consumer_active_requests.apply_date",
            "water_consumer_active_requests.workflow_id",
            "water_consumers.*"
        )
            ->leftjoin('water_consumers', 'water_consumers.id', 'water_consumer_active_requests.consumer_id')
            ->where('water_consumer_active_requests.id', $applicationId)
            ->where('water_consumer_active_requests.status', 1)
            ->orderByDesc('water_consumer_active_requests.id');
    }

    public function getApplicationById($applicationId)
    {
        return  WaterConsumerActiveRequest::where('id', $applicationId)
            ->where('status', 1);
    }

    public function updateParkedstatus($status, $applicationId)
    {
        $mWaterApplication = WaterConsumerActiveRequest::find($applicationId);
        switch ($status) {
            case (true):
                $mWaterApplication->parked = $status;
                break;

            case (false):
                $mWaterApplication->parked = $status;
                break;
        }
        $mWaterApplication->save();
    }

    public function deactivateUploadStatus($applicationId)
    {
        WaterConsumerActiveRequest::where('id', $applicationId)
            ->update([
                'doc_upload_status' => false
            ]);
    }

    public function activateUploadStatus($applicationId)
    {
        WaterConsumerActiveRequest::where('id', $applicationId)
            ->update([
                'doc_upload_status' => true
            ]);
    }

    public function searchApplication()
    {
        return WaterConsumerActiveRequest::select(
            'water_consumer_active_requests.id',
            'water_consumer_active_requests.reason',
            'water_consumer_active_requests.remarks',
            'water_consumer_active_requests.amount',
            'water_consumer_active_requests.application_no',
            "water_consumer_active_requests.apply_date",
            "water_consumer_active_requests.workflow_id",
            "water_consumers.id as consumer_id",
            "water_consumers.consumer_no",
            "water_consumers.address",
            "water_consumer_owners.applicant_name",
            "water_consumer_owners.mobile_no",
        )
            ->leftjoin('water_consumers', 'water_consumers.id', 'water_consumer_active_requests.consumer_id')
            ->leftjoin('water_consumer_owners', 'water_consumer_owners.consumer_id', 'water_consumers.id')
            ->where('water_consumer_active_requests.status', 1)
            ->orderByDesc('water_consumer_active_requests.id');
    }

    public function updateAppliVerifyStatus($applicationId)
    {
        WaterConsumerActiveRequest::where('id', $applicationId)
            ->update([
                'doc_verify_status' => true
            ]);
    }

    public function getConserDtls()
    {
        return $this->belongsTo(WaterConsumer::class, "consumer_id", "id", "id")->first();
    }

    //eoc








    ///////////////////////////////////////////////////////////////////////////////
    public function saveWaterConsumerActive($req, $consumerId, $meteReq, $refRequest, $applicationNo)
    {
        $mWaterConsumeActive = new WaterConsumerActiveRequest();
        $mWaterConsumeActive->id;
        $mWaterConsumeActive->ulb_id                   = $meteReq['ulbId'];
        $mWaterConsumeActive->application_no           = $applicationNo;
        $mWaterConsumeActive->consumer_id              = $consumerId;
        $mWaterConsumeActive->emp_details_id           = $refRequest['empId'] ?? null;
        $mWaterConsumeActive->citizen_id               = $refRequest["citizenId"] ?? null;
        $mWaterConsumeActive->apply_from               = $refRequest['applyFrom'];
        $mWaterConsumeActive->apply_date               = $meteReq['applydate'];
        $mWaterConsumeActive->amount                   = $meteReq['amount'];
        $mWaterConsumeActive->reason                   = $req->reason;
        $mWaterConsumeActive->remarks                  = $req->remarks;
        $mWaterConsumeActive->doc_verify_status        = $req->doc_verify_status;
        // $mWaterConsumeActive->payment_status           = $req->payment_status;
        $mWaterConsumeActive->charge_catagory_id       = $meteReq['chargeCategoryID'];
        $mWaterConsumeActive->corresponding_mobile_no  = $req->mobileNo;
        $mWaterConsumeActive->corresponding_address    = $req->address;
        $mWaterConsumeActive->ward_mstr_id             = $meteReq['wardmstrId'];
        $mWaterConsumeActive->initiator                = $refRequest['initiatorRoleId'];
        $mWaterConsumeActive->finisher                 = $refRequest['finisherRoleId'];
        $mWaterConsumeActive->user_type                = $refRequest['userType'];
        $mWaterConsumeActive->workflow_id              = $meteReq['ulbWorkflowId'];
        $mWaterConsumeActive->current_role             = $refRequest['initiatorRoleId'];
        $mWaterConsumeActive->save();
        return $mWaterConsumeActive;
    }

    /**
     * |------------------- Get the Application details by applicationNo -------------------|
     * | @param applicationNo
     * | @param connectionTypes 
     * | @return 
     */
    public function getDetailsByParameters($req, $applicationNo)
    {
        return WaterConsumerActiveRequest::select(
            'water_consumer_active_requests.id',
            'water_consumer_active_requests.application_no',
            'water_consumer_active_requests.ward_mstr_id',
            'water_consumers.address',
            'water_consumers.holding_no',
            'water_consumers.saf_no',
            'ulb_ward_masters.ward_name',
            DB::raw("string_agg(water_consumer_owners.applicant_name,',') as applicantName"),
            DB::raw("string_agg(water_consumers.mobile_no::VARCHAR,',') as mobileNo"),
            DB::raw("string_agg(water_consumers.guardian_name,',') as guardianName"),
        )
            ->join('water_consumers', 'water_consumers.id', '=', 'water_consumer_active_requests.consumer_id')
            ->leftJoin('ulb_ward_masters', 'ulb_ward_masters.id', '=', 'water_consumer_active_requests.ward_mstr_id')
            ->where('water_consumer_active_requests.status', true)
            ->where('water_consumer_active_requests.charge_category_id', 2)
            ->where('water_consumer_active_requests.application_no', 'LIKE', '%' . $applicationNo . '%')
            ->where('water_consumer_active_requests.ulb_id', authUser($req)->ulb_id)
            ->groupBy(
                'water_consumers.saf_no',
                'water_consumers.holding_no',
                'water_consumers.address',
                'water_consumer_active_requests.id',
                'water_applicants.application_id',
                'water_consumer_active_requests.application_no',
                'water_consumer_active_requests.ward_mstr_id',
                'ulb_ward_masters.ward_name'
            );
    }
}
