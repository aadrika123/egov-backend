<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropActiveWaiver extends Model
{
    use HasFactory;

    public function addWaiver($request)
    {
        $data = new PropActiveWaiver();
        $data->is_bill_waiver = $request->isBillWaiver;
        $data->is_one_percent_penalty = $request->isOnePercentPenalty;
        $data->is_rwh_penalty = $request->isRwhPenalty;
        $data->is_lateassessment_penalty = $request->isLateassessmentPenalty;
        $data->bill_amount = $request->billAmount;
        $data->bill_waiver_amount = $request->billWaiverAmount;
        $data->one_percent_penalty_amount = $request->onePercentPenaltyAmount;
        $data->one_percent_penalty_waiver_amount = $request->onePercentPenaltyWaiverAmount;
        $data->rwh_amount = $request->rwhAmount;
        $data->rwh_waiver_amount = $request->rwhWaiverAmount;
        $data->lateassessment_penalty_amount = $request->lateAssessmentPenaltyAmount;
        $data->lateassessment_penalty_waiver_amount = $request->lateAssessmentPenaltyWaiverAmount;
        $data->waiver_startdate = $request->waiverStartdate;
        $data->waiver_enddate = $request->waiverEnddate;
        $data->bill_id = $request->billId;
        $data->property_id = $request->propertyId;
        $data->saf_id = $request->safId;
        $data->waiver_document = $request->waiverDocument;
        $data->description = $request->description;
        $data->user_id = $request->userId;
        $data->workflow_id = $request->workflowId;
        $data->current_role = $request->currentRole;
        $data->save();
    }
}
