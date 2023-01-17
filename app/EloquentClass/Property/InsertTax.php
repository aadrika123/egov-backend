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
    public function insertTax($safId, $ulbId, $safTaxes)
    {
        $safDemand = collect($safTaxes->original['data']['details']);
        $details = $this->generateSafDemand($safDemand);

        foreach ($details as $detail) {
            $safDemand = new PropSafsDemand();
            $reqs = [
                'saf_id' => $safId,
                'arv' => $detail['arv'],
                'water_tax' => $detail['waterTax'],
                'education_cess' => $detail['educationTax'],
                'health_cess' => $detail['healthCess'],
                'latrine_tax' => $detail['latrineTax'],
                'additional_tax' => $detail['rwhPenalty'],
                'holding_tax' => $detail['holdingTax'],
                'amount' => $detail['totalTax'],
                'fyear' => $detail['quarterYear'],
                'qtr' => $detail['qtr'],
                'due_date' => $detail['dueDate'],
                'user_id' => authUser()->id,
                'ulb_id' => $ulbId,
            ];
            $safDemand->postDemands($reqs);
        }
    }
}
