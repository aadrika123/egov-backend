<?php

namespace App\Models\Water;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaterApprovalApplicationDetail extends Model
{
    use HasFactory;

    /**
     * |------------------------- Get the Approved Applecaton Details ---------------------------|
     * | @param request
     */
    public function getApprovedApplications($request)
    {
        if ($request->consumerNo) {
            $approvedWater = WaterApprovalApplicationDetail::where('consumer_no', $request->consumerNo)
                ->first();
                return $approvedWater;
        }
        $approvedWater = WaterApprovalApplicationDetail::orderByDesc('id')
            ->get();
        return $approvedWater;
    }
}
