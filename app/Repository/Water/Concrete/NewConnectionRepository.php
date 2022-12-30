<?php

namespace App\Repository\Water\Concrete;

use App\Models\Property\PropActiveSaf;
use App\Models\Property\PropProperty;
use App\Models\Water\WaterApplicant;
use App\Models\Water\WaterApplicantDoc;
use App\Models\Water\WaterApplication;
use App\Models\Water\WaterConnectionCharge;
use App\Models\Water\WaterLevelpending;
use App\Models\Water\WaterPenaltyInstallment;
use App\Models\Workflows\WfRoleusermap;
use App\Models\Workflows\WfWardUser;
use App\Models\Workflows\WfWorkflow;
use App\Models\WorkflowTrack;
use App\Repository\Water\Interfaces\iNewConnection;
use App\Traits\Ward;
use App\Traits\Workflow\Workflow;
use App\Traits\Property\SAF;
use App\Traits\Water\WaterTrait;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Exists;

/**
 * | -------------- Repository for the New Water Connection Operations ----------------------- |
 * | Created On-07-10-2022 
 * | Created By-Anshu Kumar
 * | Updated By-Sam kerketta
 */

class NewConnectionRepository implements iNewConnection
{
    use SAF;
    use Workflow;
    use Ward;
    use WaterTrait;
    private $_dealingAssistent;
    private $_vacantLand;
    private $_waterWorkflowId;

    public function __construct()
    {
        $this->_dealingAssistent = Config::get('workflow-constants.DEALING_ASSISTENT_WF_ID');
        $this->_vacantLand = Config::get('PropertyConstaint.VACANT_LAND');
        $this->_waterWorkflowId = Config::get('workflow-constants.WATER_MASTER_ID');
    }

    /**
     * | -------------------------  Apply for the new Application for Water Application  --------------------- |
     * | @param req
     * | @var vacantLand
     * | @var workflowID
     * | @var ulbId
     * | @var ulbWorkflowObj : object for the model (WfWorkflow)
     * | @var ulbWorkflowId : calling the function on model:WfWorkflow 
     * | @var objCall : object for the model (WaterNewConnection)
     * | @var newConnectionCharges :
     * | Post the value in Water Application table
     * | post the value in Water Applicants table by loop
     * | 
     * | time : 
     * | rating : 5
     * ------------------------------------------------------------------------------------
     * | Generating the demand amount for the applicant in Water Connection Charges Table 
        | Serila No : 01
     */
    public function store(Request $req)
    {
        # ref variables
        $vacantLand = $this->_vacantLand;
        $workflowID = $this->_waterWorkflowId;
        $owner = $req['owners'];
        $ulbId = $req->ulbId;

        # get initiater and finisher
        $ulbWorkflowObj = new WfWorkflow();
        $ulbWorkflowId = $ulbWorkflowObj->getulbWorkflowId($workflowID, $ulbId);
        $refInitiatorRoleId = $this->getInitiatorId($ulbWorkflowId->id);
        $refFinisherRoleId = $this->getFinisherId($ulbWorkflowId->id);
        $finisherRoleId = DB::select($refFinisherRoleId);
        $initiatorRoleId = DB::select($refInitiatorRoleId);

        # Generating Demand 
        $objCall = new WaterNewConnection();
        $newConnectionCharges = objToArray($objCall->calWaterConCharge($req));
        if (!$newConnectionCharges['status']) {
            throw new Exception(
                $newConnectionCharges['errors']
            );
        }
        $installment = $newConnectionCharges['installment_amount'];
        $waterFeeId = $newConnectionCharges['water_fee_mstr_id'];

        # Generating Application No
        $now = Carbon::now();
        $applicationNo = 'APP' . $now->getTimeStamp();

        # check the property type on vacant land
        if ($req->connection_through != '3') {
            $checkResponse = $this->checkVacantLand($req, $vacantLand);
            if ($checkResponse) {
                return $checkResponse;
            }
        }

        $objNewApplication = new WaterApplication();
        $applicationId = $objNewApplication->saveWaterApplication($req, $ulbWorkflowId, $initiatorRoleId, $finisherRoleId, $ulbId, $applicationNo, $waterFeeId);

        foreach ($owner as $owners) {
            $objApplicant = new WaterApplicant();
            $objApplicant->saveWaterApplicant($applicationId, $owners);
        }

        if (!is_null($installment)) {
            foreach ($installment as $installments) {
                $objQuaters = new WaterPenaltyInstallment();
                $objQuaters->saveWaterPenelty($applicationId, $installments);
            }
        }

        $charges = new WaterConnectionCharge();
        $charges->saveWaterCharge($applicationId, $req, $newConnectionCharges);

        return responseMsgs(true, "Successfully Saved!", $applicationNo, "", "02", "", "POST", "");
    }


    /**
     * |--------------------------------- Check property for the vacant land ------------------------------|
     * | @param req
     * | @param vacantLand
     * | @var readPropetySafCheck
     * | @var readpropetyHoldingCheck
     * | Operation : check if the applied application is in vacant land 
        | Serial No : 01.1
     */
    public function checkVacantLand($req, $vacantLand)
    {
        switch ($req) {
            case (!is_null($req->saf_no)):
                $isExist = $this->checkPropertyExist($req);
                if ($isExist) {
                    $propetySafCheck = PropActiveSaf::select('prop_type_mstr_id')
                        ->where('saf_no', $req->saf_no)
                        ->get()
                        ->first();
                    if ($propetySafCheck->prop_type_mstr_id == $vacantLand) {
                        return responseMsg(false, "water cannot be applied on Vacant land!", "");
                    }
                } else {
                    return responseMsg(false, "Saf Not Exist!", $req->saf_no);
                }
                break;
            case (!is_null($req->holdingNo)):
                $isExist = $this->checkPropertyExist($req);
                if ($isExist) {
                    $propetyHoldingCheck = PropProperty::select('prop_type_mstr_id')
                        ->where('new_holding_no', $req->holdingNo)
                        ->get()
                        ->first();
                    if ($propetyHoldingCheck->prop_type_mstr_id == $vacantLand) {
                        return responseMsg(false, "water cannot be applied on Vacant land!", "");
                    }
                } else {
                    return responseMsg(false, "Holding Not Exist!", $req->holdingNo);
                }
                break;
        }
    }


    /**
     * |---------------------------------------- check if the porperty ie,(saf/holdin) Exist ------------------------------------------------|
     * | @param req
     * | @var safCheck
     * | @var holdingCheck
        | Serial No : 01.1.1
     */
    public function checkPropertyExist($req)
    {
        if ($req->saf_no) {
            $safCheck = PropActiveSaf::select(
                'saf_no'
            )
                ->where('saf_no', $req->saf_no)
                ->get()
                ->first();
            if ($safCheck) {
                return true;
            }
        } elseif ($req->holdingNo) {
            $holdingCheck = PropProperty::select(
                'new_holding_no'
            )
                ->where('new_holding_no', $req->holdingNo)
                ->get()
                ->first();
            if ($holdingCheck) {
                return true;
            }
        }
    }



    /**
     * |----------------------------------------- water Workflow Functions Listed Below ------------------------------------------------------------|
     */


    /**
     * |------------------------------------------ water Inbox -------------------------------------|
     * | @var auth
     * | @var userId
     * | @var ulbId
     * | @var occupiedWards 
     * | @var roleId
     * | @var waterList : using the function to fetch the list of the respective water application details according to (ulbId, roleId and ward) 
     * | @var wardId : using the Workflow trait's function (getWardUserId($userId)) for respective wardId.
     * | @var roles : using the Workflow trait's function (getRoleIdByUserId($userID)) for  respective roles.
     * | @return waterList : Details to be displayed in the inbox of the offices in water workflow. 
        | Serila No : 02
        | Working
     */
    public function waterInbox()
    {
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

        $waterList = $this->getWaterApplicatioList($ulbId,)
            ->whereIn('water_applications.current_role', $roleIds)
            ->whereIn('water_applications.ward_id', $occupiedWards)
            ->orderByDesc('water_applications.id')
            ->get();

        return responseMsgs(true, "Inbox List Details!", remove_null($waterList), '', '02', '', 'Post', '');
    }



    /**
     * |----------------------------------------- Water Outbox ------------------------------------------------|
     * | @var auth
     * | @var userId
     * | @var ulbId
     * | @var workflowRoles : using the function to fetch the list of the respective water application details according to (ulbId, roleId and ward) 
     * | @var wardId : using the Workflow trait's function (getWardUserId($userId)) for respective wardId.
     * | @var roles : using the Workflow trait's function (getRoleIdByUserId($userID)) for  respective roles.
     * | @return waterList : Details to be displayed in the inbox of the offices in water workflow. 
        | Serial No : 03
        | Working 
     */
    public function waterOutbox()
    {
        $mWfRoleUser = new WfRoleusermap();
        $mWfWardUser = new WfWardUser();

        $userId = auth()->user()->id;
        $ulbId = auth()->user()->ulb_id;
        $workflowRoles = $this->getRoleIdByUserId($userId);
        $roleId = $workflowRoles->map(function ($value, $key) {                         // Get user Workflow Roles
            return $value->wf_role_id;
        });

        $refWard = $mWfWardUser->getWardsByUserId($userId);
        $wardId = $refWard->map(function ($value, $key) {
            return $value->ward_id;
        });

        $waterList = $this->getWaterApplicatioList($ulbId)
            ->whereNotIn('water_applications.current_role', $roleId)
            ->whereIn('a.ward_mstr_id', $wardId)
            ->orderByDesc('water_applications.id')
            ->get();

        return responseMsgs(true, "Outbox List", remove_null($waterList), '', '01', '.ms', 'Post', '');
    }


    /**
     * |------------------------------------------ Post Application to the next level ---------------------------------------|
     * | @param req
     * | @var 
        | Serial No : 04
        | Working
     */
    public function postNextLevel($req)
    {
        try {
            $metaReqs['moduleId'] = Config::get('module-constants.WATER_MODULE_ID');
            $metaReqs['workflowId'] = Config::get('workflow-constants.WATER_WORKFLOW_ID');
            $metaReqs['refTableDotId'] = 'water_applications.id';
            $metaReqs['refTableIdValue'] = $req->applicationId;
            $req->request->add($metaReqs);

            $objTrack = new WorkflowTrack();
            $objTrack->saveTrack($req);

            # objection Application Update Current Role Updation
            $objection = WaterApplication::find($req->applicationId);
            $objection->current_role = $req->receiverRoleId;
            $objection->save();

            return responseMsgs(true, "Successfully Forwarded The Application!!", "", "", "", '01', '.ms', 'Post', '');
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }


    /**
     * |---------------------------------------------- Special Inbox -----------------------------------------|
     * | @var auth
     * | @var userId
     * | @var wardID
     * | @var ulbId
     * | @var occupiedWards
     * | @var waterList
     * |
        | Serial No : 05
        | Unchecked
     */
    public function waterSpecialInbox()
    {
        try {
            $auth = auth()->user();
            $userId = $auth->id;
            $ulbId = $auth->ulb_id;
            $wardId = $this->getWardByUserId($userId);

            $occupiedWards = collect($wardId)->map(function ($ward) {                                   // Get Occupied Ward of the User
                return $ward->ward_id;
            });

            $waterList = $this->getWaterApplicatioList($ulbId)                                          // Get Concessions
                ->where('water_applications.is_escalate', true)
                ->whereIn('a.ward_mstr_id', $occupiedWards)
                ->orderByDesc('water_applications.id')
                ->get();

            return responseMsg(true, "Inbox List", remove_null($waterList), "", "", '01', '.ms', 'Post', '');
        } catch (Exception $error) {
            return responseMsg(false, $error->getMessage(), "");
        }
    }



    /**
     * | ----------------- Document verification processs ------------------------------- |
     * | @param Req 
     * | @var userId
     * | @var docStatus
        | Serial No : 06
        | Unchecked
     */
    public function waterDocStatus($req)
    {
        try {
            $userId = auth()->user()->id;

            $docStatus = WaterApplicantDoc::find($req->documentId);
            $docStatus->remarks = $req->docRemarks;
            $docStatus->verified_by_emp_id = $userId;
            $docStatus->verified_on = Carbon::now();
            $docStatus->updated_at = Carbon::now();

            if ($req->docStatus == 'Verified') {                        //<------------ (here data type small int)        
                $docStatus->verify_status = 1;
            }
            if ($req->docStatus == 'Rejected') {                        //<------------ (here data type small int)
                $docStatus->verify_status = 2;
            }

            $docStatus->save();

            return responseMsg(true, "Successfully Done", '');
        } catch (Exception $error) {
            return responseMsg(false, "ERROR!", $error->getMessage());
        }
    }


    /**
     * |------------------------------ Get Application details --------------------------------|
     */
    public function getApplicationsDetails($request)
    {
        $applicationDetails = WaterApplication::select(
            'ulb_ward_masters.ward_name',
            'ulb_masters.ulb_name',
            'water_applications.application_no',
            'water_connection_type_mstrs.connection_type',
            'water_property_type_mstrs.property_type',
            'water_applications.category',
            'water_applications.area_sqft',
            'water_applications.address',
            'water_applications.landmark',
            'water_applications.pin',
            'water_applications.elec_k_no',
            'water_applications.elec_category',
            'water_applications.elec_bind_book_no',
            'water_applications.apply_date',
        )
            ->join('ulb_ward_masters', 'ulb_ward_masters.id', 'water_applications.ward_id')
            ->join('ulb_masters', 'ulb_masters.id', '=', 'water_applications.ulb_id')
            ->join('water_connection_type_mstrs', 'water_connection_type_mstrs.id', '=', 'water_applications.connection_type_id')
            ->join('water_property_type_mstrs', 'water_property_type_mstrs.id', '=', 'water_applications.property_type_id')
            ->where('water_applications.id', $request->id)
            ->where('water_applications.status', 1)
            ->get()
            ->first();
        if ($applicationDetails) {
            return responseMsgs(true, "listed Data!", $applicationDetails, "", "02", ".ms", "POST", "");
        }
        return responseMsg(false, "Data Not Found!", $request->id);
    }
}
