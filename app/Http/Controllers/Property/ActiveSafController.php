<?php

namespace App\Http\Controllers\Property;

use App\EloquentClass\Property\InsertTax;
use App\EloquentClass\Property\SafCalculation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Property\reqApplySaf;
use App\Http\Requests\Property\ReqSiteVerification;
use App\MicroServices\DocUpload;
use App\MicroServices\IdGeneration;
use App\Models\CustomDetail;
use App\Models\Payment\WebhookPaymentData;
use App\Models\Property\PaymentPropPenaltyrebate;
use App\Models\Property\PaymentPropPenalty;
use App\Models\Property\PaymentPropRebate;
use App\Models\Property\PropActiveSaf;
use App\Models\Property\PropActiveSafsDoc;
use App\Models\Property\PropActiveSafsFloor;
use App\Models\Property\PropActiveSafsOwner;
use App\Models\Property\PropFloor;
use App\Models\Property\PropLevelPending;
use App\Models\Property\PropOwner;
use App\Models\Property\PropPenalty;
use App\Models\Property\PropPenaltyrebate;
use App\Models\Property\PropProperty;
use App\Models\Property\PropSafGeotagUpload;
use App\Models\Property\PropSafsDemand;
use App\Models\Property\PropSafVerification;
use App\Models\Property\PropSafVerificationDtl;
use App\Models\Property\PropTranDtl;
use App\Models\Property\PropTransaction;
use App\Models\Property\RefPropConstructionType;
use App\Models\Property\RefPropFloor;
use App\Models\Property\RefPropOccupancyType;
use App\Models\Property\RefPropOwnershipType;
use App\Models\Property\RefPropRoadType;
use App\Models\Property\RefPropTransferMode;
use App\Models\Property\RefPropType;
use App\Models\Property\RefPropUsageType;
use App\Models\UlbWardMaster;
use App\Models\Workflows\WfActiveDocument;
use App\Models\Workflows\WfRoleusermap;
use App\Models\Workflows\WfWardUser;
use App\Models\Workflows\WfWorkflow;
use App\Models\WorkflowTrack;
use App\Repository\Property\Concrete\PropertyBifurcation;
use Illuminate\Http\Request;
use App\Repository\Property\Interfaces\iSafRepository;
use App\Repository\WorkflowMaster\Concrete\WorkflowMap;
use App\Traits\Payment\Razorpay;
use App\Traits\Property\SAF;
use App\Traits\Property\SafDetailsTrait;
use App\Traits\Workflow\Workflow;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class ActiveSafController extends Controller
{
    use Workflow;
    use SAF;
    use Razorpay;
    use SafDetailsTrait;
    /**
     * | Created On-10-08-2022
     * | Created By-Anshu Kumar
     * -----------------------------------------------------------------------------------------
     * | SAF Module all operations 
     * | --------------------------- Workflow Parameters ---------------------------------------
     * |                                 # SAF New Assessment
     * | wf_master id=4 
     * | wf_workflow_id=4
     * |                                 # SAF Reassessment 
     * | wf_mstr_id=5
     * | wf_workflow_id=3
     * |                                 # SAF Mutation
     * | wf_mstr_id=9
     * | wf_workflow_id=5
     */

    protected $user_id;
    protected $_todayDate;
    protected $_workflowIds;
    protected $Repository;
    // Initializing function for Repository
    protected $saf_repository;
    public function __construct(iSafRepository $saf_repository)
    {
        $this->Repository = $saf_repository;
        $this->_workflowIds = Config::get('PropertyConstaint.SAF_WORKFLOWS');
        $this->_todayDate = Carbon::now();
    }

    /**
     * | Master data in Saf Apply
     * | @var ulbId Logged In User Ulb 
     * | Status-Closed
     * | Query Costing-369ms 
     * | Rating-3
     */
    public function masterSaf()
    {
        try {
            $redisConn = Redis::connection();
            $data = [];
            $ulbId = auth()->user()->ulb_id;
            $ulbWardMaster = new UlbWardMaster();
            $refPropOwnershipType = new RefPropOwnershipType();
            $refPropType = new RefPropType();
            $refPropFloor = new RefPropFloor();
            $refPropUsageType = new RefPropUsageType();
            $refPropOccupancyType = new RefPropOccupancyType();
            $refPropConstructionType = new RefPropConstructionType();
            $refPropTransferMode = new RefPropTransferMode();
            $refPropRoadType = new RefPropRoadType();

            // Getting Masters from Redis Cache
            $wardMaster = json_decode(Redis::get('wards-ulb-' . $ulbId));
            $ownershipTypes = json_decode(Redis::get('prop-ownership-types'));
            $propertyType = json_decode(Redis::get('property-types'));
            $floorType = json_decode(Redis::get('property-floors'));
            $usageType = json_decode(Redis::get('property-usage-types'));
            $occupancyType = json_decode(Redis::get('property-occupancy-types'));
            $constructionType = json_decode(Redis::get('property-construction-types'));
            $transferModuleType = json_decode(Redis::get('property-transfer-modes'));
            $roadType = json_decode(Redis::get('property-road-type'));

            // Ward Masters
            if (!$wardMaster) {
                $wardMaster = $ulbWardMaster->getWardByUlbId($ulbId);   // <----- Get Ward by Ulb ID By Model Function
                $redisConn->set('wards-ulb-' . $ulbId, json_encode($wardMaster));            // Caching
            }

            $data['ward_master'] = $wardMaster;

            // Ownership Types
            if (!$ownershipTypes) {
                $ownershipTypes = $refPropOwnershipType->getPropOwnerTypes();   // <--- Get Property OwnerShip Types
                $redisConn->set('prop-ownership-types', json_encode($ownershipTypes));
            }

            $data['ownership_types'] = $ownershipTypes;

            // Property Types
            if (!$propertyType) {
                $propertyType = $refPropType->propPropertyType();
                $redisConn->set('property-types', json_encode($propertyType));
            }

            $data['property_type'] = $propertyType;

            // Property Floors
            if (!$floorType) {
                $floorType = $refPropFloor->getPropTypes();
                $redisConn->set('propery-floors', json_encode($floorType));
            }

            $data['floor_type'] = $floorType;

            // Property Usage Types
            if (!$usageType) {
                $usageType = $refPropUsageType->propUsageType();
                $redisConn->set('property-usage-types', json_encode($usageType));
            }

            $data['usage_type'] = $usageType;

            // Property Occupancy Types
            if (!$occupancyType) {
                $occupancyType = $refPropOccupancyType->propOccupancyType();
                $redisConn->set('property-occupancy-types', json_encode($occupancyType));
            }

            $data['occupancy_type'] = $occupancyType;

            // property construction types
            if (!$constructionType) {
                $constructionType = $refPropConstructionType->propConstructionType();
                $redisConn->set('property-construction-types', json_encode($constructionType));
            }

            $data['construction_type'] = $constructionType;

            // property transfer modes
            if (!$transferModuleType) {
                $transferModuleType = $refPropTransferMode->getTransferModes();
                $redisConn->set('property-transfer-modes', json_encode($transferModuleType));
            }

            $data['transfer_mode'] = $transferModuleType;

            // road type master
            if (!$roadType) {
                $roadType = $refPropRoadType->propRoadType();
                $redisConn->set('property-road-type', json_encode($roadType));
            }

            $data['road_type'] = $roadType;

            return responseMsgs(true, 'Property Masters', $data, "010101", "1.0", "317ms", "GET", "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Apply for New Application
     * | Status-Closed
     * | Query Costing-500 ms
     * | Rating-5
     */
    public function applySaf(reqApplySaf $request)
    {
        try {
            $mApplyDate = Carbon::now()->format("Y-m-d");
            $user_id = auth()->user()->id;
            $ulb_id = $request->ulbId;
            $demand = array();
            $metaReqs = array();
            $assessmentTypeId = $request->assessmentType;
            if ($request->assessmentType == 1) {                                                    // New Assessment 
                $workflow_id = Config::get('workflow-constants.SAF_WORKFLOW_ID');
                $request->assessmentType = Config::get('PropertyConstaint.ASSESSMENT-TYPE.1');
            }

            if ($request->assessmentType == 2) {                                                    // Reassessment
                $workflow_id = Config::get('workflow-constants.SAF_REASSESSMENT_ID');
                $request->assessmentType = Config::get('PropertyConstaint.ASSESSMENT-TYPE.2');
            }

            if ($request->assessmentType == 3) {                                                    // Mutation
                $workflow_id = Config::get('workflow-constants.SAF_MUTATION_ID');
                $request->assessmentType = Config::get('PropertyConstaint.ASSESSMENT-TYPE.3');
            }

            $ulbWorkflowId = WfWorkflow::where('wf_master_id', $workflow_id)
                ->where('ulb_id', $ulb_id)
                ->first();

            $roadWidthType = $this->readRoadWidthType($request->roadType);          // Read Road Width Type

            $safCalculation = new SafCalculation();
            $request->request->add(['road_type_mstr_id' => $roadWidthType]);
            $safTaxes = $safCalculation->calculateTax($request);
            $mLateAssessPenalty = $safTaxes->original['data']['demand']['lateAssessmentPenalty'];

            $refInitiatorRoleId = $this->getInitiatorId($ulbWorkflowId->id);                                // Get Current Initiator ID
            $initiatorRoleId = DB::select($refInitiatorRoleId);

            $refFinisherRoleId = $this->getFinisherId($ulbWorkflowId->id);
            $finisherRoleId = DB::select($refFinisherRoleId);

            DB::beginTransaction();
            $saf = new PropActiveSaf();

            $metaReqs['lateAssessPenalty'] = $mLateAssessPenalty;
            // $metaReqs['safNo'] = $safNo;
            $metaReqs['roadWidthType'] = $roadWidthType;
            $metaReqs['userId'] = $user_id;
            $metaReqs['workflowId'] = $ulbWorkflowId->id;
            $metaReqs['ulbId'] = $ulb_id;
            $metaReqs['initiatorRoleId'] = collect($initiatorRoleId)->first()->role_id;;
            $metaReqs['finisherRoleId'] = collect($finisherRoleId)->first()->role_id;

            $request->merge($metaReqs);
            $createSaf = $saf->store($request);                                             // Store SAF Using Model function 
            $safId = $createSaf->original['safId'];
            $safNo = $createSaf->original['safNo'];

            // SAF Owner Details
            if ($request['owner']) {
                $owner_detail = $request['owner'];
                foreach ($owner_detail as $owner_details) {
                    $owner = new PropActiveSafsOwner();
                    $this->tApplySafOwner($owner, $safId, $owner_details);                                    // Trait Owner Details
                    $owner->save();
                }
            }

            // Floor Details
            if ($request['floor']) {
                $floor_detail = $request['floor'];
                foreach ($floor_detail as $floor_details) {
                    $floor = new PropActiveSafsFloor();
                    $this->tApplySafFloor($floor, $safId, $floor_details);
                    $floor->save();
                }
            }

            // Insert Tax
            $demand['amounts'] = $safTaxes->original['data']['demand'];
            $demand['details'] = $this->generateSafDemand($safTaxes->original['data']['details']);

            $tax = new InsertTax();
            $tax->insertTax($safId, $ulb_id, $safTaxes);                                               // Insert SAF Tax

            DB::commit();
            return responseMsgs(true, "Successfully Submitted Your Application Your SAF No. $safNo", [
                "safNo" => $safNo,
                "applyDate" => $mApplyDate,
                "safId" => $safId,
                "demand" => $demand
            ], "010102", "1.0", "1s", "POST", $request->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "010102", "1.0", "1s", "POST", $request->deviceId);
        }
    }

    /**
     * | Edit Applied Saf by SAF Id for BackOffice
     * | @param request $req
     */
    public function editSaf(Request $req)
    {
        $req->validate([
            'id' => 'required|numeric',
            'owner' => 'array',
            'owner.*.ownerId' => 'required|numeric',
            'owner.*.ownerName' => 'required',
            'owner.*.guardianName' => 'required',
            'owner.*.relation' => 'required',
            'owner.*.mobileNo' => 'numeric|string|digits:10',
            'owner.*.aadhar' => 'numeric|string|digits:12|nullable',
            'owner.*.email' => 'email|nullable',
        ]);

        try {
            $mPropSaf = new PropActiveSaf();
            $mPropSafOwners = new PropActiveSafsOwner();
            $mOwners = $req->owner;

            DB::beginTransaction();
            $mPropSaf->edit($req);                                                      // Updation SAF Basic Details

            collect($mOwners)->map(function ($owner) use ($mPropSafOwners) {            // Updation of Owner Basic Details
                $mPropSafOwners->edit($owner);
            });

            DB::commit();
            return responseMsgs(true, "Successfully Updated the Data", "", 010124, 1.0, "308ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", 010124, 1.0, "308ms", "POST", $req->deviceId);
        }
    }

    /**
     * ---------------------- Saf Workflow Inbox --------------------
     * | Initialization
     * -----------------
     * | @var userId > logged in user id
     * | @var ulbId > Logged In user ulb Id
     * | @var refWorkflowId > Workflow ID 
     * | @var workflowId > SAF Wf Workflow ID 
     * | @var query > Contains the Pg Sql query
     * | @var workflow > get the Data in laravel Collection
     * | @var checkDataExisting > check the fetched data collection in array
     * | @var roleId > Fetch all the Roles for the Logged In user
     * | @var data > all the Saf data of current logged roleid 
     * | @var occupiedWard > get all Permitted Ward Of current logged in user id
     * | @var wardId > filtered Ward Id from the data collection
     * | @var safInbox > Final returned Data
     * | @return response #safInbox
     * | Status-Closed
     * | Query Cost-327ms 
     * | Rating-3
     * ---------------------------------------------------------------
     */
    #Inbox
    public function inbox()
    {
        try {
            $mWfRoleUser = new WfRoleusermap();
            $mWfWardUser = new WfWardUser();

            $userId = auth()->user()->id;
            $ulbId = auth()->user()->ulb_id;
            $readWards = $mWfWardUser->getWardsByUserId($userId);                       // Model () to get Occupied Wards of Current User

            $occupiedWards = collect($readWards)->map(function ($ward) {
                return $ward->ward_id;
            });

            $readRoles = $mWfRoleUser->getRoleIdByUserId($userId);                      // Model to () get Role By User Id

            $roleIds = $readRoles->map(function ($role, $key) {
                return $role->wf_role_id;
            });

            $data = $this->Repository->getSaf($this->_workflowIds)                     // Repository function to get SAF Details
                ->where('parked', false)
                ->where('prop_active_safs.ulb_id', $ulbId)
                ->where('prop_active_safs.status', 1)
                ->whereIn('current_role', $roleIds)
                ->orderByDesc('id')
                ->groupBy('prop_active_safs.id', 'p.property_type', 'ward.ward_name')
                ->get();

            $safInbox = $data->whereIn('ward_mstr_id', $occupiedWards);

            return responseMsgs(true, "Data Fetched", remove_null($safInbox->values()), "010103", "1.0", "339ms", "POST", "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Inbox for the Back To Citizen parked true
     * | @var mUserId authenticated user id
     * | @var mUlbId authenticated user ulb id
     * | @var readWards get all the wards of the user id
     * | @var occupiedWardsId get all the wards id of the user id
     * | @var readRoles get all the roles of the user id
     * | @var roleIds get all the logged in user role ids
     */
    public function btcInbox(Request $req)
    {
        try {
            $mWfRoleUser = new WfRoleusermap();
            $mWfWardUser = new WfWardUser();

            $mUserId = authUser()->id;
            $mUlbId = authUser()->ulb_id;
            $mDeviceId = $req->deviceId ?? "";

            $readWards = $mWfWardUser->getWardsByUserId($mUserId);                  // Model function to get ward list
            $occupiedWardsId = collect($readWards)->map(function ($ward) {              // Collection filteration
                return $ward->ward_id;
            });

            $readRoles = $mWfRoleUser->getRoleIdByUserId($mUserId);                 // Model function to get Role By User Id
            $roleIds = $readRoles->map(function ($role, $key) {
                return $role->wf_role_id;
            });

            $data = $this->Repository->getSaf($this->_workflowIds)                 // Repository function getSAF
                ->where('parked', true)
                ->where('prop_active_safs.ulb_id', $mUlbId)
                ->where('prop_active_safs.status', 1)
                ->whereIn('current_role', $roleIds)
                ->orderByDesc('id')
                ->groupBy('prop_active_safs.id', 'p.property_type', 'ward.ward_name')
                ->get();

            $safInbox = $data->whereIn('ward_mstr_id', $occupiedWardsId);
            return responseMsgs(true, "BTC Inbox List", remove_null($safInbox), 010123, 1.0, "271ms", "POST", $mDeviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", 010123, 1.0, "271ms", "POST", $mDeviceId);
        }
    }

    /**
     * | Fields Verified Inbox
     */
    public function fieldVerifiedInbox(Request $req)
    {
        try {
            $mWfRoleUser = new WfRoleusermap();
            $mWfWardUser = new WfWardUser();

            $mUserId = authUser()->id;
            $mUlbId = authUser()->ulb_id;
            $mDeviceId = $req->deviceId ?? "";

            $readWards = $mWfWardUser->getWardsByUserId($mUserId);                  // Model function to get ward list
            $occupiedWardsId = collect($readWards)->map(function ($ward) {          // Collection filteration
                return $ward->ward_id;
            });

            $readRoles = $mWfRoleUser->getRoleIdByUserId($mUserId);                 // Model function to get Role By User Id
            $roleIds = $readRoles->map(function ($role, $key) {
                return $role->wf_role_id;
            });

            $data = $this->Repository->getSaf($this->_workflowIds)                 // Repository function getSAF
                ->where('is_field_verified', true)
                ->where('prop_active_safs.ulb_id', $mUlbId)
                ->where('prop_active_safs.status', 1)
                ->whereIn('current_role', $roleIds)
                ->orderByDesc('id')
                ->groupBy('prop_active_safs.id', 'p.property_type', 'ward.ward_name')
                ->get();

            $safInbox = $data->whereIn('ward_mstr_id', $occupiedWardsId);
            return responseMsgs(true, "field Verified Inbox!", remove_null($safInbox), 010125, 1.0, "", "POST", $mDeviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", 010125, 1.0, "", "POST", $mDeviceId);
        }
    }

    /**
     * | Saf Outbox
     * | @var userId authenticated user id
     * | @var ulbId authenticated user Ulb Id
     * | @var workflowRoles get All Roles of the user id
     * | @var roles filteration of roleid from collections
     * | Status-Closed
     * | Query Cost-369ms 
     * | Rating-4
     */

    public function outbox()
    {
        try {
            $mWfRoleUser = new WfRoleusermap();
            $mWfWardUser = new WfWardUser();

            $userId = auth()->user()->id;
            $ulbId = auth()->user()->ulb_id;

            $workflowRoles = $mWfRoleUser->getRoleIdByUserId($userId);
            $roles = $workflowRoles->map(function ($value, $key) {
                return $value->wf_role_id;
            });

            $refWard = $mWfWardUser->getWardsByUserId($userId);
            $wardId = $refWard->map(function ($value, $key) {
                return $value->ward_id;
            });

            $safData = $this->Repository->getSaf($this->_workflowIds)   // Repository function to get SAF
                ->where('prop_active_safs.ulb_id', $ulbId)
                ->whereNotIn('current_role', $roles)
                ->whereIn('ward_mstr_id', $wardId)
                ->orderByDesc('id')
                ->groupBy('prop_active_safs.id', 'p.property_type', 'ward.ward_name')
                ->get();
            return responseMsgs(true, "Data Fetched", remove_null($safData->values()), "010104", "1.0", "274ms", "POST", "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | @var ulbId authenticated user id
     * | @var ulbId authenticated ulb Id
     * | @var occupiedWard get ward by user id using trait
     * | @var wardId Filtered Ward ID from the collections
     * | @var safData SAF Data List
     * | @return
     * | @var \Illuminate\Support\Collection $safData
     * | Status-Closed
     * | Query Costing-336ms 
     * | Rating-2 
     */
    public function specialInbox()
    {
        try {
            $mWfWardUser = new WfWardUser();
            $userId = authUser()->id;
            $ulbId = authUser()->ulb_id;
            $occupiedWard = $mWfWardUser->getWardsByUserId($userId);                        // Get All Occupied Ward By user id using trait
            $wardId = $occupiedWard->map(function ($item, $key) {                           // Filter All ward_id in an array using laravel collections
                return $item->ward_id;
            });
            $safData = $this->Repository->getSaf($this->_workflowIds)                      // Repository function to get SAF Details
                ->where('is_escalate', 1)
                ->where('prop_active_safs.ulb_id', $ulbId)
                ->whereIn('ward_mstr_id', $wardId)
                ->orderByDesc('id')
                ->groupBy('prop_active_safs.id', 'prop_active_safs.saf_no', 'ward.ward_name', 'p.property_type')
                ->get();
            return responseMsgs(true, "Data Fetched", remove_null($safData), "010107", "1.0", "251ms", "POST", "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * @param \Illuminate\Http\Request $req
     * @return \Illuminate\Http\JsonResponse
     * desc This function get the application brief details 
     * request : saf_id (requirde)
     * ---------------Tables-----------------
     * active_saf_details            |
     * ward_mastrs                   | Saf details
     * property_type                 |
     * active_saf_owner_details      -> Saf Owner details
     * active_saf_floore_details     -> Saf Floore Details
     * workflow_tracks               |  
     * users                         | Comments and  date rolles
     * role_masters                  |
     * =======================================
     * helpers : Helpers/utility_helper.php   ->remove_null() -> for remove  null values
     * | Status-Closed
     * | Query Cost-378ms 
     * | Rating-4 
     */
    #Saf Details
    public function safDetails(Request $req)
    {
        $req->validate([
            'applicationId' => 'required|integer'
        ]);

        try {
            $mPropActiveSaf = new PropActiveSaf();
            $mPropActiveSafOwner = new PropActiveSafsOwner();
            $mActiveSafsFloors = new PropActiveSafsFloor();
            $mWorkflowTracks = new WorkflowTrack();
            $mCustomDetails = new CustomDetail();
            $getDocuments = new PropertyBifurcation();
            $forwardBackward = new WorkflowMap;
            $mRefTable = Config::get('PropertyConstaint.SAF_REF_TABLE');
            // Saf Details
            $data = array();
            $fullDetailsData = array();
            if ($req->applicationId) {                                       //<------- Search By SAF ID
                $data = $mPropActiveSaf->getActiveSafDtls()      // <------- Model function Active SAF Details
                    ->where('prop_active_safs.id', $req->applicationId)
                    ->first();
            }
            if ($req->safNo) {                                  // <-------- Search By SAF No
                $data = $mPropActiveSaf->getActiveSafDtls()    // <------- Model Function Active SAF Details
                    ->where('prop_active_safs.saf_no', $req->safNo)
                    ->first();
            }

            if (!$data)
                throw new Exception("Application Not Found for this id");

            // Basic Details
            $basicDetails = $this->generateBasicDetails($data);      // Trait function to get Basic Details
            $basicElement = [
                'headerTitle' => "Basic Details",
                "data" => $basicDetails
            ];

            // Property Details
            $propertyDetails = $this->generatePropertyDetails($data);   // Trait function to get Property Details
            $propertyElement = [
                'headerTitle' => "Property Details & Address",
                'data' => $propertyDetails
            ];

            // Corresponding Address Details
            $corrDetails = $this->generateCorrDtls($data);              // Trait function to generate corresponding address details
            $corrElement = [
                'headerTitle' => 'Corresponding Address',
                'data' => $corrDetails,
            ];

            // Electricity & Water Details
            $electDetails = $this->generateElectDtls($data);            // Trait function to generate Electricity Details
            $electElement = [
                'headerTitle' => 'Electricity & Water Details',
                'data' => $electDetails
            ];
            $fullDetailsData['application_no'] = $data->saf_no;
            $fullDetailsData['apply_date'] = $data->application_date;
            $fullDetailsData['fullDetailsData']['dataArray'] = new Collection([$basicElement, $propertyElement, $corrElement, $electElement]);
            // Table Array
            // Owner Details
            $getOwnerDetails = $mPropActiveSafOwner->getOwnersBySafId($data->id);    // Model function to get Owner Details
            $ownerDetails = $this->generateOwnerDetails($getOwnerDetails);
            $ownerElement = [
                'headerTitle' => 'Owner Details',
                'tableHead' => ["#", "Owner Name", "Gender", "DOB", "Guardian Name", "Relation", "Mobile No", "Aadhar", "PAN", "Email", "IsArmedForce", "isSpeciallyAbled"],
                'tableData' => $ownerDetails
            ];
            // Floor Details
            $getFloorDtls = $mActiveSafsFloors->getFloorsBySafId($data->id);      // Model Function to Get Floor Details
            $floorDetails = $this->generateFloorDetails($getFloorDtls);
            $floorElement = [
                'headerTitle' => 'Floor Details',
                'tableHead' => ["#", "Floor", "Usage Type", "Occupancy Type", "Construction Type", "Build Up Area", "From Date", "Upto Date"],
                'tableData' => $floorDetails
            ];
            $fullDetailsData['fullDetailsData']['tableArray'] = new Collection([$ownerElement, $floorElement]);
            // Card Detail Format
            $cardDetails = $this->generateCardDetails($data, $getOwnerDetails);
            $cardElement = [
                'headerTitle' => "About Property",
                'data' => $cardDetails
            ];
            $fullDetailsData['fullDetailsData']['cardArray'] = new Collection($cardElement);
            $data = json_decode(json_encode($data), true);
            $metaReqs['customFor'] = 'SAF';
            $metaReqs['wfRoleId'] = $data['current_role'];
            $metaReqs['workflowId'] = $data['workflow_id'];
            $metaReqs['lastRoleId'] = $data['last_role_id'];

            $levelComment = $mWorkflowTracks->getTracksByRefId($mRefTable, $data['id']);
            $fullDetailsData['levelComment'] = $levelComment;

            $citizenComment = $mWorkflowTracks->getCitizenTracks($mRefTable, $data['id'], $data['user_id']);
            $fullDetailsData['citizenComment'] = $citizenComment;

            $req->request->add($metaReqs);
            $forwardBackward = $forwardBackward->getRoleDetails($req);
            $fullDetailsData['roleDetails'] = collect($forwardBackward)['original']['data'];

            $fullDetailsData['timelineData'] = collect($req);

            $custom = $mCustomDetails->getCustomDetails($req);
            $fullDetailsData['departmentalPost'] = collect($custom)['original']['data'];

            $docList = $getDocuments->getUploadDocuments($req);
            $fullDetailsData['documentList'] = collect($docList)['original']['data'];

            return responseMsgs(true, 'Data Fetched', remove_null($fullDetailsData), "010104", "1.0", "303ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * @var userId Logged In User Id
     * desc This function set OR remove application on special category
     * request : escalateStatus (required, int type), safId(required)
     * -----------------Tables---------------------
     *  active_saf_details
     * ============================================
     * active_saf_details.is_escalate <- request->escalateStatus 
     * active_saf_details.escalate_by <- request->escalateStatus 
     * ============================================
     * #message -> return response 
     * Status-Closed
     * | Query Cost-353ms 
     * | Rating-1
     */
    public function postEscalate(Request $request)
    {
        $request->validate([
            "escalateStatus" => "required|int",
            "applicationId" => "required|int",
        ]);
        try {
            $userId = auth()->user()->id;
            $saf_id = $request->applicationId;
            $data = PropActiveSaf::find($saf_id);
            $data->is_escalate = $request->escalateStatus;
            $data->escalate_by = $userId;
            $data->save();
            return responseMsgs(true, $request->escalateStatus == 1 ? 'Saf is Escalated' : "Saf is removed from Escalated", '', "010106", "1.0", "353ms", "POST", $request->deviceId);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }

    // Post Independent Comment
    public function commentIndependent(Request $request)
    {
        $request->validate([
            'comment' => 'required',
            'applicationId' => 'required|integer',
            'senderRoleId' => 'nullable|integer'
        ]);

        try {
            $workflowTrack = new WorkflowTrack();
            $saf = PropActiveSaf::find($request->applicationId);                // SAF Details
            $mModuleId = Config::get('module-constants.PROPERTY_MODULE_ID');
            $metaReqs = array();
            DB::beginTransaction();
            // Save On Workflow Track For Level Independent
            $metaReqs = [
                'workflowId' => $saf->workflow_id,
                'moduleId' => $mModuleId,
                'refTableDotId' => "prop_active_safs.id",
                'refTableIdValue' => $saf->id,
                'message' => $request->comment
            ];
            // For Citizen Independent Comment
            if (!$request->senderRoleId) {
                $metaReqs = array_merge($metaReqs, ['citizenId' => $saf->user_id]);
            }

            $request->request->add($metaReqs);
            $workflowTrack->saveTrack($request);

            DB::commit();
            return responseMsgs(true, "You Have Commented Successfully!!", ['Comment' => $request->comment], "010108", "1.0", "", "POST", "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | @param mixed $request
     * | @var preLevelPending Get the Previous level pending data for the saf id
     * | @var levelPending new Level Pending to be add
     * | Status-Closed
     * | Query Costing-348ms 
     * | Rating-3 
     */
    public function postNextLevel(Request $request)
    {
        $request->validate([
            'applicationId' => 'required|integer',
            'senderRoleId' => 'required|integer',
            'receiverRoleId' => 'required|integer',
            'comment' => 'required',
        ]);

        try {
            $saf = PropActiveSaf::find($request->applicationId);
            // SAF Application Update Current Role Updation
            DB::beginTransaction();
            $saf->current_role = $request->receiverRoleId;
            $saf->save();

            $metaReqs['moduleId'] = Config::get('module-constants.PROPERTY_MODULE_ID');
            $metaReqs['workflowId'] = $saf->workflow_id;
            $metaReqs['refTableDotId'] = 'prop_active_safs.id';
            $metaReqs['refTableIdValue'] = $request->applicationId;
            $request->request->add($metaReqs);

            $track = new WorkflowTrack();
            $track->saveTrack($request);

            DB::commit();
            return responseMsgs(true, "Successfully Forwarded The Application!!", "", "010109", "1.0", "286ms", "POST", $request->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }

    /**
     * | Approve or Reject The SAF Application
     * --------------------------------------------------
     * | ----------------- Initialization ---------------
     * | @param mixed $req
     * | @var activeSaf The Saf Record by Saf Id
     * | @var approvedSaf replication of the saf record to be approved
     * | @var rejectedSaf replication of the saf record to be rejected
     * ------------------- Alogrithm ---------------------
     * | $req->status (if 1 Application to be approved && if 0 application to be rejected)
     * ------------------- Dump --------------------------
     * | @return msg
     * | Status-Closed
     * | Query Cost-430ms 
     * | Rating-3
     */
    public function approvalRejectionSaf(Request $req)
    {
        $req->validate([
            'workflowId' => 'required|integer',
            'roleId' => 'required|integer',
            'applicationId' => 'required|integer',
            'status' => 'required|integer'
        ]);

        try {
            // Check if the Current User is Finisher or Not
            $safDetails = PropActiveSaf::find($req->applicationId);
            $propSafVerification = new PropSafVerification();
            $propSafVerificationDtl = new PropSafVerificationDtl();
            if ($safDetails->finisher_role_id != $req->roleId) {
                return responseMsg(false, "Forbidden Access", "");
            }
            $reAssessment = Config::get('PropertyConstaint.ASSESSMENT-TYPE.2');

            $activeSaf = PropActiveSaf::query()
                ->where('id', $req->applicationId)
                ->first();
            $ownerDetails = PropActiveSafsOwner::query()
                ->where('saf_id', $req->applicationId)
                ->get();
            $floorDetails = PropActiveSafsFloor::query()
                ->where('saf_id', $req->applicationId)
                ->get();

            DB::beginTransaction();
            // Approval
            if ($req->status == 1) {
                if ($req->assessmentType == $reAssessment)
                    $safDetails->holding_no = $safDetails->previous_holding_id;
                if ($req->assessmentType != $reAssessment) {
                    $safDetails->holding_no = 'HOL-SAF-' . $req->applicationId;
                }

                $safDetails->fam_no = 'FAM/' . $req->applicationId;
                $safDetails->saf_pending_status = 0;
                $safDetails->save();

                // SAF Application replication
                $toBeProperties = PropActiveSaf::query()
                    ->where('id', $req->applicationId)
                    ->select(
                        'ulb_id',
                        'cluster_id',
                        'holding_no',
                        'applicant_name',
                        'ward_mstr_id',
                        'ownership_type_mstr_id',
                        'prop_type_mstr_id',
                        'appartment_name',
                        'no_electric_connection',
                        'elect_consumer_no',
                        'elect_acc_no',
                        'elect_bind_book_no',
                        'elect_cons_category',
                        'building_plan_approval_no',
                        'building_plan_approval_date',
                        'water_conn_no',
                        'water_conn_date',
                        'khata_no',
                        'plot_no',
                        'village_mauja_name',
                        'road_type_mstr_id',
                        'area_of_plot',
                        'prop_address',
                        'prop_city',
                        'prop_dist',
                        'prop_pin_code',
                        'prop_state',
                        'corr_address',
                        'corr_city',
                        'corr_dist',
                        'corr_pin_code',
                        'corr_state',
                        'is_mobile_tower',
                        'tower_area',
                        'tower_installation_date',
                        'is_hoarding_board',
                        'hoarding_area',
                        'hoarding_installation_date',
                        'is_petrol_pump',
                        'under_ground_area',
                        'petrol_pump_completion_date',
                        'is_water_harvesting',
                        'land_occupation_date',
                        'new_ward_mstr_id',
                        'zone_mstr_id',
                        'flat_registry_date',
                        'assessment_type',
                        'holding_type',
                        'apartment_details_id',
                        'ip_address',
                        'status',
                        'user_id'
                    )->first();

                $propProperties = $toBeProperties->replicate();
                $propProperties->setTable('prop_properties');
                $propProperties->saf_id = $activeSaf->id;
                $propProperties->new_holding_no = $activeSaf->holding_no;
                $propProperties->save();

                $approvedSaf = $activeSaf->replicate();
                $approvedSaf->setTable('prop_safs');
                $approvedSaf->id = $activeSaf->id;
                $approvedSaf->property_id = $propProperties->id;
                $approvedSaf->save();

                $activeSaf->delete();

                // SAF Owners replication
                foreach ($ownerDetails as $ownerDetail) {
                    $approvedOwner = $ownerDetail->replicate();
                    $approvedOwner->setTable('prop_safs_owners');
                    $approvedOwner->id = $ownerDetail->id;
                    $approvedOwner->save();

                    $approvedOwners = $ownerDetail->replicate();
                    $approvedOwners->setTable('prop_owners');
                    $approvedOwners->property_id = $propProperties->id;
                    $approvedOwners->save();

                    $ownerDetail->delete();
                }

                // SAF Floors Replication
                foreach ($floorDetails as $floorDetail) {
                    $approvedFloor = $floorDetail->replicate();
                    $approvedFloor->setTable('prop_safs_floors');
                    $approvedFloor->id = $floorDetail->id;
                    $approvedFloor->save();

                    $propFloor = $floorDetail->replicate();
                    $propFloor->setTable('prop_floors');
                    $propFloor->property_id = $propProperties->id;
                    $propFloor->save();

                    $floorDetail->delete();
                }

                $msg = "Application Successfully Approved !! Holding No " . $safDetails->holding_no;
            }
            // Rejection
            if ($req->status == 0) {
                // Rejected SAF Application replication
                $rejectedSaf = $activeSaf->replicate();
                $rejectedSaf->setTable('prop_rejected_safs');
                $rejectedSaf->id = $activeSaf->id;
                $rejectedSaf->push();
                $activeSaf->delete();

                // SAF Owners replication
                foreach ($ownerDetails as $ownerDetail) {
                    $approvedOwner = $ownerDetail->replicate();
                    $approvedOwner->setTable('prop_rejected_safs_owners');
                    $approvedOwner->id = $ownerDetail->id;
                    $approvedOwner->save();
                    $ownerDetail->delete();
                }

                // SAF Floors Replication
                foreach ($floorDetails as $floorDetail) {
                    $approvedFloor = $floorDetail->replicate();
                    $approvedFloor->setTable('prop_rejected_safs_floors');
                    $approvedFloor->id = $floorDetail->id;
                    $approvedFloor->save();
                    $floorDetail->delete();
                }

                $msg = "Application Rejected Successfully";
            }

            $propSafVerification->deactivateVerifications($req->applicationId);                 // Deactivate Verification From Table
            $propSafVerificationDtl->deactivateVerifications($req->applicationId);              // Deactivate Verification from Saf floor Dtls
            DB::commit();
            return responseMsgs(true, $msg, ['holdingNo' => $safDetails->holding_no], "010110", "1.0", "410ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Back to Citizen
     * | @param Request $req
     * | @var redis Establishing Redis Connection
     * | @var workflowId Workflow id of the SAF 
     * | Status-Closed
     * | Query Costing-401ms
     * | Rating-1 
     */
    public function backToCitizen(Request $req)
    {
        $req->validate([
            'applicationId' => 'required|integer',
            'workflowId' => 'required|integer',
            'currentRoleId' => 'required|integer',
            'comment' => 'required|string'
        ]);

        try {
            $saf = PropActiveSaf::find($req->applicationId);
            $track = new WorkflowTrack();
            DB::beginTransaction();
            $initiatorRoleId = $saf->initiator_role_id;
            $saf->current_role = $initiatorRoleId;
            $saf->parked = true;                        //<------ SAF Pending Status true
            $saf->save();

            $metaReqs['moduleId'] = Config::get('module-constants.PROPERTY_MODULE_ID');
            $metaReqs['workflowId'] = $saf->workflow_id;
            $metaReqs['refTableDotId'] = 'prop_active_safs.id';
            $metaReqs['refTableIdValue'] = $req->applicationId;
            $req->request->add($metaReqs);
            $track->saveTrack($req);

            DB::commit();
            return responseMsgs(true, "Successfully Done", "", "010111", "1.0", "350ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Calculate SAF by Saf ID
     * | @param req request saf id
     * | @var array contains all the details for the saf id
     * | @var data contains the details of the saf id by the current object function
     * | @return safTaxes returns all the calculated demand
     * | Status-Closed
     * | Query Costing-417ms
     * | Rating-3 
     */
    public function calculateSafBySafId(Request $req)
    {
        $safDetails = $this->details($req);
        $safNo = $safDetails['saf_no'];
        $req = $safDetails;
        $array = $this->generateSafRequest($req);                                                                       // Generate SAF Request by SAF Id Using Trait
        $safCalculation = new SafCalculation();
        $request = new Request($array);
        $safTaxes = $safCalculation->calculateTax($request);
        $safTaxes = json_decode(json_encode($safTaxes), true);
        $safTaxes['original']['safNo'] = $safNo;
        return $safTaxes['original'];
    }

    /**
     * | Generate Order ID (14)
     * | @param req requested Data
     * | @var auth authenticated users credentials
     * | @var calculateSafById calculated SAF amounts and details by request SAF ID
     * | @var totalAmount filtered total amount from the collection
     * | Status-closed
     * | Query Costing-1.41s
     * | Rating - 5
     * */
    public function generateOrderId(Request $req)
    {
        $req->validate([
            'id' => 'required|integer',
            'amount' => 'required|numeric',
            'departmentId' => 'required|integer'
        ]);

        try {
            $auth = auth()->user();
            $calculateSafById = $this->calculateSafBySafId($req);
            $safDemandDetails = $this->generateSafDemand($calculateSafById['data']['details']);
            $safDetails = PropActiveSaf::find($req->id);
            $demands = $calculateSafById['data']['demand'];
            $totalAmount = $demands['payableAmount'];
            $req->request->add(['workflowId' => $safDetails->workflow_id]);
            $orderDetails = $this->saveGenerateOrderid($req);                                      //<---------- Generate Order ID Trait
            $orderDetails['name'] = $auth->user_name;
            $orderDetails['mobile'] = $auth->mobile;
            $orderDetails['email'] = $auth->email;
            DB::beginTransaction();

            $this->postDemands($safDemandDetails, $req, $safDetails);                               // Update the data in saf prop demands
            $this->postPenaltyRebates($calculateSafById, $req); // Post Penalty Rebates

            DB::commit();
            return responseMsgs(true, "Order ID Generated", remove_null($orderDetails), "010114", "1.0", "1s", "POST", $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Post Demands (14.1)
     */
    public function postDemands($safDemandDetails, $req, $safDetails)
    {
        $mSafDemand = new PropSafsDemand();
        $mPayPropPenalty = new PaymentPropPenalty();
        collect($safDemandDetails)->map(function ($safDemandDetail) use ($mSafDemand, $req, $safDetails, $mPayPropPenalty) {
            $propSafDemand = $mSafDemand->getPropSafDemands($safDemandDetail['quarterYear'], $safDemandDetail['qtr'], $req->id); // Get SAF demand from model function
            $reqs = [
                'saf_id' => $req->id,
                'arv' => $safDemandDetail['arv'],
                'water_tax' => $safDemandDetail['waterTax'],
                'education_cess' => $safDemandDetail['educationTax'],
                'health_cess' => $safDemandDetail['healthCess'],
                'latrine_tax' => $safDemandDetail['latrineTax'],
                'additional_tax' => $safDemandDetail['rwhPenalty'],
                'holding_tax' => $safDemandDetail['holdingTax'],
                'amount' => $safDemandDetail['totalTax'],
                'fyear' => $safDemandDetail['quarterYear'],
                'qtr' => $safDemandDetail['qtr'],
                'due_date' => $safDemandDetail['dueDate'],
                'user_id' => authUser()->id,
                'ulb_id' => $safDetails->ulb_id,
            ];
            if ($propSafDemand)                                                     // <---------------- If The Data is already Existing then update the data
                $mSafDemand->editDemands($propSafDemand['id'], $reqs);
            else                                                                    // <----------------- If not Existing then add new 
                $mSafDemand->postDemands($reqs);                                     // <--------- If Exist Update

            $penaltyExist = $mPayPropPenalty->getPenaltyByDemandSafId($propSafDemand['id'], $req->id);
            $penaltyReqs = [
                'saf_demand_id' => $propSafDemand['id'],
                'saf_id' => $req->id,
                'fyear' => $safDemandDetail['quarterYear'],
                'head_name' => 'Monthly 1 % Penalty',
                'penalty_date' => Carbon::now()->format('Y-m-d'),
                'amount' => $safDemandDetail['onePercPenaltyTax']
            ];
            if ($penaltyExist)
                $mPayPropPenalty->editPenalties($penaltyExist->id, $penaltyReqs);
            else
                $mPayPropPenalty->postPenalties($penaltyReqs);
        });
    }

    /**
     * | Post Penalty Rebates (14.2)
     */
    public function postPenaltyRebates($calculateSafById, $req)
    {
        $mPaymentRebatePanelties = new PaymentPropPenaltyrebate();
        $headNames = [
            [
                'keyString' => '1% Monthly Penalty',
                'value' => $calculateSafById['data']['demand']['totalOnePercPenalty'],
                'isRebate' => false
            ],
            [
                'keyString' => 'Late Assessment Fine(Rule 14.1)',
                'value' => $calculateSafById['data']['demand']['lateAssessmentPenalty'],
                'isRebate' => false
            ],
            [
                'keyString' => 'Special Rebate',
                'value' => $calculateSafById['data']['demand']['specialRebateAmount'],
                'isRebate' => true
            ],
            [
                'keyString' => 'Rebate',
                'value' => $calculateSafById['data']['demand']['rebateAmount'],
                'isRebate' => true
            ]
        ];

        collect($headNames)->map(function ($headName) use ($mPaymentRebatePanelties, $req) {
            $propPayRebatePenalty = $mPaymentRebatePanelties->getRebatePanelties('saf_id', $req->id, $headName['keyString']);
            if ($headName['value'] > 0) {
                $reqs = [
                    'saf_id' => $req->id,
                    'head_name' => $headName['keyString'],
                    'amount' => $headName['value'],
                    'is_rebate' => $headName['isRebate'],
                    'tran_date' => Carbon::now()->format('Y-m-d')
                ];

                if ($propPayRebatePenalty)
                    $mPaymentRebatePanelties->editRebatePenalty($propPayRebatePenalty->id, $reqs);
                else
                    $mPaymentRebatePanelties->postRebatePenalty($reqs);
            }
        });
    }

    /**
     * | SAF Payment
     * | @param req  
     * | @var workflowId SAF workflow ID
     * | Status-Closed
     * | Query Consting-374ms
     * | Rating-3
     */
    public function paymentSaf(Request $req)
    {
        try {
            $userId = $req['userId'];
            $propSafsDemand = new PropSafsDemand();
            $demands = $propSafsDemand->getDemandBySafId($req['id']);
            DB::beginTransaction();
            // Property Transactions
            $propTrans = new PropTransaction();
            $propTrans->saf_id = $req['id'];
            $propTrans->amount = $req['amount'];
            $propTrans->tran_date = Carbon::now()->format('Y-m-d');
            $propTrans->tran_no = $req['transactionNo'];
            $propTrans->payment_mode = $req['paymentMode'];
            $propTrans->user_id = $userId;
            $propTrans->save();

            // Reflect on Prop Tran Details
            foreach ($demands as $demand) {
                $demand->paid_status = 1;           // <-------- Update Demand Paid Status 
                $demand->save();

                $propTranDtl = new PropTranDtl();
                $propTranDtl->tran_id = $propTrans->id;
                $propTranDtl->saf_demand_id = $demand['id'];
                $propTranDtl->total_demand = $demand['amount'];
                $propTranDtl->save();
            }

            // Update SAF Payment Status
            $activeSaf = PropActiveSaf::find($req['id']);
            $activeSaf->payment_status = 1;
            $activeSaf->save();

            // Replication Prop Rebates Penalties
            $mPropPenalRebates = new PaymentPropPenaltyrebate();
            $rebatePenalties = $mPropPenalRebates->getPenalRebatesBySafId($req['id']);

            collect($rebatePenalties)->map(function ($rebatePenalty) use ($propTrans) {
                $replicate = $rebatePenalty->replicate();
                $replicate->setTable('prop_penaltyrebates');
                $replicate->tran_id = $propTrans->id;
                $replicate->tran_date = $this->_todayDate->format('Y-m-d');
                $replicate->save();
            });

            DB::commit();
            return responseMsgs(true, "Payment Successfully Done", "", "010115", "1.0", "567ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Generate Payment Receipt(1)
     * | @param request req
     * | Status-Closed
     * | Query Cost-3
     */
    public function generatePaymentReceipt(Request $req)
    {
        $req->validate([
            'tranNo' => 'required'
        ]);

        try {
            $paymentData = new WebhookPaymentData();
            $propSafsDemand = new PropSafsDemand();
            $transaction = new PropTransaction();
            $propPenalties = new PropPenaltyrebate();

            $mTowards = Config::get('PropertyConstaint.SAF_TOWARDS');
            $mAccDescription = Config::get('PropertyConstaint.ACCOUNT_DESCRIPTION');
            $mDepartmentSection = Config::get('PropertyConstaint.DEPARTMENT_SECTION');

            $applicationDtls = $paymentData->getApplicationId($req->tranNo);
            // Saf Payment
            $safId = json_decode($applicationDtls)->applicationId;

            $reqSafId = new Request(['id' => $safId]);
            $activeSafDetails = $this->details($reqSafId);
            $demands = $propSafsDemand->getDemandBySafId($safId);
            $calDemandAmt = collect($demands)->sum('amount');
            $checkOtherTaxes = collect($demands)->first();

            $mDescriptions = $this->readDescriptions($checkOtherTaxes);      // Check the Taxes are Only Holding or Not

            $fromFinYear = $demands->first()['fyear'];
            $fromFinQtr = $demands->first()['qtr'];
            $upToFinYear = $demands->last()['fyear'];
            $upToFinQtr = $demands->last()['qtr'];

            // Get PropertyTransactions
            $propTrans = $transaction->getPropTransactions($safId, "saf_id");
            $propTrans = collect($propTrans)->last();

            // Get Property Penalties against property transaction
            $mOnePercPenalty = $propPenalties->getPenalRebateByTranId($propTrans->id, "1% Monthly Penalty");

            $taxDetails = $this->readPenalyPmtAmts($activeSafDetails['late_assess_penalty'], $mOnePercPenalty->amount, $propTrans->amount);   // Get Holding Tax Dtls
            // Response Return Data
            $responseData = [
                "departmentSection" => $mDepartmentSection,
                "accountDescription" => $mAccDescription,
                "transactionDate" => $propTrans->tran_date,
                "transactionNo" => $propTrans->tran_no,
                "transactionTime" => $propTrans->created_at->format('H:i:s'),
                "applicationNo" => $activeSafDetails['saf_no'],
                "customerName" => $activeSafDetails['applicant_name'],
                "receiptWard" => $activeSafDetails['new_ward_no'],
                "address" => $activeSafDetails['prop_address'],
                "paidFrom" => $fromFinYear,
                "paidFromQtr" => $fromFinQtr,
                "paidUpto" => $upToFinYear,
                "paidUptoQtr" => $upToFinQtr,
                "paymentMode" => $propTrans->payment_mode,
                "bankName" => "",
                "branchName" => "",
                "chequeNo" => "",
                "chequeDate" => "",
                "noOfFlats" => "",
                "monthlyRate" => "",
                "demandAmount" => roundFigure($calDemandAmt),
                "taxDetails" => $taxDetails,
                "ulbId" => $activeSafDetails['ulb_id'],
                "oldWardNo" => $activeSafDetails['old_ward_no'],
                "newWardNo" => $activeSafDetails['new_ward_no'],
                "towards" => $mTowards,
                "description" => $mDescriptions,
                "totalPaidAmount" => $propTrans->amount,
                "paidAmtInWords" => getIndianCurrency($propTrans->amount),
            ];
            return responseMsgs(true, "Payment Receipt", remove_null($responseData), "010116", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "", "010116", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Read Taxes Descriptions(1.1)
     * | @param checkOtherTaxes first collection from the details
     */
    public function readDescriptions($checkOtherTaxes)
    {
        $taxes = [
            [
                "keyString" => "Holding Tax",
                "value" => $checkOtherTaxes->holding_tax
            ],
            [
                "keyString" => "Water Tax",
                "value" => $checkOtherTaxes->water_tax
            ],
            [
                "keyString" => "Education Cess",
                "value" => $checkOtherTaxes->education_cess
            ],
            [
                "keyString" => "Latrine Tax",
                "value" => $checkOtherTaxes->latrine_tax
            ]
        ];
        $filtered = collect($taxes)->filter(function ($tax, $key) {
            if ($tax['value'] > 0) {
                return $tax['keyString'];
            }
        });

        return $filtered;
    }
    /**
     * | Read Penalty Tax Details with Penalties and final payable amount(1.2)
     */
    public function readPenalyPmtAmts($lateAssessPenalty = 0, $onePercPenalty = 0, $amount)
    {
        $amount = [
            [
                "keyString" => "Late Assessment Fine(Rule 14.1)",
                "value" => $lateAssessPenalty
            ],
            [
                "keyString" => "1% Monthly Penalty",
                "value" => roundFigure($onePercPenalty)
            ],
            [
                "keyString" => "Total Paid Amount",
                "value" => roundFigure($amount)
            ],
            [
                "keyString" => "Remaining Amount",
                "value" => 0
            ]
        ];

        $tax = collect($amount)->filter(function ($value, $key) {
            return $value['value'] > 0;
        });

        return $tax->values();
    }

    /**
     * | Get Property Transactions
     * | @param req requested parameters
     * | @var userId authenticated user id
     * | @var propTrans Property Transaction details of the Logged In User
     * | @return responseMsg
     * | Status-Closed
     * | Run time Complexity-346ms
     * | Rating - 3
     */
    public function getPropTransactions(Request $req)
    {
        try {
            $redis = Redis::connection();
            $propTransaction = new PropTransaction();
            $userId = auth()->user()->id;

            $propTrans = json_decode(Redis::get('property-transactions-user-' . $userId));                      // Should Be Deleted SAF Payment
            if (!$propTrans) {
                $propTrans = $propTransaction->getPropTransByUserId($userId);
                $redis->set('property-transactions-user-' . $userId, json_encode($propTrans));
            }
            return responseMsgs(true, "Transactions History", remove_null($propTrans), "010117", "1.0", "265ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "010117", "1.0", "265ms", "POST", $req->deviceId);
        }
    }

    /**
     * | Get Transactions by Property id or SAF id
     * | @param Request $req
     */
    public function getTransactionBySafPropId(Request $req)
    {
        try {
            $propTransaction = new PropTransaction();
            if ($req->safId)                                                // Get By SAF Id
            {
                $propTrans = $propTransaction->getPropTransBySafId($req->safId);
            }
            if ($req->propertyId)                                           // Get by Property Id
                $propTrans = $propTransaction->getPropTransByPropId($req->propertyId);

            return responseMsg(true, "Property Transactions", remove_null($propTrans));
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Get Property Details by Property Holding No
     * | Rating - 2
     * | Run Time Complexity-500 ms
     */
    public function getPropByHoldingNo(Request $req)
    {
        try {
            $mProperties = new PropProperty();
            $mPropFloors = new PropFloor();
            $mPropOwners = new PropOwner();
            $propertyDtl = [];
            if ($req->holdingNo) {
                $properties = $mProperties->getPropDtls()
                    ->where('prop_properties.ward_mstr_id', $req->wardId)
                    ->where('prop_properties.holding_no', $req->holdingNo)
                    ->first();
            }

            if ($req->propertyId) {
                $properties = $mProperties->getPropDtls()
                    ->where('prop_properties.id', $req->propertyId)
                    ->first();
            }

            $floors = $mPropFloors->getPropFloors($properties->id);        // Model function to get Property Floors
            $owners = $mPropOwners->getOwnersByPropId($properties->id);    // Model function to get Property Owners
            $propertyDtl = collect($properties);
            $propertyDtl['floors'] = $floors;
            $propertyDtl['owners'] = $owners;

            return responseMsgs(true, "Property Details", remove_null($propertyDtl), "010112", "1.0", "", "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Site Verification
     * | @param req requested parameter
     * | Status-Closed
     */
    public function siteVerification(ReqSiteVerification $req)
    {
        try {
            $taxCollectorRole = Config::get('PropertyConstaint.SAF-LABEL.TC');
            $ulbTaxCollectorRole = Config::get('PropertyConstaint.SAF-LABEL.UTC');
            $verificationStatus = $req->verificationStatus;                                             // Verification Status true or false

            $propActiveSaf = new PropActiveSaf();
            $verification = new PropSafVerification();
            $mPropSafVeriDtls = new PropSafVerificationDtl();

            switch ($req->currentRoleId) {
                case $taxCollectorRole;                                                                  // In Case of Agency TAX Collector
                    if ($verificationStatus == 1) {
                        $req->agencyVerification = true;
                        $msg = "Site Successfully Verified";
                    }
                    if ($verificationStatus == 0) {
                        $req->agencyVerification = false;
                        $msg = "Site Successfully rebuted";
                    }
                    break;
                    DB::beginTransaction();
                case $ulbTaxCollectorRole;                                                                // In Case of Ulb Tax Collector
                    if ($verificationStatus == 1) {
                        $req->ulbVerification = true;
                        $msg = "Site Successfully Verified";
                    }
                    if ($verificationStatus == 0) {
                        $req->ulbVerification = false;
                        $msg = "Site Successfully rebuted";
                    }
                    $propActiveSaf->verifyFieldStatus($req->safId);                                         // Enable Fields Verify Status
                    break;

                default:
                    return responseMsg(false, "Forbidden Access", "");
            }

            // Verification Store
            $verificationId = $verification->store($req);                           // Model function to store verification and get the id
            // Verification Dtl Table Update                                         // For Tax Collector
            foreach ($req->floorDetails as $floorDetail) {
                $verificationDtl = new PropSafVerificationDtl();
                $verificationDtl->verification_id = $verificationId;
                $verificationDtl->saf_id = $req->safId;
                $verificationDtl->saf_floor_id = $floorDetail['floorId'];
                $verificationDtl->floor_mstr_id = $floorDetail['floorMstrId'];
                $verificationDtl->usage_type_id = $floorDetail['usageType'];
                $verificationDtl->construction_type_id = $floorDetail['constructionType'];
                $verificationDtl->occupancy_type_id = $floorDetail['occupancyType'];
                $verificationDtl->builtup_area = $floorDetail['builtupArea'];
                $verificationDtl->date_from = $floorDetail['fromDate'];
                $verificationDtl->date_to = $floorDetail['toDate'];
                $verificationDtl->save();
            }

            DB::commit();
            return responseMsgs(true, $msg, "", "010118", "1.0", "310ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Geo Tagging Photo Uploads
     * | @param request req
     * | @var relativePath Geo Tagging Document Ralative path
     * | @var array images- request image path
     * | @var array directionTypes- request direction types
     */
    public function geoTagging(Request $req)
    {
        $req->validate([
            "safId" => "required|integer",
            "imagePath.*" => "image|mimes:jpeg,jpg,png,gif|required"
        ]);
        try {
            $docUpload = new DocUpload;
            $relativePath = Config::get('PropertyConstaint.GEOTAGGING_RELATIVE_PATH');
            $images = $req->imagePath;
            $directionTypes = $req->directionType;

            collect($images)->map(function ($image, $key) use ($directionTypes, $relativePath, $req, $docUpload) {
                $geoTagging = new PropSafGeotagUpload();
                $refImageName = 'saf-geotagging-' . $directionTypes[$key] . '-' . $req->safId;

                $imageName = $docUpload->upload($refImageName, $image, $relativePath);         // <------- Get uploaded image name and move the image in folder

                $geoTagging->saf_id = $req->safId;
                $geoTagging->image_path = $imageName;
                $geoTagging->direction_type = $directionTypes[$key];
                $geoTagging->relative_path = $relativePath;
                $geoTagging->user_id = authUser()->id;
                $geoTagging->save();
            });

            return responseMsgs(true, "Geo Tagging Done Successfully", "", "010119", "1.0", "289ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    // Get TC Verifications
    public function getTcVerifications(Request $req)
    {
        try {
            $data = array();
            $safVerifications = new PropSafVerification();
            $safVerificationDtls = new PropSafVerificationDtl();

            $data = $safVerifications->getVerificationsData($req->safId);           // <--------- Prop Saf Verification Model Function to Get Prop Saf Verifications Data 

            $data = json_decode(json_encode($data), true);

            $verificationDtls = $safVerificationDtls->getFullVerificationDtls($data['id']);     // <----- Prop Saf Verification Model Function to Get Verification Floor Dtls

            $data['floorDetails'] = $verificationDtls;
            return responseMsgs(true, "TC Verification Details", remove_null($data), "010120", "1.0", "258ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Get the Demandable Amount By SAF ID After Payment
     * | @param $req
     * | Query Run time -272ms 
     * | Rating-2
     */
    public function getDemandBySafId(Request $req)
    {
        try {
            $safDetails = $this->details($req);
            $req = $safDetails;
            $array = $this->generateSafRequest($req);                                                                       // Generate SAF Request by SAF Id Using Trait
            $safCalculation = new SafCalculation();
            $request = new Request($array);
            $safTaxes = $safCalculation->calculateTax($request);
            $demand['amounts'] = $safTaxes->original['data']['demand'];
            $demand['details'] = $this->generateSafDemand($safTaxes->original['data']['details']);
            return responseMsgs(true, "Demand Details", remove_null($demand), "", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }
}
