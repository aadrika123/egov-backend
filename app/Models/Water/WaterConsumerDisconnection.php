<?php

namespace App\Models\Water;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaterConsumerDisconnection extends Model
{
    use HasFactory;

    /**
     * | Save the Consumer deactivated details 
     * | @param req
     */
    public function saveDeactivationDetails($request, $currentDate,$document,$consumerDetails)
    {
        $mWaterDisconnection = new WaterConsumerDisconnection();
        $mWaterDisconnection->consumer_id           = $request->consumerId;
        $mWaterDisconnection->disconnection_date    = $currentDate;
        $mWaterDisconnection->document_path         = $document['document'];
        $mWaterDisconnection->relative_path         = $document['relaivePath'];
        $mWaterDisconnection->emp_role_id           = $request->roleId;
        $mWaterDisconnection->emp_details_id        = authUser()->id;
        $mWaterDisconnection->ward_mstr_id          = $consumerDetails->ward_mstr_id;
        $mWaterDisconnection->reason                = $request->reason;
        $mWaterDisconnection->amount                = $request->amount;
        $mWaterDisconnection->remarks               = $request->remarks;
        $mWaterDisconnection->save();
    }
}
