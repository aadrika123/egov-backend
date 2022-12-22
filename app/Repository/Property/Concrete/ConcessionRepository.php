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
use Illuminate\Support\Facades\URL;

/**
 * | Created On-16-11-2022
 * | Created By-Mrinal Kumar
 * | -----------------------------------------------------------------------------------------
 * | Concession Module all operations 
 * | --------------------------- Workflow Parameters ---------------------------------------
 * | Concession Master ID   = 35                
 * | Concession WorkflowID  = 106              
 */

class ConcessionRepository implements iConcessionRepository
{
    use WorkflowTrait;
    use Concession;

    private $_todayDate;
    private $_bifuraction;
    private $_workflowId;

    public function __construct()
    {
        $this->_todayDate = Carbon::now();
        $this->_bifuraction = new PropertyBifurcation();
        $this->_workflowId = Config::get('workflow-constants.PROPERTY_CONCESSION_ID');
    }
    //apply concession
    /**
     * | Query Costing-464ms 
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

            $applicantName = $this->getOwnerName($request->propId);
            $ownerName = $applicantName->ownerName;

            $ulbWorkflowId = WfWorkflow::where('wf_master_id', $this->_workflowId)
                ->where('ulb_id', $ulbId)
                ->first();

            $refInitiatorRoleId = $this->getInitiatorId($ulbWorkflowId->id);                // Get Current Initiator ID
            $initiatorRoleId = DB::select($refInitiatorRoleId);

            $refFinisherRoleId = $this->getFinisherId($ulbWorkflowId->id);
            $finisherRoleId = DB::select($refFinisherRoleId);

            if ($userType == "JSK") {
                $obj  = new SafRepository();
                $data = $obj->getPropByHoldingNo($request);
            }

            DB::beginTransaction();
            $concession = new PropActiveConcession;
            $concession->property_id = $request->propId;
            $concession->applicant_name = $ownerName;

            if ($request->gender == 1) {
                $concession->gender = 'Male';
            }
            if ($request->gender == 2) {
                $concession->gender = 'Female';
            }
            if ($request->gender == 3) {
                $concession->gender = 'Transgender';
            }

            $concession->dob = $request->dob;
            $concession->is_armed_force = $request->armedForce;
            $concession->is_specially_abled = $request->speciallyAbled;
            $concession->remarks = $request->remarks;
            $concession->status = '1';
            $concession->user_id = $userId;
            $concession->ulb_id = $ulbId;
            $concession->workflow_id = $ulbWorkflowId->id;
            $concession->current_role = collect($initiatorRoleId)->first()->role_id;
            $concession->initiator_role_id = collect($initiatorRoleId)->first()->role_id;
            $concession->finisher_role_id = collect($finisherRoleId)->first()->role_id;
            $concession->created_at = Carbon::now();
            $concession->date = Carbon::now();
            $concession->save();

            //concession number generate in model
            $conNo = new PropActiveConcession();
            $concessionNo = $conNo->concessionNo($concession->id);

            PropActiveConcession::where('id', $concession->id)
                ->update(['application_no' => $concessionNo]);

            //saving document in concession doc table
            if ($file = $request->file('genderDoc')) {
                $docName = "genderDoc";
                $name = $this->moveFile($docName, $file);

                $concessionDoc = new PropConcessionDocDtl();
                $concessionDoc->concession_id = $concession->id;
                $this->citizenDocUpload($concessionDoc, $name, $docName);
            }

            // dob Doc
            if ($file = $request->file('dobDoc')) {
                $docName = "dobDoc";
                $name = $this->moveFile($docName, $file);

                $concessionDoc = new PropConcessionDocDtl();
                $concessionDoc->concession_id = $concession->id;
                $this->citizenDocUpload($concessionDoc, $name, $docName);
            }

            // specially abled Doc
            if ($file = $request->file('speciallyAbledDoc')) {
                $docName = "speciallyAbledDoc";
                $name = $this->moveFile($docName, $file);

                $concessionDoc = new PropConcessionDocDtl();
                $concessionDoc->concession_id = $concession->id;
                $this->citizenDocUpload($concessionDoc, $name, $docName);
            }

            // Armed force Doc
            if ($file = $request->file('armedForceDoc')) {
                $docName = "armedForceDoc";
                $name = $this->moveFile($docName, $file);

                $concessionDoc = new PropConcessionDocDtl();
                $concessionDoc->concession_id = $concession->id;
                $this->citizenDocUpload($concessionDoc, $name, $docName);
            }

            // Property SAF Label Pendings
            $labelPending = new PropConcessionLevelpending();
            $labelPending->concession_id = $concession->id;
            $labelPending->receiver_role_id = $initiatorRoleId[0]->role_id;
            $labelPending->sender_user_id = $userId;
            $labelPending->save();

            DB::commit();
            return responseMsgs(true, 'Successfully Applied The Application', $concessionNo, '010701', '01', '382ms-547ms', 'Post', '');
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
                return responseMsgs(true, 'True', $user, '010702', '01', '334ms-401ms', 'Post', '');
            }
            return responseMsg(false, "False", "");
            // return $user['0'];
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
            $details = PropActiveConcession::select(
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

            $levelComments = PropConcessionLevelPending::select(
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

            return responseMsgs(true, "Concession Details", remove_null($details), '010705', '01', '326ms-408ms', 'Post', '');
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
            $commentOnlevel->receiver_user_id = auth()->user()->id;
            $commentOnlevel->forward_date = $this->_todayDate->format('Y-m-d');
            $commentOnlevel->forward_time = $this->_todayDate->format('H:i:m');
            $commentOnlevel->save();

            DB::commit();
            return responseMsgs(true, "Successfully Forwarded The Application!!", "", "", '010708', '01', '355ms', 'Post', '');
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
                $backId = WfWorkflowrolemap::where('workflowId', $workflowId)
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

            return responseMsgs(true, "Successfully Done", "", "", '010710', '01', '358ms', 'Post', '');
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    //concession document upload
    public function concessionDocUpload($req)
    {
        try {
            //gender doc
            if ($file = $req->file('genderDoc')) {
                $docName = "genderDoc";
                $name = $this->moveFile($docName, $file);

                $checkExisting = PropConcessionDocDtl::where('concession_id', $req->id)
                    ->where('doc_type', $docName)
                    ->get()
                    ->first();
                if ($checkExisting) {
                    $this->updateDocument($req, $name, $docName);
                } else {
                    $this->saveConcessionDoc($req, $name, $docName);
                }
            }

            //dob doc
            if ($file = $req->file('dobDoc')) {
                $docName = "dobDoc";
                $name = $this->moveFile($docName, $file);

                $checkExisting = PropConcessionDocDtl::where('concession_id', $req->id)
                    ->where('doc_type', $docName)
                    ->get()
                    ->first();
                if ($checkExisting) {
                    $this->updateDocument($req, $name, $docName);
                } else {
                    $this->saveConcessionDoc($req, $name, $docName);
                }
            }

            //specially abled doc
            if ($file = $req->file('speciallyAbledDoc')) {
                $docName = "speciallyAbledDoc";
                $name = $this->moveFile($docName, $file);

                $checkExisting = PropConcessionDocDtl::where('concession_id', $req->id)
                    ->where('doc_type', $docName)
                    ->get()
                    ->first();
                if ($checkExisting) {
                    $this->updateDocument($req, $name, $docName);
                } else {
                    $this->saveConcessionDoc($req, $name, $docName);
                }
            }

            //armed forcce doc
            if ($file = $req->file('armedForceDoc')) {
                $docName = "armedForceDoc";
                $name = $this->moveFile($docName, $file);

                $checkExisting = PropConcessionDocDtl::where('concession_id', $req->id)
                    ->where('doc_type', $docName)
                    ->get()
                    ->first();
                if ($checkExisting) {
                    $this->updateDocument($req, $name, $docName);
                } else {
                    $this->saveConcessionDoc($req, $name, $docName);
                }
            }

            //concession doc
            if ($file = $req->file('concessionFormDoc')) {
                $docName = "concessionFormDoc";
                $name = $this->moveFile($docName, $file);

                $checkExisting = PropConcessionDocDtl::where('concession_id', $req->id)
                    ->where('doc_type', $docName)
                    ->get()
                    ->first();
                if ($checkExisting) {
                    $this->updateDocument($req, $name, $docName);
                } else {
                    $this->saveConcessionDoc($req, $name, $docName);
                }
            }

            return responseMsgs(true, "Successfully Uploaded", '', "", '010715', '01', '434ms', 'Post', '');
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }


    //citizen doc upload
    public function citizenDocUpload($concessionDoc, $name, $docName)
    {
        $userId = auth()->user()->id;

        $concessionDoc->doc_type = $docName;
        $concessionDoc->relative_path = '/concession/' . $docName . '/';
        $concessionDoc->doc_name = $name;
        $concessionDoc->status = '1';
        $concessionDoc->user_id = $userId;
        $concessionDoc->date = Carbon::now();
        $concessionDoc->created_at = Carbon::now();
        $concessionDoc->save();
    }

    //save documents
    public function saveConcessionDoc($req, $name, $docName)
    {
        $userId = auth()->user()->id;
        $concessionDoc = new PropConcessionDocDtl();
        $concessionDoc->concession_id = $req->id;

        $concessionDoc->doc_type = $docName;
        $concessionDoc->relative_path = '/concession/' . $docName . '/';
        $concessionDoc->doc_name = $name;
        $concessionDoc->status = '1';
        $concessionDoc->user_id = $userId;
        $concessionDoc->date = Carbon::now();
        $concessionDoc->created_at = Carbon::now();
        $concessionDoc->save();
    }

    //update documents
    public function updateDocument($req, $name, $docName)
    {
        PropConcessionDocDtl::where('concession_id', $req->id)
            ->where('doc_type', $docName)
            ->update([
                'concession_id' => $req->id,
                'doc_type' => $docName,
                'relative_path' => ('/concession/' . $docName . '/'),
                'doc_name' => $name,
                'status' => 1,
                'verify_status' => 0,
                'remarks' => '',
                'updated_at' => Carbon::now()
            ]);
    }

    //move file to location
    public function moveFile($docName, $file)
    {
        $name = time() . $docName . '.' . $file->getClientOriginalExtension();
        $path = storage_path('app/public/concession/' . $docName . '/');
        $file->move($path, $name);

        return $name;
    }

    //owner name
    public function getOwnerName($propId)
    {
        $ownerDetails = PropProperty::select('applicant_name as ownerName')
            ->where('prop_properties.id', $propId)
            ->first();

        return $ownerDetails;
    }
}
