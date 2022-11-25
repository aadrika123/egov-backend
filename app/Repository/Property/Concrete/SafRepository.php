<?php

namespace App\Repository\Property\Concrete;

use App\Repository\Property\Interfaces\iSafRepository;
use Illuminate\Http\Request;
use App\Models\UlbWardMaster;
use App\Traits\Auth;
use App\Traits\Property\WardPermission;

use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\EloquentClass\Property\InsertTax;
use App\EloquentClass\Property\SafCalculation;
use App\Models\Property\PropActiveSaf;
use App\Models\Property\PropActiveSafsDoc;
use App\Models\Property\PropActiveSafsFloor;
use App\Models\Property\PropActiveSafsOwner;
use App\Models\Property\PropFloor;
use App\Models\Property\PropLevelPending;
use App\Models\Property\PropOwner;
use App\Models\Property\PropSafGeotagUpload;
use App\Models\Property\PropSafVerification;
use App\Models\Property\PropSafVerificationDtl;
use App\Models\Property\PropTransaction;
use App\Models\Property\RefPropConstructionType;
use App\Models\Property\RefPropFloor;
use App\Models\Property\RefPropOccupancyType;
use App\Models\Property\RefPropOwnershipType;
use App\Models\Property\RefPropTransferMode;
use App\Models\Property\RefPropType;
use App\Models\Property\RefPropUsageType;
use App\Models\Workflows\WfWorkflow;
use App\Models\Workflows\WfWorkflowrolemap;
use App\Models\WorkflowTrack;
use App\Traits\Workflow\Workflow as WorkflowTrait;
use App\Traits\Helper;
use App\Traits\Payment\Razorpay;
use App\Traits\Property\SAF as GlobalSAF;
use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;

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
class SafRepository implements iSafRepository
{
    use Auth;                                                               // Trait Used added by sandeep bara date 17-08-2022
    use WardPermission;
    use WorkflowTrait;
    use GlobalSAF;
    use Razorpay;
    use Helper;
    /**
     * | Citizens Applying For SAF
     * | Proper Validation will be applied after 
     * | @param Illuminate\Http\Request
     * | @param Request $request
     * | @param response
     */
    protected $user_id;
    protected $_redis;
    public function __construct()
    {
        $this->_redis = Redis::connection();
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
        $ulbId = auth()->user()->ulb_id;
        // Ward Masters
        $wardMaster = json_decode(Redis::get('wards-ulb-' . $ulbId));
        if (!$wardMaster) {
            $wardMaster = UlbWardMaster::select('id', 'ward_name')
                ->where('ulb_id', $ulbId)
                ->get();
            $this->_redis->set('wards-ulb-' . $ulbId, json_encode($wardMaster));            // Caching
        }
        $data = [];
        $data['ward_master'] = $wardMaster;

        // Ownership Types
        $ownershipTypes = json_decode(Redis::get('prop-ownership-types'));
        if (!$ownershipTypes) {
            $ownershipTypes = RefPropOwnershipType::select('id', 'ownership_type')
                ->where('status', 1)
                ->get();
            $this->_redis->set('prop-ownership-types', json_encode($ownershipTypes));
        }
        $data['ownership_types'] = $ownershipTypes;

        // Property Types
        $propertyType = json_decode(Redis::get('property-types'));
        if (!$propertyType) {
            $propertyType = RefPropType::select('id', 'property_type')
                ->where('status', 1)
                ->get();
            $this->_redis->set('property-types', json_encode($propertyType));
        }
        $data['property_type'] = $propertyType;

        // Property Floors
        $floorType = json_decode(Redis::get('property-floors'));
        if (!$floorType) {
            $floorType = RefPropFloor::select('id', 'floor_name')
                ->where('status', 1)
                ->get();
            $this->_redis->set('propery-floors', json_encode($floorType));
        }
        $data['floor_type'] = $floorType;

        // Property Usage Types
        $usageType = json_decode(Redis::get('property-usage-types'));
        if (!$usageType) {
            $usageType = RefPropUsageType::select('id', 'usage_type', 'usage_code')
                ->where('status', 1)
                ->get();
            $this->_redis->set('property-usage-types', json_encode($usageType));
        }
        $data['usage_type'] = $usageType;

        // Property Occupancy Types
        $occupancyType = json_decode(Redis::get('property-occupancy-types'));
        if (!$occupancyType) {
            $occupancyType = RefPropOccupancyType::select('id', 'occupancy_type')
                ->where('status', 1)
                ->get();
            $this->_redis->set('property-occupancy-types', json_encode($occupancyType));
        }
        $data['occupancy_type'] = $occupancyType;

        // property construction types
        $constructionType = json_decode(Redis::get('property-construction-types'));
        if (!$constructionType) {
            $constructionType = RefPropConstructionType::select('id', "construction_type")
                ->where('status', 1)
                ->get();
        }
        $this->_redis->set('property-construction-types', json_encode($constructionType));
        $data['construction_type'] = $constructionType;

        // property transfer modes
        $transferModuleType = json_decode(Redis::get('property-transfer-modes'));
        if (!$transferModuleType) {
            $transferModuleType = RefPropTransferMode::select('id', 'transfer_mode')
                ->where('status', 1)
                ->get();
            $this->_redis->set('property-transfer-modes', json_encode($transferModuleType));
        }
        $data['transfer_mode'] = $transferModuleType;

        return  responseMsg(true, '', $data);
    }

    /**
     * | Apply for New Application
     * | Status-Closed
     * | Query Costing-500 ms
     * | Rating-5
     */

    public function applySaf($request)
    {
        try {
            $user_id = auth()->user()->id;
            $ulb_id = auth()->user()->ulb_id;
            $assessmentTypeId = $request->assessmentType;
            if ($request->assessmentType == 1) {                                                    // New Assessment 
                $workflow_id = Config::get('workflow-constants.SAF_WORKFLOW_ID');
            }

            if ($request->assessmentType == 2) {                                                    // Reassessment
                $workflow_id = Config::get('workflow-constants.SAF_REASSESSMENT_ID');
            }

            if ($request->assessmentType == 3) {                                                    // Mutation
                $workflow_id = Config::get('workflow-constants.SAF_MUTATION_ID');
            }

            $ulbWorkflowId = WfWorkflow::where('wf_master_id', $workflow_id)
                ->where('ulb_id', $ulb_id)
                ->first();

            if ($request->roadType <= 0)
                $request->roadType = 4;
            elseif ($request->roadType > 0 && $request->roadType < 20)
                $request->roadType = 3;
            elseif ($request->roadType >= 20 && $request->roadType <= 39)
                $request->roadType = 2;
            elseif ($request->roadType > 40)
                $request->roadType = 1;

            $safCalculation = new SafCalculation();
            $safTaxes = $safCalculation->calculateTax($request);

            $refInitiatorRoleId = $this->getInitiatorId($ulbWorkflowId->id);                // Get Current Initiator ID
            $initiatorRoleId = DB::select($refInitiatorRoleId);
            DB::beginTransaction();
            // dd($request->ward);
            $safNo = $this->safNo($request->ward, $assessmentTypeId, $ulb_id);
            $saf = new PropActiveSaf();
            $this->tApplySaf($saf, $request, $safNo, $assessmentTypeId);                    // Trait SAF Apply
            // workflows
            $saf->user_id = $user_id;
            $saf->workflow_id = $ulbWorkflowId->id;
            $saf->ulb_id = $ulb_id;
            $saf->current_role = $initiatorRoleId[0]->role_id;
            $saf->save();

            // SAF Owner Details
            if ($request['owner']) {
                $owner_detail = $request['owner'];
                foreach ($owner_detail as $owner_details) {
                    $owner = new PropActiveSafsOwner();
                    $this->tApplySafOwner($owner, $saf, $owner_details);                    // Trait Owner Details
                    $owner->save();
                }
            }

            // Floor Details
            if ($request['floor']) {
                $floor_detail = $request['floor'];
                foreach ($floor_detail as $floor_details) {
                    $floor = new PropActiveSafsFloor();
                    $this->tApplySafFloor($floor, $saf, $floor_details);
                    $floor->save();
                }
            }

            // Property SAF Label Pendings
            $labelPending = new PropLevelPending();
            $labelPending->saf_id = $saf->id;
            $labelPending->receiver_role_id = $initiatorRoleId[0]->role_id;
            $labelPending->save();

            // Insert Tax
            $tax = new InsertTax();
            $tax->insertTax($saf->id, $user_id, $safTaxes);                                         // Insert SAF Tax

            DB::commit();
            return responseMsg(true, "Successfully Submitted Your Application Your SAF No. $safNo", ["safNo" => $safNo, "safId" => $saf->id, "Demand" => $safTaxes->original]);
        } catch (Exception $e) {
            DB::rollBack();
            return $e;
        }
    }

    /**
     * | Verify Document by Dealing Assistant
     * | @param req
     * | Verification Status (0 is for pending, 1 is for Approval, 2 for Rejection)
     */
    public function verifyDoc($req)
    {
        try {
            $verifications = $req->verifications;
            DB::beginTransaction();
            foreach ($verifications as $verification) {
                $activeSafDoc = new PropActiveSafsDoc();
                $document = $activeSafDoc->getSafDocument($verification['documentId']);             // Get Saf Document By id
                if ($verification['verifyStatus'] == 1) {
                    $document->verify_status = 1;
                    $document->save();
                }
                if ($verification['verifyStatus'] == 0) {
                    $document->verify_status = 2;
                    $document->save();
                }
            }
            DB::commit();
            return responseMsg(true, "Document Verification Successfully Done", "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Citizen or JSK Document Upload
     * | @param request $req
     */
    public function documentUpload($req)
    {
        try {
            $images = $req->uploads;
            foreach ($images as $image) {
                $document = new PropActiveSafsDoc();
                $document->saf_id = $req->safId;
                // Upload Image
                $base64Encode = base64_encode($image['docPath']->getClientOriginalName());
                $extention = $image['docPath']->getClientOriginalExtension();
                $imageName = time() . '-' . $base64Encode . '.' . $extention;
                $image['docPath']->storeAs('public/Property/SafOwnerDetails', $imageName);

                $document->doc_mstr_id = $image['docMastId'];
                $document->doc_path = $imageName;
                $document->saf_owner_dtl_id = $image['ownerDtlId'];
                $document->user_id = authUser()->id;
                $document->save();
            }
            return responseMsg(true, "Successfully Uploaded the Images", "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
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
            $userId = auth()->user()->id;
            $ulbId = auth()->user()->ulb_id;
            $wardId = $this->getWardByUserId($userId);                                  // Trait get Occupied Wards of Current User

            $occupiedWards = collect($wardId)->map(function ($ward) {
                return $ward->ward_id;
            });

            $roles = $this->getRoleIdByUserId($userId);                                 // Trait get Role By User Id

            $roleId = $roles->map(function ($item, $key) {
                return $item->wf_role_id;
            });

            $data = $this->getSaf()                                                     // Global SAF 
                ->where('prop_active_safs.ulb_id', $ulbId)
                ->where('prop_active_safs.status', 1)
                ->whereIn('current_role', $roleId)
                ->orderByDesc('id')
                ->groupBy('prop_active_safs.id', 'p.property_type', 'ward.ward_name', 'at.assessment_type')
                ->get();

            $safInbox = $data->whereIn('ward_mstr_id', $occupiedWards);

            return responseMsg(true, "Data Fetched", remove_null($safInbox->values()));
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
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
    #OutBox
    public function outbox()
    {
        try {
            $userId = auth()->user()->id;
            $ulbId = auth()->user()->ulb_id;

            $workflowRoles = $this->getRoleIdByUserId($userId);
            $roles = $workflowRoles->map(function ($value, $key) {
                return $value->wf_role_id;
            });

            $refWard = $this->getWardByUserId($userId);
            $wardId = $refWard->map(function ($value, $key) {
                return $value->ward_id;
            });

            $safData = $this->getSaf()
                ->where('prop_active_safs.ulb_id', $ulbId)
                ->whereNotIn('current_role', $roles)
                ->whereIn('ward_mstr_id', $wardId)
                ->orderByDesc('id')
                ->groupBy('prop_active_safs.id', 'p.property_type', 'ward.ward_name', 'at.assessment_type')
                ->get();
            return responseMsg(true, "Data Fetched", remove_null($safData->values()));
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
    public function details($req)
    {
        try {
            // Saf Details
            $data = [];
            $data = DB::table('prop_active_safs')
                ->select('prop_active_safs.*', 'at.assessment_type as assessment', 'w.ward_name as old_ward_no', 'w.ward_name as new_ward_no', 'o.ownership_type', 'p.property_type')
                ->join('ulb_ward_masters as w', 'w.id', '=', 'prop_active_safs.ward_mstr_id')
                ->leftJoin('ulb_ward_masters as nw', 'nw.id', '=', 'prop_active_safs.new_ward_mstr_id')
                ->join('ref_prop_ownership_types as o', 'o.id', '=', 'prop_active_safs.ownership_type_mstr_id')
                ->leftJoin('prop_ref_assessment_types as at', 'at.id', '=', 'prop_active_safs.assessment_type')
                ->leftJoin('ref_prop_types as p', 'p.id', '=', 'prop_active_safs.property_assessment_id')
                ->where('prop_active_safs.id', $req->id)
                ->first();
            $data = json_decode(json_encode($data), true);
            $ownerDetails = PropActiveSafsOwner::where('saf_id', $data['id'])->get();
            $data['owners'] = $ownerDetails;

            $floorDetails = DB::table('prop_active_safs_floors')
                ->select('prop_active_safs_floors.*', 'f.floor_name', 'u.usage_type', 'o.occupancy_type', 'c.construction_type')
                ->join('ref_prop_floors as f', 'f.id', '=', 'prop_active_safs_floors.floor_mstr_id')
                ->join('ref_prop_usage_types as u', 'u.id', '=', 'prop_active_safs_floors.usage_type_mstr_id')
                ->join('ref_prop_occupancy_types as o', 'o.id', '=', 'prop_active_safs_floors.occupancy_type_mstr_id')
                ->join('ref_prop_construction_types as c', 'c.id', '=', 'prop_active_safs_floors.const_type_mstr_id')
                ->where('saf_id', $data['id'])
                ->get();
            $data['floors'] = $floorDetails;

            $levelComments = DB::table('prop_level_pendings')
                ->select(
                    'prop_level_pendings.receiver_role_id as commentedByRoleId',
                    'r.role_name as commentedByRoleName',
                    'prop_level_pendings.remarks',
                    'prop_level_pendings.verification_status'
                )
                ->where('prop_level_pendings.saf_id', $data['id'])
                ->where('status', 1)
                ->leftJoin('wf_roles as r', 'r.id', '=', 'prop_level_pendings.receiver_role_id')
                ->get();
            $data['levelComments'] = $levelComments;

            $citizenComment = DB::table('workflow_tracks')
                ->select(
                    'workflow_tracks.ref_table_dot_id',
                    'workflow_tracks.message',
                    'workflow_tracks.track_date',
                    'u.email as citizenEmail',
                    'u.user_name as citizenName'
                )
                ->where('ref_table_id_value', $data['id'])
                ->join('users as u', 'u.id', '=', 'workflow_tracks.commented_by')
                ->get();

            $data['citizenComment'] = $citizenComment;

            return responseMsg(true, 'Data Fetched', remove_null($data));
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
    #Add Inbox  special category
    public function postEscalate($request)
    {
        DB::beginTransaction();
        try {
            $userId = auth()->user()->id;
            // Validation Rule
            $rules = [
                "escalateStatus" => "required|int",
                "safId" => "required",
            ];
            // Validation Message
            $message = [
                "escalateStatus.required" => "Escalate Status Is Required",
                "safId.required" => "Saf Id Is Required",
            ];
            $validator = Validator::make($request->all(), $rules, $message);
            if ($validator->fails()) {
                return responseMsg(false, $validator->errors(), $request->all());
            }

            $saf_id = $request->safId;
            $data = PropActiveSaf::find($saf_id);
            $data->is_escalate = $request->escalateStatus;
            $data->escalate_by = $userId;
            $data->save();
            DB::commit();
            return responseMsg(true, $request->escalateStatus == 1 ? 'Saf is Escalated' : "Saf is removed from Escalated", '');
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), $request->all());
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
    #Inbox  special category
    public function specialInbox()
    {
        try {
            $userId = auth()->user()->id;
            $ulbId = auth()->user()->ulb_id;
            $occupiedWard = $this->getWardByUserId($userId);                        // Get All Occupied Ward By user id using trait
            $wardId = $occupiedWard->map(function ($item, $key) {                   // Filter All ward_id in an array using laravel collections
                return $item->ward_id;
            });
            $safData = $this->getSaf()
                ->where('is_escalate', 1)
                ->where('prop_active_safs.ulb_id', $ulbId)
                ->whereIn('ward_mstr_id', $wardId)
                ->groupBy('prop_active_safs.id', 'prop_active_safs.saf_no', 'ward.ward_name', 'p.property_type', 'at.assessment_type')
                ->get();
            return responseMsg(true, "Data Fetched", remove_null($safData));
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Post Independent Comment
     * | @param mixed $request
     * | @var userId Logged In user Id
     * | @var levelPending The Level Pending Data of the Saf Id
     * | @return responseMsg
     * | Status-Closed
     * | Query Costing-427ms 
     * | Rating-2
     */
    public function commentIndependent($request)
    {
        try {
            DB::beginTransaction();
            $request->validate([
                'comment' => 'required',
                'safId' => 'required'
            ]);
            $userId = auth()->user()->id;
            $levelPending = PropLevelPending::where('saf_id', $request->safId)
                ->where('receiver_user_id', $userId)
                ->first();

            if (is_null($levelPending)) {
                $levelPending = PropLevelPending::where('saf_id', $request->safId)
                    ->orderByDesc('id')
                    ->limit(1)
                    ->first();
                if (is_null($levelPending)) {
                    return responseMsg(false, "SAF Not Found", "");
                }
            }
            $levelPending->remarks = $request->comment;
            $levelPending->receiver_user_id = $userId;
            $levelPending->save();

            // SAF Details
            $saf = PropActiveSaf::find($request->safId);

            // Save On Workflow Track
            $workflowTrack = new WorkflowTrack();
            $workflowTrack->workflow_id = Config::get('workflow-constants.SAF_WORKFLOW_ID');
            $workflowTrack->citizen_id = $saf->user_id;
            $workflowTrack->module_id = Config::get('module-constants.PROPERTY_MODULE_ID');
            $workflowTrack->ref_table_dot_id = "active_safs.id";
            $workflowTrack->ref_table_id_value = $saf->id;
            $workflowTrack->message = $request->comment;
            $workflowTrack->commented_by = $userId;
            $workflowTrack->save();

            DB::commit();
            return responseMsg(true, "You Have Commented Successfully!!", ['Comment' => $request->comment]);
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
    # postNextLevel
    public function postNextLevel($request)
    {
        DB::beginTransaction();
        try {
            // previous level pending verification enabling
            $propLevelPending = new PropLevelPending();
            $preLevelPending = $propLevelPending->getLevelBySafReceiver($request->safId, $request->receiverRoleId);
            $preLevelPending->verification_status = '1';
            $preLevelPending->save();

            $levelPending = new PropLevelPending();
            $levelPending->saf_id = $request->safId;
            $levelPending->sender_role_id = $request->senderRoleId;
            $levelPending->receiver_role_id = $request->receiverRoleId;
            $levelPending->sender_user_id = auth()->user()->id;
            $levelPending->save();

            // SAF Application Update Current Role Updation
            $saf = PropActiveSaf::find($request->safId);
            $saf->current_role = $request->receiverRoleId;
            $saf->save();

            // Add Comment On Prop Level Pending
            $commentOnlevel = PropLevelPending::where('saf_id', $request->safId)
                ->where('receiver_role_id', $request->senderRoleId)
                ->first();
            $commentOnlevel->remarks = $request->comment;
            $commentOnlevel->save();

            DB::commit();
            return responseMsg(true, "Successfully Forwarded The Application!!", "");
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
    public function approvalRejectionSaf($req)
    {
        try {
            // Check if the Current User is Finisher or Not
            $getFinisherQuery = $this->getFinisherId($req->workflowId);                                 // Get Finisher using Trait
            $refGetFinisher = collect(DB::select($getFinisherQuery))->first();
            if ($refGetFinisher->role_id != $req->roleId) {
                return responseMsg(false, "Forbidden Access", "");
            }

            DB::beginTransaction();
            // Approval
            if ($req->status == 1) {
                $safDetails = PropActiveSaf::find($req->safId);
                if ($req->assessmentType == 2)
                    $safDetails->holding_no = $safDetails->previous_holding_id;
                if ($req->assessmentType != 2) {
                    $safDetails->holding_no = 'HOL-SAF-' . $req->safId;
                }

                $safDetails->fam_no = 'FAM/002/00001';
                $safDetails->saf_pending_status = 0;
                $safDetails->save();

                // SAF Application replication
                $activeSaf = PropActiveSaf::query()
                    ->where('id', $req->safId)
                    ->first();
                $ownerDetails = PropActiveSafsOwner::query()
                    ->where('saf_id', $req->safId)
                    ->get();
                $floorDetails = PropActiveSafsFloor::query()
                    ->where('saf_id', $req->safId)
                    ->get();

                $toBeProperties = PropActiveSaf::query()
                    ->where('id', $req->safId)
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
                $activeSaf = PropActiveSaf::query()
                    ->where('id', $req->safId)
                    ->first();

                $ownerDetails = PropActiveSafsOwner::query()
                    ->where('saf_id', $req->safId)
                    ->get();

                $floorDetails = PropActiveSafsFloor::query()
                    ->where('saf_id', $req->safId)
                    ->get();

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

            DB::commit();
            return responseMsg(true, $msg, "");
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
    public function backToCitizen($req)
    {
        try {
            $redis = Redis::connection();
            $workflowId = $req->workflowId;
            $backId = json_decode(Redis::get('workflow_initiator_' . $workflowId));
            if (!$backId) {
                $backId = WfWorkflowrolemap::where('workflow_id', $workflowId)
                    ->where('is_initiator', true)
                    ->first();
                $redis->set('workflow_initiator_' . $workflowId, json_encode($backId));
            }
            DB::beginTransaction();
            $saf = PropActiveSaf::find($req->safId);
            $saf->current_role = $backId->wf_role_id;
            $saf->save();

            $propLevelPending = new PropLevelPending();
            $preLevelPending = $propLevelPending->getLevelBySafReceiver($req->safId, $req->currentRoleId);
            $preLevelPending->remarks = $req->comment;
            $preLevelPending->save();

            $levelPending = new PropLevelPending();
            $levelPending->saf_id = $req->safId;
            $levelPending->sender_role_id = $req->currentRoleId;
            $levelPending->receiver_role_id = $backId->wf_role_id;
            $levelPending->user_id = authUser()->id;
            $levelPending->sender_user_id = authUser()->id;
            $levelPending->save();

            DB::commit();
            return responseMsg(true, "Successfully Done", "");
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
    public function calculateSafBySafId($req)
    {
        $safDetails = $this->details($req);
        $req = $safDetails->original['data'];
        $array = $this->generateSafRequest($req);                                                                       // Generate SAF Request by SAF Id Using Trait
        $safCalculation = new SafCalculation();
        $request = new Request($array);
        $safTaxes = $safCalculation->calculateTax($request);
        return $safTaxes;
    }

    /**
     * | Generate Order ID 
     * | @param req requested Data
     * | @var auth authenticated users credentials
     * | @var calculateSafById calculated SAF amounts and details by request SAF ID
     * | @var totalAmount filtered total amount from the collection
     * | Status-closed
     * | Query Costing-1.41s
     * | Rating - 5
     */

    public function generateOrderId($req)
    {
        try {
            $auth = auth()->user();
            $safRepo = new SafRepository();
            $calculateSafById = $safRepo->calculateSafBySafId($req);
            $totalAmount = $calculateSafById->original['data']['demand']['payableAmount'];
            // Check Requested amount is matching with the generated amount or not
            if ($req->amount == $totalAmount) {
                $orderDetails = $this->saveGenerateOrderid($req);
                $orderDetails['name'] = $auth->user_name;
                $orderDetails['mobile'] = $auth->mobile;
                $orderDetails['email'] = $auth->email;
                return responseMsg(true, "Order ID Generated", remove_null($orderDetails));
            }

            return responseMsg(false, "Amount Not Matched", "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | SAF Payment
     * | @param req  
     * | @var workflowId SAF workflow ID
     * | Status-Closed
     * | Query Consting-374ms
     * | Rating-1
     */

    public function paymentSaf($req)
    {
        try {
            $userId = auth()->user()->id;
            $propTrans = new PropTransaction();
            $workflowId = Config::get('workflow-constants.SAF_WORKFLOW_ID');
            if ($req['workflowId'] == $workflowId)
                $propTrans->saf_id = $req['id'];
            else
                $propTrans->property_id = $req['id'];
            $propTrans->amount = $req['amount'];
            $propTrans->tran_date = Carbon::now()->format('Y-m-d');
            $propTrans->tran_no = $req['transactionNo'];
            $propTrans->payment_mode = $req['paymentMode'];
            $propTrans->save();
            Redis::del('property-transactions-user-' . $userId);
            return responseMsg(true, "Payment Successfully Done", "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
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
    public function getPropTransactions($req)
    {
        $userId = auth()->user()->id;

        $propTrans = json_decode(Redis::get('property-transactions-user-' . $userId));                      // Should Be Deleted SAF Payment
        if (!$propTrans) {
            $propTrans = DB::table('prop_transactions')
                ->select('prop_transactions.*', 'a.saf_no', 'p.holding_no')
                ->leftJoin('prop_active_safs as a', 'a.id', '=', 'prop_transactions.saf_id')
                ->leftJoin('prop_properties as p', 'p.id', '=', 'prop_transactions.property_id')
                ->where('prop_transactions.user_id', $userId)
                ->where('prop_transactions.status', 1)
                ->get();
            $this->_redis->set('property-transactions-user-' . $userId, json_encode($propTrans));
        }
        return responseMsg(true, "Transactions History", remove_null($propTrans));
    }

    /**
     * | Get Property Details by Property Holding No
     * | Rating - 2
     * | Run Time Complexity-500 ms
     */
    public function getPropByHoldingNo($req)
    {
        try {
            $propertyDtl = [];
            $properties = DB::table('prop_properties')
                ->select('s.*', 'at.assessment_type as assessment', 'w.ward_name as old_ward_no', 'o.ownership_type', 'p.property_type')
                ->join('prop_safs as s', 's.id', '=', 'prop_properties.saf_id')
                ->join('ulb_ward_masters as w', 'w.id', '=', 's.ward_mstr_id')
                ->leftJoin('ulb_ward_masters as nw', 'nw.id', '=', 's.new_ward_mstr_id')
                ->join('ref_prop_ownership_types as o', 'o.id', '=', 's.ownership_type_mstr_id')
                ->leftJoin('prop_ref_assessment_types as at', 'at.id', '=', 's.assessment_type')
                ->leftJoin('ref_prop_types as p', 'p.id', '=', 's.property_assessment_id')
                ->where('prop_properties.ward_mstr_id', $req->wardId)
                ->where('prop_properties.holding_no', $req->holdingNo)
                ->where('prop_properties.status', 1)
                ->first();

            $floors = DB::table('prop_floors')
                ->select('prop_floors.*', 'f.floor_name', 'u.usage_type', 'o.occupancy_type', 'c.construction_type')
                ->join('ref_prop_floors as f', 'f.id', '=', 'prop_floors.floor_mstr_id')
                ->join('ref_prop_usage_types as u', 'u.id', '=', 'prop_floors.usage_type_mstr_id')
                ->join('ref_prop_occupancy_types as o', 'o.id', '=', 'prop_floors.occupancy_type_mstr_id')
                ->join('ref_prop_construction_types as c', 'c.id', '=', 'prop_floors.const_type_mstr_id')
                ->where('property_id', $properties->property_id)
                ->get();

            $owners = DB::table('prop_owners')
                ->where('property_id', $properties->property_id)
                ->get();

            $propertyDtl = collect($properties);
            $propertyDtl['floors'] = $floors;
            $propertyDtl['owners'] = $owners;

            return responseMsg(true, "Property Details", remove_null($propertyDtl));
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Site Verification
     * | @param req requested parameter
     */
    public function siteVerification($req)
    {
        try {
            $taxCollectorRole = Config::get('PropertyConstaint.SAF-LABEL.TC');
            $ulbTaxCollectorRole = Config::get('PropertyConstaint.SAF-LABEL.UTC');
            $verificationStatus = $req->verificationStatus;                                             // Verification Status true or false

            $verification = new PropSafVerification();

            switch ($req->currentRoleId) {
                case $taxCollectorRole;                                                                  // In Case of Agency TAX Collector
                    if ($verificationStatus == 1) {
                        $verification->agency_verification = true;
                        $msg = "Site Successfully Verified";
                    }
                    if ($verificationStatus == 0) {
                        $verification->agency_verification = false;
                        $msg = "Site Successfully rebuted";
                    }
                    break;

                case $ulbTaxCollectorRole;                                                                // In Case of Ulb Tax Collector
                    if ($verificationStatus == 1) {
                        $verification->ulb_verification = true;
                        $msg = "Site Successfully Verified";
                    }
                    if ($verificationStatus == 0) {
                        $verification->ulb_verification = false;
                        $msg = "Site Successfully rebuted";
                    }
                    break;

                default:
                    return responseMsg(false, "Forbidden Access", "");
            }

            // Verification Store
            DB::beginTransaction();
            $verification->saf_id = $req->safId;
            $verification->prop_type_id = $req->propertyType;
            $verification->road_type_id = $req->roadTypeId;
            $verification->area_of_plot = $req->areaOfPlot;
            $verification->ward_id = $req->wardId;
            $verification->has_mobile_tower = $req->isMobileTower;
            $verification->tower_area = $req->mobileTowerArea;
            $verification->tower_installation_date = $req->mobileTowerDate;
            $verification->has_hoarding = $req->isHoardingBoard;
            $verification->hoarding_area = $req->hoardingArea;
            $verification->hoarding_installation_date = $req->hoardingDate;
            $verification->is_petrol_pump = $req->isPetrolPump;
            $verification->underground_area = $req->petrolPumpUndergroundArea;
            $verification->petrol_pump_completion_date = $req->petrolPumpDate;
            $verification->has_water_harvesting = $req->isHarvesting;
            $verification->zone_id = $req->zone;
            $verification->user_id = $req->userId;
            $verification->save();

            // Verification Dtl Table Update                                         // For Tax Collector
            foreach ($req->floorDetails as $floorDetail) {
                $verificationDtl = new PropSafVerificationDtl();
                $verificationDtl->verification_id = $verification->id;
                $verificationDtl->saf_id = $req->safId;
                $verificationDtl->saf_floor_id = $floorDetail['floorId'];
                $verificationDtl->floor_mstr_id = $floorDetail['floorMstrId'];
                $verificationDtl->usage_type_id = $floorDetail['usageType'];
                $verificationDtl->construction_type_id = $floorDetail['constructionType'];
                $verificationDtl->occupancy_type_id = $floorDetail['occupancyType'];
                $verificationDtl->builtup_area = $floorDetail['builtupArea'];
                $verificationDtl->date_from = $floorDetail['fromDate'];
                $verificationDtl->date_to = $floorDetail['toDate'];
                $verificationDtl->carpet_area = $floorDetail['carpetArea'];
                $verificationDtl->save();
            }

            DB::commit();
            return responseMsg(true, $msg, "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Geo Tagging Photo Uploads
     * | @param request req
     */
    public function geoTagging($req)
    {
        try {
            foreach ($req->uploads as $upload) {
                $geoTagging = new PropSafGeotagUpload();
                $geoTagging->saf_id = $req->safId;
                $geoTagging->latitude = $upload['latitude'];
                $geoTagging->longitude = $upload['longitude'];
                $geoTagging->direction_type = $upload['directionType'];
                $geoTagging->user_id = authUser()->id;

                // Upload Image
                $base64Encode = base64_encode($upload['imagePath']->getClientOriginalName());
                $extention = $upload['imagePath']->getClientOriginalExtension();
                $imageName = time() . '-' . $base64Encode . '.' . $extention;
                $upload['imagePath']->storeAs('public/Property/GeoTagging', $imageName);

                $geoTagging->image_path = $imageName;
                $geoTagging->save();
            }
            return responseMsg(true, "Geo Tagging Done Successfully", "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }
}
