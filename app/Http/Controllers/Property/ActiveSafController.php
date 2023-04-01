<?php

namespace App\Http\Controllers\Property;

use App\BLL\Property\CalculateSafById;
use App\BLL\Property\HandleTcVerification;
use App\BLL\Property\PostRazorPayPenaltyRebate;
use App\BLL\Property\RazorpayRequest;
use App\BLL\Property\TcVerificationDemandAdjust;
use App\EloquentClass\Property\PenaltyRebateCalculation;
use App\EloquentClass\Property\SafCalculation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Property\ReqPayment;
use App\Http\Requests\Property\ReqSiteVerification;
use App\MicroServices\DocUpload;
use App\MicroServices\IdGeneration;
use App\MicroServices\IdGenerator\PrefixIdGenerator;
use App\Models\CustomDetail;
use App\Models\Payment\TempTransaction;
use App\Models\Property\PaymentPropPenaltyrebate;
use App\Models\Property\PaymentPropPenalty;
use App\Models\Property\PropActiveSaf;
use App\Models\Property\PropActiveSafsFloor;
use App\Models\Property\PropActiveSafsOwner;
use App\Models\Property\PropChequeDtl;
use App\Models\Property\PropDemand;
use App\Models\Property\PropFloor;
use App\Models\Property\PropOwner;
use App\Models\Property\PropPenaltyrebate;
use App\Models\Property\PropProperty;
use App\Models\Property\PropRazorpayPenalrebate;
use App\Models\Property\PropRazorpayRequest;
use App\Models\Property\PropSafGeotagUpload;
use App\Models\Property\PropSafMemoDtl;
use App\Models\Property\PropSafsDemand;
use App\Models\Property\PropSafVerification;
use App\Models\Property\PropSafVerificationDtl;
use App\Models\Property\PropTranDtl;
use App\Models\Property\PropTransaction;
use App\Models\Property\RefPropConstructionType;
use App\Models\Property\RefPropFloor;
use App\Models\Property\RefPropGbbuildingusagetype;
use App\Models\Property\RefPropGbpropusagetype;
use App\Models\Property\RefPropOccupancyType;
use App\Models\Property\RefPropOwnershipType;
use App\Models\Property\RefPropRoadType;
use App\Models\Property\RefPropTransferMode;
use App\Models\Property\RefPropType;
use App\Models\Property\RefPropUsageType;
use App\Models\Property\ZoneMaster;
use App\Models\UlbWardMaster;
use App\Models\Workflows\WfRoleusermap;
use App\Models\Workflows\WfWardUser;
use App\Models\Workflows\WfWorkflow;
use App\Models\Workflows\WfWorkflowrolemap;
use App\Models\WorkflowTrack;
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
     * | Status - Open
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
     * |                                 # SAF Bifurcation
     * | wf_mstr_id=25
     * | wf_workflow_id=182 
     * |                                 # SAF Amalgamation
     * | wf_mstr_id=373
     * | wf_workflow_id=381
     */

    protected $user_id;
    protected $_todayDate;
    protected $Repository;
    protected $_moduleId;
    // Initializing function for Repository
    protected $saf_repository;
    public function __construct(iSafRepository $saf_repository)
    {
        $this->Repository = $saf_repository;
        $this->_todayDate = Carbon::now();
        $this->_moduleId = Config::get('module-constants.PROPERTY_MODULE_ID');
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
            $refPropGbbuildingusagetype = new RefPropGbbuildingusagetype();
            $refPropGbpropusagetype = new RefPropGbpropusagetype();
            $mZoneMstrs = new ZoneMaster();

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
            $gbbuildingusagetypes = json_decode(Redis::get('property-gb-building-usage-types'));
            $gbpropusagetypes = json_decode(Redis::get('property-gb-prop-usage-types'));
            $zoneMstrs = json_decode(Redis::get('zone-ulb-' . $ulbId));

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

            // GB Building Usage Types
            if (!$gbbuildingusagetypes) {
                $gbbuildingusagetypes = $refPropGbbuildingusagetype->getGbbuildingusagetypes();   // <--- Get GB Building Usage Types
                $redisConn->set('property-gb-building-usage-types', json_encode($gbbuildingusagetypes));
            }

            $data['gbbuildingusage_type'] = $gbbuildingusagetypes;

            // GB Prop Usage Types
            if (!$gbpropusagetypes) {
                $gbpropusagetypes = $refPropGbpropusagetype->getGbpropusagetypes();   // <--- Get GB Prop Usage Types
                $redisConn->set('property-gb-prop-usage-types', json_encode($gbpropusagetypes));
            }

            $data['gbpropusage_type'] = $gbpropusagetypes;

            // Zone Masters by Ulb
            if (!$zoneMstrs) {
                $zoneMstrs = $mZoneMstrs->getZone($ulbId);
                $redisConn->set('zone-ulb-' . $ulbId, json_encode($zoneMstrs));
            }
            $data['zone_mstrs'] = $zoneMstrs;

            return responseMsgs(true, 'Property Masters', $data, "010101", "1.0", "317ms", "GET", "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
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
            $mWfWorkflowRoleMaps = new WfWorkflowrolemap();

            $userId = auth()->user()->id;
            $ulbId = auth()->user()->ulb_id;

            $occupiedWards = $mWfWardUser->getWardsByUserId($userId)->pluck('ward_id');                       // Model () to get Occupied Wards of Current User
            $roleIds = $mWfRoleUser->getRoleIdByUserId($userId)->pluck('wf_role_id');                      // Model to () get Role By User Id
            $workflowIds = $mWfWorkflowRoleMaps->getWfByRoleId($roleIds)->pluck('workflow_id');

            $safInbox = $this->Repository->getSaf($workflowIds)                                          // Repository function to get SAF Details
                ->where('parked', false)
                ->where('prop_active_safs.ulb_id', $ulbId)
                ->where('prop_active_safs.status', 1)
                ->whereIn('current_role', $roleIds)
                ->whereIn('ward_mstr_id', $occupiedWards)
                ->orderByDesc('id')
                ->groupBy('prop_active_safs.id', 'p.property_type', 'ward.ward_name')
                ->get();

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
            $mWfWorkflowRoleMaps = new WfWorkflowrolemap();

            $mUserId = authUser()->id;
            $mUlbId = authUser()->ulb_id;
            $mDeviceId = $req->deviceId ?? "";

            $occupiedWardsId = $mWfWardUser->getWardsByUserId($mUserId)->pluck('ward_id');                  // Model function to get ward list

            $roleIds = $mWfRoleUser->getRoleIdByUserId($mUserId)->pluck('wf_role_id');                 // Model function to get Role By User Id

            $workflowIds = $mWfWorkflowRoleMaps->getWfByRoleId($roleIds)->pluck('workflow_id');
            $safInbox = $this->Repository->getSaf($workflowIds)                 // Repository function getSAF
                ->where('parked', true)
                ->where('prop_active_safs.ulb_id', $mUlbId)
                ->where('prop_active_safs.status', 1)
                ->whereIn('current_role', $roleIds)
                ->whereIn('ward_mstr_id', $occupiedWardsId)
                ->orderByDesc('id')
                ->groupBy('prop_active_safs.id', 'p.property_type', 'ward.ward_name')
                ->get();

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
            $mWfWorkflowRoleMaps = new WfWorkflowrolemap();

            $mUserId = authUser()->id;
            $mUlbId = authUser()->ulb_id;
            $mDeviceId = $req->deviceId ?? "";

            $occupiedWardsId = $mWfWardUser->getWardsByUserId($mUserId)->pluck('ward_id');                  // Model function to get ward list
            $roleIds = $mWfRoleUser->getRoleIdByUserId($mUserId)->pluck('wf_role_id');                 // Model function to get Role By User Id
            $workflowIds = $mWfWorkflowRoleMaps->getWfByRoleId($roleIds)->pluck('workflow_id');

            $safInbox = $this->Repository->getSaf($workflowIds)                 // Repository function getSAF
                ->where('is_field_verified', true)
                ->where('prop_active_safs.ulb_id', $mUlbId)
                ->where('prop_active_safs.status', 1)
                ->whereIn('current_role', $roleIds)
                ->whereIn('ward_mstr_id', $occupiedWardsId)
                ->orderByDesc('id')
                ->groupBy('prop_active_safs.id', 'p.property_type', 'ward.ward_name')
                ->get();

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
            $mWfWorkflowRoleMaps = new WfWorkflowrolemap();

            $userId = auth()->user()->id;
            $ulbId = auth()->user()->ulb_id;

            $roleIds = $mWfRoleUser->getRoleIdByUserId($userId)->pluck('wf_role_id');
            $wardId = $mWfWardUser->getWardsByUserId($userId)->pluck('ward_id');

            $workflowIds = $mWfWorkflowRoleMaps->getWfByRoleId($roleIds)->pluck('workflow_id');
            $safData = $this->Repository->getSaf($workflowIds)   // Repository function to get SAF
                ->where('prop_active_safs.ulb_id', $ulbId)
                ->whereNotIn('current_role', $roleIds)
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
            $mWfRoleUserMaps = new WfRoleusermap();
            $mWfWorkflowRoleMaps = new WfWorkflowrolemap();
            $userId = authUser()->id;
            $ulbId = authUser()->ulb_id;

            $wardIds = $mWfWardUser->getWardsByUserId($userId)->pluck('ward_id');                        // Get All Occupied Ward By user id using trait
            $roleIds = $mWfRoleUserMaps->getRoleIdByUserId($userId)->pluck('wf_role_id');
            $workflowIds = $mWfWorkflowRoleMaps->getWfByRoleId($roleIds)->pluck('workflow_id');

            $safData = $this->Repository->getSaf($workflowIds)                      // Repository function to get SAF Details
                ->where('is_escalate', 1)
                ->where('prop_active_safs.ulb_id', $ulbId)
                ->whereIn('ward_mstr_id', $wardIds)
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
            'applicationId' => 'required|digits_between:1,9223372036854775807'
        ]);

        try {
            $mPropActiveSaf = new PropActiveSaf();
            $mPropActiveSafOwner = new PropActiveSafsOwner();
            $mActiveSafsFloors = new PropActiveSafsFloor();
            $mWorkflowTracks = new WorkflowTrack();
            $mCustomDetails = new CustomDetail();
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
            $fullDetailsData['doc_verify_status'] = $data->doc_verify_status;
            $fullDetailsData['doc_upload_status'] = $data->doc_upload_status;
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

            return responseMsgs(true, 'Data Fetched', remove_null($fullDetailsData), "010104", "1.0", "303ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Get Static Saf Details
     */
    public function getStaticSafDetails(Request $req)
    {
        $req->validate([
            'applicationId' => 'required|digits_between:1,9223372036854775807'
        ]);
        try {
            // Variable Assignments
            $mPropActiveSaf = new PropActiveSaf();
            $mPropActiveSafOwner = new PropActiveSafsOwner();
            $mActiveSafsFloors = new PropActiveSafsFloor();
            $mPropSafMemoDtls = new PropSafMemoDtl();
            $memoDtls = array();
            $data = array();

            // Derivative Assignments
            $data = $mPropActiveSaf->getActiveSafDtls()                         // <------- Model function Active SAF Details
                ->where('prop_active_safs.id', $req->applicationId)
                ->first();
            if (!$data)
                throw new Exception("Data Not Found");
            $data = json_decode(json_encode($data), true);

            $ownerDtls = $mPropActiveSafOwner->getOwnersBySafId($data['id']);
            $data['owners'] = $ownerDtls;
            $getFloorDtls = $mActiveSafsFloors->getFloorsBySafId($data['id']);      // Model Function to Get Floor Details
            $data['floors'] = $getFloorDtls;

            $memoDtls = $mPropSafMemoDtls->memoLists($data['id']);
            $data['memoDtls'] = $memoDtls;
            return responseMsgs(true, "Saf Dtls", remove_null($data), "010127", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return $e->getMessage();
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
        ]);

        try {
            $userId = authUser()->id;
            $userType = authUser()->user_type;
            $workflowTrack = new WorkflowTrack();
            $mWfRoleUsermap = new WfRoleusermap();
            $saf = PropActiveSaf::findOrFail($request->applicationId);                // SAF Details
            $mModuleId = Config::get('module-constants.PROPERTY_MODULE_ID');
            $metaReqs = array();
            // Save On Workflow Track For Level Independent
            $metaReqs = [
                'workflowId' => $saf->workflow_id,
                'moduleId' => $mModuleId,
                'refTableDotId' => "prop_active_safs.id",
                'refTableIdValue' => $saf->id,
                'message' => $request->comment
            ];
            if ($userType != 'Citizen') {
                $roleReqs = new Request([
                    'workflowId' => $saf->workflow_id,
                    'userId' => $userId,
                ]);
                $wfRoleId = $mWfRoleUsermap->getRoleByUserWfId($roleReqs);
                $metaReqs = array_merge($metaReqs, ['senderRoleId' => $wfRoleId->wf_role_id]);
                $metaReqs = array_merge($metaReqs, ['user_id' => $userId]);
            }
            DB::beginTransaction();
            // For Citizen Independent Comment
            if ($userType == 'Citizen') {
                $metaReqs = array_merge($metaReqs, ['citizenId' => $userId]);
                $metaReqs = array_merge($metaReqs, ['ulb_id' => $saf->ulb_id]);
                $metaReqs = array_merge($metaReqs, ['user_id' => NULL]);
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
     * | Function for Post Next Level(9)
     * | @param mixed $request
     * | @var preLevelPending Get the Previous level pending data for the saf id
     * | @var levelPending new Level Pending to be add
     * | Status-Closed
     * | Rating-3 
     */
    public function postNextLevel(Request $request)
    {
        $request->validate([
            'applicationId' => 'required|integer',
            'receiverRoleId' => 'nullable|integer',
            'action' => 'required|In:forward,backward'
        ]);

        try {
            // Variable Assigments
            $userId = authUser()->id;
            $wfLevels = Config::get('PropertyConstaint.SAF-LABEL');
            $saf = PropActiveSaf::findOrFail($request->applicationId);
            $mWfMstr = new WfWorkflow();
            $track = new WorkflowTrack();
            $mWfWorkflows = new WfWorkflow();
            $mWfRoleMaps = new WfWorkflowrolemap();
            $samHoldingDtls = array();

            // Derivative Assignments
            $senderRoleId = $saf->current_role;
            $request->validate([
                'comment' => $senderRoleId == $wfLevels['BO'] ? 'nullable' : 'required',

            ]);
            $ulbWorkflowId = $saf->workflow_id;
            $ulbWorkflowMaps = $mWfWorkflows->getWfDetails($ulbWorkflowId);
            $roleMapsReqs = new Request([
                'workflowId' => $ulbWorkflowMaps->id,
                'roleId' => $senderRoleId
            ]);
            $forwardBackwardIds = $mWfRoleMaps->getWfBackForwardIds($roleMapsReqs);
            DB::beginTransaction();
            if ($request->action == 'forward') {
                $wfMstrId = $mWfMstr->getWfMstrByWorkflowId($saf->workflow_id);
                $samHoldingDtls = $this->checkPostCondition($senderRoleId, $wfLevels, $saf, $wfMstrId);          // Check Post Next level condition
                $saf->current_role = $forwardBackwardIds->forward_role_id;
                $saf->last_role_id =  $forwardBackwardIds->forward_role_id;                     // Update Last Role Id
                $metaReqs['verificationStatus'] = 1;
                $metaReqs['receiverRoleId'] = $forwardBackwardIds->forward_role_id;
            }
            // SAF Application Update Current Role Updation
            if ($request->action == 'backward') {
                $saf->current_role = $forwardBackwardIds->backward_role_id;
                $metaReqs['verificationStatus'] = 0;
                $metaReqs['receiverRoleId'] = $forwardBackwardIds->backward_role_id;
            }


            $saf->save();
            $metaReqs['moduleId'] = Config::get('module-constants.PROPERTY_MODULE_ID');
            $metaReqs['workflowId'] = $saf->workflow_id;
            $metaReqs['refTableDotId'] = Config::get('PropertyConstaint.SAF_REF_TABLE');
            $metaReqs['refTableIdValue'] = $request->applicationId;
            $metaReqs['senderRoleId'] = $senderRoleId;
            $metaReqs['user_id'] = $userId;

            $request->request->add($metaReqs);

            $track->saveTrack($request);

            DB::commit();
            return responseMsgs(true, "Successfully Forwarded The Application!!", $samHoldingDtls, "010109", "1.0", "", "POST", $request->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "", "010109", "1.0", "", "POST", $request->deviceId);
        }
    }

    /**
     * | check Post Condition for backward forward(9.1)
     */
    public function checkPostCondition($senderRoleId, $wfLevels, $saf, $wfMstrId)
    {
        // Variable Assigments
        $reAssessWfMstrId = Config::get('workflow-constants.SAF_REASSESSMENT_ID');
        $mPropSafDemand = new PropSafsDemand();
        $mPropMemoDtl = new PropSafMemoDtl();
        $todayDate = Carbon::now()->format('Y-m-d');
        $fYear = calculateFYear($todayDate);

        $ptParamId = Config::get('PropertyConstaint.PT_PARAM_ID');
        $samParamId = Config::get('PropertyConstaint.SAM_PARAM_ID');

        // Derivative Assignments
        $demand = $mPropSafDemand->getFirstDemandByFyearSafId($saf->id, $fYear);
        if (collect($demand)->isEmpty())
            throw new Exception("Demand Not Available for the Current Year to Generate SAM");
        switch ($senderRoleId) {
            case $wfLevels['BO']:                        // Back Office Condition
                if ($saf->doc_upload_status == 0)
                    throw new Exception("Document Not Fully Uploaded");
                break;

            case $wfLevels['DA']:                       // DA Condition
                if ($saf->doc_verify_status == 0)
                    throw new Exception("Document Not Fully Verified");
                $idGeneration = new PrefixIdGenerator($ptParamId, $saf->ulb_id);
                $ptNo = $idGeneration->generate();
                $saf->pt_no = $ptNo;                        // Generate New Property Tax No for All Conditions
                $saf->save();

                $samIdGeneration = new PrefixIdGenerator($samParamId, $saf->ulb_id);
                $samNo = $samIdGeneration->generate();                 // Generate SAM No
                $mergedDemand = array_merge($demand->toArray(), [
                    'memo_type' => 'SAM',
                    'memo_no' => $samNo,
                    'pt_no' => $ptNo,
                    'ward_id' => $saf->ward_mstr_id
                ]);
                $memoReqs = new Request($mergedDemand);
                $mPropMemoDtl->postSafMemoDtls($memoReqs);
                $this->replicateSaf($saf->id);
                break;

            case $wfLevels['TC']:
                if ($saf->is_geo_tagged == false)
                    throw new Exception("Geo Tagging Not Done");
                break;
            case $wfLevels['UTC']:
                if ($saf->is_field_verified == false)
                    throw new Exception("Field Verification Not Done");
                break;
        }
        return [
            'holdingNo' =>  $holdingNo ?? "",
            'samNo' => $samNo ?? "",
            'ptNo' => $ptNo ?? "",
        ];
    }

    /**
     * | Replicate Tables of saf to property
     */
    public function replicateSaf($safId)
    {
        $activeSaf = PropActiveSaf::query()
            ->where('id', $safId)
            ->first();
        $ownerDetails = PropActiveSafsOwner::query()
            ->where('saf_id', $safId)
            ->get();
        $floorDetails = PropActiveSafsFloor::query()
            ->where('saf_id', $safId)
            ->get();

        $toBeProperties = PropActiveSaf::query()
            ->where('id', $safId)
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
                'user_id',
                'citizen_id',
                'pt_no',
                'building_name',
                'street_name',
                'location',
                'landmark',
                'is_gb_saf',
                'gb_office_name',
                'gb_usage_types',
                'gb_prop_usage_types'
            )->first();

        $assessmentType = $activeSaf->assessment_type;

        if (in_array($assessmentType, ['New Assessment', 'Bifurcation', 'Amalgamation'])) { // Make New Property For New Assessment,Bifurcation and Amalgamation
            $propProperties = $toBeProperties->replicate();
            $propProperties->setTable('prop_properties');
            $propProperties->saf_id = $activeSaf->id;
            $propProperties->new_holding_no = $activeSaf->new_holding_no;
            $propProperties->save();

            // SAF Owners replication
            foreach ($ownerDetails as $ownerDetail) {
                $approvedOwners = $ownerDetail->replicate();
                $approvedOwners->setTable('prop_owners');
                $approvedOwners->property_id = $propProperties->id;
                $approvedOwners->save();
            }

            // SAF Floors Replication
            foreach ($floorDetails as $floorDetail) {
                $propFloor = $floorDetail->replicate();
                $propFloor->setTable('prop_floors');
                $propFloor->property_id = $propProperties->id;
                $propFloor->save();
            }
        }

        // Edit In Case of Reassessment,Mutation
        if (in_array($assessmentType, ['Re Assessment', 'Mutation'])) {         // Edit Property In case of Reassessment, Mutation
            $propId = $activeSaf->previous_holding_id;
            $mProperty = new PropProperty();
            $mPropOwners = new PropOwner();
            $mPropFloors = new PropFloor();
            // Edit Property
            $mProperty->editPropBySaf($propId, $activeSaf);
            // Edit Owners 
            foreach ($ownerDetails as $ownerDetail) {
                $ifOwnerExist = $mPropOwners->getPropOwnerByOwnerId($ownerDetail->id);
                $ownerDetail = array_merge($ownerDetail->toArray(), ['property_id' => $propId]);
                $ownerDetail = new Request($ownerDetail);
                if ($ifOwnerExist)
                    $mPropOwners->editOwner($ownerDetail);
                else
                    $mPropOwners->postOwner($ownerDetail);
            }
            // Edit Floors
            foreach ($floorDetails as $floorDetail) {
                $ifFloorExist = $mPropFloors->getFloorByFloorId($floorDetail->prop_floor_details_id);
                $floorReqs = new Request([
                    'floor_mstr_id' => $floorDetail->floor_mstr_id,
                    'usage_type_mstr_id' => $floorDetail->usage_type_id,
                    'const_type_mstr_id' => $floorDetail->construction_type_id,
                    'occupancy_type_mstr_id' => $floorDetail->occupancy_type_id,
                    'builtup_area' => $floorDetail->builtup_area,
                    'date_from' => $floorDetail->date_from,
                    'date_upto' => $floorDetail->date_to,
                    'carpet_area' => $floorDetail->carpet_area,
                    'property_id' => $propId,
                    'saf_id' => $safId

                ]);
                if ($ifFloorExist) {
                    $mPropFloors->editFloor($ifFloorExist, $floorReqs);
                } else
                    $mPropFloors->postFloor($floorReqs);
            }
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
            'applicationId' => 'required|integer',
            'status' => 'required|integer'
        ]);

        try {
            // Check if the Current User is Finisher or Not (Variable Assignments)
            $safDetails = PropActiveSaf::findOrFail($req->applicationId);
            $mWfRoleUsermap = new WfRoleusermap();
            $propSafVerification = new PropSafVerification();
            $propSafVerificationDtl = new PropSafVerificationDtl();
            $mPropSafMemoDtl = new PropSafMemoDtl();
            $mPropSafDemand = new PropSafsDemand();
            $mPropProperties = new PropProperty();
            $mPropDemand = new PropDemand();
            $handleTcVerification = new TcVerificationDemandAdjust;
            $todayDate = Carbon::now()->format('Y-m-d');
            $currentFinYear = calculateFYear($todayDate);
            $famParamId = Config::get('PropertyConstaint.FAM_PARAM_ID');

            $userId = authUser()->id;
            $safId = $req->applicationId;
            // Derivative Assignments
            $workflowId = $safDetails->workflow_id;
            $getRoleReq = new Request([                                                 // make request to get role id of the user
                'userId' => $userId,
                'workflowId' => $workflowId
            ]);
            $readRoleDtls = $mWfRoleUsermap->getRoleByUserWfId($getRoleReq);
            if (collect($readRoleDtls)->isEmpty())
                throw new Exception("You Are Not Authorized for this workflow");

            $roleId = $readRoleDtls->wf_role_id;

            if ($safDetails->finisher_role_id != $roleId)
                throw new Exception("Forbidden Access");
            $activeSaf = PropActiveSaf::query()
                ->where('id', $req->applicationId)
                ->first();
            $ownerDetails = PropActiveSafsOwner::query()
                ->where('saf_id', $req->applicationId)
                ->get();
            $floorDetails = PropActiveSafsFloor::query()
                ->where('saf_id', $req->applicationId)
                ->get();

            $propDtls = $mPropProperties->getPropIdBySafId($req->applicationId);
            $propId = $propDtls->id;
            $fieldVerifiedSaf = $propSafVerification->getVerificationsBySafId($safId);          // Get fields Verified Saf with all Floor Details
            if (collect($fieldVerifiedSaf)->isEmpty())
                throw new Exception("Site Verification not Exist");

            DB::beginTransaction();
            // Approval
            if ($req->status == 1) {
                $safDetails->saf_pending_status = 0;
                $safDetails->save();

                $demand = $mPropDemand->getFirstDemandByFyearPropId($propId, $currentFinYear);
                if (collect($demand)->isEmpty())
                    $demand = $mPropSafDemand->getFirstDemandByFyearSafId($safId, $currentFinYear);
                if (collect($demand)->isEmpty())
                    throw new Exception("Demand Not Available for the Current Year to Generate FAM");

                $idGeneration = new PrefixIdGenerator($famParamId, $activeSaf->ulb_id);

                // SAF Application replication
                $famNo = $idGeneration->generate();
                $mergedDemand = array_merge($demand->toArray(), [
                    'memo_type' => 'FAM',
                    'memo_no' => $famNo,
                    'holding_no' => $activeSaf->new_holding_no ?? $activeSaf->holding_no,
                    'pt_no' => $activeSaf->pt_no,
                    'ward_id' => $activeSaf->ward_mstr_id,
                    'prop_id' => $propId,
                    'saf_id' => $safId
                ]);
                $memoReqs = new Request($mergedDemand);
                $mPropSafMemoDtl->postSafMemoDtls($memoReqs);
                $this->finalApprovalSafReplica($mPropProperties, $propId, $fieldVerifiedSaf, $activeSaf, $ownerDetails, $floorDetails, $safId);
                $tcVerifyParams = [
                    'safId' => $safId,
                    'fieldVerificationDtls' => $fieldVerifiedSaf,
                    'assessmentType' => $safDetails->assessment_type,
                    'ulbId' => $activeSaf->ulb_id,
                    'activeSafDtls' => $activeSaf,
                    'propId' => $propId
                ];
                $handleTcVerification->generateTcVerifiedDemand($tcVerifyParams);                // current object function (10.3)
                $msg = "Application Approved Successfully";
            }
            // Rejection
            if ($req->status == 0) {
                $this->finalRejectionSafReplica($activeSaf, $ownerDetails, $floorDetails);
                $msg = "Application Rejected Successfully";
            }

            $propSafVerification->deactivateVerifications($req->applicationId);                 // Deactivate Verification From Table
            $propSafVerificationDtl->deactivateVerifications($req->applicationId);              // Deactivate Verification from Saf floor Dtls
            DB::commit();
            return responseMsgs(true, $msg, ['holdingNo' => $safDetails->holding_no, 'ptNo' => $safDetails->pt_no], "010110", "1.0", "410ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Replication of Final Approval SAf(10.1)
     */
    public function finalApprovalSafReplica($mPropProperties, $propId, $fieldVerifiedSaf, $activeSaf, $ownerDetails, $floorDetails, $safId)
    {
        $mPropFloors = new PropFloor();
        $mPropProperties->replicateVerifiedSaf($propId, collect($fieldVerifiedSaf)->first());             // Replicate to Prop Property Table
        $approvedSaf = $activeSaf->replicate();
        $approvedSaf->setTable('prop_safs');
        $approvedSaf->id = $activeSaf->id;
        $approvedSaf->property_id = $propId;
        $approvedSaf->save();
        $activeSaf->delete();

        // Saf Owners Replication
        foreach ($ownerDetails as $ownerDetail) {
            $approvedOwner = $ownerDetail->replicate();
            $approvedOwner->setTable('prop_safs_owners');
            $approvedOwner->id = $ownerDetail->id;
            $approvedOwner->save();
            $ownerDetail->delete();
        }

        // Saf Floors Replication
        foreach ($floorDetails as $floorDetail) {
            $approvedFloor = $floorDetail->replicate();
            $approvedFloor->setTable('prop_safs_floors');
            $approvedFloor->id = $floorDetail->id;
            $approvedFloor->save();
            $floorDetail->delete();
        }

        foreach ($fieldVerifiedSaf as $key) {
            $ifFloorExist = $mPropFloors->getFloorBySafFloorIdSafId($safId, $key->saf_floor_id);
            $floorReqs = new Request([
                'floor_mstr_id' => $key->floor_mstr_id,
                'usage_type_mstr_id' => $key->usage_type_id,
                'const_type_mstr_id' => $key->construction_type_id,
                'occupancy_type_mstr_id' => $key->occupancy_type_id,
                'builtup_area' => $key->builtup_area,
                'date_from' => $key->date_from,
                'date_upto' => $key->date_to,
                'carpet_area' => $key->carpet_area,
                'property_id' => $propId,
                'saf_id' => $safId

            ]);
            if ($ifFloorExist) {
                $mPropFloors->editFloor($ifFloorExist, $floorReqs);
            } else
                $mPropFloors->postFloor($floorReqs);
        }
    }

    /**
     * | Replication of Final Rejection Saf(10.2)
     */
    public function finalRejectionSafReplica($activeSaf, $ownerDetails, $floorDetails)
    {
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
            $metaReqs['user_id'] = authUser()->id;
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
        try {
            $calculateSafById = new CalculateSafById;
            $demand = $calculateSafById->calculateTax($req);
            return responseMsgs(true, "Demand Details", remove_null($demand));
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "");
        }
    }

    /**
     * | One Percent Penalty Calculation(13.1)
     */
    public function calcOnePercPenalty($item)
    {
        $penaltyRebateCalc = new PenaltyRebateCalculation;
        $onePercPenalty = $penaltyRebateCalc->calcOnePercPenalty($item->due_date);                  // Calculation One Percent Penalty
        $item['onePercPenalty'] = $onePercPenalty;
        $onePercPenaltyTax = ($item['balance'] * $onePercPenalty) / 100;
        $item['onePercPenaltyTax'] = roundFigure($onePercPenaltyTax);
        return $item;
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
        ]);

        try {
            $ipAddress = getClientIpAddress();
            $mPropRazorPayRequest = new PropRazorpayRequest();
            $postRazorPayPenaltyRebate = new PostRazorPayPenaltyRebate;
            $req->merge(['departmentId' => 1]);
            $calculateSafById = $this->calculateSafBySafId($req);
            $safDetails = PropActiveSaf::find($req->id);
            $demands = $calculateSafById->original['data']['demand'];
            $totalAmount = $demands['payableAmount'];
            $req->request->add(['workflowId' => $safDetails->workflow_id, 'ghostUserId' => 0, 'amount' => $totalAmount]);
            DB::beginTransaction();
            $orderDetails = $this->saveGenerateOrderid($req);                                      //<---------- Generate Order ID Trait
            $demands = array_merge($demands->toArray(), [
                'orderId' => $orderDetails['orderId']
            ]);
            // Store Razor pay Request
            $razorPayRequest = [
                'order_id' => $demands['orderId'],
                'saf_id' => $req->id,
                'from_fyear' => $demands['dueFromFyear'],
                'from_qtr' => $demands['dueFromQtr'],
                'to_fyear' => $demands['dueToFyear'],
                'to_qtr' => $demands['dueToQtr'],
                'demand_amt' => $demands['totalTax'],
                'ulb_id' => $safDetails->ulb_id,
                'ip_address' => $ipAddress,
            ];
            $storedRazorPayReqs = $mPropRazorPayRequest->store($razorPayRequest);
            // Store Razor pay penalty Rebates
            $postRazorPayPenaltyRebate->_safId = $req->id;
            $postRazorPayPenaltyRebate->_razorPayRequestId = $storedRazorPayReqs['razorPayReqId'];
            $postRazorPayPenaltyRebate->postRazorPayPenaltyRebates($demands);
            DB::commit();
            return responseMsgs(true, "Order ID Generated", remove_null($orderDetails), "010114", "1.0", "1s", "POST", $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Post Penalty Rebates (14.2)
     */
    public function postPenaltyRebates($calculateSafById, $safId, $tranId, $clusterId = null)
    {
        $mPaymentRebatePanelties = new PropPenaltyrebate();
        $calculatedRebates = collect($calculateSafById->original['data']['demand']['rebates']);
        $rebateList = array();
        $rebatePenalList = collect(Config::get('PropertyConstaint.REBATE_PENAL_MASTERS'));

        foreach ($calculatedRebates as $item) {
            $rebate = [
                'keyString' => $item['keyString'],
                'value' => $item['rebateAmount'],
                'isRebate' => true
            ];
            array_push($rebateList, $rebate);
        }
        $headNames = [
            [
                'keyString' => $rebatePenalList->where('id', 1)->first()['value'],
                'value' => $calculateSafById->original['data']['demand']['totalOnePercPenalty'],
                'isRebate' => false
            ],
            [
                'keyString' => $rebatePenalList->where('id', 5)->first()['value'],
                'value' => $calculateSafById->original['data']['demand']['lateAssessmentPenalty'],
                'isRebate' => false
            ]
        ];
        $headNames = array_merge($headNames, $rebateList);
        collect($headNames)->map(function ($headName) use ($mPaymentRebatePanelties, $safId, $tranId, $clusterId) {
            if ($headName['value'] > 0) {
                $reqs = [
                    'tran_id' => $tranId,
                    'saf_id' => $safId,
                    'cluster_id' => $clusterId,
                    'head_name' => $headName['keyString'],
                    'amount' => $headName['value'],
                    'is_rebate' => $headName['isRebate'],
                    'tran_date' => Carbon::now()->format('Y-m-d')
                ];

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
    public function paymentSaf(ReqPayment $req)
    {
        try {
            // Variable Assignments
            $offlinePaymentModes = Config::get('payment-constants.PAYMENT_MODE_OFFLINE');
            $todayDate = Carbon::now();
            $idGeneration = new IdGeneration;
            $propTrans = new PropTransaction();
            $mPropSafsDemands = new PropSafsDemand();
            $verifyPaymentModes = Config::get('payment-constants.VERIFICATION_PAYMENT_MODES');

            $userId = $req['userId'];
            $safId = $req['id'];
            $tranBy = 'ONLINE';

            $activeSaf = PropActiveSaf::findOrFail($req['id']);

            if (!$userId)
                $userId = auth()->user()->id ?? 0;                                      // Authenticated user or Ghost User

            $tranNo = $req['transactionNo'];
            // Derivative Assignments
            if (!$tranNo)
                $tranNo = $idGeneration->generateTransactionNo();

            $safCalculation = $this->calculateSafBySafId($req);
            $demands = $safCalculation->original['data']['details'];
            $amount = $safCalculation->original['data']['demand']['payableAmount'];

            if (!$demands || collect($demands)->isEmpty())
                throw new Exception("Demand Not Available for Payment");

            if (in_array($req['paymentMode'], $offlinePaymentModes)) {
                $userId = auth()->user()->id ?? null;
                if (!$userId)
                    throw new Exception("User Should Be Logged In");
                $tranBy = authUser()->user_type;
            }

            // Property Transactions
            $req->merge([
                'userId' => $userId,
                'todayDate' => $todayDate->format('Y-m-d'),
                'tranNo' => $tranNo,
                'workflowId' => $activeSaf->workflow_id,
                'amount' => $amount,
                'tranBy' => $tranBy
            ]);
            $activeSaf->payment_status = 1; // Paid for Online or Cash
            if (in_array($req['paymentMode'], $verifyPaymentModes)) {
                $req->merge([
                    'verifyStatus' => 2
                ]);
                $activeSaf->payment_status = 2;         // Under Verification for Cheque, Cash, DD
            }
            DB::beginTransaction();
            $propTrans = $propTrans->postSafTransaction($req, $demands);

            if (in_array($req['paymentMode'], $offlinePaymentModes)) {
                $req->merge([
                    'chequeDate' => $req['chequeDate'],
                    'tranId' => $propTrans['id']
                ]);
                $this->postOtherPaymentModes($req);
            }
            // Reflect on Prop Tran Details
            foreach ($demands as $demand) {
                $safDemand = $mPropSafsDemands->getDemandById($demand['id']);
                $safDemand->balance = 0;
                $safDemand->paid_status = 1;           // <-------- Update Demand Paid Status 
                $safDemand->save();

                $propTranDtl = new PropTranDtl();
                $propTranDtl->tran_id = $propTrans['id'];
                $propTranDtl->saf_demand_id = $demand['id'];
                $propTranDtl->total_demand = $demand['amount'];
                $propTranDtl->save();
            }

            // Replication Prop Rebates Penalties
            $this->postPenaltyRebates($safCalculation, $safId, $propTrans['id']);
            // Update SAF Payment Status
            $activeSaf->save();
            $this->sendToWorkflow($activeSaf);        // Send to Workflow(15.2)
            DB::commit();
            return responseMsgs(true, "Payment Successfully Done",  ['TransactionNo' => $tranNo], "010115", "1.0", "567ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Offline Saf Payment
     */
    public function offlinePaymentSaf(ReqPayment $req)
    {
        try {
            // Variable Assignments
            $offlinePaymentModes = Config::get('payment-constants.PAYMENT_MODE_OFFLINE');
            $todayDate = Carbon::now();
            $idGeneration = new IdGeneration;
            $propTrans = new PropTransaction();
            $mPropSafsDemands = new PropSafsDemand();
            $verifyPaymentModes = Config::get('payment-constants.VERIFICATION_PAYMENT_MODES');

            $safId = $req['id'];

            $activeSaf = PropActiveSaf::findOrFail($req['id']);

            $userId = auth()->user()->id;                                      // Authenticated user or Ghost User
            $tranBy = authUser()->user_type;

            $tranNo = $req['transactionNo'];
            // Derivative Assignments
            if (!$tranNo)
                $tranNo = $idGeneration->generateTransactionNo();

            $safCalculation = $this->calculateSafBySafId($req);
            $demands = $safCalculation->original['data']['details'];
            $amount = $safCalculation->original['data']['demand']['payableAmount'];

            if (!$demands || collect($demands)->isEmpty())
                throw new Exception("Demand Not Available for Payment");


            // Property Transactions
            $req->merge([
                'userId' => $userId,
                'todayDate' => $todayDate->format('Y-m-d'),
                'tranNo' => $tranNo,
                'workflowId' => $activeSaf->workflow_id,
                'amount' => $amount,
                'tranBy' => $tranBy
            ]);
            $activeSaf->payment_status = 1; // Paid for Online or Cash
            if (in_array($req['paymentMode'], $verifyPaymentModes)) {
                $req->merge([
                    'verifyStatus' => 2
                ]);
                $activeSaf->payment_status = 2;         // Under Verification for Cheque, Cash, DD
            }
            DB::beginTransaction();
            $propTrans = $propTrans->postSafTransaction($req, $demands);

            if (in_array($req['paymentMode'], $offlinePaymentModes)) {
                $req->merge([
                    'chequeDate' => $req['chequeDate'],
                    'tranId' => $propTrans['id']
                ]);
                $this->postOtherPaymentModes($req);
            }
            // Reflect on Prop Tran Details
            foreach ($demands as $demand) {
                $safDemand = $mPropSafsDemands->getDemandById($demand['id']);
                $safDemand->balance = 0;
                $safDemand->paid_status = 1;           // <-------- Update Demand Paid Status 
                $safDemand->save();

                $propTranDtl = new PropTranDtl();
                $propTranDtl->tran_id = $propTrans['id'];
                $propTranDtl->saf_demand_id = $demand['id'];
                $propTranDtl->total_demand = $demand['amount'];
                $propTranDtl->save();
            }

            // Replication Prop Rebates Penalties
            $this->postPenaltyRebates($safCalculation, $safId, $propTrans['id']);
            // Update SAF Payment Status
            $activeSaf->save();
            $this->sendToWorkflow($activeSaf);        // Send to Workflow(15.2)
            DB::commit();
            return responseMsgs(true, "Payment Successfully Done",  ['TransactionNo' => $tranNo], "010115", "1.0", "567ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Post Other Payment Modes for Cheque,DD,Neft
     */
    public function postOtherPaymentModes($req, $clusterId = null)
    {
        $cash = Config::get('payment-constants.PAYMENT_MODE.3');
        $moduleId = Config::get('module-constants.PROPERTY_MODULE_ID');
        $mTempTransaction = new TempTransaction();
        if ($req['paymentMode'] != $cash) {
            $mPropChequeDtl = new PropChequeDtl();
            $chequeReqs = [
                'user_id' => $req['userId'],
                'prop_id' => $req['id'],
                'transaction_id' => $req['tranId'],
                'cheque_date' => $req['chequeDate'],
                'bank_name' => $req['bankName'],
                'branch_name' => $req['branchName'],
                'cheque_no' => $req['chequeNo'],
                'cluster_id' => $clusterId
            ];

            $mPropChequeDtl->postChequeDtl($chequeReqs);
        }

        $tranReqs = [
            'transaction_id' => $req['tranId'],
            'application_id' => $req['id'],
            'module_id' => $moduleId,
            'workflow_id' => $req['workflowId'],
            'transaction_no' => $req['tranNo'],
            'application_no' => $req->applicationNo,
            'amount' => $req['amount'],
            'payment_mode' => $req['paymentMode'],
            'cheque_dd_no' => $req['chequeNo'],
            'bank_name' => $req['bankName'],
            'tran_date' => $req['todayDate'],
            'user_id' => $req['userId'],
            'ulb_id' => $req['ulbId'],
            'cluster_id' => $clusterId
        ];
        $mTempTransaction->tempTransaction($tranReqs);
    }

    /**
     * | Send to Workflow Level after payment(15.2)
     */
    public function sendToWorkflow($activeSaf)
    {
        $mWorkflowTrack = new WorkflowTrack();
        $todayDate = $this->_todayDate;
        $refTable = Config::get('PropertyConstaint.SAF_REF_TABLE');
        $reqWorkflow = [
            'workflow_id' => $activeSaf->workflow_id,
            'ref_table_dot_id' => $refTable,
            'ref_table_id_value' => $activeSaf->id,
            'track_date' => $todayDate->format('Y-m-d h:i:s'),
            'module_id' => $this->_moduleId,
            'user_id' => null,
            'receiver_role_id' => $activeSaf->current_role,
            'ulb_id' => $activeSaf->ulb_id,
        ];
        $mWorkflowTrack->store($reqWorkflow);
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
            $propSafsDemand = new PropSafsDemand();
            $transaction = new PropTransaction();
            $propPenalties = new PropPenaltyrebate();

            $mTowards = Config::get('PropertyConstaint.SAF_TOWARDS');
            $mAccDescription = Config::get('PropertyConstaint.ACCOUNT_DESCRIPTION');
            $mDepartmentSection = Config::get('PropertyConstaint.DEPARTMENT_SECTION');
            $rebatePenalMstrs = collect(Config::get('PropertyConstaint.REBATE_PENAL_MASTERS'));

            $onePercKey = $rebatePenalMstrs->where('id', 1)->first()['value'];
            $specialRebateKey = $rebatePenalMstrs->where('id', 6)->first()['value'];
            $firstQtrKey = $rebatePenalMstrs->where('id', 2)->first()['value'];
            $lateAssessKey = $rebatePenalMstrs->where('id', 5)->first()['value'];
            $onlineRebate = $rebatePenalMstrs->where('id', 3)->first()['value'];

            $safTrans = $transaction->getPropByTranPropId($req->tranNo);
            // Saf Payment
            $safId = $safTrans->saf_id;
            $reqSafId = new Request(['id' => $safId]);
            $activeSafDetails = $this->details($reqSafId);
            $calDemandAmt = $safTrans->demand_amt;
            $checkOtherTaxes =  $propSafsDemand->getFirstDemandBySafId($safId);

            $mDescriptions = $this->readDescriptions($checkOtherTaxes);      // Check the Taxes are Only Holding or Not

            $fromFinYear = $safTrans->from_fyear;
            $fromFinQtr = $safTrans->from_qtr;
            $upToFinYear = $safTrans->to_fyear;
            $upToFinQtr = $safTrans->to_qtr;

            // Get Property Penalties against property transaction
            $penalRebates = $propPenalties->getPropPenalRebateByTranId($safTrans->id);
            $onePercPanalAmt = $penalRebates->where('head_name', $onePercKey)->first()['amount'] ?? "";
            $rebateAmt = $penalRebates->where('head_name', 'Rebate')->first()['amount'] ?? "";
            $specialRebateAmt = $penalRebates->where('head_name', $specialRebateKey)->first()['amount'] ?? "";
            $firstQtrRebate = $penalRebates->where('head_name', $firstQtrKey)->first()['amount'] ?? "";
            $lateAssessPenalty = $penalRebates->where('head_name', $lateAssessKey)->first()['amount'] ?? "";
            $jskOrOnlineRebate = collect($penalRebates)->where('head_name', $onlineRebate)->first()->amount ?? 0;

            $taxDetails = $this->readPenalyPmtAmts($lateAssessPenalty, $onePercPanalAmt, $rebateAmt,  $specialRebateAmt, $firstQtrRebate, $safTrans->amount, $jskOrOnlineRebate);   // Get Holding Tax Dtls
            // Response Return Data
            $responseData = [
                "departmentSection" => $mDepartmentSection,
                "accountDescription" => $mAccDescription,
                "transactionDate" => $safTrans->tran_date,
                "transactionNo" => $safTrans->tran_no,
                "transactionTime" => $safTrans->created_at->format('H:i:s'),
                "applicationNo" => $activeSafDetails['saf_no'],
                "customerName" => $activeSafDetails['applicant_name'],
                "receiptWard" => $activeSafDetails['new_ward_no'],
                "address" => $activeSafDetails['prop_address'],
                "paidFrom" => $fromFinYear,
                "paidFromQtr" => $fromFinQtr,
                "paidUpto" => $upToFinYear,
                "paidUptoQtr" => $upToFinQtr,
                "paymentMode" => $safTrans->payment_mode,
                "bankName" => $safTrans->bank_name,
                "branchName" => $safTrans->branch_name,
                "chequeNo" => $safTrans->cheque_no,
                "chequeDate" => $safTrans->cheque_date,
                "demandAmount" => roundFigure((float)$calDemandAmt),
                "taxDetails" => $taxDetails,
                "ulbId" => $activeSafDetails['ulb_id'],
                "oldWardNo" => $activeSafDetails['old_ward_no'],
                "newWardNo" => $activeSafDetails['new_ward_no'],
                "towards" => $mTowards,
                "description" => $mDescriptions,
                "totalPaidAmount" => $safTrans->amount,
                "paidAmtInWords" => getIndianCurrency($safTrans->amount),
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
    public function readPenalyPmtAmts($lateAssessPenalty = 0, $onePercPenalty = 0, $rebate = 0, $specialRebate = 0, $firstQtrRebate = 0, $amount, $onlineRebate = 0)
    {
        $amount = [
            [
                "keyString" => "Late Assessment Fine(Rule 14.1)",
                "value" => $lateAssessPenalty
            ],
            [
                "keyString" => "1% Interest On Monthly Penalty(Notification No-641)",
                "value" => roundFigure((float)$onePercPenalty)
            ],
            [
                "keyString" => "Rebate",
                "value" => roundFigure((float)$rebate)
            ],
            [
                "keyString" => "Rebate From Jsk/Online Payment",
                "value" => roundFigure((float)$onlineRebate)
            ],
            [
                "keyString" => "Special Rebate",
                "value" => roundFigure((float)$specialRebate)
            ],
            [
                "keyString" => "First Qtr Rebate",
                "value" => roundFigure((float)$firstQtrRebate)
            ],
            [
                "keyString" => "Total Paid Amount",
                "value" => roundFigure((float)$amount)
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
            $propTransaction = new PropTransaction();
            $userId = auth()->user()->id;
            $propTrans = $propTransaction->getPropTransByUserId($userId);
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
        $req->validate(
            isset($req->holdingNo) ? ['holdingNo' => 'required'] : ['propertyId' => 'required|numeric']
        );
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
            if (!$properties) {
                throw new Exception("Property Not Found");
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
            $propActiveSaf = new PropActiveSaf();
            $verification = new PropSafVerification();
            $mWfRoleUsermap = new WfRoleusermap();
            $verificationDtl = new PropSafVerificationDtl();
            $userId = authUser()->id;
            $ulbId = authUser()->ulb_id;

            $safDtls = $propActiveSaf->getSafNo($req->safId);
            $workflowId = $safDtls->workflow_id;
            $roadWidthType = $this->readRoadWidthType($req->roadWidth);                                 // Read Road Width Type by Trait
            $getRoleReq = new Request([                                                                 // make request to get role id of the user
                'userId' => $userId,
                'workflowId' => $workflowId
            ]);

            $readRoleDtls = $mWfRoleUsermap->getRoleByUserWfId($getRoleReq);
            $roleId = $readRoleDtls->wf_role_id;

            DB::beginTransaction();
            switch ($roleId) {
                case $taxCollectorRole:                                                                  // In Case of Agency TAX Collector
                    $req->agencyVerification = true;
                    $req->ulbVerification = false;
                    $msg = "Site Successfully Verified";
                    break;
                case $ulbTaxCollectorRole:                                                                // In Case of Ulb Tax Collector
                    $req->agencyVerification = false;
                    $req->ulbVerification = true;
                    $msg = "Site Successfully Verified";
                    $propActiveSaf->verifyFieldStatus($req->safId);                                         // Enable Fields Verify Status
                    break;

                default:
                    return responseMsg(false, "Forbidden Access", "");
            }
            $req->merge(['roadType' => $roadWidthType, 'userId' => $userId, 'ulbId' => $ulbId]);
            // Verification Store
            $verificationId = $verification->store($req);                            // Model function to store verification and get the id
            // Verification Dtl Table Update                                         // For Tax Collector
            foreach ($req->floor as $floorDetail) {
                if ($floorDetail['useType'] == 1)
                    $carpetArea =  $floorDetail['buildupArea'] * 0.70;
                else
                    $carpetArea =  $floorDetail['buildupArea'] * 0.80;

                $floorReq = [
                    'verification_id' => $verificationId,
                    'saf_id' => $req->safId,
                    'saf_floor_id' => $floorDetail['floorId'] ?? null,
                    'floor_mstr_id' => $floorDetail['floorNo'],
                    'usage_type_id' => $floorDetail['useType'],
                    'construction_type_id' => $floorDetail['constructionType'],
                    'occupancy_type_id' => $floorDetail['occupancyType'],
                    'builtup_area' => $floorDetail['buildupArea'],
                    'date_from' => $floorDetail['dateFrom'],
                    'date_to' => $floorDetail['dateUpto'],
                    'carpet_area' => $carpetArea,
                    'user_id' => $userId,
                    'ulb_id' => $ulbId
                ];
                $verificationDtl->store($floorReq);
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
            "safId" => "required|numeric",
            "imagePath" => "required|array|min:3|max:3",
            "imagePath.*" => "required|image|mimes:jpeg,jpg,png,gif",
            "directionType" => "required|array|min:3|max:3",
            "directionType.*" => "required|In:Left,Right,Front",
            "longitude" => "required|array|min:3|max:3",
            "longitude.*" => "required|numeric",
            "latitude" => "required|array|min:3|max:3",
            "latitude.*" => "required|numeric"
        ]);
        try {
            $docUpload = new DocUpload;
            $geoTagging = new PropSafGeotagUpload();
            $relativePath = Config::get('PropertyConstaint.GEOTAGGING_RELATIVE_PATH');
            $safDtls = PropActiveSaf::findOrFail($req->safId);
            $images = $req->imagePath;
            $directionTypes = $req->directionType;
            $longitude = $req->longitude;
            $latitude = $req->latitude;

            DB::beginTransaction();
            collect($images)->map(function ($image, $key) use ($directionTypes, $relativePath, $req, $docUpload, $longitude, $latitude, $geoTagging) {
                $refImageName = 'saf-geotagging-' . $directionTypes[$key] . '-' . $req->safId;
                $docExistReqs = new Request([
                    'safId' => $req->safId,
                    'directionType' => $directionTypes[$key]
                ]);
                $imageName = $docUpload->upload($refImageName, $image, $relativePath);         // <------- Get uploaded image name and move the image in folder
                $isDocExist = $geoTagging->getGeoTagBySafIdDirectionType($docExistReqs);

                $docReqs = [
                    'saf_id' => $req->safId,
                    'image_path' => $imageName,
                    'direction_type' => $directionTypes[$key],
                    'longitude' => $longitude[$key],
                    'latitude' => $latitude[$key],
                    'relative_path' => $relativePath,
                    'user_id' => authUser()->id
                ];
                if ($isDocExist)
                    $geoTagging->edit($isDocExist, $docReqs);
                else
                    $geoTagging->store($docReqs);
            });

            $safDtls->is_geo_tagged = true;
            $safDtls->save();

            DB::commit();
            return responseMsgs(true, "Geo Tagging Done Successfully", "", "010119", "1.0", "289ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Get The Verification done by Agency Tc
     */
    public function getTcVerifications(Request $req)
    {
        $req->validate([
            'safId' => 'required|numeric'
        ]);
        try {
            $data = array();
            $safVerifications = new PropSafVerification();
            $safVerificationDtls = new PropSafVerificationDtl();
            $mSafGeoTag = new PropSafGeotagUpload();

            $data = $safVerifications->getVerificationsData($req->safId);                       // <--------- Prop Saf Verification Model Function to Get Prop Saf Verifications Data 
            if (collect($data)->isEmpty())
                throw new Exception("Tc Verification Not Done");

            $data = json_decode(json_encode($data), true);

            $verificationDtls = $safVerificationDtls->getFullVerificationDtls($data['id']);     // <----- Prop Saf Verification Model Function to Get Verification Floor Dtls
            $existingFloors = $verificationDtls->where('saf_floor_id', '!=', NULL);
            $newFloors = $verificationDtls->where('saf_floor_id', NULL);
            $data['newFloors'] = $newFloors->values();
            $data['existingFloors'] = $existingFloors->values();
            $geoTags = $mSafGeoTag->getGeoTags($req->safId);
            $data['geoTagging'] = $geoTags;
            return responseMsgs(true, "TC Verification Details", remove_null($data), "010120", "1.0", "258ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Get the Demandable Amount By SAF ID
     * | @param $req
     * | Query Run time -272ms 
     * | Rating-2
     */
    public function getDemandBySafId(Request $req)
    {
        $req->validate([
            'id' => 'required|numeric'
        ]);
        try {
            $safDetails = $this->details($req);
            $safTaxes = $this->calculateSafBySafId($req);
            $req = $safDetails;
            $demand['basicDetails'] = [
                "ulb_id" => $req['ulb_id'],
                "saf_no" => $req['saf_no'],
                "prop_address" => $req['prop_address'],
                "is_mobile_tower" => $req['is_mobile_tower'],
                "is_hoarding_board" => $req['is_hoarding_board'],
                "is_petrol_pump" => $req['is_petrol_pump'],
                "is_water_harvesting" => $req['is_water_harvesting'],
                "zone_mstr_id" => $req['zone_mstr_id'],
                "holding_no" => $req['new_holding_no'] ?? $req['holding_no'],
                "old_ward_no" => $req['old_ward_no'],
                "new_ward_no" => $req['new_ward_no'],
                "property_type" => $req['property_type'],
                "doc_upload_status" => $req['doc_upload_status']
            ];
            $demand['amounts'] = $safTaxes->original['data']['demand'];
            $demand['details'] = collect($safTaxes->original['data']['details']);
            $demand['paymentStatus'] = $safDetails['payment_status'];
            $demand['applicationNo'] = $safDetails['saf_no'];
            return responseMsgs(true, "Demand Details", remove_null($demand), "", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    # code by sandeep bara 
    # date 31-01-2023
    // ----------start------------
    public function getVerifications(Request $request)
    {
        try {
            $data = array();
            $request->validate([
                'applicationId' => 'required|digits_between:1,9223372036854775807',
            ]);
            $verifications = PropSafVerification::select(
                'prop_saf_verifications.*',
                'p.property_type',
                'r.road_type',
                'u.ward_name as ward_no',
                "users.user_name"
            )
                ->leftjoin('ref_prop_types as p', 'p.id', '=', 'prop_saf_verifications.prop_type_id')
                ->leftjoin('ref_prop_road_types as r', 'r.id', '=', 'prop_saf_verifications.road_type_id')
                ->leftjoin('ulb_ward_masters as u', 'u.id', '=', 'prop_saf_verifications.ward_id')
                ->leftjoin('users', 'users.id', '=', 'prop_saf_verifications.emp_id')
                ->where("prop_saf_verifications.id", $request->applicationId)
                ->first();
            if (!$verifications) {
                throw new Exception("verification Data NOt Found");
            }
            $saf = PropActiveSaf::select('prop_active_safs.*', 'p.property_type', 'r.road_type', 'u.ward_name as ward_no', "ownership_types.ownership_type")
                ->leftjoin('ref_prop_types as p', 'p.id', '=', 'prop_active_safs.prop_type_mstr_id')
                ->leftjoin('ref_prop_road_types as r', 'r.id', '=', 'prop_active_safs.road_type_mstr_id')
                ->leftjoin('ulb_ward_masters as u', 'u.id', '=', 'prop_active_safs.ward_mstr_id')
                ->leftjoin('ref_prop_ownership_types as ownership_types', 'ownership_types.id', '=', 'prop_active_safs.ownership_type_mstr_id')
                ->where("prop_active_safs.id", $verifications->saf_id)
                ->first();
            $tbl = "prop_active_safs";
            if (!$saf) {
                $saf = DB::table("prop_rejected_safs")
                    ->select('prop_rejected_safs.*', 'p.property_type', 'r.road_type', 'u.ward_name as ward_no', "ownership_types.ownership_type")
                    ->leftjoin('ref_prop_types as p', 'p.id', '=', 'prop_rejected_safs.prop_type_mstr_id')
                    ->leftjoin('ref_prop_road_types as r', 'r.id', '=', 'prop_rejected_safs.road_type_mstr_id')
                    ->leftjoin('ulb_ward_masters as u', 'u.id', '=', 'prop_rejected_safs.ward_mstr_id')
                    ->leftjoin('ref_prop_ownership_types as ownership_types', 'ownership_types.id', '=', 'prop_active_safs.ownership_type_mstr_id')
                    ->where("prop_rejected_safs.id", $verifications->saf_id)
                    ->first();
                $tbl = "prop_rejected_safs";
            }
            if (!$saf) {
                $saf = DB::table("prop_safs")
                    ->select('prop_safs.*', 'p.property_type', 'r.road_type', 'u.ward_name as ward_no', "ownership_types.ownership_type")
                    ->leftjoin('ref_prop_types as p', 'p.id', '=', 'prop_safs.prop_type_mstr_id')
                    ->leftjoin('ref_prop_road_types as r', 'r.id', '=', 'prop_safs.road_type_mstr_id')
                    ->leftjoin('ulb_ward_masters as u', 'u.id', '=', 'prop_safs.ward_mstr_id')
                    ->leftjoin('ref_prop_ownership_types as ownership_types', 'ownership_types.id', '=', 'prop_active_safs.ownership_type_mstr_id')
                    ->where("prop_safs.id", $verifications->saf_id)
                    ->first();
                $tbl = "prop_safs";
            }
            if (!$saf) {
                throw new Exception("Saf Data Not Found");
            }
            $floars = DB::table($tbl . "_floors")
                ->select($tbl . "_floors.*", 'f.floor_name', 'u.usage_type', 'o.occupancy_type', 'c.construction_type')
                ->leftjoin('ref_prop_floors as f', 'f.id', '=', $tbl . "_floors.floor_mstr_id")
                ->leftjoin('ref_prop_usage_types as u', 'u.id', '=', $tbl . "_floors.usage_type_mstr_id")
                ->leftjoin('ref_prop_occupancy_types as o', 'o.id', '=', $tbl . "_floors.occupancy_type_mstr_id")
                ->leftjoin('ref_prop_construction_types as c', 'c.id', '=', $tbl . "_floors.const_type_mstr_id")
                ->where($tbl . "_floors.saf_id", $saf->id)
                ->get();
            $verifications_detals = PropSafVerificationDtl::select('prop_saf_verification_dtls.*', 'f.floor_name', 'u.usage_type', 'o.occupancy_type', 'c.construction_type')
                ->leftjoin('ref_prop_floors as f', 'f.id', '=', 'prop_saf_verification_dtls.floor_mstr_id')
                ->leftjoin('ref_prop_usage_types as u', 'u.id', '=', 'prop_saf_verification_dtls.usage_type_id')
                ->leftjoin('ref_prop_occupancy_types as o', 'o.id', '=', 'prop_saf_verification_dtls.occupancy_type_id')
                ->leftjoin('ref_prop_construction_types as c', 'c.id', '=', 'prop_saf_verification_dtls.construction_type_id')
                ->where("verification_id", $verifications->id)
                ->get();

            $prop_compairs = [
                [
                    "key" => "Ward No",
                    "values" => $saf->ward_mstr_id == $verifications->ward_id,
                    "according_application" => $saf->ward_no,
                    "according_verification" => $verifications->ward_no,
                ],
                [
                    "key" => "Property Type",
                    "values" => $saf->prop_type_mstr_id == $verifications->prop_type_id,
                    "according_application" => $saf->property_type,
                    "according_verification" => $verifications->property_type,
                ],
                [
                    "key" => "Plot Area",
                    "values" => $saf->area_of_plot == $verifications->area_of_plot,
                    "according_application" => $saf->area_of_plot,
                    "according_verification" => $verifications->area_of_plot,
                ],
                [
                    "key" => "Road Type",
                    "values" => $saf->road_type_mstr_id == $verifications->road_type_id,
                    "according_application" => $saf->road_type,
                    "according_verification" => $verifications->road_type,
                ],
                [
                    "key" => "Mobile Tower",
                    "values" => $saf->is_mobile_tower == $verifications->has_mobile_tower,
                    "according_application" => $saf->is_mobile_tower ? "Yes" : "No",
                    "according_verification" => $verifications->has_mobile_tower ? "Yes" : "No",
                ],
                [
                    "key" => "Hoarding Board",
                    "values" => $saf->is_hoarding_board == $verifications->has_hoarding,
                    "according_application" => $saf->is_hoarding_board ? "Yes" : "No",
                    "according_verification" => $verifications->has_hoarding ? "Yes" : "No",
                ],
                [
                    "key" => "Petrol Pump",
                    "values" => $saf->is_petrol_pump == $verifications->is_petrol_pump,
                    "according_application" => $saf->is_petrol_pump ? "Yes" : "No",
                    "according_verification" => $verifications->is_petrol_pump ? "Yes" : "No",
                ],
                [
                    "key" => "Water Harvesting",
                    "values" => $saf->is_water_harvesting == $verifications->has_water_harvesting,
                    "according_application" => $saf->is_water_harvesting ? "Yes" : "No",
                    "according_verification" => $verifications->has_water_harvesting ? "Yes" : "No",
                ],
            ];
            $size = sizeOf($floars) >= sizeOf($verifications_detals) ? $floars : $verifications_detals;
            $keys = sizeOf($floars) >= sizeOf($verifications_detals) ? "floars" : "detals";
            $floors_compais = array();
            $floors_compais = $size->map(function ($val, $key) use ($floars, $verifications_detals, $keys) {
                if ($keys == "floars") {
                    // $saf_data=($floars->where("id",$val->id))->values();
                    // $verification=($verifications_detals->where("saf_floor_id",$val->id))->values();
                    $saf_data = collect(array_values(objToArray(($floars->where("id", $val->id))->values())))->all();
                    $verification = collect(array_values(objToArray(($verifications_detals->where("saf_floor_id", $val->id))->values())))->all();
                } else {
                    // $saf_data=($floars->where("id",$val->saf_floor_id))->values();
                    // $verification=($verifications_detals->where("id",$val->id))->values();
                    $saf_data = collect(array_values(objToArray(($floars->where("id", $val->saf_floor_id))->values())))->all();
                    $verification = collect(array_values(objToArray(($verifications_detals->where("id", $val->id))->values())))->all();
                }
                return [
                    "floar_name" => $val->floor_name,
                    "values" => [
                        [
                            "key" => "Usage Type",
                            "values" => ($saf_data[0]->usage_type_mstr_id ?? "") == ($verification[0]->usage_type_id ?? ""),
                            "according_application" => $saf_data[0]->usage_type ?? "",
                            "according_verification" => $verification[0]->usage_type ?? "",
                        ],
                        [
                            "key" => "Occupancy Type",
                            "values" => ($saf_data[0]->occupancy_type_mstr_id ?? "") == ($verification[0]->occupancy_type_id ?? ""),
                            "according_application" => $saf_data[0]->occupancy_type ?? "",
                            "according_verification" => $verification[0]->occupancy_type ?? "",
                        ],
                        [
                            "key" => "Construction Type",
                            "values" => ($saf_data[0]->const_type_mstr_id ?? "") == ($verification[0]->construction_type_id ?? ""),
                            "according_application" => $saf_data[0]->construction_type ?? "",
                            "according_verification" => $verification[0]->construction_type ?? "",
                        ],
                        [
                            "key" => "Built Up Area (in Sq. Ft.)",
                            "values" => ($saf_data[0]->builtup_area ?? "") == ($verification[0]->builtup_area ?? ""),
                            "according_application" => $saf_data[0]->builtup_area ?? "",
                            "according_verification" => $verification[0]->builtup_area ?? "",
                        ],
                        [
                            "key" => "Date of Completion",
                            "values" => ($saf_data[0]->date_from ?? "") == ($verification[0]->date_from ?? ""),
                            "according_application" => $saf_data[0]->date_from ?? "",
                            "according_verification" => $verification[0]->date_from ?? "",
                        ]
                    ]
                ];
            });
            $message = "ULB TC Verification Details";
            if ($verifications->agency_verification) {
                $PropertyDeactivate = new \App\Repository\Property\Concrete\PropertyDeactivate();
                $geoTagging = PropSafGeotagUpload::where("saf_id", $saf->id)->get()->map(function ($val) use ($PropertyDeactivate) {
                    $val->paths = $PropertyDeactivate->readDocumentPath($val->relative_path . "/" . $val->image_path);
                    return $val;
                });
                $message = "TC Verification Details";
                $data["geoTagging"] = $geoTagging;
            } else {
                $owners = DB::table($tbl . "_owners")
                    ->select($tbl . "_owners.*")
                    ->where($tbl . "_owners.saf_id", $saf->id)
                    ->get();

                $redis = Redis::connection();
                $redissafTaxes = Redis::get('safTaxes:' . $verifications->id . "." . $saf->id);                           // Ward No Value from Redis
                if (!$redissafTaxes) {
                    $safDetails = $saf;
                    $safDetails = json_decode(json_encode($safDetails), true);
                    $safDetails['floors'] = $floars;
                    $safDetails['owners'] = $owners;
                    $req = $safDetails;
                    $array = $this->generateSafRequest($req);                                                                       // Generate SAF Request by SAF Id Using Trait
                    $safCalculation = new SafCalculation();
                    $request = new Request($array);
                    $safTaxes = $safCalculation->calculateTax($request);

                    // $safTaxes = json_decode(json_encode($safTaxes), true);

                    $safDetails2 = json_decode(json_encode($verifications), true);

                    $safDetails2["ward_mstr_id"] = $safDetails2["ward_id"];
                    $safDetails2["prop_type_mstr_id"] = $safDetails2["prop_type_id"];
                    $safDetails2["land_occupation_date"] = $saf->land_occupation_date;
                    $safDetails2["ownership_type_mstr_id"] = $saf->ownership_type_mstr_id;
                    $safDetails2["zone_mstr_id"] = $saf->zone_mstr_id;
                    $safDetails2["road_type_mstr_id"] = $saf->road_type_mstr_id;
                    $safDetails2["road_width"] = $saf->road_width;
                    $safDetails2["is_gb_saf"] = $saf->is_gb_saf;

                    $safDetails2["is_mobile_tower"] = $safDetails2["has_mobile_tower"];
                    $safDetails2["tower_area"] = $safDetails2["tower_area"];
                    $safDetails2["tower_installation_date"] = $safDetails2["tower_installation_date"];

                    $safDetails2["is_hoarding_board"] = $safDetails2["has_hoarding"];
                    $safDetails2["hoarding_area"] = $safDetails2["hoarding_area"];
                    $safDetails2["hoarding_installation_date"] = $safDetails2["hoarding_installation_date"];

                    $safDetails2["is_petrol_pump"] = $safDetails2["is_petrol_pump"];
                    $safDetails2["under_ground_area"] = $safDetails2["underground_area"];
                    $safDetails2["petrol_pump_completion_date"] = $safDetails2["petrol_pump_completion_date"];

                    $safDetails2["is_water_harvesting"] = $safDetails2["has_water_harvesting"];

                    $safDetails2['floors'] = $verifications_detals;
                    $safDetails2['floors'] = $safDetails2['floors']->map(function ($val) {
                        $val->usage_type_mstr_id    = $val->usage_type_id;
                        $val->const_type_mstr_id    = $val->construction_type_id;
                        $val->occupancy_type_mstr_id = $val->occupancy_type_id;
                        $val->builtup_area          = $val->builtup_area;
                        $val->date_from             = $val->date_from;
                        $val->date_upto             = $val->date_to;
                        return $val;
                    });


                    $safDetails2['owners'] = $owners;
                    $array2 = $this->generateSafRequest($safDetails2);
                    // dd($array);
                    $request2 = new Request($array2);
                    $safTaxes2 = $safCalculation->calculateTax($request2);
                    // $safTaxes2 = json_decode(json_encode($safTaxes2), true);
                    $safTaxes3 = $this->reviewTaxCalculation($safTaxes);
                    // dd($safTaxes2);
                    $safTaxes4 = $this->reviewTaxCalculation($safTaxes2);
                    // dd(json_decode(json_encode($safTaxes), true));
                    $compairTax = $this->reviewTaxCalculationCom($safTaxes, $safTaxes2);

                    $safTaxes2 = json_decode(json_encode($safTaxes4), true);
                    $safTaxes = json_decode(json_encode($safTaxes3), true);
                    $compairTax = json_decode(json_encode($compairTax), true);

                    $data["Tax"]["according_application"] = $safTaxes["original"]["data"];
                    $data["Tax"]["according_verification"] = $safTaxes2["original"]["data"];
                    $data["Tax"]["compairTax"] = $compairTax["original"]["data"];
                    $redis->set('safTaxes:' . $verifications->id . "." . $saf->id, json_encode($data));
                    $redis->expire('safTaxes:' . $verifications->id . "." . $saf->id, 18000);
                } else {
                    $data = json_decode($redissafTaxes, true);
                }
            }
            $data["saf_details"] = $saf;
            $data["employee_details"] = ["user_name" => $verifications->user_name, "date" => $verifications->created_at];
            $data["property_comparison"] = $prop_compairs;
            $data["floor_comparison"] = $floors_compais;
            return responseMsgs(true, $message, remove_null($data), "010121", "1.0", "258ms", "POST", $request->deviceId);
        } catch (Exception $e) {
            // dd($e->getMessage(),$e->getFile(),$e->getLine());
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    public function getSafVerificationList(Request $request)
    {
        try {
            $data = array();
            $request->validate([
                'applicationId' => 'required|digits_between:1,9223372036854775807',
            ]);
            $verifications = PropSafVerification::select(
                'id',
                'created_at',
                'agency_verification',
                "ulb_verification"
            )
                ->where("prop_saf_verifications.status", 1)
                ->where("prop_saf_verifications.saf_id", $request->applicationId)
                ->get();
            $data = $verifications->map(function ($val) {
                $val->veryfied_by = $val->agency_verification ? "AGENCY TC" : "ULB TC";
                return $val;
            });
            return responseMsgs(true, "Data Fetched", remove_null($data), "010122", "1.0", "258ms", "POST", $request->deviceId);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    private function reviewTaxCalculation(object $response)
    {
        try {
            $finalResponse['demand'] = $response->original['data']['demand'];
            $reviewDetails = collect($response->original['data']['details'])->groupBy(['ruleSet', 'mFloorNo', 'mUsageType']);
            $finalTaxReview = collect();
            $review = collect($reviewDetails)->map(function ($reviewDetail) use ($finalTaxReview) {
                $table = collect($reviewDetail)->map(function ($floors) use ($finalTaxReview) {
                    $usageType = collect($floors)->map(function ($floor) use ($finalTaxReview) {
                        $first = $floor->first();
                        $response = $first->only([
                            'mFloorNo',
                            'mUsageType',
                            'arv',
                            'buildupArea',
                            'dateFrom',
                            'quarterYear',
                            'qtr',
                            'ruleSet',
                            'holdingTax',
                            'waterTax',
                            'latrineTax',
                            'educationTax',
                            'healthTax',
                            'totalTax',
                            'rwhPenalty',
                            'rentalValue',
                            'carpetArea',
                            'calculationPercFactor',
                            'multiFactor',
                            'rentalRate',
                            'occupancyFactor',
                            'circleRate',
                            'taxPerc',
                            'calculationFactor',
                            'matrixFactor'
                        ]);
                        $finalTaxReview->push($response);
                        return $response;
                    });
                    return $usageType;
                });
                return $table;
            });
            $ruleSetCollections = collect($finalTaxReview)->groupBy(['ruleSet']);
            $reviewCalculation = collect($ruleSetCollections)->map(function ($collection) {
                return collect($collection)->pipe(function ($collect) {
                    $quaters['floors'] = $collect;
                    $groupByFloors = $collect->groupBy(['quarterYear', 'qtr']);
                    $quaterlyTaxes = collect();
                    collect($groupByFloors)->map(function ($qtrYear) use ($quaterlyTaxes) {
                        return collect($qtrYear)->map(function ($qtr, $key) use ($quaterlyTaxes) {
                            return collect($qtr)->pipe(function ($floors) use ($quaterlyTaxes, $key) {
                                $taxes = [
                                    'key' => $key,
                                    'effectingFrom' => $floors->first()['dateFrom'],
                                    'qtr' => $floors->first()['qtr'],
                                    'arv' => roundFigure($floors->sum('arv')),
                                    'holdingTax' => roundFigure($floors->sum('holdingTax')),
                                    'waterTax' => roundFigure($floors->sum('waterTax')),
                                    'latrineTax' => roundFigure($floors->sum('latrineTax')),
                                    'educationTax' => roundFigure($floors->sum('educationTax')),
                                    'healthTax' => roundFigure($floors->sum('healthTax')),
                                    'rwhPenalty' => roundFigure($floors->sum('rwhPenalty')),
                                    'quaterlyTax' => roundFigure($floors->sum('totalTax')),
                                ];
                                $quaterlyTaxes->push($taxes);
                            });
                        });
                    });
                    $quaters['totalQtrTaxes'] = $quaterlyTaxes;
                    return $quaters;
                });
            });
            $finalResponse['details'] = $reviewCalculation;
            return responseMsg(true, "", $finalResponse);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    private function reviewTaxCalculationCom(object $response, object $response2)
    {

        try {
            $finalResponse['demand'] = $response->original['data']['demand'];
            $finalResponse2['demand'] = $response2->original['data']['demand'];
            // dd( $response->original['data'],  $response2->original['data']);
            $reviewDetails = collect($response->original['data']['details'])->groupBy(['ruleSet', 'mFloorNo', 'mUsageType']);
            $reviewDetails2 = collect($response2->original['data']['details'])->groupBy(['ruleSet', 'mFloorNo', 'mUsageType']);

            $finalTaxReview = collect();
            $finalTaxReview2 = collect();

            $review = collect($reviewDetails)->map(function ($reviewDetail) use ($finalTaxReview) {

                $table = collect($reviewDetail)->map(function ($floors) use ($finalTaxReview) {

                    $usageType = collect($floors)->map(function ($floor) use ($finalTaxReview) {

                        $first = $floor->first();

                        $response = $first->only([
                            'mFloorNo',
                            'mUsageType',
                            'arv',
                            'buildupArea',
                            'dateFrom',
                            'quarterYear',
                            'qtr',
                            'ruleSet',
                            'holdingTax',
                            'waterTax',
                            'latrineTax',
                            'educationTax',
                            'healthTax',
                            'totalTax',
                            'rwhPenalty',
                            'rentalValue',
                            'carpetArea',
                            'calculationPercFactor',
                            'multiFactor',
                            'rentalRate',
                            'occupancyFactor',
                            'circleRate',
                            'taxPerc',
                            'calculationFactor',
                            'matrixFactor'
                        ]);
                        $finalTaxReview->push($response);
                        return $response;
                    });
                    return $usageType;
                });
                return $table;
            });

            $review2 = collect($reviewDetails2)->map(function ($reviewDetail) use ($finalTaxReview2) {

                $table = collect($reviewDetail)->map(function ($floors) use ($finalTaxReview2) {

                    $usageType = collect($floors)->map(function ($floor) use ($finalTaxReview2) {

                        $first = $floor->first();

                        $response = $first->only([
                            'mFloorNo',
                            'mUsageType',
                            'arv',
                            'buildupArea',
                            'dateFrom',
                            'quarterYear',
                            'qtr',
                            'ruleSet',
                            'holdingTax',
                            'waterTax',
                            'latrineTax',
                            'educationTax',
                            'healthTax',
                            'totalTax',
                            'rwhPenalty',
                            'rentalValue',
                            'carpetArea',
                            'calculationPercFactor',
                            'multiFactor',
                            'rentalRate',
                            'occupancyFactor',
                            'circleRate',
                            'taxPerc',
                            'calculationFactor',
                            'matrixFactor'
                        ]);
                        $finalTaxReview2->push($response);
                        return $response;
                    });
                    return $usageType;
                });
                return $table;
            });

            $ruleSetCollections = collect($finalTaxReview)->groupBy(['ruleSet']);
            $ruleSetCollections2 = collect($finalTaxReview2)->groupBy(['ruleSet']);

            $reviewCalculation = collect($ruleSetCollections2)->map(function ($collection, $key) use ($ruleSetCollections) {
                $collection2 = collect($ruleSetCollections[$key] ?? []);
                // dd($key);
                return collect($collection)->pipe(function ($collect) use ($collection2) {

                    $quaters['floors'] = $collect;
                    $quaters2['floors'] = $collection2;

                    $groupByFloors = $collect->groupBy(['quarterYear', 'qtr']);
                    $groupByFloors2 = $collection2->groupBy(['quarterYear', 'qtr']) ?? [];

                    $quaterlyTaxes = collect();

                    collect($groupByFloors)->map(function ($qtrYear, $key1) use ($quaterlyTaxes, $groupByFloors2) {

                        $qtrYear2 = collect($groupByFloors2[$key1] ?? []);

                        return collect($qtrYear)->map(function ($qtr, $key) use ($quaterlyTaxes, $qtrYear2) {

                            $qtr2 = $qtrYear2[$key] ?? collect([]);

                            return collect($qtr)->pipe(function ($floors) use ($quaterlyTaxes, $key, $qtr2) {

                                $taxes = [
                                    'key' => $key,
                                    'effectingFrom' => $floors->first()['dateFrom'],
                                    'qtr' => $floors->first()['qtr'],
                                    'arv' => roundFigure(($floors->sum('arv')) - ($qtr2->sum('arv'))),
                                    'holdingTax' => roundFigure(($floors->sum('holdingTax')) - ($qtr2->sum('holdingTax'))),
                                    'waterTax' => roundFigure(($floors->sum('waterTax')) - ($qtr2->sum('waterTax'))),
                                    'latrineTax' => roundFigure(($floors->sum('latrineTax')) - ($qtr2->sum('latrineTax'))),
                                    'educationTax' => roundFigure(($floors->sum('educationTax')) - ($qtr2->sum('educationTax'))),
                                    'healthTax' => roundFigure(($floors->sum('healthTax')) - ($qtr2->sum('healthTax'))),
                                    'rwhPenalty' => roundFigure(($floors->sum('rwhPenalty')) - ($qtr2->sum('rwhPenalty'))),
                                    'quaterlyTax' => roundFigure(($floors->sum('totalTax')) - ($qtr2->sum('totalTax'))),
                                ];
                                $quaterlyTaxes->push($taxes);
                            });
                        });
                    });

                    $quaters['totalQtrTaxes'] = $quaterlyTaxes;
                    return $quaters;
                });
            });
            $finalResponse2['details'] = $reviewCalculation;
            return responseMsg(true, "", $finalResponse2);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }
    // ---------end----------------
}
