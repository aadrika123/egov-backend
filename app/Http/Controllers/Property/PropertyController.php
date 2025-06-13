<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ThirdPartyController;
use App\Models\ActiveCitizen;
use App\Models\Citizen\ActiveCitizenUndercare;
use App\Models\Property\PropActiveConcession;
use App\Models\Property\PropActiveHarvesting;
use App\Models\Property\PropActiveObjection;
use App\Models\Property\PropActiveSaf;
use App\Models\Property\PropDemand;
use App\Models\Property\PropFloor;
use App\Models\Property\PropOwner;
use App\Models\Property\PropProperty;
use App\Models\Property\PropSaf;
use App\Models\Property\PropSafsDemand;
use App\Models\Property\PropTransaction;
use App\Models\Workflows\WfActiveDocument;
use App\Pipelines\SearchHolding;
use App\Pipelines\SearchPtn;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;
use App\Repository\Property\Interfaces\iSafRepository;

/**
 * | Created On - 11-03-2023
 * | Created By - Mrinal Kumar
 * | Status - Open
 */

class PropertyController extends Controller
{
    protected $_safRepo;
    public function __construct(iSafRepository $safRepo)
    {
        $this->_safRepo = $safRepo;
    }

    /**
     * | Send otp for caretaker property
     */
    public function caretakerOtp(Request $req)
    {
        try {
            $mPropOwner = new PropOwner();
            $ThirdPartyController = new ThirdPartyController();
            $propertyModuleId = Config::get('module-constants.PROPERTY_MODULE_ID');
            $waterModuleId = Config::get('module-constants.WATER_MODULE_ID');
            $tradeModuleId = Config::get('module-constants.TRADE_MODULE_ID');
            if ($req->moduleId == $propertyModuleId) {
            }
            $propDtl = app(Pipeline::class)
                ->send(PropProperty::query()->where('status', 1))
                ->through([
                    SearchHolding::class,
                    SearchPtn::class
                ])
                ->thenReturn()
                ->first();

            if (!isset($propDtl))
                throw new Exception('Property Not Found');
            $propOwners = $mPropOwner->getOwnerByPropId($propDtl->id);
            $firstOwner = collect($propOwners)->first();
            if (!$firstOwner)
                throw new Exception('Owner Not Found');
            $ownerMobile = $firstOwner->mobileNo;

            $myRequest = new \Illuminate\Http\Request();
            $myRequest->setMethod('POST');
            $myRequest->request->add(['mobileNo' => $ownerMobile]);
            $response = $ThirdPartyController->sendOtp($myRequest);

            $response = collect($response)->toArray();
            $data['otp'] = $response['original']['data'];
            $data['mobileNo'] = $ownerMobile;

            return responseMsgs(true, "OTP send successfully", $data, '011701', '01', '623ms', 'Post', '');
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Care taker property tag
     * | Tag a property to the caretaker (active citizen) based on holding number or PT number.
       | caretakerPropertyTag:1
     */
    public function caretakerPropertyTag(Request $req)
    {
        $req->validate([
            'holdingNo' => 'required_without:ptNo|max:255',
            'ptNo' => 'required_without:holdingNo|numeric',
        ]);
        try {
            $userId = authUser($req)->id;
            $activeCitizen = ActiveCitizen::findOrFail($userId);

            $propDtl = app(Pipeline::class)
                ->send(PropProperty::query()->where('status', 1))
                ->through([
                    SearchHolding::class,
                    SearchPtn::class
                ])
                ->thenReturn()
                ->first();

            if (!isset($propDtl))
                throw new Exception('Property Not Found');

            $allPropIds = $this->ifPropertyExists($propDtl->id, $activeCitizen);
            $activeCitizen->caretaker = $allPropIds;
            $activeCitizen->save();

            return responseMsgs(true, "Property Tagged!", '', '011702', '01', '623ms', 'Post', '');
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Function if Property Exists
       | caretakerPropertyTag:1.1
     */
    public function ifPropertyExists($propId, $activeCitizen)
    {
        $propIds = collect(explode(',', $activeCitizen->caretaker));
        $propIds->push($propId);
        return $propIds->implode(',');
    }

    /**
     * | Logged in citizen Holding & Saf
     */
    public function citizenHoldingSaf(Request $req)
    {
        $req->validate([
            'type' => 'required|In:holding,saf,ptn',
            'ulbId' => 'required|numeric'
        ]);
        try {
            $citizenId = authUser($req)->id;
            $ulbId = $req->ulbId;
            $type = $req->type;
            $mPropSafs = new PropSaf();
            $mPropActiveSafs = new PropActiveSaf();
            $mPropProperty = new PropProperty();
            $mActiveCitizenUndercare = new ActiveCitizenUndercare();
            $caretakerProperty =  $mActiveCitizenUndercare->getTaggedPropsByCitizenId($citizenId);

            if ($type == 'saf') {
                $data = $mPropActiveSafs->getCitizenSafs($citizenId, $ulbId);
                $msg = 'Citizen Safs';
            }

            if ($type == 'holding') {
                $data = $mPropProperty->getCitizenHoldings($citizenId, $ulbId);
                if ($caretakerProperty->isNotEmpty()) {
                    $propertyId = collect($caretakerProperty)->pluck('property_id');
                    $data2 = $mPropProperty->getNewholding($propertyId);
                    $data = $data->merge($data2);
                }
                $data = collect($data)->map(function ($value) {
                    if (isset($value['new_holding_no'])) {
                        return $value;
                    }
                })->filter()->values();
                $msg = 'Citizen Holdings';
            }

            if ($type == 'ptn') {
                $data = $mPropProperty->getCitizenPtn($citizenId, $ulbId);
                $msg = 'Citizen Ptn';

                if ($caretakerProperty->isNotEmpty()) {
                    $propertyId = collect($caretakerProperty)->pluck('property_id');
                    $data2 = $mPropProperty->getPtn($propertyId);
                    $data = $data->merge($data2);
                }
                $data = collect($data)->map(function ($value) {
                    if (isset($value['pt_no'])) {
                        return $value;
                    }
                })->filter()->values();
            }

            if ($data->isEmpty())
                throw new Exception('No Data Found');

            return responseMsgs(true, $msg, remove_null($data), '011703', '01', '623ms', 'Post', '');
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * |  Edit basic property and owner details.
     */
    public function basicPropertyEdit(Request $req)
    {
        try {
            $mPropProperty = new PropProperty();
            $mPropOwners = new PropOwner();
            $propId = $req->propertyId;
            $mOwners = $req->owner;

            $mreq = new Request(
                [
                    "new_ward_mstr_id" => $req->newWardMstrId,
                    "khata_no" => $req->khataNo,
                    "plot_no" => $req->plotNo,
                    "village_mauja_name" => $req->villageMauja,
                    "prop_pin_code" => $req->pinCode,
                    "building_name" => $req->buildingName,
                    "street_name" => $req->streetName,
                    "location" => $req->location,
                    "landmark" => $req->landmark,
                    "prop_address" => $req->address,
                    "corr_pin_code" => $req->corrPin,
                    "corr_address" => $req->corrAddress
                ]
            );
            $mPropProperty->editProp($propId, $mreq);

            collect($mOwners)->map(function ($owner) use ($mPropOwners) {            // Updation of Owner Basic Details
                if (isset($owner['ownerId'])) {

                    $req = new Request([
                        'id' =>  $owner['ownerId'],
                        'owner_name' => $owner['ownerName'],
                        'guardian_name' => $owner['guardianName'],
                        'relation_type' => $owner['relation'],
                        'mobile_no' => $owner['mobileNo'],
                        'aadhar_no' => $owner['aadhar'],
                        'pan_no' => $owner['pan'],
                        'email' => $owner['email'],
                    ]);
                    $mPropOwners->editPropOwner($req);
                }
            });

            return responseMsgs(true, 'Data Updated', '', '011704', '01', '', 'Post', '');
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Check if the property id exist in the workflow
       | CheckProperty:1
     */
    public function CheckProperty(Request $req)
    {
        $validated = Validator::make(
            $req->all(),
            [
                'type' => 'required|in:Reassesment,Mutation,Concession,Objection,Harvesting,Bifurcation',
                'propertyId' => 'required|numeric',
            ]
        );
        if ($validated->fails()) {
            return validationError($validated);
        }

        try {
            $holdingTaxController = new HoldingTaxController($this->_safRepo);
            $type = $req->type;
            $propertyId = $req->propertyId;
            $sms = "";

            switch ($type) {
                case 'Reassesment':
                    $data = PropActiveSaf::select('prop_active_safs.id', 'role_name', 'saf_no as application_no')
                        ->join('wf_roles', 'wf_roles.id', 'prop_active_safs.current_role')
                        ->where('previous_holding_id', $propertyId)
                        ->where('prop_active_safs.status', 1)
                        ->first();
                    break;
                case 'Mutation':
                    $sms  = $this->checkOccupiedProperty($req);
                    $data = PropActiveSaf::select('prop_active_safs.id', 'role_name', 'saf_no as application_no')
                        ->join('wf_roles', 'wf_roles.id', 'prop_active_safs.current_role')
                        ->where('previous_holding_id', $propertyId)
                        ->where('prop_active_safs.status', 1)
                        ->first();
                    break;
                case 'Bifurcation':
                    $sms  = $this->checkOccupiedProperty($req);
                    $data = PropActiveSaf::select('prop_active_safs.id', 'role_name', 'saf_no as application_no')
                        ->join('wf_roles', 'wf_roles.id', 'prop_active_safs.current_role')
                        ->where('assessment_type', 'Mutation')
                        ->where('previous_holding_id', $propertyId)
                        ->where('prop_active_safs.status', 1)
                        ->first();

                    if (!$data)
                        $data = PropActiveSaf::select('prop_active_safs.id', 'role_name', 'saf_no as application_no')
                            ->join('wf_roles', 'wf_roles.id', 'prop_active_safs.current_role')
                            ->where('assessment_type', 'Reassessment')
                            ->where('previous_holding_id', $propertyId)
                            ->where('prop_active_safs.status', 1)
                            ->first();
                    break;
                case 'Concession':
                    $data = PropActiveConcession::select('prop_active_concessions.id', 'role_name', 'application_no')
                        ->join('wf_roles', 'wf_roles.id', 'prop_active_concessions.current_role')
                        ->where('property_id', $propertyId)
                        ->where('prop_active_concessions.status', 1)
                        ->first();
                    break;
                case 'Objection':
                    $data = PropActiveObjection::select('prop_active_objections.id', 'role_name', 'objection_no as application_no')
                        ->join('wf_roles', 'wf_roles.id', 'prop_active_objections.current_role')
                        ->where('property_id', $propertyId)
                        ->where('prop_active_objections.status', 1)
                        ->first();
                    break;
                case 'Harvesting':
                    $data = PropActiveHarvesting::select('prop_active_harvestings.id', 'role_name', 'application_no')
                        ->join('wf_roles', 'wf_roles.id', 'prop_active_harvestings.current_role')
                        ->where('property_id', $propertyId)
                        ->where('prop_active_harvestings.status', 1)
                        ->first();
                    break;
            }

            // if ($data) {
            //     $msg['id'] = $data->id;
            //     $msg['inWorkflow'] = true;
            //     $msg['currentRole'] = $data->role_name;
            //     $msg['message'] = "The application is still in workflow and pending at " . $data->role_name . ". Please Track your application with " . $data->application_no;
            // } else
            //     $msg['inWorkflow'] = false;
            if ($sms) {
                $msg['inWorkflow'] = true;
                $msg['message'] = $sms;
                return responseMsgs(true, 'Data Checked', $msg, '011705', '01', responseTime(), 'Post', '');
            }

            if ($data) {
                $msg['id'] = $data->id;
                $msg['inWorkflow'] = true;
                $msg['currentRole'] = $data->role_name;
                $msg['message'] = "Your " . $data->assessment_type . " application is still in workflow and pending at " . $data->role_name . ". Please Track your application with " . $data->application_no;
            } else {

                $sms = "";
                $req->merge(["propId" => $propertyId]);
                $demand = $holdingTaxController->getHoldingDues($req);
                if ($demand->original['status'] == true) {
                    $demandDetails = $demand->original['data']['duesList'];
                    //Temporary solution please comment next two lines
                    if (in_array($type, ['Reassesment', 'Mutation', 'Bifurcation']))
                        $sms = "Please Clear The Due Amount Of ₹" . $demandDetails['payableAmount'] . " Before Applying The Application.";
                    if ($demandDetails['dueFromFyear'] < getFY())
                        $sms = "Please Clear The Arrear Amount Of ₹" . $demandDetails['payableAmount'] . " Before Applying The Application.";
                }
                if ($sms) {
                    $msg['inWorkflow'] = true;
                    $msg['message'] = $sms;
                } else {
                    $msg['inWorkflow'] = false;
                }
            }

            return responseMsgs(true, 'Data Checked', $msg, '011705', '01', responseTime(), 'Post', '');
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", '011705', '01', responseTime(), 'Post', '');
        }
    }

    /**
     * | Check if th property is occupied 
       | CheckProperty:1.1
     */
    public function checkOccupiedProperty($req)
    {
        $propTypes = Config::get("PropertyConstaint.PROPERTY-TYPE");
        $propTypes = collect($propTypes)->flip();
        $propDtls = PropProperty::find($req->propertyId);
        $propDtls->prop_type_mstr_id;
        if (in_array($propDtls->prop_type_mstr_id, [$propTypes['SUPER STRUCTURE'], $propTypes['OCCUPIED PROPERTY']])) {
            $msg = "SUPER STRUCTURE & OCCUPIED PROPERTY Cannot Apply For " . $req->type;
            return $msg;
        }
    }

    /**
     * | Get the Property LatLong for Heat map
     * | Using wardId used in dashboard data 
       | For MVP testing
       | getpropLatLong:1
     */
    public function getpropLatLong(Request $req)
    {
        $req->validate([
            'wardId' => 'required|integer',
            'ulbId' =>  'nullable|integer',
        ]);
        try {
            $mPropDemand    = new PropDemand();
            $mPropProperty  = new PropProperty();
            $propDetails    = $mPropProperty->getPropLatlong($req->wardId, $req->ulbId);
            $propertyIds    = $propDetails->pluck('property_id');
            $propDemand     = $mPropDemand->getDueDemandByPropIdV2($propertyIds);
            $propDemand     = collect($propDemand);
            $currentDate    = Carbon::now()->format('Y-04-01');
            $refCurrentDate = Carbon::createFromFormat('Y-m-d', $currentDate);
            $ref2023        = Carbon::createFromFormat('Y-m-d', "2023-01-01")->toDateString();

            # Looping process for 
            $propDetails = collect($propDetails)->map(function ($value)
            use ($propDemand, $refCurrentDate, $ref2023) {

                $geoDate    = strtotime($value['created_at']);
                $geoDate    = date('Y-m-d', $geoDate);
                $path       = $this->readDocumentPath($value['doc_path']);

                # arrrer,current,paid
                $refUnpaidPropDemands   = $propDemand->where('property_id', $value['property_id']);
                $checkPropDemand        = collect($refUnpaidPropDemands)->last();
                if (!$checkPropDemand) {
                    $currentStatus  = 3;                                                             // Static
                    $statusName     = "No Dues";                                                         // Static
                }
                if ($checkPropDemand) {
                    if (is_null($checkPropDemand->due_date)) {
                        $currentStatus  = 3;                                                         // Static
                        $statusName     = "No Dues";                                                     // Static
                    }
                    $refDate = Carbon::createFromFormat('Y-m-d', $checkPropDemand->due_date);
                    if ($refDate < $refCurrentDate) {
                        $currentStatus  = 1;                                                         // Static
                        $statusName     = "Arrear";                                                    // Static
                    } else {
                        $currentStatus  = 2;                                                         // Static
                        $statusName     = "Current Dues";                                               // Static
                    }
                }
                $value['statusName']    = $statusName;
                $value['currentStatus'] = $currentStatus;

                # For the document 
                if ($geoDate < $ref2023) {
                    $path = $this->readRefDocumentPath($value['doc_path']);
                    $value['full_doc'] = !empty(trim($value['doc_path'])) ? $path : null;
                    return $value;
                }
                $value['full_doc'] = !empty(trim($value['doc_path'])) ? $path : null;
                return $value;
            })->filter();

            return responseMsgs(true, "latLong Details", remove_null($propDetails), "011707", "01", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), $e->getFile(), "011707", "01", responseTime(), "POST", $req->deviceId);
        }
    }

    /**
     * | Read the document path for the reference document
       | getpropLatLong:1.1
     */
    public function readRefDocumentPath($path)
    {
        $path = ("https://smartulb.co.in/RMCDMC/getImageLink.php?path=" . "/" . $path);                      // Static
        return $path;
    }

    /**
     * | Read the document path for the document
       | getpropLatLong:1.2
     */
    public function readDocumentPath($path)
    {
        $path = (config('app.url') . "/" . $path);
        return $path;
    }

    /**
     * | Get citizen details with property_id and activate in case of deactive
     * | written by prity pandey
     */
    public function citizenStatusUpdate(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'propertyId' => 'required|numeric',
                'citizenId' => ($request->has("deactiveStatus") ? "required" : 'nullable') . '|numeric',
                'deactiveStatus' => ($request->citizenId ? "required" : 'nullable') . '|boolean'
            ]
        );
        if ($validated->fails()) {
            return validationError($validated);
        }
        try {

            $propertyId = $request->propertyId;
            $careTaker = ActiveCitizenUndercare::where('property_id', $propertyId)->get();
            if ($careTaker->isEmpty()) {
                throw new Exception("Invalid property id");
            }
            $citizenId = $careTaker->unique('citizen_id')->pluck("citizen_id");


            if ($citizenId  && (!$request->has("citizenID") && !$request->has("deactiveStatus"))) {
                $citizenData = ActiveCitizen::whereIn('id', $citizenId)->get();

                return responseMsgs(true, "Citizen data", $citizenData, "", "01", responseTime(), "POST", $request->deviceId);
            } elseif ($request->citizenId == null && $request->deactiveStatus == null) {
                throw new Exception("Data not found");
            } elseif ($request->propertyId && $request->citizenId && !$request->deactiveStatus) {
                DB::connection("pgsql_master")->beginTransaction();
                ActiveCitizenUndercare::where('citizen_id', $request->citizenId)
                    ->where("property_id", $propertyId)
                    ->update(['deactive_status' => true]);
                DB::connection("pgsql_master")->commit();
                return responseMsgs(true, "citizen updated", "011706", "01", responseTime(), "POST", $request->deviceId);
            }
        } catch (Exception $e) {
            DB::connection("pgsql_master")->rollBack();
            return responseMsgs(false, $e->getMessage(), "", "011706", "01", responseTime(), "POST", $request->deviceId);
        }
    }

    /**
     * | Check Amalgamation reqs
     */
    public function checkAmalgamation(Request $request)
    {
        try {
            $validated = Validator::make(
                $request->all(),
                [
                    'propertyId' => 'required|array',
                ]
            );
            if ($validated->fails()) {
                return validationError($validated);
            }
            $mPropProperties = new PropProperty();
            $mPropFloor = new PropFloor();
            $holdingTaxController = new HoldingTaxController($this->_safRepo);
            $holdingDtls = $mPropProperties->getMultipleProperty($request->propertyId);
            $plotArea = collect($holdingDtls)->sum('area_of_plot');
            $propertyIds = collect($holdingDtls)->pluck('id');
            $floorDtls = $mPropFloor->getAppartmentFloor($propertyIds)->get();
            $demands = array();

            foreach ($propertyIds as $propId) {
                $request->merge(["propId" => $propId]);
                $demand = $holdingTaxController->getHoldingDues($request);
                $demand = $demand->original;
                if ($demand['status'] == false)
                    // $demandDetails = $demand['data']['duesList'];
                    array_push($demands, $demand);
            }
            return $demands;


            return responseMsgs(true, "Amalgamation Requirement Fulfilled", [], '011707', '01', responseTime(), $request->getMethod(), $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], '011707', '01', responseTime(), $request->getMethod(), $request->deviceId);
        }
    }

    /**
     * | Check Amalgamation Property
     */
    public function checkAmalgamationProperty(Request $request)
    {
        try {
            $validated = Validator::make(
                $request->all(),
                [
                    'holdingNo' => 'required|array',
                ]
            );
            if ($validated->fails()) {
                return validationError($validated);
            }
            $mPropProperties = new PropProperty();
            $mPropFloor = new PropFloor();
            $holdingTaxController = new HoldingTaxController($this->_safRepo);
            $holdingDtls = $mPropProperties->searchCollectiveHolding($request->holdingNo);
            foreach ($request->holdingNo as $holdingNo) {
                $holdingDtlsV1 = $mPropProperties->searchByHoldingNo($holdingNo);
                if (collect($holdingDtlsV1)->isEmpty())
                    throw new Exception("No Property found for the respective holding no." . $holdingNo);
            }
            if (collect($holdingDtls)->isEmpty())
                throw new Exception("No Property found for the respective holding no.");

            $plotArea = collect($holdingDtls)->sum('area_of_plot');
            $propertyIds = collect($holdingDtls)->pluck('id');
            $floorDtls = $mPropFloor->getAppartmentFloor($propertyIds)->get();
            $demands = array();
            foreach ($propertyIds as $propId) {
                $request->merge(["propId" => $propId]);
                $demand = $holdingTaxController->getHoldingDues($request);
                $demand = $demand->original;
                if ($demand['status'] == true) {
                    throw new Exception("Previous Demand is not clear for the respective property." . $demand['data']['basicDetails']['holding_no'] ?? 'N/A'); // Return false immediately if any demand has status false
                }
                if ($demand['status'] == false) {
                    $demand['data']['basicDetails']['property_id'] = $propId;
                    array_push($demands, $demand['data']['basicDetails']);
                }
            }

            if (collect($demands)->isEmpty())
                throw new Exception("Previous Demand is not clear for the respective property.");

            return responseMsgs(true, "Amalgamation Requirement Fulfilled", $demands, '011707', '01', responseTime(), $request->getMethod(), $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], '011707', '01', responseTime(), $request->getMethod(), $request->deviceId);
        }
    }

    /**
     * | Master Holding Data
     * | Fetch master holding data including amalgamated properties and floors.
     */
    public function masterHoldingData(Request $request)
    {
        try {
            $mPropFloor = new PropFloor();
            $mPropProperties = new PropProperty();
            $holdingLists = array();
            $safController = new ActiveSafController($this->_safRepo);
            $propIds = collect($request->amalgamation)->pluck('propId')->unique();
            $holdingDtls = $mPropProperties->getMultipleProperty($propIds);
            $plotArea = collect($holdingDtls)->sum('area_of_plot');
            $floorDtls = $mPropFloor->getAppartmentFloor($propIds)->get();
            $masterHoldingId = collect($request->amalgamation)->where('isMasterHolding', true)->first();
            $reqPropId = new Request(['propertyId' => $masterHoldingId['propId']]);
            $masterData = $safController->getPropByHoldingNo($reqPropId)->original['data'];
            $masterData['floors'] = $floorDtls;
            $masterData['area_of_plot'] = $plotArea;
            foreach ($holdingDtls as $property) {
                $holdingList = $property->new_holding_no ?? $property->holding_no;
                array_push($holdingLists, $holdingList);
            }
            $masterData['holdingNoLists'] = $holdingLists;

            return responseMsgs(true, "Master Holding Data", $masterData, '011707', '01', responseTime(), $request->getMethod(), $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], '011707', '01', responseTime(), $request->getMethod(), $request->deviceId);
        }
    }
}
