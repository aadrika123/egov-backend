<?php

namespace App\Models\Water;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaterConsumerActiveRequest extends Model
{
    use HasFactory;

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
        $mWaterConsumerActiveRequest->apply_from                = $refRequest['applyFrom'];
        $mWaterConsumerActiveRequest->initiator                 = $refRequest['initiatorRoleId'];
        $mWaterConsumerActiveRequest->workflow_id               = $refRequest['ulbWorkflowId'];
        $mWaterConsumerActiveRequest->ulb_id                    = $req['ulbId'];
        $mWaterConsumerActiveRequest->finisher                  = $refRequest['finisherRoleId'];
        $mWaterConsumerActiveRequest->user_type                 = $refRequest['userType'];
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
}
