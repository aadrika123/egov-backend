<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Models\Property\PropActiveConcession;
use App\Models\Property\PropActiveDeactivationRequest;
use App\Models\Property\PropActiveGbOfficer;
use App\Models\Property\PropActiveHarvesting;
use App\Models\Property\PropActiveObjection;
use App\Models\Property\PropActiveSaf;
use App\Models\Property\PropActiveSafsOwner;
use App\Models\Property\PropConcession;
use App\Models\Property\PropDemand;
use App\Models\Property\PropGbofficer;
use App\Models\Property\PropHarvesting;
use App\Models\Property\PropObjection;
use App\Models\Property\PropOwner;
use App\Models\Property\PropProperty;
use App\Models\Property\PropSaf;
use App\Models\Property\PropSafsOwner;
use App\Models\Workflows\WfRoleusermap;
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
                'searchBy' => 'required',
                'filteredBy' => 'required',
                'value' => 'required',
            ]);

            $mPropActiveSaf = new PropActiveSaf();
            $mPropActiveSafOwners = new PropActiveSafsOwner();
            $mPropActiveGbOfficer = new PropActiveGbOfficer();
            $mPropActiveConcessions = new PropActiveConcession();
            $mPropActiveObjection = new PropActiveObjection();
            $mPropActiveHarvesting = new PropActiveHarvesting();
            $mPropActiveDeactivationRequest = new PropActiveDeactivationRequest();
            $mPropSafs = new PropSaf();
            $mPropOwners = new PropOwner();
            $mPropSafOwners = new PropSafsOwner();
            $mPropGbofficer = new PropGbofficer();
            $mPropConcessions = new PropConcession();
            $mPropObjection = new PropObjection();
            $mPropHarvesting = new PropHarvesting();
            $searchBy = $request->searchBy;
            $key = $request->filteredBy;
            // $applicationNo = $request->applicationNo;

            //search by application no.
            if ($searchBy == 'applicationNo') {
                $applicationNo = $request->value;
                switch ($key) {

                    case ("saf"):
                        $application = collect($mPropActiveSaf->getSafDtlsBySafNo($applicationNo));
                        if ($application->isEmpty()) {
                            $application = collect($mPropSafs->getSafDtlsBySafNo($applicationNo));
                            $owners = collect($mPropSafOwners->getOwnerDtlsBySafId1($application['id']));
                            $details[] = $application->merge($owners);
                            break;
                        }
                        $owners = collect($mPropActiveSafOwners->getOwnerDtlsBySafId1($application['id']));
                        $details[] = $application->merge($owners);
                        break;

                    case ("gbsaf"):
                        $application = collect($mPropActiveSaf->getGbSafDtlsBySafNo($applicationNo));
                        if ($application->isEmpty()) {
                            $application = collect($mPropSafs->getGbSafDtlsBySafNo($applicationNo));
                            $owners = collect($mPropGbofficer->getOfficerBySafId($application['id']));
                            $details[] = $application->merge($owners);
                            break;
                        }
                        $owners = collect($mPropActiveGbOfficer->getOfficerBySafId($application['id']));
                        $details[] = $application->merge($owners);
                        break;

                    case ("concession"):
                        $details[] = $mPropActiveConcessions->getDtlsByConcessionNo($applicationNo);
                        if (!$details)
                            $details[] =  $mPropConcessions->getDtlsByConcessionNo($applicationNo);
                        break;

                    case ("objection"):
                        $application = collect($mPropActiveObjection->getObjByObjNo($applicationNo));
                        if ($application->isEmpty())
                            $application = collect($mPropObjection->getObjByObjNo($applicationNo));
                        $owners = collect($mPropOwners->getfirstOwner($application['property_id']));
                        $details[] = $application->merge($owners);
                        break;

                    case ("harvesting"):
                        $application = collect($mPropActiveHarvesting->getDtlsByHarvestingNo($applicationNo));
                        if ($application->isEmpty())
                            $application = collect($mPropHarvesting->getDtlsByHarvestingNo($applicationNo));
                        $owners = collect($mPropOwners->getfirstOwner($application['property_id']));
                        $details[] = $application->merge($owners);
                        break;

                    case ('holdingDeactivation'):
                        $application = collect($mPropActiveDeactivationRequest->getDeactivationApplication($applicationNo));
                        $application['application_no'] = "dummy";
                        $refowners = collect($mPropOwners->getOwnerByPropId($application['property_id']));
                        $owners = collect($refowners)->map(function ($value) {
                            $returnVal['ownerName'] = $value['ownerName'];
                            $returnVal['mobileNo'] = $value['mobileNo'];
                            return $returnVal;
                        })->first();
                        $details[] = $application->merge($owners);
                        break;
                }
            }

            // search by name
            if ($searchBy == 'name') {
                $ownerName = $request->value;
                switch ($key) {
                    case ("saf"):
                        $propSaf  = $mPropSafs->searchSafs()
                            ->where('so.owner_name', 'LIKE', '%' . strtoupper($ownerName) . '%')
                            ->groupby('prop_safs.id', 'ulb_ward_masters.ward_name', 'wf_roles.role_name');

                        $activeSaf = $mPropActiveSaf->searchSafs()
                            ->where('so.owner_name', 'LIKE', '%' . strtoupper($ownerName) . '%')
                            ->groupby('prop_active_safs.id', 'ulb_ward_masters.ward_name', 'wf_roles.role_name');

                        $details =  $propSaf->union($activeSaf)->get();
                        break;

                    case ("gbsaf"):
                        $propGbSaf =  $mPropSafs->searchGbSafs()
                            ->where('gbo.officer_name', 'LIKE', '%' . strtoupper($ownerName) . '%');

                        $activeGbSaf =  $mPropActiveSaf->searchGbSafs()
                            ->where('gbo.officer_name', 'LIKE', '%' . strtoupper($ownerName) . '%');

                        $details = $propGbSaf->union($activeGbSaf)->get();
                        break;
                    case ("concession"):
                        $approvedConcession = $mPropConcessions->searchConcessions()
                            ->where('prop_owners.owner_name', 'LIKE', '%' . strtoupper($ownerName) . '%');

                        $activeConcession = $mPropActiveConcessions->searchConcessions()
                            ->where('prop_owners.owner_name', 'LIKE', '%' . strtoupper($ownerName) . '%');

                        $details = $approvedConcession->union($activeConcession)->get();
                        break;
                    case ("objection"):
                        $approvedObjection = $mPropObjection->searchObjections()
                            ->where('prop_owners.owner_name', 'LIKE', '%' . strtoupper($ownerName) . '%');

                        $activeObjection = $mPropActiveObjection->searchObjections()
                            ->where('prop_owners.owner_name', 'LIKE', '%' . strtoupper($ownerName) . '%');

                        $details = $approvedObjection->union($activeObjection)->get();
                        break;
                    case ("harvesting"):
                        $approvedHarvesting = $mPropHarvesting->searchHarvesting()
                            ->where('prop_owners.owner_name', 'LIKE', '%' . strtoupper($ownerName) . '%');

                        $activeHarvesting = $mPropActiveHarvesting->searchHarvesting()
                            ->where('prop_owners.owner_name', 'LIKE', '%' . strtoupper($ownerName) . '%');

                        $details = $approvedHarvesting->union($activeHarvesting)->get();
                        break;
                    case ('holdingDeactivation'):
                        $details = 'No Data Found';
                        break;
                }
            }

            // search by name
            if ($searchBy == 'mobileNo') {
                $mobileNo = $request->value;
                switch ($key) {
                    case ("saf"):
                        $propSaf  = $mPropSafs->searchSafs()
                            ->where('so.mobile_no', 'LIKE', '%' . $mobileNo . '%')
                            ->groupby('prop_safs.id', 'ulb_ward_masters.ward_name', 'wf_roles.role_name');

                        $activeSaf = $mPropActiveSaf->searchSafs()
                            ->where('so.mobile_no', 'LIKE', '%' . $mobileNo . '%')
                            ->groupby('prop_active_safs.id', 'ulb_ward_masters.ward_name', 'wf_roles.role_name');

                        $details = ($propSaf->union($activeSaf)->get());
                        $details = (object)$details;
                        break;
                    case ("gbsaf"):
                        $propGbSaf =  $mPropSafs->searchGbSafs()
                            ->where('gbo.mobile_no', 'LIKE', '%' . $mobileNo . '%');

                        $activeGbSaf = $mPropActiveSaf->searchGbSafs()
                            ->where('gbo.mobile_no', 'LIKE', '%' . $mobileNo . '%');

                        $details = $propGbSaf->union($activeGbSaf)->get();
                        break;
                    case ("concession"):
                        $approvedConcession = $mPropConcessions->searchConcessions()
                            ->where('prop_owners.mobile_no', 'LIKE', '%' . $mobileNo . '%');

                        $activeConcession = $mPropActiveConcessions->searchConcessions()
                            ->where('prop_owners.mobile_no', 'LIKE', '%' . $mobileNo . '%');

                        $details = $approvedConcession->union($activeConcession)->get();
                        break;
                    case ("objection"):
                        $approvedObjection = $mPropObjection->searchObjections()
                            ->where('prop_owners.mobile_no', 'LIKE', '%' . $mobileNo . '%');

                        $activeObjection = $mPropActiveObjection->searchObjections()
                            ->where('prop_owners.mobile_no', 'LIKE', '%' . $mobileNo . '%');

                        $details = $approvedObjection->union($activeObjection)->get();
                        break;
                    case ("harvesting"):
                        $approvedHarvesting = $mPropHarvesting->searchHarvesting()
                            ->where('prop_owners.mobile_no', 'LIKE', '%' . $mobileNo . '%');

                        $activeHarvesting = $mPropActiveHarvesting->searchHarvesting()
                            ->where('prop_owners.mobile_no', 'LIKE', '%' . $mobileNo . '%');

                        $details = $approvedHarvesting->union($activeHarvesting)->get();
                        break;
                    case ('holdingDeactivation'):
                        $details = 'No Data Found';
                        break;
                }
            }

            // search by ptn
            if ($searchBy == 'ptn') {
                $ptn = $request->value;
                switch ($key) {
                    case ("saf"):
                        $propSaf = $mPropSafs->searchSafs()
                            ->where('prop_safs.pt_no', $ptn)
                            ->groupby('prop_safs.id', 'ulb_ward_masters.ward_name', 'wf_roles.role_name');

                        $activeSaf = $mPropActiveSaf->searchSafs()
                            ->where('prop_active_safs.pt_no', $ptn)
                            ->groupby('prop_active_safs.id', 'ulb_ward_masters.ward_name', 'wf_roles.role_name');

                        $details =  $propSaf->union($activeSaf)->get();
                        break;
                    case ("gbsaf"):
                        $propGbSaf = $mPropSafs->searchGbSafs()
                            ->where('prop_active_safs.pt_no',  $ptn);

                        $activeGbSaf = $mPropActiveSaf->searchGbSafs()
                            ->where('prop_active_safs.pt_no',  $ptn);

                        $details = $propGbSaf->union($activeGbSaf)->get();
                        break;
                    case ("concession"):
                        $approvedConcession =  $mPropConcessions->searchConcessions()
                            ->where('pp.pt_no', $ptn);

                        $activeConcession =  $mPropActiveConcessions->searchConcessions()
                            ->where('pp.pt_no', $ptn);

                        $details = $approvedConcession->union($activeConcession)->get();
                        break;
                    case ("objection"):
                        $approvedObjection = $mPropObjection->searchObjections()
                            ->where('pp.pt_no', $ptn);

                        $activeObjection = $mPropActiveObjection->searchObjections()
                            ->where('pp.pt_no', $ptn);

                        $details = $approvedObjection->union($activeObjection)->get();
                        break;
                    case ("harvesting"):
                        $approvedHarvesting = $mPropHarvesting->searchHarvesting()
                            ->where('pp.pt_no', $ptn);

                        $activeHarvesting = $mPropActiveHarvesting->searchHarvesting()
                            ->where('pp.pt_no', $ptn);

                        $details = $approvedHarvesting->union($activeHarvesting)->get();
                        break;
                    case ('holdingDeactivation'):
                        $details = 'No Data Found';
                        break;
                }
            }

            // search with holding no
            if ($searchBy == 'holding') {
                $holding = $request->value;
                switch ($key) {
                    case ("saf"):
                        $propSaf  = $mPropSafs->searchSafs()
                            ->where('prop_active_safs.holding_no', $holding)
                            ->groupby('prop_safs.id', 'ulb_ward_masters.ward_name', 'wf_roles.role_name');

                        $activeSaf = $mPropActiveSaf->searchSafs()
                            ->where('prop_active_safs.holding_no', $holding)
                            ->groupby('prop_active_safs.id', 'ulb_ward_masters.ward_name', 'wf_roles.role_name');

                        $details =  $propSaf->union($activeSaf)->get();
                        break;
                    case ("gbsaf"):
                        $propGbSaf = $mPropSafs->searchGbSafs()
                            ->where('prop_active_safs.holding_no', $holding);

                        $activeGbSaf = $mPropActiveSaf->searchGbSafs()
                            ->where('prop_active_safs.holding_no', $holding);

                        $details = $propGbSaf->union($activeGbSaf)->get();
                        break;
                    case ("concession"):
                        $approvedConcession = $mPropConcessions->searchConcessions()
                            ->where('pp.holding_no',  $holding)
                            ->orWhere('pp.new_holding_no',  $holding);

                        $activeConcession = $mPropActiveConcessions->searchConcessions()
                            ->where('pp.holding_no',  $holding)
                            ->orWhere('pp.new_holding_no',  $holding);

                        $details = $approvedConcession->union($activeConcession)->get();
                        break;
                    case ("objection"):
                        $approvedObjection =  $mPropObjection->searchObjections()
                            ->where('pp.holding_no',  $holding)
                            ->orWhere('pp.new_holding_no',  $holding);

                        $activeObjection =  $mPropActiveObjection->searchObjections()
                            ->where('pp.holding_no',  $holding)
                            ->orWhere('pp.new_holding_no',  $holding);

                        $details = $approvedObjection->union($activeObjection)->get();
                        break;
                    case ("harvesting"):
                        $approvedHarvesting = $mPropHarvesting->searchHarvesting()
                            ->where('pp.holding_no',  $holding)
                            ->orWhere('pp.new_holding_no',  $holding);

                        $activeHarvesting = $mPropActiveHarvesting->searchHarvesting()
                            ->where('pp.holding_no',  $holding)
                            ->orWhere('pp.new_holding_no',  $holding);

                        $details = $approvedHarvesting->union($activeHarvesting)->get();
                        break;
                    case ('holdingDeactivation'):
                        $details = 'No Data Found';
                        break;
                }
            }
            return responseMsgs(true, "Application Details", remove_null($details), "010501", "1.0", "", "POST", $request->deviceId ?? "");
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
            $mPropProperty = new PropProperty();
            $mWfRoleUser = new WfRoleusermap();
            $userId = authUser()->id;
            $roleIds = $mWfRoleUser->getRoleIdByUserId($userId)->pluck('wf_role_id');                      // Model to () get Role By User Id
            $key = $request->filteredBy;
            $parameter = $request->parameter;
            switch ($key) {
                case ("holdingNo"):
                    $data = $mPropProperty->searchProperty()
                        ->where('prop_properties.holding_no', 'LIKE', '%' . $parameter . '%')
                        ->orWhere('prop_properties.new_holding_no', 'LIKE', '%' . $parameter . '%')
                        ->groupby('prop_properties.id', 'ulb_ward_masters.ward_name')
                        ->get();
                    break;

                case ("ptn"):
                    $data = $mPropProperty->searchProperty()
                        ->where('prop_properties.pt_no', 'LIKE', '%' . $parameter . '%')
                        ->groupby('prop_properties.id', 'ulb_ward_masters.ward_name')
                        ->get();
                    break;

                case ("ownerName"):
                    $data = $mPropProperty->searchProperty()
                        ->where('prop_owners.owner_name', 'LIKE', '%' . strtoupper($parameter) . '%')
                        ->groupby('prop_properties.id', 'ulb_ward_masters.ward_name')
                        ->get();
                    break;

                case ("address"):
                    $data = $mPropProperty->searchProperty()
                        ->where('prop_properties.prop_address', 'LIKE', '%' . strtoupper($parameter) . '%')
                        ->groupby('prop_properties.id', 'ulb_ward_masters.ward_name')
                        ->get();
                    break;

                case ("mobileNo"):
                    $data = $mPropProperty->searchProperty()
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
