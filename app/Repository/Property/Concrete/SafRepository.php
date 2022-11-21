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
    /**
     * | Master data in Saf Apply
     * | @var ulbId Logged In User Ulb 
     * | Status-Closed
     */
    public function masterSaf()
    {
        $ulbId = auth()->user()->ulb_id;
        $wardMaster = UlbWardMaster::select('id', 'ward_name')
            ->where('ulb_id', $ulbId)
            ->get();
        $data = [];
        $data['ward_master'] = $wardMaster;
        $ownershipTypes = RefPropOwnershipType::select('id', 'ownership_type')
            ->where('status', 1)
            ->get();
        $data['ownership_types'] = $ownershipTypes;
        $propertyType = RefPropType::select('id', 'property_type')
            ->where('status', 1)
            ->get();
        $data['property_type'] = $propertyType;
        $floorType = RefPropFloor::select('id', 'floor_name')
            ->where('status', 1)
            ->get();
        $data['floor_type'] = $floorType;
        $usageType = RefPropUsageType::select('id', 'usage_type', 'usage_code')
            ->where('status', 1)
            ->get();
        $data['usage_type'] = $usageType;
        $occupancyType = RefPropOccupancyType::select('id', 'occupancy_type')
            ->where('status', 1)
            ->get();
        $data['occupancy_type'] = $occupancyType;
        $constructionType = RefPropConstructionType::select('id', "construction_type")
            ->where('status', 1)
            ->get();
        $data['construction_type'] = $constructionType;

        $transferModuleType = RefPropTransferMode::select('id', 'transfer_mode')
            ->where('status', 1)
            ->get();
        $data['transfer_mode'] = $transferModuleType;
        return  responseMsg(true, '', $data);
    }

    /**
     * | Apply for New Application
     * | Status-Closed
     */

    public function applySaf(Request $request)
    {
        $user_id = auth()->user()->id;
        $ulb_id = auth()->user()->ulb_id;

        try {
            if ($request->assessmentType == 1) {                                                    // New Assessment 
                $assessmentTypeId = Config::get("PropertyConstaint.ASSESSMENT-TYPE.NewAssessment");
                $workflow_id = Config::get('workflow-constants.SAF_WORKFLOW_ID');
            }

            if ($request->assessmentType == 2) {                                                    // Reassessment
                $assessmentTypeId = Config::get("PropertyConstaint.ASSESSMENT-TYPE.ReAssessment");
                $workflow_id = Config::get('workflow-constants.SAF_REASSESSMENT_ID');
            }

            if ($request->assessmentType == 3) {                                                    // Mutation
                $assessmentTypeId = Config::get("PropertyConstaint.ASSESSMENT-TYPE.Mutation");
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
            return responseMsg(true, "Successfully Submitted Your Application Your SAF No. $safNo", ["safNo" => $safNo]);
        } catch (Exception $e) {
            DB::rollBack();
            return $e;
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
                ->groupBy('prop_active_safs.id', 'p.property_type', 'ward.ward_name')
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
                ->groupBy('prop_active_safs.id', 'p.property_type', 'ward.ward_name')
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
     */
    #Saf Details
    public function details(Request $req)
    {
        $req->validate([
            'id' => 'required|integer'
        ]);
        try {
            // Saf Details
            $data = [];
            $data = DB::table('prop_active_safs')
                ->select('prop_active_safs.*', 'w.ward_name as old_ward_no', 'o.ownership_type', 'p.property_type')
                ->join('ulb_ward_masters as w', 'w.id', '=', 'prop_active_safs.ward_mstr_id')
                ->join('ref_prop_ownership_types as o', 'o.id', '=', 'prop_active_safs.ownership_type_mstr_id')
                ->leftJoin('ref_prop_types as p', 'p.id', '=', 'prop_active_safs.property_assessment_id')
                ->where('prop_active_safs.id', $req->id)
                ->first();
            $data = json_decode(json_encode($data), true);
            $ownerDetails = PropActiveSafsOwner::where('saf_id', $data['id'])->get();
            $data['owners'] = $ownerDetails;

            $floorDetails = PropActiveSafsFloor::where('saf_id', $data['id'])->get();
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
                ->groupBy('prop_active_safs.id', 'prop_active_safs.saf_no', 'ward.ward_name', 'p.property_type')
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
     */
    # postNextLevel
    public function postNextLevel($request)
    {
        DB::beginTransaction();
        try {
            // previous level pending verification enabling
            $preLevelPending = PropLevelPending::where('saf_id', $request->safId)
                ->orderByDesc('id')
                ->limit(1)
                ->first();
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
                    $safDetails->holding_no = 'Hol/Ward/001';
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

                $approvedSaf = $activeSaf->replicate();
                $approvedSaf->setTable('prop_safs');
                $approvedSaf->id = $activeSaf->id;
                $approvedSaf->save();
                $activeSaf->delete();

                // SAF Owners replication

                foreach ($ownerDetails as $ownerDetail) {
                    $approvedOwner = $ownerDetail->replicate();
                    $approvedOwner->setTable('prop_safs_owners');
                    $approvedOwner->id = $ownerDetail->id;
                    $approvedOwner->save();
                    $ownerDetail->delete();
                }

                // SAF Floors Replication

                foreach ($floorDetails as $floorDetail) {
                    $approvedFloor = $floorDetail->replicate();
                    $approvedFloor->setTable('prop_safs_floors');
                    $approvedFloor->id = $floorDetail->id;
                    $approvedFloor->save();
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
            $saf = PropActiveSaf::find($req->safId);
            $saf->current_role = $backId->wf_role_id;
            $saf->save();
            return responseMsg(true, "Successfully Done", "");
        } catch (Exception $e) {
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
     */
    public function calculateSafBySafId($req)
    {
        $safDetails = $this->details($req);
        $req = $safDetails->original['data'];
        $array = $this->generateSafRequest($req);                                                       // Generate SAF Request Using Trait
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
     */

    public function paymentSaf($req)
    {
        try {
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
     */
    public function getPropTransactions($req)
    {
        $userId = auth()->user()->id;

        $propTrans = DB::table('prop_transactions')
            ->select('prop_transactions.*', 'a.saf_no', 'p.holding_no')
            ->leftJoin('prop_active_safs as a', 'a.id', '=', 'prop_transactions.saf_id')
            ->leftJoin('prop_properties as p', 'p.id', '=', 'prop_transactions.property_id')
            ->where('prop_transactions.user_id', $userId)
            ->where('prop_transactions.status', 1)
            ->get();
        return responseMsg(true, "Transactions History", remove_null($propTrans));
    }
}
