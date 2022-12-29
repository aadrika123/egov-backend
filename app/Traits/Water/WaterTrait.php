<?php

namespace App\Traits\Water;

use App\Models\Water\WaterApplication;

/**
 * Created By-Sam kerketta
 * Created On- 27-06-2022
 * Creation Purpose- For Common function or water module
 */

trait WaterTrait
{

    /**
     * |----------------------------- Get Water Application List For the Workflow ---------------------------|
     * | @param ulbId
     * | Rating : 
     * | Opertation : serch the application for the respective ulb/workflow
     */
    public function getWaterApplicatioList($ulbId)
    {
        return WaterApplication::select(
            'water_applications.id',
            'a.id as owner_id',
            'a.applicant_name as owner_name',
            'a.ward_mstr_id',
            'u.ward_name as ward_no',
            'a.prop_type_mstr_id',
            'p.property_type',
            'water_applications.workflow_id',
            'water_applications.current_role as role_id',
            'water_applications.apply_date',
        )
            ->leftJoin('prop_properties as a', 'a.id', '=', 'water_applications.prop_id')
            ->join('ref_prop_types as p', 'p.id', '=', 'a.prop_type_mstr_id')
            ->join('ulb_ward_masters as u', 'u.id', '=', 'a.ward_mstr_id')
            ->where('water_applications.status', 1)
            ->where('water_applications.ulb_id', $ulbId);
    }
}
