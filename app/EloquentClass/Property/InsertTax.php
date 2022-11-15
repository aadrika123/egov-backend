<?php

namespace App\EloquentClass\Property;

use App\Models\Property\SafsDemand;

class InsertTax
{
    public function insertTax($safId, $userId, $demand)
    {
        $details = collect($demand->original['data']['details'])->toArray();

        foreach ($details as $detail) {
            $safDemand = new SafsDemand();
            $safDemand->saf_id = $safId;
            $safDemand->qtr = $detail['qtr'];
            $safDemand->holding_tax = $detail['holdingTax'];
            $safDemand->water_tax = $detail['waterTax'];
            $safDemand->education_cess = $detail['educationTax'];
            $safDemand->health_cess = $detail['healthTax'];
            $safDemand->latrine_tax = $detail['latrineTax'];
            $safDemand->additional_tax = 0;
            $safDemand->amount = $detail['totalTax'];
            $safDemand->balance = $detail['totalTax'];
            $safDemand->paid_status = 0;
            $safDemand->arv = $detail['arv'];
            $safDemand->rwh_penalty = $detail['rwhPenalty'];
            $safDemand->fyear = $detail['quarterYear'];
            $safDemand->due_date = $detail['dueDate'];
            $safDemand->status = 1;
            $safDemand->user_id = $userId;
            $safDemand->save();
        }
    }
}
