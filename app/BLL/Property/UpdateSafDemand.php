<?php

namespace App\BLL\Property;

use App\Models\Property\PropSafsDemand;

/**
 * | For the updation of Saf Generated Demand
 */

class UpdateSafDemand
{
    private $_mPropSafDemand;

    public function __construct()
    {
        $this->_mPropSafDemand = new PropSafsDemand();
    }

    /**
     * | Update Demand
     */
    public function updateDemand(array $demands): void
    {
        foreach ($demands as $demand) {
            $propDemand = $this->_mPropSafDemand::findOrFail($demand['id']);
            $propDemand->update([
                'balance' => 0,
                'paid_status' => 1
            ]);
        }
    }
}
