<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Models\Property\PropActiveConcession;
use App\Models\Property\PropActiveDeactivationRequest;
use App\Models\Property\PropActiveHarvesting;
use App\Models\Property\PropActiveObjection;
use App\Models\Property\PropActiveSaf;
use App\Models\Property\PropActiveSafsOwner;
use App\Models\Property\PropOwner;
use App\Models\Property\PropProperty;
use App\Repository\Property\Interfaces\iPropertyDetailsRepo;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PropertyDetailsController extends Controller
{
    /**
     * | Created On-26-11-2022 
     * | Modified by-Anshu Kumar On-(17/01/2023)
     * --------------------------------------------------------------------------------------
     * | Controller regarding with Propery Module (Property Details)
     * | Status-Open
     */

    // Construction 
    private $propertyDetails;
    public function __construct(iPropertyDetailsRepo $propertyDetails)
    {
        $this->propertyDetails = $propertyDetails;
    }

    // get details of the property filtering with the provided details
    public function applicationsListByKey(Request $request)
    {
        try {
            $request->validate([
                'filteredBy' => 'required',
                'applicationNo' => 'required'
            ]);

            $key = $request->filteredBy;
            $applicationNo = $request->applicationNo;
            switch ($key) {
                case ("saf"):
                    $mPropActiveSaf = new PropActiveSaf();
                    $mPropActiveSafOwners = new PropActiveSafsOwner();
                    $application = collect($mPropActiveSaf->getSafDtlsBySafNo($applicationNo));
                    $owners = collect($mPropActiveSafOwners->getOwnerDtlsBySafId($application['id']));
                    $details = $application->merge($owners);
                    break;
                case ("concession"):
                    $mPropConcessions = new PropActiveConcession();
                    $details = $mPropConcessions->getDtlsByConcessionNo($applicationNo);
                    break;
                case ("objection"):
                    $mPropObjections = new PropActiveObjection();
                    $mPropOwners = new PropOwner();
                    $application = collect($mPropObjections->getObjByObjNo($applicationNo));
                    $owners = collect($mPropOwners->getOwnerByPropId($application['property_id']));
                    $details = $application->merge($owners);
                    break;
                case ("harvesting"):
                    $mPropHarvesting = new PropActiveHarvesting();
                    $mPropOwners = new PropOwner();
                    $application = collect($mPropHarvesting->getDtlsByHarvestingNo($applicationNo));
                    $owners = collect($mPropOwners->getOwnerByPropId($application['property_id']));
                    $details = $application->merge($owners);
                    break;
                case ('holdingDeactivation'):
                    $mPropActiveDeactivationRequest = new PropActiveDeactivationRequest();
                    $mPropOwners = new PropOwner();
                    $application = collect($mPropActiveDeactivationRequest->getDeactivationApplication($applicationNo));
                    $application['application_no'] = "dummy";
                    $refowners = collect($mPropOwners->getOwnerByPropId($application['property_id']));
                    $owners = collect($refowners)->map(function ($value) {
                        $returnVal['ownerName'] = $value['ownerName'];
                        $returnVal['mobileNo'] = $value['mobileNo'];
                        return $returnVal;
                    })->first();
                    $details = $application->merge($owners);
                    break;
            }
            return responseMsgs(true, "Application Details", [remove_null($details)], "010501", "1.0", "", "POST", $request->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "010501", "1.0", "", "POST", $request->deviceId ?? "");
        }
    }


    // get details of the diff operation in property
    public function propertyListByKey(Request $request)
    {
        $request->validate([
            'filteredBy' => "required",
            'parameter' => "required"
        ]);

        try {
            $key = $request->filteredBy;
            $parameter = $request->parameter;
            $mPropProperty = new PropProperty();
            switch ($key) {
                case ("holdingNo"):
                    $data = PropProperty::select(
                        'prop_properties.id',
                        'prop_properties.holding_no',
                        'prop_properties.new_holding_no',
                        'ward_name',
                        'prop_address',
                        'prop_properties.status',
                        DB::raw("string_agg(prop_owners.mobile_no::VARCHAR,',') as mobile_no"),
                        DB::raw("string_agg(prop_owners.owner_name,',') as owner_name"),
                    )
                        ->join('ulb_ward_masters', 'ulb_ward_masters.id', 'prop_properties.ward_mstr_id')
                        ->join('prop_owners', 'prop_owners.property_id', 'prop_properties.id')
                        ->where('prop_properties.holding_no', 'LIKE', '%' . $parameter . '%')
                        ->orWhere('prop_properties.new_holding_no', 'LIKE', '%' . $parameter . '%')
                        ->groupby('prop_properties.id', 'ulb_ward_masters.ward_name')
                        ->get();
                    break;

                case ("ownerName"):
                    $data = PropProperty::select(
                        'prop_properties.id',
                        'prop_properties.holding_no',
                        'prop_properties.new_holding_no',
                        'ward_name',
                        'prop_address',
                        DB::raw("string_agg(prop_owners.mobile_no::VARCHAR,',') as mobile_no"),
                        DB::raw("string_agg(prop_owners.owner_name,',') as owner_name"),
                    )
                        ->join('ulb_ward_masters', 'ulb_ward_masters.id', 'prop_properties.ward_mstr_id')
                        ->join('prop_owners', 'prop_owners.property_id', 'prop_properties.id')
                        ->where('prop_owners.owner_name', 'LIKE', '%' . strtoupper($parameter) . '%')
                        ->groupby('prop_properties.id', 'ulb_ward_masters.ward_name')
                        ->get();
                    break;

                case ("address"):
                    $data = PropProperty::select(
                        'prop_properties.id',
                        'prop_properties.holding_no',
                        'prop_properties.new_holding_no',
                        'ward_name',
                        'prop_address',
                        DB::raw("string_agg(prop_owners.mobile_no::VARCHAR,',') as mobile_no"),
                        DB::raw("string_agg(prop_owners.owner_name,',') as owner_name"),
                    )
                        ->join('prop_owners', 'prop_owners.property_id', 'prop_properties.id')
                        ->join('ulb_ward_masters', 'ulb_ward_masters.id', 'prop_properties.ward_mstr_id')
                        ->where('prop_properties.prop_address', 'LIKE', '%' . strtoupper($parameter) . '%')
                        ->groupby('prop_properties.id', 'ulb_ward_masters.ward_name')
                        ->get();
                    break;

                case ("mobileNo"):
                    $data = PropProperty::select(
                        'prop_properties.id',
                        'prop_properties.holding_no',
                        'prop_properties.new_holding_no',
                        DB::raw("string_agg(prop_owners.mobile_no::VARCHAR,',') as mobile_no"),
                        DB::raw("string_agg(prop_owners.owner_name,',') as owner_name"),
                        'ward_name',
                        'prop_address'
                    )
                        ->join('prop_owners', 'prop_owners.property_id', 'prop_properties.id')
                        ->join('ulb_ward_masters', 'ulb_ward_masters.id', 'prop_properties.ward_mstr_id')
                        ->where('prop_owners.mobile_no', 'LIKE', '%' . $parameter . '%')
                        ->groupby('prop_properties.id', 'ulb_ward_masters.ward_name')
                        ->get();
                    break;
            }
            return responseMsgs(true, "Application Details", remove_null($data), "010501", "1.0", "", "POST", $request->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "010502", "1.0", "", "POST", $request->deviceId ?? "");
        }
    }

    // All saf no from Active Saf no
    /**
     | ----------flag
     */
    public function getListOfSaf()
    {
        $getSaf = new PropActiveSaf();
        return $getSaf->allNonHoldingSaf();
    }

    // All the listing of the Details of Applications According to the respective Id
    public function getUserDetails(Request $request)
    {
        return $this->propertyDetails->getUserDetails($request);
    }
}
