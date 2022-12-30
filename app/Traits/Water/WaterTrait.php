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
            'water_applications.application_no',
            'a.id as owner_id',
            'water_applicants.applicant_name as owner_name',
            'water_applications.ward_id',
            'u.ward_name as ward_no',
            'a.prop_type_mstr_id',
            'water_applications.workflow_id',
            'water_applications.current_role as role_id',
            'water_applications.apply_date',
        )
            ->leftJoin('prop_properties as a', 'a.id', '=', 'water_applications.prop_id')
            ->join('ulb_ward_masters as u', 'u.id', '=', 'water_applications.ward_id')
            ->join('water_applicants','water_applicants.application_id','=','water_applications.id')
            ->where('water_applications.status', 1)
            ->where('water_applications.ulb_id', $ulbId);
    }
}
