<?php

namespace App\Models\Water;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaterApprovalApplicationDetail extends Model
{
    use HasFactory;

    /**
     * |------------------------- Get the Approved Applecaton Details ---------------------------|
     * | @param request
     */
    public function getApprovedApplications()
    {
        $approvedWater = WaterApprovalApplicationDetail::orderByDesc('id');
        return $approvedWater;
    }


    /**
     * |
     */
    public function getApplicationRelatedDetails()
    {
        return WaterApprovalApplicationDetail::join('ulb_masters', 'ulb_masters.id', '=', 'water_approval_application_details.ulb_id')
            ->join('ulb_ward_masters', 'ulb_ward_masters.id', '=', 'water_approval_application_details.ward_id')
            ->orderByDesc('id');
    }
}
