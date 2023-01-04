<?php

namespace App\Repository\Water\Concrete;

use App\Models\Property\PropActiveSaf;
use App\Models\Property\PropProperty;
use App\Models\Water\WaterApplicant;
use App\Models\Water\WaterApplicantDoc;
use App\Models\Water\WaterApplication;
use App\Models\Water\WaterApprovalApplicationDetail;
use App\Models\Water\WaterConnectionCharge;
use App\Models\Water\WaterLevelpending;
use App\Models\Water\WaterPenaltyInstallment;
use App\Models\Workflows\WfRoleusermap;
use App\Models\Workflows\WfTrack;
use App\Models\Workflows\WfWardUser;
use App\Models\Workflows\WfWorkflow;
use App\Models\WorkflowTrack;
use App\Repository\Water\Interfaces\iNewConnection;
use App\Repository\WorkflowMaster\Concrete\WorkflowRoleUserMapRepository;
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
    private $_waterWorkId;
    private $_waterModulId;

    public function __construct()
    {
        $this->_dealingAssistent = Config::get('workflow-constants.DEALING_ASSISTENT_WF_ID');
        $this->_vacantLand = Config::get('PropertyConstaint.VACANT_LAND');
        $this->_waterWorkflowId = Config::get('workflow-constants.WATER_MASTER_ID');
        $this->_waterWorkId = Config::get('workflow-constants.WATER_WORKFLOW_ID');
        $this->_waterModulId = Config::get('module-constants.WATER_MODULE_ID');
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
     * | @param isExist
     * | @var propetySafCheck
     * | @var propetyHoldingCheck
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
     * | @return value : true or nothing 
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
     * | @var userId
     * | @var ulbId
     * | @var occupiedWards 
     * | @var roleIds
     * | @var readRoles
     * | @var mWfWardUser
     * | @var mWfRoleUser
     * | @var waterList : using the function to fetch the list of the respective water application details according to (ulbId, roleId and ward) 
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
            ->where('water_applications.is_escalate', false)
            ->orderByDesc('water_applications.id')
            ->get();

        return responseMsgs(true, "Inbox List Details!", remove_null($waterList), '', '02', '', 'Post', '');
    }



    /**
     * |----------------------------------------- Water Outbox ------------------------------------------------|
     * | @var userId
     * | @var ulbId
     * | @var workflowRoles : using the function to fetch the list of the respective water application details according to (ulbId, roleId and ward) 
     * | @var wardId : using the Workflow trait's function (getWardUserId($userId)) for respective wardId.
     * | @var refWard
     * | @var roleId : using the Workflow trait's function (getRoleIdByUserId($userID)) for  respective roles.
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
            ->whereIn('water_applications.ward_id', $wardId)
            ->orderByDesc('water_applications.id')
            ->get();

        return responseMsgs(true, "Outbox List", remove_null($waterList), '', '01', '.ms', 'Post', '');
    }


    /**
     * |------------------------------------------ Post Application to the next level ---------------------------------------|
     * | @param req
     * | @var metaReqs
     * | @var waterTrack
     * | @var waterApplication
        | Serial No : 04
        | Working
     */
    public function postNextLevel($req)
    {
        $metaReqs['moduleId'] =  $this->_waterModulId;
        $metaReqs['workflowId'] = $this->_waterWorkId;
        $metaReqs['refTableDotId'] = 'water_applications.id';
        $metaReqs['refTableIdValue'] = $req->appId;
        $req->request->add($metaReqs);

        $waterTrack = new WorkflowTrack();
        $waterTrack->saveTrack($req);

        # objection Application Update Current Role Updation
        $waterApplication = WaterApplication::find($req->appId);
        $waterApplication->current_role = $req->receiverRoleId;
        $waterApplication->save();

        return responseMsgs(true, "Successfully Forwarded The Application!!", "", "", "", '01', '.ms', 'Post', '');
    }


    /**
     * |---------------------------------------------- Special Inbox -----------------------------------------|
     * | @param request
     * | @var mWfWardUser
     * | @var userId
     * | @var wardID
     * | @var ulbId
     * | @var occupiedWards
     * | @var waterList
     * | @var waterData
     * | @return waterData :
     * |
        | Serial No : 05
        | Unchecked 
     */
    public function waterSpecialInbox($request)
    {
        $mWfWardUser = new WfWardUser();
        $userId = authUser()->id;
        $ulbId = authUser()->ulb_id;

        $occupiedWard = $mWfWardUser->getWardsByUserId($userId);                        // Get All Occupied Ward By user id using trait
        $wardId = $occupiedWard->map(function ($item, $key) {                           // Filter All ward_id in an array using laravel collections
            return $item->ward_id;
        });
        $waterData = $this->getWaterApplicatioList($ulbId)                      // Repository function to get SAF Details
            ->where('water_applications.is_escalate', 1)
            ->whereIn('ward_mstr_id', $wardId)
            ->orderByDesc('id')
            ->get();
        return responseMsgs(true, "Data Fetched", remove_null($waterData), "010107", "1.0", "251ms", "POST", "");
    }


    /**
     * |--------------------------- post Escalate -----------------------------|
     * | @param request
     * | @var userId
     * | @var applicationId
     * | @var applicationsData
     * | @var 
        | Serial No : 06 
        | working
     */
    public function postEscalate($request)
    {
        $userId = auth()->user()->id;
        $applicationId = $request->applicationId;
        $applicationsData = WaterApplication::find($applicationId);
        $applicationsData->is_escalate = $request->escalateStatus;
        $applicationsData->escalate_by = $userId;
        $applicationsData->save();
        return responseMsgs(true, $request->escalateStatus == 1 ? 'Water is Escalated' : "Water is removed from Escalated", '', "", "1.0", ".ms", "POST", $request->deviceId);
    }


    /**
     * | ----------------- Document verification processs ------------------------------- |
     * | @param Req 
     * | @var userId
     * | @var docStatus
     * | @var msg
     * | @return msg : status
        | Serial No : 07
        | Working / Flag
     */
    public function waterDocStatus($req)
    {
        $userId = auth()->user()->id;

        if ($req->docStatus == 'Verified') {                        //<------------ (here data type small int)        
            $docStatus = 1;
            $msg = "Doc Status Verified!";
        }
        if ($req->docStatus == 'Rejected') {                        //<------------ (here data type small int)
            $docStatus = 2;
            $msg = "Doc Status Rejected!";
        }

        WaterApplicantDoc::where('id', $req->id)
            ->update([
                'remarks' => $req->docRemarks ?? null,
                'verify_by_emp_id' => $userId,
                'verified_on' =>  Carbon::now(),
                'updated_at' =>  Carbon::now(),
                'verify_status' => $docStatus
            ]);

        return responseMsg(true, $msg, '');
    }


    /**
     * |------------------------------ Approval Rejection Water -------------------------------|
     * | @param request 
     * | @var waterDetails
     * | @var approvedWater
     * | @var rejectedWater
     * | @var msg
        | Serial No : 08 
        | Working
     */
    public function approvalRejectionWater($request)
    {
        $now = Carbon::now();
        $waterDetails = WaterApplication::find($request->id);
        if ($waterDetails->finisher != $request->roleId) {
            throw new Exception("You're Not the finisher!");
        }
        if ($waterDetails->current_role != $request->roleId) {
            throw new Exception("Application has not Reached to the finisher!");
        }
        DB::beginTransaction();
        // Approval
        if ($request->status == 1) {
            $approvedWater = WaterApplication::query()
                ->where('id', $request->id)
                ->first();

            $approvedWaterRep = $approvedWater->replicate();
            $approvedWaterRep->setTable('water_approval_application_details');
            $approvedWaterRep->id = $approvedWater->id;
            $approvedWaterRep->consumer_no = 'CON' . $now->getTimeStamp();
            $approvedWaterRep->save();
            $approvedWater->delete();

            $msg = "Application Successfully Approved !!";
        }
        // Rejection
        if ($request->status == 0) {
            $rejectedWater = WaterApplication::query()
                ->where('id', $request->id)
                ->first();

            $rejectedWaterRep = $rejectedWater->replicate();
            $rejectedWaterRep->setTable('water_rejection_application_details');
            $rejectedWaterRep->id = $rejectedWater->id;
            $rejectedWaterRep->save();
            $rejectedWater->delete();
            $msg = "Application Successfully Rejected !!";
        }
        DB::commit();
        return responseMsgs(true, $msg, $approvedWaterRep->consumer_no ?? "Empty", '', 01, '.ms', 'Post', $request->deviceId);
    }




    /**
     * |------------------------------ Get Application details --------------------------------|
     * | @param request
     * | @var ownerDetails
     * | @var applicantDetails
     * | @var applicationDetails
     * | @var returnDetails
     * | @return returnDetails : list of individual applications
        | Serial No : 07
        | Workinig / Flag
     */
    public function getApplicationsDetails($request)
    {
        # application details
        $applicationDetails = WaterApplication::select(
            'water_applications.*',
            'ulb_ward_masters.ward_name',
            'ulb_masters.ulb_name',
            'water_connection_type_mstrs.connection_type',
            'water_property_type_mstrs.property_type',
            'water_connection_through_mstrs.connection_through',
            'wf_roles.role_name AS current_role_name',
        )
            ->join('wf_roles', 'wf_roles.id', '=', 'water_applications.current_role')
            ->join('ulb_ward_masters', 'ulb_ward_masters.id', 'water_applications.ward_id')
            ->join('water_connection_through_mstrs', 'water_connection_through_mstrs.id', '=', 'water_applications.connection_through')
            ->join('ulb_masters', 'ulb_masters.id', '=', 'water_applications.ulb_id')
            ->join('water_connection_type_mstrs', 'water_connection_type_mstrs.id', '=', 'water_applications.connection_type_id')
            ->join('water_property_type_mstrs', 'water_property_type_mstrs.id', '=', 'water_applications.property_type_id')
            ->where('water_applications.id', $request->id)
            ->where('water_applications.status', 1)
            ->get();

        if (collect($applicationDetails)->first() == null) {
            return responseMsg(false, "Application Data Not found!", $request->id);
        }

        # owner Details
        $ownerDetails = WaterApplication::select(
            'water_applicants.applicant_name as owner_name',
            'guardian_name',
            'mobile_no',
            'email'
        )
            ->join('water_applicants', 'water_applicants.application_id', '=', 'water_applications.id')
            ->where('water_applications.id', $request->id)
            ->where('water_applications.status', 1)
            ->get();

        $ownerDetails = collect($ownerDetails)->map(function ($value, $key) {
            return $value;
        });

        # track details
        $trackDetails = WorkflowTrack::select(
            'workflow_tracks.message',
            'workflow_tracks.module_id',
            'workflow_tracks.user_id',
            'workflow_tracks.sender_role_id'
        )
            ->where('workflow_tracks.ref_table_dot_id', 'water_applications.id')
            ->where('workflow_tracks.ref_table_id_value', $request->id)
            ->where('workflow_tracks.status', 1)
            ->get();

        # connection Charges
        $connectionCharge = WaterConnectionCharge::select(
            'amount',
            'charge_category',
            'penalty',
            'conn_fee',
            'rule_set'
        )
            ->where('application_id', $request->id)
            ->where('water_connection_charges.status', 1)
            ->get();

        (collect($applicationDetails)->first())['owner_details'] = $ownerDetails;
        (collect($applicationDetails)->first())['track_details'] = $trackDetails;
        (collect($applicationDetails)->first())['payment_details'] = $connectionCharge;

        $returnDetails = collect($applicationDetails)->first();
        return responseMsgs(true, "listed Data!", remove_null($returnDetails), "", "02", ".ms", "POST", "");
    }


    /**
     * |-------------------------------- get the document details ---------------------------|
     * | @param request
     * | @var applicationNo
     * | @var mUploadDocument
     * | @var refApplicationNo
     * | @var refApplicationId
     * | @var data
     * | @return data : document details
        | Serial No : 09
        | Working
     */
    public function getWaterDocDetails($request)
    {
        $applicationId = null;
        $mUploadDocument = (array)null;
        $applicationId   = $request->id;

        $refApplicationNo = WaterApplication::where('id', $applicationId)->get();
        if (!$refApplicationNo) {
            throw new Exception("Data Not Found!");
        }

        $refApplicationId = collect($refApplicationNo)->first();
        $mUploadDocument = $this->getWaterDocuments($refApplicationId->id)
            ->map(function ($val) {
                if (isset($val["doc_name"])) {
                    $path = $this->readDocumentPath($val["doc_name"]);
                    $val["doc_path"] = !empty(trim($val["doc_name"])) ? $path : null;
                }
                return $val;
            });
        $data["uploadDocument"] = $mUploadDocument;
        return responseMsgs(true, "list Of Uploaded Doc!", $data, "", "02", ".ms", "POST", $request->deviceId);
    }


    /**
     * |---------------------------------- Calling function for the doc details from database -------------------------------|
     * | @param applicationId
     * | @var docDetails
     * | @return docDetails : listed doc details according to application Id
        | Serial No : 09.01
        | Working
     */
    public function getWaterDocuments($applicationId)
    {
        $docDetails = WaterApplicantDoc::select(
            "water_applicant_docs.id",
            "water_applicant_docs.doc_name",
            "water_applicant_docs.doc_for",
            "water_applicant_docs.remarks",
            "water_applicant_docs.document_id",
            "water_applicant_docs.verify_status",
            'water_param_document_types.document_name',

        )
            ->join('water_param_document_types', 'water_param_document_types.id', '=', 'water_applicant_docs.document_id')
            ->where('water_applicant_docs.application_id', $applicationId)
            ->where('water_applicant_docs.status', 1)
            ->where('water_param_document_types.status', 1)
            ->orderBy('water_applicant_docs.id', 'desc')
            ->get();
        return remove_null($docDetails);
    }

    /**
     * |-------------------------------------- Calling function for the doc path -----------------------------------|
     * | @param path
     * | @var docPath
     * | @return docPath : doc url
        | Serial No : 09.02
        | Working
     */
    public function readDocumentPath($path)
    {
        $docPath = (config('app.url') . '/api/getImageLink?path=' . $path);
        return $docPath;
    }

    /**
     * |----------------------- comment Indipendent -----------------------|
     * | @param request
     * | @var applicationId
     * | @var workflowTrack
     * | @var mSafWorkflowId
     * | @var mModuleId
     * | @var metaReqs
        | Serial No : 00
        | Unchecked
     */
    public function commentIndependent($request)
    {
        // $userId = auth()->user()->id; 
        $applicationId = WaterApplication::find($request->id);
        $workflowTrack = new WorkflowTrack();
        $mSafWorkflowId = $this->_waterWorkId;
        $mModuleId =  $this->_waterModulId;
        $workflowID = $this->_waterWorkflowId;
        $metaReqs = array();

        //     $ulbWorkflowObj = new WfWorkflow();
        //    return $ulbWorkflowId = $ulbWorkflowObj->getulbWorkflowId($workflowID, $request->ulbId);

        //     $obj = new WorkflowRoleUserMapRepository();
        //     $transfer['userId'] = auth()->user()->id;
        //     $metaReqs = new Request($transfer);
        //    return  $roleId = $obj->getRolesByUserId($metaReqs);

        if (!$applicationId) {
            throw new Exception("Application Don't Exist!");
        }

        // Save On Workflow Track
        $metaReqs = [
            'workflowId' => $mSafWorkflowId,
            'moduleId' => $mModuleId,
            'workflowId' => $mSafWorkflowId,
            'refTableDotId' => "water_applications.id",
            'refTableIdValue' => $applicationId->id,
            // 'senderRoleId'  => $roleId
        ];
        $request->request->add($metaReqs);
        $workflowTrack->saveTrack($request);
        return responseMsgs(true, "You Have Commented Successfully!!", ['Comment' => $request->comment], "010108", "1.0", "427ms", "POST", "");
    }

    /**
     * |-----
     */
    public function getApprovedWater($request)
    {
        $obj = new WaterApprovalApplicationDetail();
        $approvedWater = $obj->getApplicationRelatedDetails()
            ->select(
                'water_approval_application_details.*',
                'ulb_masters.ulb_name',
                'ulb_ward_masters.ward_name'
            )
            ->where('consumer_no', $request->consumerNo)
            ->first();
        if ($approvedWater) {
            $applicationId = $approvedWater['id'];
            $connectionCharge = WaterConnectionCharge::select(
                'amount',
                'charge_category',
                'penalty',
                'conn_fee',
                'rule_set'
            )
                ->where('application_id', $applicationId)
                ->where('water_connection_charges.status', 1)
                ->get();

            $approvedWater['payment'] = $connectionCharge;
            return responseMsgs(true, "Consumer Details!", $approvedWater, "", "01", ".ms", "POST", $request->deviceId);
        }
        throw new Exception("Data Not Found!");
    }
}
