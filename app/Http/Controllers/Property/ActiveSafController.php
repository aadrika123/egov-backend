<?php

namespace App\Http\Controllers\Property;

use App\EloquentClass\Property\InsertTax;
use App\EloquentClass\Property\SafCalculation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Property\reqApplySaf;
use App\Http\Requests\Property\ReqSiteVerification;
use App\Models\Property\PropActiveSaf;
use App\Models\Property\PropActiveSafsFloor;
use App\Models\Property\PropActiveSafsOwner;
use App\Models\Property\PropLevelPending;
use App\Models\Property\PropTransaction;
use App\Models\Property\RefPropConstructionType;
use App\Models\Property\RefPropFloor;
use App\Models\Property\RefPropOccupancyType;
use App\Models\Property\RefPropOwnershipType;
use App\Models\Property\RefPropTransferMode;
use App\Models\Property\RefPropType;
use App\Models\Property\RefPropUsageType;
use App\Models\UlbWardMaster;
use App\Models\Workflows\WfRoleusermap;
use App\Models\Workflows\WfWardUser;
use App\Models\Workflows\WfWorkflow;
use App\Models\WorkflowTrack;
use Illuminate\Http\Request;
use App\Repository\Property\Interfaces\iSafRepository;
use App\Traits\Property\SAF;
use App\Traits\Workflow\Workflow;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class ActiveSafController extends Controller
{
    use Workflow;
    use SAF;
    /**
     * | Created On-08-08-2022 
     * | Created By-Anshu Kumar
     * --------------------------------------------------------------------------------------
     * | Controller regarding with SAF Module
     */

    protected $user_id;
    protected $_todayDate;
    protected $_workflowIds;
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

            // Getting Masters from Redis Cache
            $wardMaster = json_decode(Redis::get('wards-ulb-' . $ulbId));
            $ownershipTypes = json_decode(Redis::get('prop-ownership-types'));
            $propertyType = json_decode(Redis::get('property-types'));
            $floorType = json_decode(Redis::get('property-floors'));
            $usageType = json_decode(Redis::get('property-usage-types'));
            $occupancyType = json_decode(Redis::get('property-occupancy-types'));
            $constructionType = json_decode(Redis::get('property-construction-types'));
            $transferModuleType = json_decode(Redis::get('property-transfer-modes'));

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
            $safTaxes = $safCalculation->calculateTax($request);
            $mLateAssessPenalty = $safTaxes->original['data']['demand']['lateAssessmentPenalty'];

            $refInitiatorRoleId = $this->getInitiatorId($ulbWorkflowId->id);                                // Get Current Initiator ID
            $initiatorRoleId = DB::select($refInitiatorRoleId);

            $refFinisherRoleId = $this->getFinisherId($ulbWorkflowId->id);
            $finisherRoleId = DB::select($refFinisherRoleId);

            DB::beginTransaction();
            $safNo = $this->safNo($request->ward, $assessmentTypeId, $ulb_id);
            $saf = new PropActiveSaf();

            $metaReqs['lateAssessPenalty'] = $mLateAssessPenalty;
            $metaReqs['safNo'] = $safNo;
            $metaReqs['roadWidthType'] = $roadWidthType;
            $metaReqs['userId'] = $user_id;
            $metaReqs['workflowId'] = $ulbWorkflowId->id;
            $metaReqs['ulbId'] = $ulb_id;
            $metaReqs['initiatorRoleId'] = collect($initiatorRoleId)->first()->role_id;;
            $metaReqs['finisherRoleId'] = collect($finisherRoleId)->first()->role_id;

            $request->merge($metaReqs);
            $safId = $saf->store($request);                                             // Store SAF Using Model function 

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

            // Property SAF Label Pendings
            $labelPending = new PropLevelPending();
            $labelPending->saf_id = $safId;
            $labelPending->receiver_role_id = collect($initiatorRoleId)->first()->role_id;
            $labelPending->save();

            // Insert Tax
            $demand['amounts'] = $safTaxes->original['data']['demand'];
            $demand['details'] = $this->generateSafDemand($safTaxes->original['data']['details']);

            $tax = new InsertTax();
            $tax->insertTax($safId, $user_id, $safTaxes);                                               // Insert SAF Tax

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
            'id' => 'required|integer',
            'zone' => 'required|integer',
            'owner' => 'array',
            'owner.*.ownerId' => 'required|integer',
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

    // Document Upload By Citizen Or JSK
    public function documentUpload(Request $req)
    {
        $req->validate([
            'safId' => 'required|integer'
        ]);
        return $this->Repository->documentUpload($req);
    }

    // Verify Document By Dealing Assistant
    public function verifyDoc(Request $req)
    {
        $req->validate([
            "verifications" => "required"
        ]);
        return $this->Repository->verifyDoc($req);
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

            $data = $this->getSaf($this->_workflowIds)                                  // Global SAF 
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

            $data = $this->getSaf($this->_workflowIds)                                       // Global SAF 
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

            $safData = $this->getSaf($this->_workflowIds)
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
            $safData = $this->getSaf($this->_workflowIds)
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


    public function details(Request $request)
    {
        $data = $this->Repository->details($request);
        return $data;
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
            "safId" => "required|int",
        ]);
        try {
            $userId = auth()->user()->id;
            $saf_id = $request->safId;
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
            'safId' => 'required|integer'
        ]);

        try {
            $propLevelPending = new PropLevelPending();
            $workflowTrack = new WorkflowTrack();
            $userId = auth()->user()->id;
            $saf = PropActiveSaf::find($request->safId);                // SAF Details
            $mSafWorkflowId = Config::get('workflow-constants.SAF_WORKFLOW_ID');
            $mModuleId = Config::get('module-constants.PROPERTY_MODULE_ID');
            $metaReqs = array();
            DB::beginTransaction();

            $levelPending = $propLevelPending->getLevelBySafReceiver($request->safId, $userId);     // <---- Get level Pending by Model Function

            if (is_null($levelPending)) {
                $levelPending = $propLevelPending->getLastLevelBySafId($request->safId);            // <---- Get Last Level By SAf id by Model function
                if (is_null($levelPending)) {
                    return responseMsg(false, "SAF Not Found", "");
                }
            }
            $levelPending->remarks = $request->comment;
            $levelPending->receiver_user_id = $userId;
            $levelPending->save();

            // Save On Workflow Track
            $metaReqs = [
                'workflowId' => $mSafWorkflowId,
                'userId' => $saf->user_id,
                'moduleId' => $mModuleId,
                'workflowId' => $mSafWorkflowId,
                'refTableDotId' => "active_safs.id",
                'refTableIdValue' => $saf->id,
                'message' => $request->comment
            ];
            $request->request->add($metaReqs);
            $workflowTrack->saveTrack($request);

            DB::commit();
            return responseMsgs(true, "You Have Commented Successfully!!", ['Comment' => $request->comment], "010108", "1.0", "427ms", "POST", "");
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
            'safId' => 'required|integer',
            'senderRoleId' => 'required|integer',
            'receiverRoleId' => 'required|integer',
            'comment' => 'required',
        ]);

        try {
            // SAF Application Update Current Role Updation
            DB::beginTransaction();
            $saf = PropActiveSaf::find($request->safId);
            if ($request->senderRoleId == $saf->initiator_role_id) {                                // Initiator Role Id
                $saf->doc_upload_status = 1;
            }
            // Check if the application is in case of BTC
            if ($saf->parked == true) {
                $levelPending = new PropLevelPending();
                $lastLevelEntry = $levelPending->getLastLevelBySafId($request->safId);              // Send Last Level Current Role
                $saf->parked = false;                                                               // Disable BTC
                $saf->current_role = $lastLevelEntry->sender_role_id;
            } else
                $saf->current_role = $request->receiverRoleId;
            $saf->save();

            // previous level pending verification enabling
            $levelPending = new PropLevelPending();
            $levelPending->saf_id = $request->safId;
            $levelPending->sender_role_id = $request->senderRoleId;
            $levelPending->receiver_role_id = $request->receiverRoleId;
            $levelPending->sender_user_id = auth()->user()->id;
            $levelPending->save();

            // Add Comment On Prop Level Pending
            $propLevelPending = new PropLevelPending();
            $commentOnlevel = $propLevelPending->getLevelBySafReceiver($request->safId, $request->senderRoleId);    //<-----Get SAF level Pending By safid and current role ID
            $commentOnlevel->remarks = $request->comment;
            $commentOnlevel->verification_status = 1;
            $commentOnlevel->forward_date = $this->_todayDate->format('Y-m-d');
            $commentOnlevel->forward_time = $this->_todayDate->format('H:i:m');
            $commentOnlevel->verification_status = 1;
            $commentOnlevel->save();

            DB::commit();
            return responseMsgs(true, "Successfully Forwarded The Application!!", "", "010109", "1.0", "286ms", "POST", $request->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }

    // Saf Application Approval Or Reject
    public function approvalRejectionSaf(Request $req)
    {
        $req->validate([
            'workflowId' => 'required|integer',
            'roleId' => 'required|integer',
            'safId' => 'required|integer',
            'status' => 'required|integer'
        ]);

        return $this->Repository->approvalRejectionSaf($req);
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
            'safId' => 'required|integer',
            'workflowId' => 'required|integer',
            'currentRoleId' => 'required|integer',
            'comment' => 'required|string'
        ]);

        try {
            $saf = PropActiveSaf::find($req->safId);
            $propLevelPending = new PropLevelPending();
            DB::beginTransaction();
            $initiatorRoleId = $saf->initiator_role_id;
            $saf->current_role = $initiatorRoleId;
            $saf->parked = true;                        //<------ SAF Pending Status true
            $saf->save();

            $preLevelPending = $propLevelPending->getLevelBySafReceiver($req->safId, $req->currentRoleId);
            $preLevelPending->remarks = $req->comment;
            $preLevelPending->forward_date = $this->_todayDate->format('Y-m-d');
            $preLevelPending->forward_time = $this->_todayDate->format('H:i:m');
            $preLevelPending->save();

            $levelPending = new PropLevelPending();
            $levelPending->saf_id = $req->safId;
            $levelPending->sender_role_id = $req->currentRoleId;
            $levelPending->receiver_role_id = $initiatorRoleId;
            $levelPending->user_id = authUser()->id;
            $levelPending->sender_user_id = authUser()->id;
            $levelPending->save();

            DB::commit();
            return responseMsgs(true, "Successfully Done", "", "010111", "1.0", "350ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    // Calculate SAF by saf ID
    public function calculateSafBySafId(Request $req)
    {
        return $this->Repository->calculateSafBySafId($req);
    }

    // Generate Payment Order ID
    public function generateOrderId(Request $req)
    {
        $req->validate([
            'id' => 'required|integer',
            'amount' => 'required|numeric',
            'departmentId' => 'required|integer'
        ]);

        return $this->Repository->generateOrderId($req);
    }

    // SAF Payment 
    public function paymentSaf(Request $req)
    {
        return $this->Repository->paymentSaf($req);
    }

    // Generate Payment Receipt
    public function generatePaymentReceipt(Request $req)
    {
        $req->validate([
            'paymentId' => 'required'
        ]);

        return $this->Repository->generatePaymentReceipt($req);
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
                $propTrans = $propTransaction->getPropTransBySafId($req->safId);
            if ($req->propertyId)                                           // Get by Property Id
                $propTrans = $propTransaction->getPropTransByPropId($req->propertyId);

            return responseMsg(true, "Property Transactions", remove_null($propTrans));
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    // Get Property by Holding No
    public function getPropByHoldingNo(Request $req)
    {
        return $this->Repository->getPropByHoldingNo($req);
    }

    // Site Verification
    public function siteVerification(ReqSiteVerification $req)
    {
        return $this->Repository->siteVerification($req);
    }

    // Geo Tagging
    public function geoTagging(Request $req)
    {
        $req->validate([
            "safId" => "required|integer",
            "imagePath.*" => "image|mimes:jpeg,jpg,png,gif|required"
        ]);
        return $this->Repository->geoTagging($req);
    }

    //document verification
    public function safDocStatus(Request $req)
    {
        return $this->Repository->safDocStatus($req);
    }

    // Get TC Verifications
    public function getTcVerifications(Request $req)
    {
        return $this->Repository->getTcVerifications($req);
    }
}
