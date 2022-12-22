<?php

namespace App\Http\Controllers\Property;

use App\Repository\Property\Interfaces\iObjectionRepository;
use App\Http\Controllers\Controller;
use App\Models\PropActiveObjectionDocdtl;
use App\Models\Property\PropActiveObjection;
use Illuminate\Http\Request;
use App\Traits\Workflow\Workflow as WorkflowTrait;
use App\Traits\Property\Objection;
use App\Models\Property\RefPropObjectionType;
use App\Models\Property\PropOwner;
use Illuminate\Support\Facades\DB;
use Exception;

class ObjectionController extends Controller
{
    use WorkflowTrait;
    use Objection;

    protected $objection;
    public function __construct(iObjectionRepository $objection)
    {
        $this->Repository = $objection;
    }

    //Objection for Clerical Mistake
    public function applyObjection(Request $request)
    {
        $request->validate([
            'propId' => 'required|integer'
        ]);
        return $this->Repository->applyObjection($request);
    }

    //objection type list
    public function objectionType()
    {
        try {
            $objType = new RefPropObjectionType();
            return $objType->objectionType();
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    // get owner Details
    public function ownerDetails(Request $request)
    {
        try {

            $Details = new PropOwner();
            $ownerDetails = $Details->getOwnerDetails($request);

            return responseMsg(true, "Successfully Retrieved", $ownerDetails);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    //assesment details
    public function assesmentDetails(Request $request)
    {
        return $this->Repository->assesmentDetails($request);
    }

    /**
     * | Get Inbox List of Objection Workflow
     */
    public function inbox()
    {
        try {
            $auth = auth()->user();
            $userId = $auth->id;
            $ulbId = $auth->ulb_id;
            $wards = $this->getWardByUserId($userId);

            $occupiedWards = collect($wards)->map(function ($ward) {                               // Get Occupied Ward of the User
                return $ward->ward_id;
            });

            $roles = $this->getRoleIdByUserId($userId);

            $roleId = collect($roles)->map(function ($role) {                                       // get Roles of the user
                return $role->wf_role_id;
            });

            $objection = $this->getObjectionList($ulbId)                                            // Objection List
                ->whereIn('prop_active_objections.current_role', $roleId)
                ->whereIn('p.ward_mstr_id', $occupiedWards)
                ->orderByDesc('prop_active_objections.id')
                ->get();

            return responseMsgs(true, "", remove_null($objection), '010805', '01', '474ms-573ms', 'Post', '');
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Get the Objection Outbox
     */
    public function outbox(Request $request)
    {
        try {
            $auth = auth()->user();
            $userId = $auth->id;
            $ulbId = $auth->ulb_id;

            $workflowRoles = $this->getRoleIdByUserId($userId);                             // Get all The roles of the Users

            $roleId = $workflowRoles->map(function ($value) {                               // Get user Workflow Roles
                return $value->wf_role_id;
            });

            $refWard = $this->getWardByUserId($userId);                                     // Get Ward List by user Id
            $occupiedWards = $refWard->map(function ($value) {
                return $value->ward_id;
            });

            $objections = $this->getObjectionList($ulbId)                                   // Get Outbox Objection List
                ->whereNotIn('prop_active_objections.current_role', $roleId)
                ->whereIn('p.ward_mstr_id', $occupiedWards)
                ->orderByDesc('prop_active_objections.id')
                ->get();

            return responseMsgs(true, "Outbox List", remove_null($objections), '010806', '01', '336ms-420ms', 'Post', '');
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    // Get Details by id
    public function getDetailsById(Request $req)
    {
        $req->validate([
            'id' => 'required|integer'
        ]);

        return $this->Repository->getDetailsById($req);
    }

    /**
     * | Post Escalate the Applications
     * | @param req $req
     */
    public function postEscalate(Request $req)
    {
        try {
            $req->validate([
                'escalateStatus' => 'required|bool',
                'objectionId' => 'required|integer'
            ]);

            DB::beginTransaction();
            $userId = authUser()->id;
            $objId = $req->objectionId;
            $data = PropActiveObjection::find($objId);
            $data->is_escalated = $req->escalateStatus;
            $data->escalated_by = $userId;
            $data->save();
            DB::commit();
            return responseMsgs(true, $req->escalateStatus == 1 ? 'Objection is Escalated' : "Objection is removed from Escalated", '', '010808', '01', '474ms-573', 'Post', '');
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    // List of the Escalated Application
    public function specialInbox()
    {
        try {
            $userId = authUser()->id;
            $ulbId = authUser()->ulb_id;
            $occupiedWard = $this->getWardByUserId($userId);                        // Get All Occupied Ward By user id using trait
            $wardId = $occupiedWard->map(function ($item, $key) {                   // Filter All ward_id in an array using laravel collections
                return $item->ward_id;
            });
            $safData = $this->getObjectionList($ulbId)
                ->where('prop_active_objections.is_escalated', true)
                ->whereIn('p.ward_mstr_id', $wardId)
                ->get();
            return responseMsgs(true, "Data Fetched", remove_null($safData), '010809', '01', '474ms-573', 'Post', '');
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    // Post Next Level Application
    public function postNextLevel(Request $req)
    {
        $req->validate([
            'objectionId' => 'required',
            'senderRoleId' => 'required',
            'receiverRoleId' => 'required',
            'comment' => 'required'
        ]);

        return $this->Repository->postNextLevel($req);
    }

    /**
     * | Objection Application approval Rejection
     * | @param request $req
     */
    public function approvalRejection(Request $req)
    {
        try {
            $req->validate([
                "objectionId" => "required",
                "status" => "required"
            ]);
            // Check if the Current User is Finisher or Not
            $getFinisherQuery = $this->getFinisherId($req->workflowId);                 // Get Finisher using Trait
            $refGetFinisher = collect(DB::select($getFinisherQuery))->first();
            if ($refGetFinisher->role_id != $req->roleId) {
                return responseMsg(false, "Forbidden Access", "");
            }

            DB::beginTransaction();

            // Approval
            if ($req->status == 1) {
                // Objection Application replication
                $activeObjection = PropActiveObjection::query()
                    ->where('id', $req->objectionId)
                    ->first();

                $approvedObjection = $activeObjection->replicate();
                $approvedObjection->setTable('prop_objections');
                $approvedObjection->id = $activeObjection->id;
                $approvedObjection->save();
                $activeObjection->delete();

                $msg =  "Application Successfully Approved !!";
            }

            // Rejection

            if ($req->status == 0) {
                // Objection Application replication
                $activeObjection = PropActiveObjection::query()
                    ->where('id', $req->objectionId)
                    ->first();

                $approvedObjection = $activeObjection->replicate();
                $approvedObjection->setTable('prop_rejected_objections');
                $approvedObjection->id = $activeObjection->id;
                $approvedObjection->save();
                $activeObjection->delete();
                $msg = "Application Successfully Rejected !!";
            }
            DB::commit();

            return responseMsgs(true, $msg, "", '010811', '01', '474ms-573', 'Post', '');
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    // Application back To citizen
    public function backToCitizen(Request $req)
    {
        $req->validate([
            'objectionId' => "required",
            "workflowId" => "required"
        ]);
        return $this->Repository->backToCitizen($req);
    }

    //objection list
    public function objectionList()
    {
        return $this->Repository->objectionList();
    }

    //objection list  by id
    public function objectionByid(Request $req)
    {
        return $this->Repository->objectionByid($req);
    }

    //get document status by id
    public function objectionDocList(Request $req)
    {
        return $this->Repository->objectionDocList($req);
    }

    //get document status by id
    public function objectionDocUpload(Request $req)
    {
        return $this->Repository->objectionDocUpload($req);
    }

    //post document status
    public function objectionDocStatus(Request $req)
    {
        try {

            $docStatus = new PropActiveObjectionDocdtl();
            $docStatus->verifyDoc($req);

            return responseMsgs(true, "Successfully Done", '', '010817', '01', '302ms-356ms', 'Post', '');
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
}
