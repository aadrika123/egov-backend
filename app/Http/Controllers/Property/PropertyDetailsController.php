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
use App\Models\Property\PropDeactivationRequest;
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
        $request->validate([
            'searchBy' => 'required',
            'filteredBy' => 'required',
            'value' => 'required',
        ]);
        try {

            $mPropActiveSaf = new PropActiveSaf();
            $mPropActiveConcessions = new PropActiveConcession();
            $mPropActiveObjection = new PropActiveObjection();
            $mPropActiveHarvesting = new PropActiveHarvesting();
            $mPropActiveDeactivationRequest = new PropActiveDeactivationRequest();
            $mPropSafs = new PropSaf();
            $mPropConcessions = new PropConcession();
            $mPropObjection = new PropObjection();
            $mPropHarvesting = new PropHarvesting();
            $mPropDeactivationRequest = new PropDeactivationRequest();
            $searchBy = $request->searchBy;
            $key = $request->filteredBy;
            $perPage = $request->perPage ?? 10;

            //search by application no.
            if ($searchBy == 'applicationNo') {
                $applicationNo = $request->value;
                switch ($key) {

                    case ("saf"):
                        $approved  = $mPropSafs->searchSafs()
                            ->where('prop_safs.saf_no', strtoupper($applicationNo))
                            ->groupby('prop_safs.id', 'u.ward_name', 'uu.ward_name', 'wf_roles.role_name');

                        $active = $mPropActiveSaf->searchSafs()
                            ->where('prop_active_safs.saf_no', strtoupper($applicationNo))
                            ->groupby('prop_active_safs.id', 'u.ward_name', 'uu.ward_name', 'wf_roles.role_name');

                        // $details = $approved->union($active)->get();
                        break;

                    case ("gbsaf"):
                        $approved =  $mPropSafs->searchGbSafs()
                            ->where('prop_safs.saf_no', strtoupper($applicationNo));

                        $active =  $mPropActiveSaf->searchGbSafs()
                            ->where('prop_active_safs.saf_no', strtoupper($applicationNo));

                        // $details = $approved->union($active)->get();
                        break;

                    case ("concession"):
                        $approved = $mPropConcessions->searchConcessions()
                            ->where('prop_concessions.application_no', strtoupper($applicationNo));

                        $active = $mPropActiveConcessions->searchConcessions()
                            ->where('prop_active_concessions.application_no', strtoupper($applicationNo));

                        // $details = $approved->union($active)->get();
                        break;

                    case ("objection"):
                        $approved = $mPropObjection->searchObjections()
                            ->where('prop_objections.objection_no', strtoupper($applicationNo));

                        $active = $mPropActiveObjection->searchObjections()
                            ->where('prop_active_objections.objection_no', strtoupper($applicationNo));

                        // $details = $approved->union($active)->get();
                        break;

                    case ("harvesting"):
                        $approved = $mPropHarvesting->searchHarvesting()
                            ->where('application_no', strtoupper($applicationNo));

                        $active = $mPropActiveHarvesting->searchHarvesting()
                            ->where('application_no', strtoupper($applicationNo));

                        // $details = $approved->union($active)->get();
                        break;

                    case ('holdingDeactivation'):
                        $approved = $mPropDeactivationRequest->getDeactivationApplication()
                            ->where('prop_deactivation_requests.application_no', strtoupper($applicationNo));

                        $active = $mPropActiveDeactivationRequest->getDeactivationApplication()
                            ->where('prop_active_deactivation_requests.application_no', strtoupper($applicationNo));

                        // $details = $approved->union($active)->get();
                        break;
                }
            }

            // search by name
            if ($searchBy == 'name') {
                $ownerName = $request->value;
                switch ($key) {
                    case ("saf"):
                        $approved  = $mPropSafs->searchSafs()
                            ->where('so.owner_name', 'LIKE', '%' . strtoupper($ownerName) . '%')
                            ->groupby('prop_safs.id', 'u.ward_name', 'uu.ward_name', 'wf_roles.role_name');

                        $active = $mPropActiveSaf->searchSafs()
                            ->where('so.owner_name', 'LIKE', '%' . strtoupper($ownerName) . '%')
                            ->groupby('prop_active_safs.id', 'u.ward_name', 'uu.ward_name', 'wf_roles.role_name');

                        // $details = $approved->union($active)->get();
                        break;

                    case ("gbsaf"):
                        $approved =  $mPropSafs->searchGbSafs()
                            ->where('gbo.officer_name', 'LIKE', '%' . strtoupper($ownerName) . '%');

                        $active =  $mPropActiveSaf->searchGbSafs()
                            ->where('gbo.officer_name', 'LIKE', '%' . strtoupper($ownerName) . '%');

                        // $details = $approved->union($active)->get();
                        break;
                    case ("concession"):
                        $approved = $mPropConcessions->searchConcessions()
                            ->where('prop_owners.owner_name', 'LIKE', '%' . strtoupper($ownerName) . '%');

                        $active = $mPropActiveConcessions->searchConcessions()
                            ->where('prop_owners.owner_name', 'LIKE', '%' . strtoupper($ownerName) . '%');

                        // $details = $approved->union($active)->get();
                        break;
                    case ("objection"):
                        $approved = $mPropObjection->searchObjections()
                            ->where('prop_owners.owner_name', 'LIKE', '%' . strtoupper($ownerName) . '%');

                        $active = $mPropActiveObjection->searchObjections()
                            ->where('prop_owners.owner_name', 'LIKE', '%' . strtoupper($ownerName) . '%');

                        // $details = $approved->union($active)->get();
                        break;
                    case ("harvesting"):
                        $approved = $mPropHarvesting->searchHarvesting()
                            ->where('prop_owners.owner_name', 'LIKE', '%' . strtoupper($ownerName) . '%');

                        $active = $mPropActiveHarvesting->searchHarvesting()
                            ->where('prop_owners.owner_name', 'LIKE', '%' . strtoupper($ownerName) . '%');

                        // $details = $approved->union($active)->get();
                        break;
                    case ('holdingDeactivation'):
                        $details = 'No Data Found';
                        break;
                }
            }

            // search by mobileNo
            if ($searchBy == 'mobileNo') {
                $mobileNo = $request->value;
                switch ($key) {
                    case ("saf"):
                        $approved  = $mPropSafs->searchSafs()
                            ->where('so.mobile_no', 'LIKE', '%' . $mobileNo . '%')
                            ->groupby('prop_safs.id', 'u.ward_name', 'uu.ward_name', 'wf_roles.role_name');

                        $active = $mPropActiveSaf->searchSafs()
                            ->where('so.mobile_no', 'LIKE', '%' . $mobileNo . '%')
                            ->groupby('prop_active_safs.id', 'u.ward_name', 'uu.ward_name', 'wf_roles.role_name');

                        // $details = $approved->union($active)->get();
                        // $details = (object)$details;
                        break;
                    case ("gbsaf"):
                        $approved =  $mPropSafs->searchGbSafs()
                            ->where('gbo.mobile_no', 'LIKE', '%' . $mobileNo . '%');

                        $active = $mPropActiveSaf->searchGbSafs()
                            ->where('gbo.mobile_no', 'LIKE', '%' . $mobileNo . '%');

                        // $details = $approved->union($active)->get();
                        break;
                    case ("concession"):
                        $approved = $mPropConcessions->searchConcessions()
                            ->where('prop_owners.mobile_no', 'LIKE', '%' . $mobileNo . '%');

                        $active = $mPropActiveConcessions->searchConcessions()
                            ->where('prop_owners.mobile_no', 'LIKE', '%' . $mobileNo . '%');

                        // $details = $approved->union($active)->get();
                        break;
                    case ("objection"):
                        $approved = $mPropObjection->searchObjections()
                            ->where('prop_owners.mobile_no', 'LIKE', '%' . $mobileNo . '%');

                        $active = $mPropActiveObjection->searchObjections()
                            ->where('prop_owners.mobile_no', 'LIKE', '%' . $mobileNo . '%');

                        // $details = $approved->union($active)->get();
                        break;
                    case ("harvesting"):
                        $approved = $mPropHarvesting->searchHarvesting()
                            ->where('prop_owners.mobile_no', 'LIKE', '%' . $mobileNo . '%');

                        $active = $mPropActiveHarvesting->searchHarvesting()
                            ->where('prop_owners.mobile_no', 'LIKE', '%' . $mobileNo . '%');

                        // $details = $approved->union($active)->get();
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
                        $approved = $mPropSafs->searchSafs()
                            ->where('prop_safs.pt_no', $ptn)
                            ->groupby('prop_safs.id', 'u.ward_name', 'uu.ward_name', 'wf_roles.role_name');

                        $active = $mPropActiveSaf->searchSafs()
                            ->where('prop_active_safs.pt_no', $ptn)
                            ->groupby('prop_active_safs.id', 'u.ward_name', 'uu.ward_name', 'wf_roles.role_name');

                        // $details = $approved->union($active)->get();
                        break;
                    case ("gbsaf"):
                        $approved = $mPropSafs->searchGbSafs()
                            ->where('prop_active_safs.pt_no',  $ptn);

                        $active = $mPropActiveSaf->searchGbSafs()
                            ->where('prop_active_safs.pt_no',  $ptn);

                        // $details = $approved->union($active)->get();
                        break;
                    case ("concession"):
                        $approved =  $mPropConcessions->searchConcessions()
                            ->where('pp.pt_no', $ptn);

                        $active =  $mPropActiveConcessions->searchConcessions()
                            ->where('pp.pt_no', $ptn);

                        // $details = $approved->union($active)->get();
                        break;
                    case ("objection"):
                        $approved = $mPropObjection->searchObjections()
                            ->where('pp.pt_no', $ptn);

                        $active = $mPropActiveObjection->searchObjections()
                            ->where('pp.pt_no', $ptn);

                        // $details = $approved->union($active)->get();
                        break;
                    case ("harvesting"):
                        $approved = $mPropHarvesting->searchHarvesting()
                            ->where('pp.pt_no', $ptn);

                        $active = $mPropActiveHarvesting->searchHarvesting()
                            ->where('pp.pt_no', $ptn);

                        // $details = $approved->union($active)->get();
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
                        $approved  = $mPropSafs->searchSafs()
                            ->where('prop_active_safs.holding_no', $holding)
                            ->groupby('prop_safs.id', 'u.ward_name', 'uu.ward_name', 'wf_roles.role_name');

                        $active = $mPropActiveSaf->searchSafs()
                            ->where('prop_active_safs.holding_no', $holding)
                            ->groupby('prop_active_safs.id', 'u.ward_name', 'uu.ward_name', 'wf_roles.role_name');

                        // $details = $approved->union($active)->get();
                        break;
                    case ("gbsaf"):
                        $approved = $mPropSafs->searchGbSafs()
                            ->where('prop_active_safs.holding_no', $holding);

                        $active = $mPropActiveSaf->searchGbSafs()
                            ->where('prop_active_safs.holding_no', $holding);

                        // $details = $approved->union($active)->get();
                        break;
                    case ("concession"):
                        $approved = $mPropConcessions->searchConcessions()
                            ->where('pp.holding_no',  $holding)
                            ->orWhere('pp.new_holding_no',  $holding);

                        $active = $mPropActiveConcessions->searchConcessions()
                            ->where('pp.holding_no',  $holding)
                            ->orWhere('pp.new_holding_no',  $holding);

                        // $details = $approved->union($active)->get();
                        break;
                    case ("objection"):
                        $approved =  $mPropObjection->searchObjections()
                            ->where('pp.holding_no',  $holding)
                            ->orWhere('pp.new_holding_no',  $holding);

                        $active =  $mPropActiveObjection->searchObjections()
                            ->where('pp.holding_no',  $holding)
                            ->orWhere('pp.new_holding_no',  $holding);

                        // $details = $approved->union($active)->get();
                        break;
                    case ("harvesting"):
                        $approved = $mPropHarvesting->searchHarvesting()
                            ->where('pp.holding_no',  $holding)
                            ->orWhere('pp.new_holding_no',  $holding);

                        $active = $mPropActiveHarvesting->searchHarvesting()
                            ->where('pp.holding_no',  $holding)
                            ->orWhere('pp.new_holding_no',  $holding);

                        // $details = $approved->union($active)->get();
                        break;
                    case ('holdingDeactivation'):
                        $details = 'No Data Found';
                        break;
                }
            }
            $details = $approved->union($active)->paginate($perPage);

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
            'parameter' => "nullable",
            // 'plotNo' => 'sometimes|required_if:filteredBy=khataNo',
            // 'plotNo' => 'sometimes|required_if:filteredBy=khataNo'
            // 'plotNo' => 'sometimes|required_if:filteredBy=khataNo'
        ]);

        try {
            $mPropProperty = new PropProperty();
            $mWfRoleUser = new WfRoleusermap();
            $user = authUser($request);
            $userId = $user->id;
            $userType = $user->user_type;
            $ulbId = $user->ulb_id ?? $request->ulbId;
            $roleIds = $mWfRoleUser->getRoleIdByUserId($userId)->pluck('wf_role_id');                      // Model to () get Role By User Id
            $role = $roleIds->first();
            $key = $request->filteredBy;
            $parameter = $request->parameter;
            $isLegacy = $request->isLegacy;
            $perPage = $request->perPage ?? 10;

            switch ($key) {
                case ("holdingNo"):
                    $data = $mPropProperty->searchProperty($ulbId)
                        ->where('prop_properties.holding_no', 'LIKE', '%' . $parameter . '%')
                        ->orWhere('prop_properties.new_holding_no', 'LIKE', '%' . $parameter . '%');
                    break;

                case ("ptn"):
                    $data = $mPropProperty->searchProperty($ulbId)
                        ->where('prop_properties.pt_no', 'LIKE', '%' . $parameter . '%');
                    break;

                case ("ownerName"):
                    $data = $mPropProperty->searchProperty($ulbId)
                        ->where('prop_owners.owner_name', 'LIKE', '%' . strtoupper($parameter) . '%');
                    break;

                case ("address"):
                    $data = $mPropProperty->searchProperty($ulbId)
                        ->where('prop_properties.prop_address', 'LIKE', '%' . strtoupper($parameter) . '%');
                    break;

                case ("mobileNo"):
                    $data = $mPropProperty->searchProperty($ulbId)
                        ->where('prop_owners.mobile_no', 'LIKE', '%' . $parameter . '%');
                    break;

                case ("khataNo"):
                    if ($request->khataNo)
                        $data = $mPropProperty->searchProperty($ulbId)
                            ->where('prop_properties.khata_no', $request->khataNo);

                    if ($request->plotNo)
                        $data = $mPropProperty->searchProperty($ulbId)
                            ->where('prop_properties.plot_no',  $request->plotNo);

                    if ($request->maujaName)
                        $data = $mPropProperty->searchProperty($ulbId)
                            ->where('prop_properties.village_mauja_name',  $request->maujaName);

                    if ($request->khataNo && $request->plotNo)
                        $data = $mPropProperty->searchProperty($ulbId)
                            ->where('prop_properties.khata_no',  $request->khataNo)
                            ->where('prop_properties.plot_no',  $request->plotNo);

                    if ($request->khataNo && $request->maujaName)
                        $data = $mPropProperty->searchProperty($ulbId)
                            ->where('prop_properties.khata_no',  $request->khataNo)
                            ->where('prop_properties.village_mauja_name',  $request->maujaName);

                    if ($request->plotNo && $request->maujaName)
                        $data = $mPropProperty->searchProperty($ulbId)
                            ->where('prop_properties.plot_no',  $request->plotNo)
                            ->where('prop_properties.village_mauja_name',  $request->maujaName);

                    if ($request->khataNo && $request->plotNo && $request->maujaName)
                        $data = $mPropProperty->searchProperty($ulbId)
                            ->where('prop_properties.khata_no',  $request->khataNo)
                            ->where('prop_properties.plot_no',  $request->plotNo)
                            ->where('prop_properties.village_mauja_name',  $request->maujaName);
                    break;
            }

            if ($userType != 'Citizen')
                $data = $data->where('prop_properties.ulb_id', $ulbId);

            if ($isLegacy == true) {
                $paginator = $data->where('new_holding_no', null)
                    ->where('latitude', null)
                    ->where('longitude', null)
                    ->groupby('prop_properties.id', 'ulb_ward_masters.ward_name', 'latitude', 'longitude')
                    ->paginate($perPage);
                // $data = (array_values(objtoarray($data)));
            }
            if ($isLegacy == false) {
                if ($key == 'ptn') {
                    $paginator =
                        $data
                        ->groupby('prop_properties.id', 'ulb_ward_masters.ward_name', 'latitude', 'longitude')
                        ->paginate($perPage);
                } else {
                    $paginator = $data->where('new_holding_no', '!=', null)
                        ->groupby('prop_properties.id', 'ulb_ward_masters.ward_name', 'latitude', 'longitude')
                        ->paginate($perPage);
                }
            }

            $list = [
                "current_page" => $paginator->currentPage(),
                "last_page" => $paginator->lastPage(),
                "data" => $paginator->items(),
                "total" => $paginator->total(),
            ];

            return responseMsgs(true, "Application Details", remove_null($list), "010501", "1.0", "", "POST", $request->deviceId ?? "");
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
