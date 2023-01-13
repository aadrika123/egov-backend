<?php

namespace App\EloquentClass\Property;

use App\Models\Property\PropSafsDemand;
use App\Traits\Property\SAF;

class InsertTax
{
    use SAF;

    /**
     * | Save Generated Demand Tax
     * | @param safId
     * | @param userId 
     * | @param safTaxes
     */
    public function insertTax($safId, $userId, $safTaxes)
    {
        $safDemand = collect($safTaxes->original['data']['details']);
        $details = $this->generateSafDemand($safDemand);

        foreach ($details as $detail) {
            $safDemand = new PropSafsDemand();
            $safDemand->qtr = $detail['qtr'];
            $this->tSaveSafDemand($safDemand, $detail, $safId);     // <-------- Trait to Save Fields
            $safDemand->paid_status = 0;
            $safDemand->fyear = $detail['quarterYear'];
            $safDemand->due_date = $detail['dueDate'];
            $safDemand->status = 1;
            $safDemand->user_id = $userId;
            $safDemand->save();
        }
    }
}
