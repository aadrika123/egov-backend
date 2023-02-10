<?php

namespace App\Repository\Water\Concrete;

use App\Models\CustomDetail;
use App\Models\Property\PropActiveSaf;
use App\Models\Property\PropProperty;
use App\Models\Water\WaterApplicant;
use App\Models\Water\WaterApplication;
use App\Models\Water\WaterApprovalApplicationDetail;
use App\Models\Water\WaterConnectionCharge;
use App\Models\Water\WaterConsumer;
use App\Models\Water\WaterConsumerOwner;
use App\Models\Water\WaterParamConnFee;
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
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use App\Repository\WorkflowMaster\Concrete\WorkflowMap;

/**
 * | -------------- Repository for the New Water Connection Operations ----------------------- |
 * | Created On-07-10-2022 
 * | Created By-Anshu Kumar
 * | Created By-Sam kerketta
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
    private $_juniorEngRoleId;

    public function __construct()
    {
        $this->_dealingAssistent = Config::get('workflow-constants.DEALING_ASSISTENT_WF_ID');
        $this->_vacantLand = Config::get('PropertyConstaint.VACANT_LAND');
        $this->_waterWorkflowId = Config::get('workflow-constants.WATER_MASTER_ID');
        $this->_waterWorkId = Config::get('workflow-constants.WATER_WORKFLOW_ID');
        $this->_waterModulId = Config::get('module-constants.WATER_MODULE_ID');
        $this->_juniorEngRoleId  = Config::get('workflow-constants.WATER_JE_ROLE_ID');
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
        | Check the ulb_id
     */
    public function store(Request $req)
    {
        # ref variables
        $vacantLand = $this->_vacantLand;
        $workflowID = $this->_waterWorkflowId;
        $owner = $req['owners'];
        $ulbId = $req->ulbId;

        # check the property type on vacant land
        $checkResponse = $this->checkVacantLand($req, $vacantLand);
        if ($checkResponse) {
            return $checkResponse;
        }

        # get initiater and finisher
        $ulbWorkflowObj = new WfWorkflow();
        $ulbWorkflowId = $ulbWorkflowObj->getulbWorkflowId($workflowID, $ulbId);
        if (!$ulbWorkflowId) {
            throw new Exception("The respective Ulb is not maped to Water Workflow!");
        }
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

        DB::beginTransaction();
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
        DB::commit();

        $returnResponse = [
            'applicationNo' => $applicationNo,
            'applicationId' => $applicationId
        ];
        return responseMsgs(true, "Successfully Saved!", $returnResponse, "", "02", "", "POST", "");
    }


    /**
     * |--------------------------------- Check property for the vacant land ------------------------------|
     * | @param req
     * | @param vacantLand
     * | @param isExist
     * | @var propetySafCheck
     * | @var propetyHoldingCheck
     * | Operation : check if the applied application is in vacant land 
        | Serial No : 01.2
     */
    public function checkVacantLand($req, $vacantLand)
    {
        switch ($req) {
            case (!is_null($req->saf_no)):
                $isExist = $this->checkPropertyExist($req);
                if ($isExist) {
                    $propetySafCheck = PropActiveSaf::select('prop_type_mstr_id')
                        ->where('saf_no', $req->saf_no)
                        ->where('ulb_id', $req->ulbId)
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
                        ->orwhere('holding_no', $req->holdingNo)
                        ->where('ulb_id', $req->ulbId)
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
        | Serial No : 01.2.1
     */
    public function checkPropertyExist($req)
    {
        switch ($req) {
            case (!is_null($req->saf_no)): {
                    $safCheck = PropActiveSaf::select(
                        'id',
                        'saf_no'
                    )
                        ->where('saf_no', $req->saf_no)
                        ->where('ulb_id', $req->ulbId)
                        ->first();
                    if ($safCheck) {
                        return true;
                    }
                }
            case (!is_null($req->holdingNo)): {
                    $holdingCheck = PropProperty::select(
                        'id',
                        'new_holding_no'
                    )
                        ->where('new_holding_no', $req->holdingNo)
                        ->orwhere('holding_no', $req->holdingNo)
                        ->where('ulb_id', $req->ulbId)
                        ->first();
                    if ($holdingCheck) {
                        return true;
                    }
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
            ->where('water_applications.parked', false)
            ->orderByDesc('water_applications.id')
            ->get();
        $filterWaterList = collect($waterList)->unique('id')->values();
        return responseMsgs(true, "Inbox List Details!", remove_null($filterWaterList), '', '02', '', 'Post', '');
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
        $filterWaterList = collect($waterList)->unique('id')->values();
        return responseMsgs(true, "Outbox List", remove_null($filterWaterList), '', '01', '.ms', 'Post', '');
    }


    /**
     * |------------------------------------------ Post Application to the next level ---------------------------------------|
     * | @param req
     * | @var metaReqs
     * | @var waterTrack
     * | @var waterApplication
        | Serial No : 04
        | Working / track the last role 
     */
    public function postNextLevel($req)
    {
        $wfLevels = Config::get('waterConstaint.ROLE-LABEL');
        $waterApplication = WaterApplication::find($req->applicationId);

        if ($req->action == 'forward') {
            $this->checkPostCondition($req->senderRoleId, $wfLevels, $waterApplication);            // Check Post Next level condition
            $waterApplication->last_role_id = $req->receiverRoleId;                                 // Update Last Role Id
        }
        $metaReqs['moduleId'] =  $this->_waterModulId;
        $metaReqs['workflowId'] = $this->_waterWorkId;
        $metaReqs['refTableDotId'] = 'water_applications.id';
        $metaReqs['refTableIdValue'] = $req->applicationId;
        $req->request->add($metaReqs);

        DB::beginTransaction();
        $waterTrack = new WorkflowTrack();
        $waterTrack->saveTrack($req);

        $waterApplication->current_role = $req->receiverRoleId;
        $waterApplication->save();
        DB::commit();

        return responseMsgs(true, "Successfully Forwarded The Application!!", "", "", "", '01', '.ms', 'Post', '');
    }

    /**
     * | check Post Condition for backward forward
        | Serial No : 04.01
        | working 
     */
    public function checkPostCondition($senderRoleId, $wfLevels, $saf)
    {
        switch ($senderRoleId) {
            case $wfLevels['BO']:                        // Back Office Condition
                if ($saf->doc_upload_status == 0)
                    throw new Exception("Document Not Fully Uploaded");
                break;
            case $wfLevels['DA']:                       // DA Condition
                if ($saf->doc_verify_status == 0)
                    throw new Exception("Document Not Fully Verified");
                break;
        }
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
        | Woking 
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
        $waterData = $this->getWaterApplicatioList($ulbId)                              // Repository function to get SAF Details
            ->where('water_applications.is_escalate', 1)
            ->whereIn('water_applications.ward_id', $wardId)
            ->orderByDesc('water_applications.id')
            ->get();
        $filterWaterList = collect($waterData)->unique('id')->values();
        return responseMsgs(true, "Data Fetched", remove_null($filterWaterList), "010107", "1.0", "251ms", "POST", "");
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
     * |------------------------------ Approval Rejection Water -------------------------------|
     * | @param request 
     * | @var waterDetails
     * | @var approvedWater
     * | @var rejectedWater
     * | @var msg
        | Serial No : 07 
        | Working / Check it / remove the comment ?? for delete
     */
    public function approvalRejectionWater($request)
    {

        $waterDetails = WaterApplication::find($request->applicationId);
        if ($waterDetails->finisher != $request->roleId) {
            throw new Exception("You're Not the finisher!");
        }
        if ($waterDetails->current_role != $request->roleId) {
            throw new Exception("Application has not Reached to the finisher!");
        }
        if ($waterDetails->doc_status == false) {
            throw new Exception("Documet is Not verified!");
        }
        if ($waterDetails->payment_status == false) {
            throw new Exception("Payment Not Done!");
        }
        if ($waterDetails->doc_upload_status == false) {
            throw new Exception("Full document is Not Uploaded!");
        }
        if ($waterDetails->is_field_verified == false) {
            throw new Exception("Field Verification Not Done!!");
        }

        DB::beginTransaction();
        # Approval of water application 
        if ($request->status == 1) {

            $now = Carbon::now();
            $mWaterApplication = new WaterApplication();
            $mWaterApplicant = new WaterApplicant();
            $consumerNo = 'CON' . $now->getTimeStamp();

            $mWaterApplication->finalApproval($request, $consumerNo);
            $mWaterApplicant->finalApplicantApproval($request);
            $msg = "Application Successfully Approved !!";
        }
        # Rejection of water application
        if ($request->status == 0) {

            $mWaterApplication = new WaterApplication();
            $mWaterApplicant = new WaterApplicant();

            $mWaterApplication->finalRejectionOfAppication($request);
            $mWaterApplicant->finalOwnerRejection($request);
            $msg = "Application Successfully Rejected !!";
        }
        DB::commit();
        return responseMsgs(true, $msg, $consumerNo ?? "Empty", '', 01, '.ms', 'Post', $request->deviceId);
    }


    /**
     * |------------------------------ Get Application details --------------------------------|
     * | @param request
     * | @var ownerDetails
     * | @var applicantDetails
     * | @var applicationDetails
     * | @var returnDetails
     * | @return returnDetails : list of individual applications
        | Serial No : 08
        | Workinig 
     */
    public function getApplicationsDetails($request)
    {
        # ref
        $waterObj = new WaterApplication();
        $ownerObj = new WaterApplicant();
        $forwardBackward = new WorkflowMap;
        $mWorkflowTracks = new WorkflowTrack();
        $mCustomDetails = new CustomDetail();
        $mWaterNewConnection = new WaterNewConnection();

        # application details
        $applicationDetails = $waterObj->fullWaterDetails($request)->get();
        if (collect($applicationDetails)->first() == null) {
            return responseMsg(false, "Application Data Not found!", $request->applicationId);
        }

        # owner Details
        $ownerDetails = $ownerObj->ownerByApplication($request)->get();
        $ownerDetail = collect($ownerDetails)->map(function ($value, $key) {
            return $value;
        });

        $aplictionList = [
            'application_no' => collect($applicationDetails)->first()->application_no,
            'apply_date' => collect($applicationDetails)->first()->apply_date
        ];

        # DataArray
        $basicDetails = $this->getBasicDetails($applicationDetails);
        $propertyDetails = $this->getpropertyDetails($applicationDetails);
        $electricDetails = $this->getElectricDetails($applicationDetails);
        $firstView = [
            'headerTitle' => 'Basic Details',
            'data' => $basicDetails
        ];

        $secondView = [
            'headerTitle' => 'Applicant Property Details',
            'data' => $propertyDetails
        ];

        $thirdView = [
            'headerTitle' => 'Applicant Electricity Details',
            'data' => $electricDetails
        ];
        $fullDetailsData['fullDetailsData']['dataArray'] = new collection([$firstView, $secondView, $thirdView]);

        # CardArray
        $cardDetails = $this->getCardDetails($applicationDetails, $ownerDetails);
        $cardData = [
            'headerTitle' => 'Water Connection',
            'data' => $cardDetails
        ];
        $fullDetailsData['fullDetailsData']['cardArray'] = new Collection($cardData);

        # TableArray
        $ownerList = $this->getOwnerDetails($ownerDetail);
        $ownerView = [
            'headerTitle' => 'Owner Details',
            'tableHead' => ["#", "Owner Name", "Guardian Name", "Mobile No", "Email", "City", "District"],
            'tableData' => $ownerList
        ];
        $fullDetailsData['fullDetailsData']['tableArray'] = new Collection([$ownerView]);

        # Level comment
        $mtableId = $applicationDetails->first()->id;
        $mRefTable = "water_applications.id";
        $levelComment['levelComment'] = $mWorkflowTracks->getTracksByRefId($mRefTable, $mtableId);

        #citizen comment
        $refCitizenId = $applicationDetails->first()->user_id;
        $citizenComment['citizenComment'] = $mWorkflowTracks->getCitizenTracks($mRefTable, $mtableId, $refCitizenId);

        # Role Details
        $data = json_decode(json_encode($applicationDetails->first()), true);
        $metaReqs = [
            'customFor' => 'Water',
            'wfRoleId' => $data['current_role'],
            'workflowId' => $data['workflow_id'],
            'lastRoleId' => $data['last_role_id']
        ];
        $request->request->add($metaReqs);
        $forwardBackward = $forwardBackward->getRoleDetails($request);
        $roleDetails['roleDetails'] = collect($forwardBackward)['original']['data'];

        # Timeline Data
        $timelineData['timelineData'] = collect($request);

        # Departmental Post
        $custom = $mCustomDetails->getCustomDetails($request);
        $departmentPost['departmentalPost'] = collect($custom)['original']['data'];

        # Document Details
        // $metaReqs = [
        //     'userId' => auth()->user()->id,
        //     'ulbId' => auth()->user()->ulb_id,
        // ];
        // $request->request->add($metaReqs);
        // $document = $mWaterNewConnection->documentUpload($request);
        // $documentDetails = collect($document)['original']['data'];

        # Payments Details
        $returnValues = array_merge($aplictionList, $fullDetailsData, $levelComment, $citizenComment, $roleDetails, $timelineData, $departmentPost);
        return responseMsgs(true, "listed Data!", remove_null($returnValues), "", "02", ".ms", "POST", "");
    }


    /**
     * |------------------ Basic Details ------------------|
     * | @param applicationDetails
     * | @var collectionApplications
        | Serial No : 08.01
     */
    public function getBasicDetails($applicationDetails)
    {
        $collectionApplications = collect($applicationDetails)->first();
        return new Collection([
            ['displayString' => 'Ward No',            'key' => 'WardNo',              'value' => $collectionApplications->ward_id],
            ['displayString' => 'Type of Connection', 'key' => 'TypeOfConnection',    'value' => $collectionApplications->connection_type],
            ['displayString' => 'Property Type',      'key' => 'PropertyType',        'value' => $collectionApplications->property_type],
            ['displayString' => 'Connection Through', 'key' => 'ConnectionThrough',   'value' => $collectionApplications->connection_through],
            ['displayString' => 'Category',           'key' => 'Category',            'value' => $collectionApplications->category],
            ['displayString' => 'Flat Count',         'key' => 'FlatCount',           'value' => $collectionApplications->flat_count],
            ['displayString' => 'Pipeline Type',      'key' => 'PipelineType',        'value' => $collectionApplications->pipeline_type],
            ['displayString' => 'Apply From',         'key' => 'ApplyFrom',           'value' => $collectionApplications->apply_from],
            ['displayString' => 'Apply Date',         'key' => 'ApplyDate',           'value' => $collectionApplications->apply_date]
        ]);
    }

    /**
     * |------------------ Property Details ------------------|
     * | @param applicationDetails
     * | @var propertyDetails
     * | @var collectionApplications
        | Serial No : 08.02
     */
    public function getpropertyDetails($applicationDetails)
    {
        $propertyDetails = array();
        $collectionApplications = collect($applicationDetails)->first();

        if (!is_null($collectionApplications->holding_no)) {
            array_push($propertyDetails, ['displayString' => 'Holding No',    'key' => 'AppliedBy',  'value' => $collectionApplications->holding_no]);
        }
        if (!is_null($collectionApplications->saf_no)) {
            array_push($propertyDetails, ['displayString' => 'Saf No',        'key' => 'AppliedBy',    'value' => $collectionApplications->saf_no]);
        }
        if (is_null($collectionApplications->saf_no) && is_null($collectionApplications->holding_no)) {
            array_push($propertyDetails, ['displayString' => 'Applied By',    'key' => 'AppliedBy',   'value' => 'Id Proof']);
        }
        array_push($propertyDetails, ['displayString' => 'Ward No',       'key' => 'WardNo',      'value' => $collectionApplications->ward_id]);
        array_push($propertyDetails, ['displayString' => 'Area in Sqft',  'key' => 'AreaInSqft',  'value' => $collectionApplications->area_sqft]);
        array_push($propertyDetails, ['displayString' => 'Address',       'key' => 'Address',     'value' => $collectionApplications->address]);
        array_push($propertyDetails, ['displayString' => 'Landmark',      'key' => 'Landmark',    'value' => $collectionApplications->landmark]);
        array_push($propertyDetails, ['displayString' => 'Pin',           'key' => 'Pin',         'value' => $collectionApplications->pin]);

        return $propertyDetails;
    }

    /**
     * |------------------ Electric details ------------------|
     * | @param applicationDetails
     * | @var collectionApplications
        | Serial No : 08.03
     */
    public function getElectricDetails($applicationDetails)
    {
        $collectionApplications = collect($applicationDetails)->first();
        return new Collection([
            ['displayString' => 'K.No',             'key' => 'KNo',            'value' => $collectionApplications->elec_k_no],
            ['displayString' => 'Bind Book No',     'key' => 'BindBookNo',    'value' => $collectionApplications->elec_bind_book_no],
            ['displayString' => 'Elec Account No',  'key' => 'ElecAccountNo', 'value' => $collectionApplications->elec_account_no],
            ['displayString' => 'Elec Category',    'key' => 'ElecCategory',   'value' => $collectionApplications->elec_category]
        ]);
    }

    /**
     * |------------------ Owner details ------------------|
     * | @param ownerDetails
        | Serial No : 08.04
     */
    public function getOwnerDetails($ownerDetails)
    {
        return collect($ownerDetails)->map(function ($value, $key) {
            return [
                $key + 1,
                $value['owner_name'],
                $value['guardian_name'],
                $value['mobile_no'],
                $value['email'],
                $value['city'],
                $value['district']
            ];
        });
    }

    /**
     * |------------------ Get Card Details ------------------|
     * | @param applicationDetails
     * | @param ownerDetails
     * | @var ownerDetail
     * | @var collectionApplications
        | Serial No : 08.05
     */
    public function getCardDetails($applicationDetails, $ownerDetails)
    {
        $ownerDetail = collect($ownerDetails)->first();
        $collectionApplications = collect($applicationDetails)->first();
        return new Collection([
            ['displayString' => 'Ward No.',             'key' => 'WardNo.',           'value' => $collectionApplications->ward_id],
            ['displayString' => 'Application No.',      'key' => 'ApplicationNo.',    'value' => $collectionApplications->application_no],
            ['displayString' => 'Owner Name',           'key' => 'OwnerName',         'value' => $ownerDetail['owner_name']],
            ['displayString' => 'Property Type',        'key' => 'PropertyType',      'value' => $collectionApplications->property_type],
            ['displayString' => 'Connection Type',      'key' => 'ConnectionType',    'value' => $collectionApplications->connection_type],
            ['displayString' => 'Connection Through',   'key' => 'ConnectionThrough', 'value' => $collectionApplications->connection_through],
            ['displayString' => 'Apply-Date',           'key' => 'ApplyDate',         'value' => $collectionApplications->apply_date],
            ['displayString' => 'Total Area (sqt)',     'key' => 'TotalArea',         'value' => $collectionApplications->area_sqft]
        ]);
    }

    /**
     * |----------------------- comment Indipendent -----------------------|
     * | @param request
     * | @var applicationId
     * | @var workflowTrack
     * | @var mSafWorkflowId
     * | @var mModuleId
     * | @var metaReqs
        | Serial No : 09
        | Working 
     */
    public function commentIndependent($request)
    {
        $applicationId = WaterApplication::find($request->applicationId);
        $workflowTrack = new WorkflowTrack();
        $mSafWorkflowId = $this->_waterWorkId;
        $mModuleId =  $this->_waterModulId;
        $metaReqs = array();

        if (!$applicationId) {
            throw new Exception("Application Don't Exist!");
        }

        # Save On Workflow Track
        $metaReqs = [
            'workflowId' => $mSafWorkflowId,
            'moduleId' => $mModuleId,
            'workflowId' => $mSafWorkflowId,
            'refTableDotId' => "water_applications.id",
            'refTableIdValue' => $applicationId->id,
        ];

        # For Citizen Independent Comment
        if (!$request->senderRoleId) {
            $metaReqs = array_merge($metaReqs, ['citizenId' => auth()->user()->id]);
        }
        $request->request->add($metaReqs);
        $workflowTrack->saveTrack($request);
        return responseMsgs(true, "You Have Commented Successfully!!", ['Comment' => $request->comment], "010108", "1.0", "427ms", "POST", "");
    }

    /**
     * |-------------------------- Get Approved Application Details According to Consumer No -----------------------|
     * | @param request
     * | @var obj
     * | @var approvedWater
     * | @var applicationId
     * | @var connectionCharge
     * | @return connectionCharge : list of approved application by Consumer Id
        | Serial No :10
        | Working / Flag / Check / reused
     */
    public function getApprovedWater($request)
    {
        $mWaterConsumer = new WaterConsumer();
        $mWaterConnectionCharge = new WaterConnectionCharge();
        $mWaterConsumerOwner = new WaterConsumerOwner();
        $mWaterParamConnFee = new WaterParamConnFee();

        $key = collect($request)->map(function ($value, $key) {
            return $key;
        })->first();
        $string = preg_replace("/([A-Z])/", "_$1", $key);
        $refstring = strtolower($string);
        $approvedWater = $mWaterConsumer->getConsumerByConsumerNo($refstring, $request->consumerNo);
        $connectionCharge = $mWaterConnectionCharge->getWaterchargesById($approvedWater['id'])->firstOrFail();
        $waterOwner['ownerDetails'] = $mWaterConsumerOwner->getConsumerOwner($approvedWater['id']);
        $water['calcullation'] = $mWaterParamConnFee->getCallParameter($approvedWater['property_type_id'], $approvedWater['area_sqft'])->first();

        $consumerDetails = collect($approvedWater)->merge($connectionCharge)->merge($waterOwner)->merge($water);
        return remove_null($consumerDetails);
    }

    /**
     * |-------------------- field Verified Inbox list ----------------------------|
     * | @param req
     * | @var mWfWardUser
     * | @var userId
     * | @var ulbId
     * | @var roleId
     * | @var refWard
     * | @var wardId
     * | @var waterList
     * | @return waterList 
        | Serial No : 11
        | Working
     */
    public function fieldVerifiedInbox($req)
    {
        $mWfWardUser = new WfWardUser();
        $userId = auth()->user()->id;
        $ulbId = auth()->user()->ulb_id;

        $roleId = Config::get('waterConstaint.ROLE-LABEL.JE');

        $refWard = $mWfWardUser->getWardsByUserId($userId);
        $wardId = $refWard->map(function ($value, $key) {
            return $value->ward_id;
        });

        $waterList = $this->getWaterApplicatioList($ulbId)
            ->where('water_applications.current_role', $roleId)
            ->whereIn('water_applications.ward_id', $wardId)
            ->where('is_field_verified', true)
            ->orderByDesc('water_applications.id')
            ->get();

        return responseMsgs(true, "field Verified Inbox", remove_null($waterList), 010125, 1.0, "", "POST", "");
    }
}
