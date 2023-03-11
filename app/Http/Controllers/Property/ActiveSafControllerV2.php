<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Models\Property\PropActiveSaf;
use App\Models\Property\PropActiveSafsFloor;
use App\Models\Property\PropActiveSafsOwner;
use App\Models\Property\PropApartmentDtl;
use App\Models\Property\PropDemand;
use App\Models\Property\PropProperty;
use App\Models\Property\PropSafMemoDtl;
use App\Models\Property\PropSafsDemand;
use App\Models\Workflows\WfRoleusermap;
use App\Models\Workflows\WfWardUser;
use App\Models\Workflows\WfWorkflowrolemap;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use App\Repository\Property\Interfaces\iSafRepository;


/**
 * | Created On-10-02-2023 
 * | Created By-Mrinal Kumar
 * */

class ActiveSafControllerV2 extends Controller
{

    /**
     * | Edit Applied Saf by SAF Id for BackOffice
     * | @param request $req
     * | Serial 01
     */
    public function editCitizenSaf(Request $req)
    {
        $req->validate([
            'id' => 'required|numeric'
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
     * | Serial 02
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
     * | Serial 03
     */
    public function memoReceipt(Request $req)
    {
        $req->validate([
            'memoId' => 'required|numeric'
        ]);
        try {
            $mPropSafMemoDtl = new PropSafMemoDtl();
            $mPropDemands = new PropDemand();
            $mPropSafDemands = new PropSafsDemand();

            $details = $mPropSafMemoDtl->getMemoDtlsByMemoId($req->memoId);
            if (collect($details)->isEmpty())
                $details = $mPropSafMemoDtl->getPropMemoDtlsByMemoId($req->memoId);

            if (collect($details)->isEmpty())
                throw new Exception("Memo Details Not Available");
            $details = collect($details)->first();

            // Fam Receipt
            if ($details->memo_type == 'FAM') {
                $memoFyear = $details->from_fyear;
                $propId = $details->prop_id;
                $safId = $details->saf_id;
                $propDemands = $mPropDemands->getFullDemandsByPropId($propId);
                $safDemands = $mPropSafDemands->getFullDemandsBySafId($safId);

                $holdingTax2Perc = $propDemands->where('fyear', $memoFyear);
                if (collect($holdingTax2Perc)->isEmpty())
                    $holdingTax2Perc = $safDemands->where('fyear', $memoFyear);

                if (collect($holdingTax2Perc)->isEmpty())
                    throw new Exception("Demand Not Available");

                $groupedPropTaxDiff = $propDemands->where('due_date', '>=', $holdingTax2Perc->first()->due_date)->values();
                $groupedSafTaxDiff = $safDemands->where('due_date', '>=', $holdingTax2Perc->first()->due_date)->values();
                $merged = $groupedSafTaxDiff->merge($groupedPropTaxDiff);
                $taxDiffs = $merged->groupBy('arv');
                $qtrParam = 5;                                                                                                      // For Calculating Qtr
                $holdingTaxes = collect($taxDiffs)->map(function ($taxDiff) use ($qtrParam) {
                    $totalFirstQtrs = $qtrParam - $taxDiff->first()->qtr;
                    $selfAssessAmt = ($taxDiff->first()->amount - $taxDiff->first()->additional_tax) * $totalFirstQtrs;               // Holding Tax Amount without penalty
                    $ulbAssessAmt = ($taxDiff->first()->amount - $taxDiff->first()->additional_tax) * $totalFirstQtrs;                // Holding Tax Amount Without Panalty
                    $diffAmt = $ulbAssessAmt - $selfAssessAmt;
                    return [
                        'Particulars' => $taxDiff->first()->fyear == '2016-2017' ? "Holding Tax @ 2%" : "Holding Tax @ 0.075% or 0.15% or 0.2%",
                        'quarterFinancialYear' => 'Quarter' . $taxDiff->first()->qtr . '/' . $taxDiff->first()->fyear,
                        'basedOnSelfAssess' => roundFigure($selfAssessAmt),
                        'basedOnUlbCalc' => roundFigure($ulbAssessAmt),
                        'diffAmt' => roundFigure($diffAmt)
                    ];
                });
                $holdingTaxes = $holdingTaxes->values();

                $total = collect([
                    'Particulars' => 'Total Amount',
                    'quarterFinancialYear' => "",
                    'basedOnSelfAssess' => roundFigure($holdingTaxes->sum('basedOnSelfAssess')),
                    'basedOnUlbCalc' => roundFigure($holdingTaxes->sum('basedOnUlbCalc')),
                    'diffAmt' => roundFigure($holdingTaxes->sum('diffAmt')),
                ]);
                $details->taxTable = $holdingTaxes->merge([$total])->values();
            }
            return responseMsgs(true, "", remove_null($details), "011803", 1.0, "", "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "011803", 1.0, "", "POST", $req->deviceId);
        }
    }

    /**
     * | Search Holding of user not logged in
     * | Serial 04
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

            if ($data->isNotEmpty()) {
                return responseMsgs(true, "Holding Details", $data, 010124, 1.0, "308ms", "POST", $req->deviceId);
            }

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

            if ($data->isEmpty()) {
                throw new Exception("Enter Valid Holding No.");
            }

            return responseMsgs(true, "Holding Details", $data, 010124, 1.0, "308ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", 010124, 1.0, "308ms", "POST", $req->deviceId);
        }
    }

    /**
     * | Serial 05
     */
    public function verifyHoldingNo(Request $req)
    {
        try {
            $req->validate([
                'holdingNo' => 'required',
                'ulbId' => 'required',
            ]);
            $mPropProperty = new PropProperty();
            $data = $mPropProperty->verifyHolding($req);

            if (!isset($data)) {
                throw new Exception("Enter Valid Holding No.");
            }

            return responseMsgs(true, "Holding Exist", '', 010124, 1.0, "308ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", 010124, 1.0, "308ms", "POST", $req->deviceId);
        }
    }

    /**
     * | Get Apartment List by Ward Id
     */
    public function getAptList(Request $req)
    {
        try {
            $req->validate([
                'wardMstrId' => 'required',
                'ulbId' => 'required',
            ]);
            $mPropApartmentDtl = new PropApartmentDtl();
            $data = $mPropApartmentDtl->apartmentList($req);

            if (($data->isEmpty())) {
                throw new Exception("Enter Valid wardMstrId");
            }

            return responseMsgs(true, "Apartment List", $data, 010124, 1.0, "308ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", 010124, 1.0, "308ms", "POST", $req->deviceId);
        }
    }

    /**
     * | Get Pending GeoTaggings
     */
    public function pendingGeoTaggingList(Request $req, iSafRepository $iSafRepo)
    {
        try {
            $agencyTcRole = Config::get('PropertyConstaint.SAF-LABEL.TC');
            $mWfWardUser = new WfWardUser();
            $mWorkflowRoleMap = new WfWorkflowrolemap();
            $userId = auth()->user()->id;
            $ulbId = auth()->user()->ulb_id;

            $workflowIds = $mWorkflowRoleMap->getWfByRoleId([$agencyTcRole])->pluck('workflow_id');
            $readWards = $mWfWardUser->getWardsByUserId($userId);                       // Model () to get Occupied Wards of Current User
            $occupiedWards = collect($readWards)->pluck('ward_id');
            $safInbox = $iSafRepo->getSaf($workflowIds)                                 // Repository function to get SAF Details
                ->where('parked', false)
                ->where('is_geo_tagged', false)
                ->where('prop_active_safs.ulb_id', $ulbId)
                ->where('prop_active_safs.status', 1)
                ->where('current_role', $agencyTcRole)
                ->whereIn('ward_mstr_id', $occupiedWards)
                ->orderByDesc('id')
                ->groupBy('prop_active_safs.id', 'p.property_type', 'ward.ward_name')
                ->get();

            return responseMsgs(true, "Data Fetched", remove_null($safInbox->values()), "011806", "1.0", "", "POST", "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "011806", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }
}
