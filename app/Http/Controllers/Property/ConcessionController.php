<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Models\Property\PropActiveConcession;
use App\Models\Property\PropConcessionDocDtl;
use App\Repository\Property\Interfaces\iConcessionRepository;
use Illuminate\Http\Request;
use App\Traits\Workflow\Workflow as WorkflowTrait;
use App\Traits\Property\Concession;
use Illuminate\Support\Carbon;
use Exception;

/**
 * | Created On-15-11-2022 
 * | Created By-Mrinal Kumar
 * --------------------------------------------------------------------------------------
 * | Controller for Concession
 */


class ConcessionController extends Controller
{
    use WorkflowTrait;
    use Concession;

    private $_todayDate;
    private $_bifuraction;
    private $_workflowId;

    protected $concession_repository;
    public function __construct(iConcessionRepository $concession_repository)
    {
        $this->Repository = $concession_repository;
    }


    //apply concession
    public function applyConcession(Request $request)
    {
        $request->validate([
            'propId' => "required"
        ]);
        return $this->Repository->applyConcession($request);
    }

    //post Holding
    public function postHolding(Request $request)
    {
        $request->validate([
            'holdingNo' => 'required'
        ]);
        return $this->Repository->postHolding($request);
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

            return $concessions = $this->getConcessionList($ulbId)
                ->whereIn('prop_active_concessions.current_role', $roleId)
                ->whereIn('a.ward_mstr_id', $occupiedWards)
                ->orderByDesc('prop_active_concessions.id')
                ->get();
            return responseMsgs(true, "Inbox List", remove_null($concessions), '010703', '01', '326ms-478ms', 'Post', '');
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

            return responseMsgs(true, "Outbox List", remove_null($concessions), '010704', '01', '355ms-419ms', 'Post', '');
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    // Get Concession Details by ID
    public function getDetailsById(Request $req)
    {
        $req->validate([
            'id' => 'required'
        ]);

        return $this->Repository->getDetailsById($req);
    }

    /**
     * | Escalate application
     * | @param req request parameters
     * | Query Costing-400ms 
     * | Rating-2
     * | Status-Closed
     */
    public function escalateApplication(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'escalateStatus' => 'required'
            ]);
            $userId = auth()->user()->id;
            if ($req->escalateStatus == 1) {
                $concession = PropActiveConcession::find($req->id);
                $concession->is_escalate = 1;
                $concession->escalated_by = $userId;
                $concession->save();
                return responseMsgs(true, "Successfully Escalated the application", "", '010706', '01', '400ms', 'Post', '');
            }
            if ($req->escalateStatus == 0) {
                $concession = PropActiveConcession::find($req->id);
                $concession->is_escalate = 0;
                $concession->escalated_by = null;
                $concession->save();
                return responseMsgs(true, "Successfully De-Escalated the application", "", '010706', '01', '400ms', 'Post', '');
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

            return responseMsg(true, "Inbox List", remove_null($concessions), "", '010707', '01', '303ms', 'Post', '');
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    // Post Next Level Application
    public function postNextLevel(Request $req)
    {
        $req->validate([
            'concessionId' => 'required',
            'senderRoleId' => 'required',
            'receiverRoleId' => 'required',
            'comment' => 'required'
        ]);
        return $this->Repository->postNextLevel($req);
    }

    // Application Approval Rejection
    public function approvalRejection(Request $req)
    {
        $req->validate([
            "concessionId" => "required",
            "status" => "required"
        ]);
        return $this->Repository->approvalRejection($req);
    }

    // Application back To citizen
    public function backToCitizen(Request $req)
    {
        $req->validate([
            'concessionId' => "required",
            "workflowId" => "required"
        ]);
        return $this->Repository->backToCitizen($req);
    }

    // get owner details by propId
    public function getOwnerDetails(Request $request)
    {
        $request->validate([
            'propId' => "required"
        ]);
        return $this->Repository->getOwnerDetails($request);
    }

    //concesssion list
    public function concessionList()
    {
        try {
            $list = PropActiveConcession::select(
                'prop_active_concessions.id',
                'prop_active_concessions.applicant_name as ownerName',
                'holding_no as holdingNo',
                'ward_name as wardId',
                'property_type as propertyType'
            )
                ->where('prop_active_concessions.status', 1)
                ->orderByDesc('prop_active_concessions.id')
                ->join('prop_properties', 'prop_properties.id', 'prop_active_concessions.property_id')
                ->join('ref_prop_types', 'ref_prop_types.id', 'prop_properties.prop_type_mstr_id')
                ->join('ulb_ward_masters', 'ulb_ward_masters.id', 'prop_properties.ward_mstr_id')
                ->get();

            return responseMsgs(true, "Successfully Done", $list, "", '010712', '01', '308ms-396ms', 'Post', '');
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    //concesion list  by id
    public function concessionByid(Request $req)
    {
        try {
            $list = PropActiveConcession::select(
                'prop_active_concessions.id',
                'prop_active_concessions.applicant_name as ownerName',
                'holding_no as holdingNo',
                'ward_name as wardId',
                'property_type as propertyType',
                'dob',
                'gender',
                'is_armed_force as armedForce',
                'is_specially_abled as speciallyAbled'
            )
                ->where('prop_active_concessions.id', $req->id)
                ->where('prop_active_concessions.status', 1)
                ->orderByDesc('prop_active_concessions.id')
                ->join('prop_properties', 'prop_properties.id', 'prop_active_concessions.property_id')
                ->join('ref_prop_types', 'ref_prop_types.id', 'prop_properties.prop_type_mstr_id')
                ->join('ulb_ward_masters', 'ulb_ward_masters.id', 'prop_properties.ward_mstr_id')
                ->first();

            return responseMsgs(true, "Successfully Done", $list, "", '010713', '01', '312ms-389ms', 'Post', '');
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    //get document status by id
    public function concessionDocList(Request $req)
    {
        try {
            $list = PropConcessionDocDtl::select(
                'id',
                'doc_type as docName',
                'relative_path',
                'doc_name as docUrl',
                'verify_status as docStatus',
                'remarks as docRemarks'
            )
                // ->select(DB::raw("CONCAT(relative_path,doc_name) AS url", "id"))
                ->where('prop_concession_doc_dtls.concession_id', $req->id)
                ->get();
            $list = $list->map(function ($val) {
                $path = $this->_bifuraction->readDocumentPath($val->relative_path . $val->docUrl);
                $val->docUrl = $path;
                return $val;
            });
            return responseMsgs(true, "Successfully Done", remove_null($list), "", '010714', '01', '314ms-451ms', 'Post', '');
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    //doc upload
    public function concessionDocUpload(Request $req)
    {
        return $this->Repository->concessionDocUpload($req);
    }

    //post document status
    public function concessionDocStatus(Request $req)
    {
        try {
            $userId = auth()->user()->id;

            $docStatus = PropConcessionDocDtl::find($req->id);
            $docStatus->remarks = $req->docRemarks;
            $docStatus->verified_by_emp_id = $userId;
            $docStatus->verified_on = Carbon::now();
            $docStatus->updated_at = Carbon::now();

            if ($req->docStatus == 'Verified') {
                $docStatus->verify_status = 1;
            }
            if ($req->docStatus == 'Rejected') {
                $docStatus->verify_status = 2;
            }
            $docStatus->save();

            return responseMsgs(true, "Successfully Done", '', "", '010716', '01', '308ms-431ms', 'Post', '');
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
}
