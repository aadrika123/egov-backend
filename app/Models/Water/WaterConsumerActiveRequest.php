<?php

namespace App\Models\Water;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaterConsumerActiveRequest extends Model
{
    use HasFactory;

    /**
     * | Save request details 
     */
    public function saveRequestDetails($req)
    {
        $mWaterConsumerActiveRequest = new WaterConsumerActiveRequest();
        $mWaterConsumerActiveRequest->consumer_id           = $req;
        $mWaterConsumerActiveRequest->apply_date            = $req;
        $mWaterConsumerActiveRequest->citizen_id            = $req;
        $mWaterConsumerActiveRequest->created_at            = $req;
        $mWaterConsumerActiveRequest->emp_details_id        = $req;
        $mWaterConsumerActiveRequest->ward_mstr_id          = $req;
        $mWaterConsumerActiveRequest->reason                = $req;
        $mWaterConsumerActiveRequest->amount                = $req;
        $mWaterConsumerActiveRequest->remarks               = $req;
        $mWaterConsumerActiveRequest->apply_from            = $req;
        $mWaterConsumerActiveRequest->current_role          = $req;
        $mWaterConsumerActiveRequest->initiator             = $req;
        $mWaterConsumerActiveRequest->workflow_id           = $req;
        $mWaterConsumerActiveRequest->ulb_id                = $req;
        $mWaterConsumerActiveRequest->finisher              = $req;
        $mWaterConsumerActiveRequest->last_role_id          = $req;
        $mWaterConsumerActiveRequest->user_type             = $req;
        $mWaterConsumerActiveRequest->application_no        = $req;
        $mWaterConsumerActiveRequest->charge_catagory_id    = $req;
        $mWaterConsumerActiveRequest->save();
    }
}
