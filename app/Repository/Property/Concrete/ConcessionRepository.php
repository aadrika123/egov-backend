<?php

namespace App\Repository\Property\Concrete;

use App\Repository\Property\Interfaces\iConcessionRepository;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use App\Models\Property\PropProperty;
use App\Models\Property\PropActiveConcession;
use App\Models\Property\PropConcessionLevelPending;
use App\Traits\Workflow\Workflow as WorkflowTrait;
use Illuminate\Support\Facades\DB;
use App\Models\Workflows\WfWorkflow;
use App\Models\Workflows\WfWorkflowrolemap;
use App\Traits\Property\Concession;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redis;
use App\Models\Property\PropConcessionDocDtl;

class ConcessionRepository implements iConcessionRepository
{

    //wf_master_id = 35;
    //workflow_id = 106;
    use WorkflowTrait;
    use Concession;

    private $_todayDate;

    public function __construct()
    {
        $this->_todayDate = Carbon::now();
    }
    //apply concession
    /**
     * | Query Costing-382ms 
     * | Rating-3
     * | Status-Closed
     */
    public function applyConcession($request)
    {
        try {
            $userId = auth()->user()->id;
            $ulbId = auth()->user()->ulb_id;
            $userType = auth()->user()->user_type;
            $concessionNo = "";

            if ($userType == "JSK") {
                $obj  = new SafRepository();
                $data = $obj->getPropByHoldingNo($request);
            }

            DB::beginTransaction();
            $workflow_id = Config::get('workflow-constants.PROPERTY_CONCESSION_ID');
            $concession = new PropActiveConcession;
            $concession->property_id = $request->propId;
            $concession->application_no = $request->applicationNo;
            $concession->applicant_name = $request->applicantName;
            $concession->gender = $request->gender;
            $concession->dob = $request->dob;
            $concession->is_armed_force = $request->armedForce;
            $concession->is_specially_abled = $request->speciallyAbled;
            $concession->remarks = $request->remarks;
            $concession->status = 1;
            $concession->user_id = $userId;
            $concession->ulb_id = $ulbId;


            $ulbWorkflowId = WfWorkflow::where('wf_master_id', $workflow_id)
                ->where('ulb_id', $ulbId)
                ->first();

            $refInitiatorRoleId = $this->getInitiatorId($ulbWorkflowId->id);                // Get Current Initiator ID
            $initiatorRoleId = DB::select($refInitiatorRoleId);

            $concession->workflow_id = $ulbWorkflowId->id;
            $concession->current_role = collect($initiatorRoleId)->first()->role_id;
            $concession->created_at = Carbon::now();
            $concession->date = Carbon::now();
            $concession->save();

            $concessionNo = $this->concessionNo($concession->id);
            PropActiveConcession::where('id', $concession->id)
                ->update(['application_no' => $concessionNo]);

            //saving document in concession doc table
            if ($file = $request->file('genderDoc')) {

                $name = time() . 'gender.' . $file->getClientOriginalExtension();
                $path = public_path('concession/genderDoc');
                $file->move($path, $name);

                $concessionDoc = new PropConcessionDocDtl();
                $concessionDoc->concession_id = $concession->id;
                $concessionDoc->doc_type = 'genderDoc';
                $concessionDoc->doc_name = $name;
                $concessionDoc->status = 1;
                $concessionDoc->date = Carbon::now();
                $concessionDoc->created_at = Carbon::now();
                $concessionDoc->save();
            }

            // dob Doc
            if ($file = $request->file('dobDoc')) {

                $name = time() . 'dob.' . $file->getClientOriginalExtension();
                $path = public_path('concession/dobDoc');
                $file->move($path, $name);

                $concessionDoc = new PropConcessionDocDtl();
                $concessionDoc->concession_id = $concession->id;
                $concessionDoc->doc_type = 'dobDoc';
                $concessionDoc->doc_name = $name;
                $concessionDoc->status = 1;
                $concessionDoc->date = Carbon::now();
                $concessionDoc->created_at = Carbon::now();
                $concessionDoc->save();
            }

            // specially abled Doc
            if ($file = $request->file('speciallyAbledDoc')) {

                $name = time() . 'speciallabled.' . $file->getClientOriginalExtension();
                $path = public_path('concession/speciallyAbledDoc');
                $file->move($path, $name);

                $concessionDoc = new PropConcessionDocDtl();
                $concessionDoc->concession_id = $concession->id;
                $concessionDoc->doc_type = 'speciallyAbledDoc';
                $concessionDoc->doc_name = $name;
                $concessionDoc->status = 1;
                $concessionDoc->date = Carbon::now();
                $concessionDoc->created_at = Carbon::now();
                $concessionDoc->save();
            }

            // Armed force Doc
            if ($file = $request->file('armedForceDoc')) {

                $name = time() . 'armedforce.' . $file->getClientOriginalExtension();
                $path = public_path('concession/armedForceDoc');
                $file->move($path, $name);

                $concessionDoc = new PropConcessionDocDtl();
                $concessionDoc->concession_id = $concession->id;
                $concessionDoc->doc_type = 'armedForceDoc';
                $concessionDoc->doc_name = $name;
                $concessionDoc->status = 1;
                $concessionDoc->date = Carbon::now();
                $concessionDoc->created_at = Carbon::now();
                $concessionDoc->save();
            }


            // Property SAF Label Pendings
            $labelPending = new PropConcessionLevelpending();
            $labelPending->concession_id = $concession->id;
            $labelPending->receiver_role_id = $initiatorRoleId[0]->role_id;
            $labelPending->save();

            DB::commit();
            return responseMsg(true, 'Successfully Applied The Application', $concessionNo);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    //get owner details
    public function postHolding(Request $request)
    {
        try {
            $user = PropProperty::where('holding_no', $request->holdingNo)
                ->get();
            if (!empty($user['0'])) {
                return responseMsg(true, 'True', $user);
            }
            return responseMsg(false, "False", "");
            // return $user['0'];
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Property Concession Inbox List
     * | @var auth autheticated user data
     * | Query Costing-293ms 
     * | Rating-3
     * | Status-Closed
     */
    public function inbox()
    {
        try {
            $auth = auth()->user();
            $userId = $auth->id;
            $ulbId = $auth->ulb_id;
            $wardId = $this->getWardByUserId($userId);

            $occupiedWards = collect($wardId)->map(function ($ward) {                               // Get Occupied Ward of the User
                return $ward->ward_id;
            });

            $roles = $this->getRoleIdByUserId($userId);

            $roleId = collect($roles)->map(function ($role) {                                       // get Roles of the user
                return $role->wf_role_id;
            });

            $concessions = $this->getConcessionList($ulbId)
                ->whereIn('prop_active_concessions.current_role', $roleId)
                ->whereIn('a.ward_mstr_id', $occupiedWards)
                ->orderByDesc('prop_active_concessions.id')
                ->get();
            return responseMsg(true, "Inbox List", remove_null($concessions));
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Outbox List
     * | @var auth authenticated user list
     * | @var ulbId authenticated user ulb
     * | @var userid authenticated user id
     * | Query Costing-309 
     * | Rating-3
     * | Status-Closed
     */
    public function outbox()
    {
        try {
            $auth = auth()->user();
            $userId = $auth->id;
            $ulbId = $auth->ulb_id;

            $workflowRoles = $this->getRoleIdByUserId($userId);
            $roleId = $workflowRoles->map(function ($value, $key) {                         // Get user Workflow Roles
                return $value->wf_role_id;
            });

            $refWard = $this->getWardByUserId($userId);                                     // Get Ward List by user Id
            $occupiedWards = $refWard->map(function ($value, $key) {
                return $value->ward_id;
            });

            $concessions = $this->getConcessionList($ulbId)
                ->whereNotIn('prop_active_concessions.current_role', $roleId)
                ->whereIn('a.ward_mstr_id', $occupiedWards)
                ->orderByDesc('prop_active_concessions.id')
                ->get();

            return responseMsg(true, "Outbox List", remove_null($concessions));
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Get Concession Details by Concession ID
     * | Query Costing-320 ms 
     * | Rating-3
     * | Status-Closed
     */
    public function getDetailsById($req)
    {
        try {
            $details = array();
            $details = DB::table('prop_active_concessions')
                ->select(
                    'prop_active_concessions.*',
                    'prop_active_concessions.applicant_name as owner_name',
                    's.holding_no',
                    's.ward_mstr_id',
                    'u.ward_name as ward_no',
                    's.prop_type_mstr_id',
                    'p.property_type'
                )
                ->join('prop_properties as s', 's.id', '=', 'prop_active_concessions.property_id')
                ->join('ulb_ward_masters as u', 'u.id', '=', 's.ward_mstr_id')
                ->join('ref_prop_types as p', 'p.id', '=', 's.prop_type_mstr_id')
                ->where('prop_active_concessions.id', $req->id)
                ->first();
            $details = json_decode(json_encode($details), true);

            $levelComments = DB::table('prop_concession_levelpendings')
                ->select(
                    'prop_concession_levelpendings.id',
                    'prop_concession_levelpendings.receiver_role_id as commentedByRoleId',
                    'r.role_name as commentedByRoleName',
                    'prop_concession_levelpendings.remarks',
                    'prop_concession_levelpendings.forward_date',
                    'prop_concession_levelpendings.forward_time',
                    'prop_concession_levelpendings.verification_status',
                    'prop_concession_levelpendings.created_at as received_at'
                )
                ->where('prop_concession_levelpendings.concession_id', $req->id)
                ->where('prop_concession_levelpendings.status', 1)
                ->leftJoin('wf_roles as r', 'r.id', '=', 'prop_concession_levelpendings.receiver_role_id')
                ->orderByDesc('prop_concession_levelpendings.id')
                ->get();

            $details['levelComments'] = $levelComments;

            return responseMsg(true, "Concession Details", remove_null($details));
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Escalate application
     * | @param req request parameters
     * | Query Costing-400ms 
     * | Rating-2
     * | Status-Closed
     */
    public function escalateApplication($req)
    {
        try {
            $userId = auth()->user()->id;
            if ($req->escalateStatus == 1) {
                $concession = PropActiveConcession::find($req->id);
                $concession->is_escalate = 1;
                $concession->escalated_by = $userId;
                $concession->save();
                return responseMsg(true, "Successfully Escalated the application", "");
            }
            if ($req->escalateStatus == 0) {
                $concession = PropActiveConcession::find($req->id);
                $concession->is_escalate = 0;
                $concession->escalated_by = null;
                $concession->save();
                return responseMsg(true, "Successfully De-Escalated the application", "");
            }
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Special Inbox (Escalated Applications)
     * | Query Costing-303 ms 
     * | Rating-2
     * | Status-Closed
     */
    public function specialInbox()
    {
        try {
            $auth = auth()->user();
            $userId = $auth->id;
            $ulbId = $auth->ulb_id;
            $wardId = $this->getWardByUserId($userId);

            $occupiedWards = collect($wardId)->map(function ($ward) {                               // Get Occupied Ward of the User
                return $ward->ward_id;
            });

            $concessions = $this->getConcessionList($ulbId)                                         // Get Concessions
                ->where('prop_active_concessions.is_escalate', true)
                ->whereIn('a.ward_mstr_id', $occupiedWards)
                ->orderByDesc('prop_active_concessions.id')
                ->get();

            return responseMsg(true, "Inbox List", remove_null($concessions));
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Post Next Level Application i.e. forward or backward application
     * | Query Costing-355ms 
     * | Rating-2
     * | Status-Closed
     */
    public function postNextLevel($req)
    {
        try {
            DB::beginTransaction();

            // previous level pending verification enabling
            $preLevelPending = PropConcessionLevelpending::where('concession_id', $req->concessionId)
                ->orderByDesc('id')
                ->limit(1)
                ->first();
            $preLevelPending->verification_status = '1';
            $preLevelPending->save();

            $levelPending = new PropConcessionLevelpending();
            $levelPending->concession_id = $req->concessionId;
            $levelPending->sender_role_id = $req->senderRoleId;
            $levelPending->receiver_role_id = $req->receiverRoleId;
            $levelPending->sender_user_id = auth()->user()->id;
            $levelPending->save();

            // Concession Application Update Current Role Updation
            $concession = PropActiveConcession::find($req->concessionId);
            $concession->current_role = $req->receiverRoleId;
            $concession->save();

            // Add Comment On Prop Level Pending
            $receiverLevelPending = new PropConcessionLevelPending();
            $commentOnlevel = $receiverLevelPending->getReceiverLevel($req->concessionId, $req->senderRoleId);
            $commentOnlevel->remarks = $req->comment;
            $commentOnlevel->forward_date = $this->_todayDate->format('Y-m-d');
            $commentOnlevel->forward_time = $this->_todayDate->format('H:i:m');
            $commentOnlevel->save();

            DB::commit();
            return responseMsg(true, "Successfully Forwarded The Application!!", "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Concession Application Approval or Rejected 
     * | @param req
     * | Status-closed
     * | Query Costing-376 ms
     * | Rating-2
     * | Status-Closed
     */
    public function approvalRejection($req)
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
                // Concession Application replication
                $activeConcession = PropActiveConcession::query()
                    ->where('id', $req->concessionId)
                    ->first();

                $approvedConcession = $activeConcession->replicate();
                $approvedConcession->setTable('prop_concessions');
                $approvedConcession->id = $activeConcession->id;
                $approvedConcession->save();
                $activeConcession->delete();

                $msg = "Application Successfully Approved !!";
            }
            // Rejection
            if ($req->status == 0) {
                // Concession Application replication
                $activeConcession = PropActiveConcession::query()
                    ->where('id', $req->concessionId)
                    ->first();

                $approvedConcession = $activeConcession->replicate();
                $approvedConcession->setTable('prop_rejected_concessions');
                $approvedConcession->id = $activeConcession->id;
                $approvedConcession->save();
                $activeConcession->delete();
                $msg = "Application Successfully Rejected !!";
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
     * | @param req
     * | Status-Closed
     * | Query Costing-358 ms 
     * | Rating-2
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
            $saf = PropActiveConcession::find($req->concessionId);
            $saf->current_role = $backId->wf_role_id;
            $saf->save();

            $levelPending = new PropConcessionLevelPending;
            $levelPending->concession_id = $req->concessionId;
            $levelPending->sender_role_id = $req->currentRoleId;
            $levelPending->receiver_role_id = $backId->wf_role_id;
            $levelPending->user_id = authUser()->id;
            $levelPending->sender_user_id = authUser()->id;
            $levelPending->save();

            $receiverLevelPending = new PropConcessionLevelPending();
            $receiverLevelPending = $receiverLevelPending->getReceiverLevel($req->concessionId, $req->currentRoleId);
            $receiverLevelPending->remarks = $req->comment;
            $receiverLevelPending->forward_date = $this->_todayDate->format('Y-m-d');
            $receiverLevelPending->forward_time = $this->_todayDate->format('H:i:m');
            $receiverLevelPending->save();

            return responseMsg(true, "Successfully Done", "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }


    // get owner details by propId
    public function getOwnerDetails($request)
    {
        try {
            $ownerDetails = PropProperty::select('applicant_name as ownerName',  'id as ownerId')
                ->where('prop_properties.id', $request->propId)
                ->first();

            $checkExisting = PropActiveConcession::where('property_id', $request->propId)
                ->where('status', 1)
                ->first();
            if ($checkExisting) {
                $checkExisting->property_id = $request->propId;
                $checkExisting->save();
                return responseMsg(1, "User Already Applied", $ownerDetails);
            } else return responseMsg(0, "User Not Exist", $ownerDetails);
            // return responseMsg(true, '', remove_null($ownerDetails));
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    //concession number generation
    public function concessionNo($id)
    {
        try {
            $count = PropActiveConcession::where('id', $id)
                ->select('id')
                ->get();
            $concessionNo = 'CON' . "/" . str_pad($count['0']->id, 5, '0', STR_PAD_LEFT);

            return $concessionNo;
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    //concession list
    public function concessionList()
    {
        try {
            $list = PropActiveConcession::select(
                'prop_active_concessions.id',
                'prop_active_concessions.applicant_name as ownerName',
                'holding_no as holdingNo',
                'ward_mstr_id as wardId',
                'prop_type_mstr_id as propertyType'
            )
                ->where('prop_active_concessions.status', 1)
                ->orderByDesc('prop_active_concessions.id')
                ->join('prop_properties', 'prop_properties.id', 'prop_active_concessions.property_id')
                ->get();

            return responseMsg(true, "Successfully Done", $list);
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    //get concession list by id
    public function getConcessionByid($req)
    {
        try {
            $list = PropActiveConcession::select(
                'prop_active_concessions.id',
                'prop_active_concessions.applicant_name as ownerName',
                'holding_no as holdingNo',
                'ward_mstr_id as wardId',
                'prop_type_mstr_id as propertyType',
                'dob',
                'gender',
                'is_armed_force as armedForce',
                'is_specially_abled as speciallyAbled'
            )
                ->where('prop_active_concessions.id', $req->id)
                ->where('prop_active_concessions.status', 1)
                ->orderByDesc('prop_active_concessions.id')
                ->join('prop_properties', 'prop_properties.id', 'prop_active_concessions.property_id')
                ->first();

            return responseMsg(true, "Successfully Done", $list);
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    //get concession list
    public function concessionDocList($req)
    {
        try {
            $list = PropConcessionDocDtl::select(
                'prop_concession_doc_dtls.doc_name as docName',
                'prop_concession_doc_dtls.doc_type as docType',
                'prop_concession_levelpendings.verification_status as verificationStatus'
            )
                ->where('prop_concession_levelpendings.concession_id', $req->id)
                ->join(
                    'prop_concession_levelpendings',
                    'prop_concession_levelpendings.concession_id',
                    'prop_concession_doc_dtls.concession_id'
                )
                ->get();

            return responseMsg(true, "Successfully Done", $list);
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    //post concession status
    public function concessionDocStatus($req)
    {
        try {


            return responseMsg(true, "Successfully Done", '');
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
}
