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

class ConcessionRepository implements iConcessionRepository
{

    //wf_master_id = 35;
    //workflow_id = 106;
    use WorkflowTrait;
    use Concession;

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
            // workflows


            DB::beginTransaction();
            $workflow_id = Config::get('workflow-constants.PROPERTY_CONCESSION_ID');
            $concession = new PropActiveConcession;
            $concession->property_id = $request->propertyId;
            $concession->saf_id = $request->safId;
            $concession->application_no = $request->applicationNo;
            $concession->applicant_name = $request->applicantName;
            $concession->gender = $request->gender;
            $concession->dob = $request->dob;
            $concession->is_armed_force = $request->armedForce;
            $concession->is_specially_abled = $request->speciallyAbled;
            $concession->doc_type = $request->docType;
            $concession->remarks = $request->remarks;
            $concession->status = $request->status;
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

            //gender Doc
            if ($file = $request->file('genderDoc')) {

                $name = time() . $file . '.' . $file->getClientOriginalExtension();
                $path = public_path('concession/genderDoc');
                $file->move($path, $name);
            }

            // dob Doc
            if ($file = $request->file('dobDoc')) {

                $name = time() . $file . '.' . $file->getClientOriginalExtension();
                $path = public_path('concession/dobDoc');
                $file->move($path, $name);
            }

            // specially abled Doc
            if ($file = $request->file('speciallyAbledDoc')) {

                $name = time() . $file . '.' . $file->getClientOriginalExtension();
                $path = public_path('concession/speciallyAbledDoc');
                $file->move($path, $name);
            }

            // Armed force Doc
            if ($file = $request->file('armedForceDoc')) {

                $name = time() . $file . '.' . $file->getClientOriginalExtension();
                $path = public_path('concession/armedForceDoc');
                $file->move($path, $name);
            }

            // Property SAF Label Pendings
            $labelPending = new PropConcessionLevelpending();
            $labelPending->concession_id = $concession->id;
            $labelPending->receiver_role_id = $initiatorRoleId[0]->role_id;
            $labelPending->save();

            DB::commit();
            return responseMsg(true, 'Successfully Applied The Application', remove_null($concession));
        } catch (Exception $e) {
            DB::rollBack();
            return response()->responseMsg(false, $e->getMessage(), "");
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
            $commentOnlevel = PropConcessionLevelPending::where('concession_id', $req->concessionId)
                ->where('receiver_role_id', $req->senderRoleId)
                ->first();

            $commentOnlevel->remarks = $req->comment;
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
            return responseMsg(true, "Successfully Done", "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }
}
