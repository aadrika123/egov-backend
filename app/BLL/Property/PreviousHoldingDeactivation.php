<?php

namespace App\BLL\Property;

use App\Models\Property\PropProperty;
use Illuminate\Support\Facades\Config;

/**
 * | Created On-03-04-2023 
 * | Created By-Anshu Kumar
 * | Created for = The BLL for the Previous holding Deactivation application
 */
class PreviousHoldingDeactivation
{
    private $_mPropProperty;
    public function __construct()
    {
        $this->_mPropProperty = new PropProperty();
    }
    /**
     * | @param safDetails Active Saf Details
     */
    public function deactivatePreviousHoldings($safDetails)
    {
        $reassessmentTypes = Config::get('PropertyConstaint.REASSESSMENT_TYPES');
        $assessmentType = $safDetails->assessment_type;
        // Deactivate for the kind of properties reassessment,mutation,amalgamation,bifurcation
        if (in_array($assessmentType, $reassessmentTypes)) {
            $explodedPreviousHoldingIds = explode(',', $safDetails->previous_holding_id);
            $this->_mPropProperty->deactivateHoldingByIds($explodedPreviousHoldingIds);     // Deactivation of Holding
        }
    }
}
