<?php

namespace App\Traits;

use App\Models\UlbWardMaster;

/**
 * | Created On-19-08-2022 
 * | Created By-Anshu Kumar
 * ---------------------------------------------------------------------------
 * | Trait Used For the Ward operations
 */
trait Ward
{
    // Store ulb ward
    public function store($ulb_ward, $request)
    {
        $ulb_ward->ulb_id = $request->ulbID;
        $ulb_ward->ward_name = $request->wardName;
        $ulb_ward->old_ward_name = $request->oldWardName;
    }

    // Check Existance for Ulb and Ward
    public function checkExistance($request)
    {
        return UlbWardMaster::where('ulb_id', $request->ulbID)
            ->where('ward_name', $request->wardName)
            ->first();
    }

    // Fetch Ulb Ward
    public function fetchUlbWard($ulb_ward)
    {
        return $ulb_ward
            ->select('ulb_ward_masters.*', 'ulb_masters.ulb_name')
            ->join("ulb_masters", "ulb_masters.id", "=", "ulb_ward_masters.ulb_id");
    }

    // //////////////////////////////////////////////////////////////////////////////
    // Save Ward Users
    public function savingWardUser($ward_user, $request, $ulb_wards)
    {
        $ward_user->user_id = $request->userID;
        $ward_user->ulb_ward_id = $ulb_wards;
        $ward_user->is_admin = $request->isAdmin;
    }

    // Query for Get Ward Users By WARD User ID
    public function qWardUser()
    {
        $query = "SELECT 
                    w.id,
                    w.user_id,
                    u.user_name,
                    w.ulb_ward_id,
                    w.is_admin,
                    uwm.ulb_id,
                    um.ulb_name,
                    uwm.ward_name,
                    uwm.old_ward_name
                    FROM ward_users w
                    INNER JOIN users u ON u.id=w.user_id
                    INNER JOIN ulb_ward_masters uwm ON uwm.id=w.ulb_ward_id
                    INNER JOIN ulb_masters um ON um.id=uwm.ulb_id";
        return $query;
    }
}
