<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Models\Property\PropActiveSaf;
use App\Models\Property\PropActiveSafsFloor;
use App\Models\Property\PropActiveSafsOwner;
use App\Models\Property\PropSafMemoDtl;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ActiveSafControllerV2 extends Controller
{

    /**
     * | Edit Applied Saf by SAF Id for BackOffice
     * | @param request $req
     */
    public function editCitizenSaf(Request $req)
    {
        $req->validate([
            'id' => 'required|numeric',
            'owner' => 'array',
            'owner.*.ownerId' => 'numeric',
            'owner.*.mobileNo' => 'numeric|string|digits:10',
            'owner.*.aadhar' => 'numeric|string|digits:12|nullable',
            'owner.*.email' => 'email|nullable',
        ]);

        try {
            $id = $req->id;
            $mPropActiveSaf = PropActiveSaf::find($id);
            $mPropSaf = new PropActiveSaf();
            $citizenId = authUser()->id;
            $mPropSafOwners = new PropActiveSafsOwner();
            $mPropSafFloors = new PropActiveSafsFloor();
            $mOwners = $req->owner;
            $mfloors = $req->floor;

            if ($mPropActiveSaf->payment_status == 1)
                throw new Exception("You cannot edit the application");

            if ($mPropActiveSaf->payment_status == 0) {
                DB::beginTransaction();

                $mPropSaf->safEdit($req, $mPropActiveSaf, $citizenId);

                collect($mOwners)->map(function ($owner) use ($mPropSafOwners, $citizenId, $id) {            // Updation of Owner Basic Details
                    if (isset($owner['ownerId']))
                        $mPropSafOwners->ownerEdit($owner, $citizenId);
                    else
                        $mPropSafOwners->addOwner($owner, $id, $citizenId);
                });

                collect($mfloors)->map(function ($floor) use ($mPropSafFloors, $citizenId, $id) {            // Updation of Owner Basic Details
                    if (isset($floor['floorId']))
                        $mPropSafFloors->editFloor($floor, $citizenId);
                    else
                        $mPropSafFloors->addFloor($floor, $id, $citizenId);
                });
                DB::commit();
            }

            return responseMsgs(true, "Successfully Updated the Data", "", 010124, 1.0, "308ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", 010124, 1.0, "308ms", "POST", $req->deviceId);
        }
    }

    /**
     * | Delete Citizen Saf
     */
    public function deleteCitizenSaf(Request $req)
    {
        try {
            $id = $req->id;
            $mPropActiveSaf = PropActiveSaf::find($id);
            $mPropSafOwner = PropActiveSafsOwner::where('saf_id', $id)->get();
            $mPropSafFloor =  PropActiveSafsFloor::where('saf_id', $id)->get();

            if ($mPropActiveSaf->payment_status == 1)
                throw new Exception("Payment Done Saf Cannot be deleted");

            if ($mPropActiveSaf->payment_status == 0) {
                $mPropActiveSaf->status = 0;
                $mPropActiveSaf->update();

                foreach ($mPropSafOwner as $mPropSafOwners) {
                    $mPropSafOwners->status = 0;
                    $mPropSafOwners->save();
                }
                foreach ($mPropSafFloor as $mPropSafFloors) {
                    $mPropSafFloors->status = 0;
                    $mPropSafFloors->save();
                }
            }
            return responseMsgs(true, "Saf Deleted", "", 010124, 1.0, "308ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", 010124, 1.0, "308ms", "POST", $req->deviceId);
        }
    }

    /**
     * | Generate memo receipt
     */
    public function memoReceipt(Request $req)
    {
        $req->validate([
            'memoId' => 'required|numeric'
        ]);
        try {
            $mPropSafMemoDtl = new PropSafMemoDtl();
            $details = $mPropSafMemoDtl->getMemoDtlsByMemoId($req->memoId);
            $details = collect($details)->first();
            return responseMsgs(true, "", remove_null($details), "011803", 1.0, "", "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "011803", 1.0, "", "POST", $req->deviceId);
        }
    }
}
