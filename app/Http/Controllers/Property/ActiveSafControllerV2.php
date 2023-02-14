<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Models\Property\PropActiveSaf;
use App\Models\Property\PropActiveSafsFloor;
use App\Models\Property\PropActiveSafsOwner;
use App\Models\Property\PropProperty;
use App\Models\Property\PropSafMemoDtl;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use function PHPUnit\Framework\isEmpty;

/**
 * | Created On-10-02-2023 
 * | Created By-Mrinal Kumar
 * */

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
            // 'owner' => 'array',
            // 'owner.*.ownerId' => 'required|numeric',
            // 'owner.*.ownerName' => 'required',
            // 'owner.*.guardianName' => 'required',
            // 'owner.*.relation' => 'required',
            // 'owner.*.mobileNo' => 'numeric|string|digits:10',
            // 'owner.*.aadhar' => 'numeric|string|digits:12|nullable',
            // 'owner.*.email' => 'email|nullable',
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


    /**
     * | Search Holding of user not logged in
     */
    public function searchHolding(Request $req)
    {
        $req->validate([
            "holdingNo" => "required",
            "ulbId" => "required"
        ]);
        try {
            $holdingNo = $req->holdingNo;
            $ulbId = $req->ulbId;

            $data = PropProperty::select(
                'prop_properties.id',
                'ulb_name as ulb',
                'prop_properties.holding_no',
                'prop_properties.new_holding_no',
                'ward_name',
                'prop_address',
                'prop_properties.status',
                DB::raw("string_agg(prop_owners.mobile_no::VARCHAR,',') as mobile_no"),
                DB::raw("string_agg(prop_owners.owner_name,',') as owner_name"),
            )
                ->join('ulb_ward_masters', 'ulb_ward_masters.id', 'prop_properties.ward_mstr_id')
                ->join('ulb_masters', 'ulb_masters.id', 'prop_properties.ulb_id')
                ->join('prop_owners', 'prop_owners.property_id', 'prop_properties.id')
                ->where('prop_properties.holding_no', $holdingNo)
                ->where('prop_properties.ulb_id', $ulbId)
                ->where('prop_properties.status', 1)
                ->groupby('prop_properties.id', 'ulb_ward_masters.ward_name', 'ulb_name')
                ->get();

            if (!$data) {
                $data = PropProperty::select(
                    'prop_properties.id',
                    'ulb_name as ulb',
                    'prop_properties.holding_no',
                    'prop_properties.new_holding_no',
                    'ward_name',
                    'prop_address',
                    'prop_properties.status',
                    DB::raw("string_agg(prop_owners.mobile_no::VARCHAR,',') as mobile_no"),
                    DB::raw("string_agg(prop_owners.owner_name,',') as owner_name"),
                )
                    ->join('ulb_ward_masters', 'ulb_ward_masters.id', 'prop_properties.ward_mstr_id')
                    ->join('ulb_masters', 'ulb_masters.id', 'prop_properties.ulb_id')
                    ->join('prop_owners', 'prop_owners.property_id', 'prop_properties.id')
                    ->where('prop_properties.new_holding_no', $holdingNo)
                    ->where('prop_properties.ulb_id', $ulbId)
                    ->where('prop_properties.status', 1)
                    ->groupby('prop_properties.id', 'ulb_ward_masters.ward_name', 'ulb_name')
                    ->get();
            }

            if ($data->isEmpty()) {
                throw new Exception("Enter Valid Holding No.");
            }

            return responseMsgs(true, "Holding Details", $data, 010124, 1.0, "308ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", 010124, 1.0, "308ms", "POST", $req->deviceId);
        }
    }
}