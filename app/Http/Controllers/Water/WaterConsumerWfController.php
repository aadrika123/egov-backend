<?php

namespace App\Http\Controllers\Water;

use App\Http\Controllers\Controller;
use App\Http\Controllers\UlbMaster;
use App\Http\Requests\Water\ReqApplicationId;
use App\MicroServices\DocUpload;
use App\MicroServices\IdGenerator\PrefixIdGenerator;
use App\Models\CustomDetail;
use App\Models\Masters\RefRequiredDocument;
use App\Models\UlbMaster as ModelsUlbMaster;
use App\Models\UlbWardMaster;
use App\Models\Water\WaterConnectionCharge;
use App\Models\Water\WaterConsumerActiveRequest;
use App\Models\Water\WaterConsumerCharge;
use App\Models\Water\WaterConsumerDemand;
use App\Models\Water\WaterConsumerMeter;
use App\Models\Water\WaterConsumerOwner;
use App\Models\Water\WaterConsumer;
use App\Models\Water\WaterSiteInspection;
use App\Models\Water\WaterSiteInspectionsScheduling;
use App\Models\Water\WaterTran;
use App\Models\Workflows\WfActiveDocument;
use App\Models\Workflows\WfRole;
use App\Models\Workflows\WfRoleusermap;
use App\Models\Workflows\WfWorkflow;
use App\Models\Workflows\WfWorkflowrolemap;
use App\Models\WorkflowTrack;
use App\Repository\Common\CommonFunction;
use App\Repository\WorkflowMaster\Concrete\WorkflowMap;
use App\Traits\Ward;
use App\Traits\Water\WaterRequestTrait;
use App\Traits\Water\WaterTrait;
use App\Traits\Workflow\Workflow;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

/**
 * | ----------------------------------------------------------------------------------
 * | Water Module | Consumer Workflow
 * |-----------------------------------------------------------------------------------
 * | Created On- 17-07-2023
 * | Created By- Sam kerketta 
 * | Created For- Water consumer workflow related operations
 */

class WaterConsumerWfController extends Controller
{
    use Ward;
    use Workflow;
    use WaterTrait;
    use WaterRequestTrait;

    private $_waterRoles;
    private $_waterModuleId;
    protected $_DB_NAME;
    protected $_DB;

    public function __construct()
    {
        $this->_waterRoles = Config::get('waterConstaint.ROLE-LABEL');
        $this->_waterModuleId = Config::get('module-constants.WATER_MODULE_ID');
        $this->_DB_NAME = "pgsql_water";
        $this->_DB = DB::connection($this->_DB_NAME);
    }

    /**
     * | Database transaction
     */
    public function begin()
    {
        $db1 = DB::connection()->getDatabaseName();
        $db2 = $this->_DB->getDatabaseName();
        DB::beginTransaction();
        if ($db1 != $db2)
            $this->_DB->beginTransaction();
    }
    /**
     * | Database transaction
     */
    public function rollback()
    {
        $db1 = DB::connection()->getDatabaseName();
        $db2 = $this->_DB->getDatabaseName();
        DB::rollBack();
        if ($db1 != $db2)
            $this->_DB->rollBack();
    }
    /**
     * | Database transaction
     */
    public function commit()
    {
        $db1 = DB::connection()->getDatabaseName();
        $db2 = $this->_DB->getDatabaseName();
        DB::commit();
        if ($db1 != $db2)
            $this->_DB->commit();
    }


    /**
     * | Get details of application for displaying 
        | Serial No :
        | Under Con
     */
    public function getConApplicationDetails(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'applicationId' => 'nullable|integer',
            ]
        );
        if ($validated->fails())
            return validationError($validated);

        try {
            $returDetails = $this->getConActiveAppDetails($request->applicationId)
                ->where('wc.status', 2)
                ->first();
            if (!$returDetails) {
                throw new Exception("Application Details Not found!");
            }
            return responseMsgs(true, "Application Detials!", remove_null($returDetails), '', '01', responseTime(), $request->getMethod(), $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], '', '01', responseTime(), $request->getMethod(), $request->deviceId);
        }
    }

    /**
     * | Get the Citizen applied applications 
     * | Application list according to citizen 
        | Serial No :
        | Under Con
     */
    public function getRequestedApplication(Request $request)
    {
        try {
            $user = authUser($request);
            $mWaterConsumerActiveRequest = new WaterConsumerActiveRequest();
            $refUserType = Config::get('waterConstaint.REF_USER_TYPE');

            # User type changes 
            $detailsDisconnections = $mWaterConsumerActiveRequest->getApplicationByUser($user->id)->get();
            if (!collect($detailsDisconnections)->first()) {
                throw new Exception("Data not found!");
            }
            return responseMsgs(true, "list of disconnection ", remove_null($detailsDisconnections), "", "1.0", "350ms", "POST", $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", $e->getCode(), "1.0", "", 'POST', "");
        }
    }
    /**
     * postnext level water Disconnection
     * 
     */
    // public function consumerPostNextLevel(Request $request)
    // {
    //     $wfLevels = Config::get('waterConstaint.ROLE-LABEL');
    //     $request->validate([
    //         'applicationId'     => 'required',
    //         'senderRoleId'      => 'required',
    //         'receiverRoleId'    => 'required',
    //         'action'            => 'required|In:forward,backward',
    //         'comment'           => $request->senderRoleId == $wfLevels['DA'] ? 'nullable' : 'required',

    //     ]);
    //     try {
    //         return $this->postNextLevelRequest($request);
    //     } catch (Exception $error) {
    //         DB::rollBack();
    //         return responseMsg(false, $error->getMessage(), "");
    //     }
    // }

    /**
     * post next level for water consumer other request 
     */

    // public function postNextLevelRequest($req)
    // {

    //     $mWfWorkflows        = new WfWorkflow();
    //     $mWfRoleMaps         = new WfWorkflowrolemap();

    //     $current             = Carbon::now();
    //     $wfLevels            = Config::get('waterConstaint.ROLE-LABEL');
    //     $waterConsumerActive = WaterConsumerActiveRequest::find($req->applicationId);

    //     # Derivative Assignments
    //     $senderRoleId   = $waterConsumerActive->current_role;
    //     $ulbWorkflowId  = $waterConsumerActive->workflow_id;
    //     $ulbWorkflowMaps = $mWfWorkflows->getWfDetails($ulbWorkflowId);
    //     $roleMapsReqs   = new Request([
    //         'workflowId' => $ulbWorkflowMaps->id,
    //         'roleId' => $senderRoleId
    //     ]);
    //     $forwardBackwardIds = $mWfRoleMaps->getWfBackForwardIds($roleMapsReqs);

    //     DB::beginTransaction();
    //     if ($req->action == 'forward') {
    //         $this->checkRequestPostCondition($req->senderRoleId, $wfLevels, $waterConsumerActive);            // Check Post Next level condition
    //         if ($waterConsumerActive->current_role == $wfLevels['JE']) {
    //             $waterConsumerActive->is_field_verified = true;
    //         }
    //         $metaReqs['verificationStatus'] = 1;
    //         $waterConsumerActive->current_role = $forwardBackwardIds->forward_role_id;
    //         $waterConsumerActive->last_role_id =  $forwardBackwardIds->forward_role_id;                                      // Update Last Role Id

    //     }
    //     if ($req->action == 'backward') {
    //         $waterConsumerActive->current_role = $forwardBackwardIds->backward_role_id;
    //     }

    //     $waterConsumerActive->save();
    //     $metaReqs['moduleId']           =  $this->_waterModuleId;
    //     $metaReqs['workflowId']         = $waterConsumerActive->workflow_id;
    //     $metaReqs['refTableDotId']      = 'water_consumer_active_requests.id';
    //     $metaReqs['refTableIdValue']    = $req->applicationId;
    //     $metaReqs['user_id']            = authUser($req)->id;
    //     $req->request->add($metaReqs);
    //     $waterTrack         = new WorkflowTrack();
    //     $waterTrack->saveTrack($req);

    //     # check in all the cases the data if entered in the track table 
    //     // Updation of Received Date
    //     $preWorkflowReq = [
    //         'workflowId'        => $waterConsumerActive->workflow_id,
    //         'refTableDotId'     => "water_consumer_active_requests.id",
    //         'refTableIdValue'   => $req->applicationId,
    //         'receiverRoleId'    => $senderRoleId
    //     ];

    //     $previousWorkflowTrack = $waterTrack->getWfTrackByRefId($preWorkflowReq);
    //     $previousWorkflowTrack->update([
    //         'forward_date' => $current,
    //         'forward_time' => $current
    //     ]);
    //     DB::commit();
    //     return responseMsgs(true, "Successfully Forwarded The Application!!", "", "", "", '01', '.ms', 'Post', '');
    // }

    /**
     * postnext level water Disconnection
     * 
     */
    public function consumerPostNextLevel(Request $request)
    {
        $wfLevels = Config::get('waterConstaint.ROLE-LABEL');
        $request->validate([
            'applicationId' => 'required',
            'senderRoleId' => 'required',
            'receiverRoleId' => 'required',
            'action' => 'required|In:forward,backward',
            'comment' => $request->senderRoleId == $wfLevels['DA'] ? 'nullable' : 'required',

        ]);
        try {
            return $this->postNextLevelRequest($request);
        } catch (Exception $error) {
            DB::rollBack();
            return responseMsg(false, $error->getMessage(), "");
        }
    }

    /**
     * post next level for water consumer other request 
     */

    public function postNextLevelRequest($req)
    {

        $mWfWorkflows = new WfWorkflow();
        $mWfRoleMaps = new WfWorkflowrolemap();

        $current = Carbon::now();
        $wfLevels = Config::get('waterConstaint.ROLE-LABEL');
        $waterConsumerActive = WaterConsumerActiveRequest::find($req->applicationId);

        # Derivative Assignments
        $senderRoleId = $waterConsumerActive->current_role;
        $ulbWorkflowId = $waterConsumerActive->workflow_id;
        $ulbWorkflowMaps = $mWfWorkflows->getWfDetails($ulbWorkflowId);
        $roleMapsReqs = new Request([
            'workflowId' => $ulbWorkflowMaps->id,
            'roleId' => $senderRoleId
        ]);
        $forwardBackwardIds = $mWfRoleMaps->getWfBackForwardIds($roleMapsReqs);

        DB::beginTransaction();
        if ($req->action == 'forward') {
            $this->checkPostCondition($req->senderRoleId, $wfLevels, $waterConsumerActive);            // Check Post Next level condition
            if ($waterConsumerActive->current_role == $wfLevels['JE']) {
                $waterConsumerActive->is_field_verified = true;
            }
            $metaReqs['verificationStatus'] = 1;
            $waterConsumerActive->current_role = $forwardBackwardIds->forward_role_id;
            $waterConsumerActive->last_role_id = $forwardBackwardIds->forward_role_id;                                      // Update Last Role Id

        }
        if ($req->action == 'backward') {
            $waterConsumerActive->current_role = $forwardBackwardIds->backward_role_id;
        }

        $waterConsumerActive->save();
        $metaReqs['moduleId'] = $this->_waterModuleId;
        $metaReqs['workflowId'] = $waterConsumerActive->workflow_id;
        $metaReqs['refTableDotId'] = 'water_consumer_active_requests.id';
        $metaReqs['refTableIdValue'] = $req->applicationId;
        $metaReqs['user_id'] = authUser($req)->id;
        $req->request->add($metaReqs);
        $waterTrack = new WorkflowTrack();
        $waterTrack->saveTrack($req);

        # check in all the cases the data if entered in the track table 
        // Updation of Received Date
        $preWorkflowReq = [
            'workflowId' => $waterConsumerActive->workflow_id,
            'refTableDotId' => "water_consumer_active_requests.id",
            'refTableIdValue' => $req->applicationId,
            'receiverRoleId' => $senderRoleId
        ];

        $previousWorkflowTrack = $waterTrack->getWfTrackByRefId($preWorkflowReq);
        $previousWorkflowTrack->update([
            'forward_date' => $current,
            'forward_time' => $current
        ]);
        DB::commit();
        return responseMsgs(true, "Successfully Forwarded The Application!!", "", "", "", '01', '.ms', 'Post', '');
    }

    public function checkPostCondition($senderRoleId, $wfLevels, $application)
    {
        $mWaterSiteInspection = new WaterSiteInspection();

        $refRole = Config::get("waterConstaint.ROLE-LABEL");
        switch ($senderRoleId) {
            case $wfLevels['DA']:                                                                       // DA Condition
                if ($application->payment_status != 1)
                    throw new Exception("payment Not Fully paid");
                break;
            case $wfLevels['JE']:                                                                       // JE Coditon in case of site adjustment
                // if ($application->doc_status == false || $application->payment_status != 1)
                //     throw new Exception("Document Not Fully Verified or Payment in not Done!");
                if ($application->doc_upload_status == false) {
                    throw new Exception("Document Not Fully Uploaded");
                }
                if ($application->is_field_verified == false) {
                    throw new Exception("Field Verification Failed");
                }
                if ($application->je_doc_upload_status == false) {
                    throw new Exception("Report Not Submitted");
                }
                // $siteDetails = $mWaterSiteInspection->getSiteDetails($application->id)
                //     ->where('order_officer', $refRole['JE'])
                //     ->where('payment_status', 1)
                //     ->first();
                // if (!$siteDetails) {
                //     throw new Exception("Site Not Verified!");
                // }
                break;
            case $wfLevels['SH']:                                                                       // SH conditional checking
                // if ($application->doc_status == false || $application->payment_status != 1)
                if ($application->je_doc_upload_status == false || $application->payment_status != 1)
                    throw new Exception("Document Not Fully Verified or Payment in not Done!");
                if ($application->doc_upload_status == false || $application->is_field_verified == false) {
                    throw new Exception("Document Not Fully Uploaded or site inspection not done!");
                }
                break;
            case $wfLevels['AE']:                                                                       // AE conditional checking
                if ($application->payment_status != 1)
                    throw new Exception(" Payment in not Done!");

                break;
        }
    }


    public function checkRequestPostCondition($senderRoleId, $wfLevels, $application)
    {
        $mWaterSiteInspection = new WaterSiteInspection();

        $refRole = Config::get("waterConstaint.ROLE-LABEL");
        switch ($senderRoleId) {
            case $wfLevels['BO']:                                                                       // DA Condition
                if ($application->doc_upload_status != true)
                    throw new Exception("document not fully uploaded");
                break;
            case $wfLevels['DA']:                                                                       // DA Condition
                if ($application->doc_verify_status != true)
                    throw new Exception("document not fully verified");
                break;
            case $wfLevels['JE']:                                                                       // JE Coditon in case of site adjustment
                if ($application->doc_status == false || $application->payment_status != 1)
                    throw new Exception("Document Not Fully Verified or Payment in not Done!");
                if ($application->doc_upload_status == false) {
                    throw new Exception("Document Not Fully Uploaded");
                }
                $siteDetails = $mWaterSiteInspection->getSiteDetails($application->id)
                    ->where('order_officer', $refRole['JE'])
                    ->where('payment_status', 1)
                    ->first();
                if (!$siteDetails) {
                    throw new Exception("Site Not Verified!");
                }
                break;
            case $wfLevels['SH']:                                                                       // SH conditional checking
                if ($application->doc_status == false || $application->payment_status != 1)
                    throw new Exception("Document Not Fully Verified or Payment in not Done!");
                if ($application->doc_upload_status == false || $application->is_field_verified == false) {
                    throw new Exception("Document Not Fully Uploaded or site inspection not done!");
                }
                break;
            case $wfLevels['AE']:                                                                       // AE conditional checking
                if ($application->payment_status != 1)
                    throw new Exception(" Payment in not Done!");

                break;
        }
    }
    /**
     * water disconnection approval or reject 
     */
    // public function consumerApprovalRejection(Request $request)
    // {
    //     $request->validate([
    //         "applicationId" => "required",
    //         "status"        => "required",
    //         "comment"       => "required"
    //     ]);
    //     try {
    //         $mWfRoleUsermap = new WfRoleusermap();
    //         $waterDetails = WaterConsumerActiveRequest::find($request->applicationId);

    //         # check the login user is AE or not
    //         $userId = authUser($request)->id;
    //         $workflowId = $waterDetails->workflow_id;
    //         $getRoleReq = new Request([                                                 // make request to get role id of the user
    //             'userId' => $userId,
    //             'workflowId' => $workflowId
    //         ]);
    //         $readRoleDtls = $mWfRoleUsermap->getRoleByUserWfId($getRoleReq);
    //         $roleId = $readRoleDtls->wf_role_id;
    //         if ($roleId != $waterDetails->finisher) {
    //             throw new Exception("You are not the Finisher!");
    //         }
    //         DB::beginTransaction();
    //         $this->approvalRejectionWater($request, $roleId);
    //         DB::commit();
    //         return responseMsg(true, "Request approved/rejected successfully", "");;
    //     } catch (Exception $e) {
    //         // DB::rollBack();
    //         return responseMsg(false, $e->getMessage(), "");
    //     }
    // }
    // public function approvalRejectionWater($request, $roleId)
    // {

    //     $mWaterConsumerActive  =  new WaterConsumerActiveRequest();
    //     $this->preApprovalConditionCheck($request, $roleId);

    //     # Approval of water application 
    //     if ($request->status == 1) {

    //         $mWaterConsumerActive->finalApproval($request);
    //         $msg = "Application Successfully Approved !!";
    //     }
    //     # Rejection of water application
    //     if ($request->status == 0) {
    //         $mWaterConsumerActive->finalRejectionOfAppication($request);
    //         $msg = "Application Successfully Rejected !!";
    //     }


    //     return responseMsgs(true, $msg, $request ?? "Empty", '', 01, '.ms', 'Post', $request->deviceId);
    // }


    /**
     * get all applications details by id from workflow
     |working ,not completed
     */
    public function getWorkflow(Request $request)
    {

        $request->validate([
            'applicationId' => "required"

        ]);

        try {
            return $this->getApplicationsDetails($request);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }
    public function getApplicationsDetails($request)
    {

        $forwardBackward = new WorkflowMap();
        $mWorkflowTracks = new WorkflowTrack();
        $mCustomDetails = new CustomDetail();
        $mUlbNewWardmap = new UlbWardMaster();
        $mwaterConsumerActive = new WaterConsumerActiveRequest();
        $mwaterOwner = new WaterConsumerOwner();
        # applicatin details
        $applicationDetails = $mwaterConsumerActive->fullWaterDetails($request)->get();
        if (collect($applicationDetails)->first() == null) {
            return responseMsg(false, "Application Data Not found!", $request->applicationId);
        }
        # Ward Name
        $refApplication = collect($applicationDetails)->first();
        $wardDetails = $mUlbNewWardmap->getWard($refApplication->ward_mstr_id);
        # owner Details
        $ownerDetails = $mwaterOwner->ownerByApplication($refApplication->consumer_id)->get();
        $ownerDetail = collect($ownerDetails)->map(function ($value, $key) {
            return $value;
        });
        $aplictionList = [
            'application_no' => $refApplication->application_no,
            'apply_date' => $refApplication->apply_date
        ];


        # DataArray
        $basicDetails = $this->getBasicDetails($applicationDetails);

        $firstView = [
            'headerTitle' => 'Basic Details',
            'data' => $basicDetails
        ];
        $fullDetailsData['fullDetailsData']['dataArray'] = new Collection([$firstView]);
        # CardArray
        $cardDetails = $this->getCardDetails($applicationDetails, $ownerDetail);
        $cardData = [
            'headerTitle' => 'Water Disconnection',
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
        $mRefTable = "water_consumer_active_requests.id";
        $levelComment['levelComment'] = $mWorkflowTracks->getTracksByRefId($mRefTable, $mtableId);

        #citizen comment
        $refCitizenId = $applicationDetails->first()->citizen_id;
        $citizenComment['citizenComment'] = $mWorkflowTracks->getCitizenTracks($mRefTable, $mtableId, $refCitizenId);

        # Role Details
        $data = json_decode(json_encode($applicationDetails->first()), true);
        $metaReqs = [
            'customFor' => 'Water Disconnection',
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
        # Payments Details
        $returnValues = array_merge($aplictionList, $fullDetailsData, $levelComment, $citizenComment, $roleDetails, $timelineData, $departmentPost);
        return responseMsgs(true, "listed Data!", remove_null($returnValues), "", "02", ".ms", "POST", "");
    }
    /**
     * function for return data of basic details
     */
    public function getBasicDetails($applicationDetails)
    {
        $collectionApplications = collect($applicationDetails)->first();
        return new Collection([
            ['displayString' => 'Ward No', 'key' => 'WardNo', 'value' => $collectionApplications->ward_name],
            ['displayString' => 'Charge Category', 'key' => 'chargeCategory', 'value' => $collectionApplications->charge_category],
            ['displayString' => 'Ubl Id', 'key' => 'ulbId', 'value' => $collectionApplications->ulb_id],
            ['displayString' => 'ApplyDate', 'key' => 'applyDate', 'value' => $collectionApplications->apply_date],
        ]);
    }
    /**
     * return data fro card details 
     */
    public function getCardDetails($applicationDetails, $ownerDetail)
    {
        $ownerName = collect($ownerDetail)->map(function ($value) {
            return $value['owner_name'];
        });
        $ownerDetail = $ownerName->implode(',');
        $collectionApplications = collect($applicationDetails)->first();
        return new Collection([
            ['displayString' => 'Ward No.', 'key' => 'WardNo.', 'value' => $collectionApplications->ward_name],
            ['displayString' => 'Application No.', 'key' => 'ApplicationNo.', 'value' => $collectionApplications->application_no],
            ['displayString' => 'Owner Name', 'key' => 'OwnerName', 'value' => $ownerDetail],
            ['displayString' => 'Charge Category', 'key' => 'ChageCategory', 'value' => $collectionApplications->charge_category],


        ]);
    }
    /**
     * return data of consumer owner data on behalf of disconnection 
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

    //written by prity pandey

    public function consumerInbox(Request $req)
    {
        // $validated = Validator::make(
        //     $req->all(),
        //     [
        //         'perPage' => 'nullable|integer',
        //     ]
        // );
        // if ($validated->fails())
        //     return validationError($validated);

        try {
            $user = authUser($req);
            $pages = $req->perPage ?? 10;
            $userId = $user->id;
            $ulbId = $user->ulb_id;
            $mWfWorkflowRoleMaps = new WfWorkflowrolemap();
            $commonFunction = new CommonFunction();

            $occupiedWards = $this->getWardByUserId($userId)->pluck('ward_id');
            $roleId = $this->getRoleIdByUserId($userId)->pluck('wf_role_id');
            $workflowIds = $mWfWorkflowRoleMaps->getWfByRoleId($roleId)->pluck('workflow_id');
            $refWorkflow = Config::get('workflow-constants.WATER_DISCONNECTION');
            //$roleId[] = $commonFunction->getUserRoll($userId,$ulbId,$refWorkflow)->role_id;

            $inboxDetails = $this->getConsumerWfBaseQuerry($workflowIds, $ulbId)
                ->whereIn('water_consumer_active_requests.current_role', $roleId)
                // ->whereIn('water_consumer_active_requests.ward_mstr_id', $occupiedWards)
                ->where('water_consumer_active_requests.is_escalate', false)
                ->where('water_consumer_active_requests.parked', false)
                ->orderByDesc('water_consumer_active_requests.id')
                ->get();
            //->paginate($pages);

            // $list = [
            //     "current_page" => $inboxDetails->currentPage(),
            //     "last_page" => $inboxDetails->lastPage(),
            //     "data" => $inboxDetails->items(),
            //     "total" => $inboxDetails->total(),
            // ]; 
            // return responseMsgs(true, "List of Appication!", $list, "", "01", "723 ms", "POST", "");

            return responseMsgs(true, "Successfully listed consumer req inbox details!", $inboxDetails, "", "01", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], '', '01', responseTime(), "POST", $req->deviceId);
        }
    }


    public function consumerOutbox(Request $req)
    {
        // $validated = Validator::make(
        //     $req->all(),
        //     [
        //         'perPage' => 'nullable|integer',
        //     ]
        // );
        // if ($validated->fails())
        //     return validationError($validated);

        try {
            $user = authUser($req);
            $pages = $req->perPage ?? 10;
            $userId = $user->id;
            $ulbId = $user->ulb_id;
            $mWfWorkflowRoleMaps = new WfWorkflowrolemap();
            $commonFunction = new CommonFunction();

            $occupiedWards = $this->getWardByUserId($userId)->pluck('ward_id');
            $roleId = $this->getRoleIdByUserId($userId)->pluck('wf_role_id');
            $workflowIds = $mWfWorkflowRoleMaps->getWfByRoleId($roleId)->pluck('workflow_id');
            $refWorkflow = Config::get('workflow-constants.WATER_DISCONNECTION');
            // $roleId[] = $commonFunction->getUserRoll($userId, $ulbId, $refWorkflow)->role_id;

            $inboxDetails = $this->getConsumerWfBaseQuerry($workflowIds, $ulbId)
                ->whereNotIn('water_consumer_active_requests.current_role', $roleId)
                ->whereIn('water_consumer_active_requests.ward_mstr_id', $occupiedWards)
                ->where('water_consumer_active_requests.is_escalate', false)
                ->where('water_consumer_active_requests.parked', false)
                ->orderByDesc('water_consumer_active_requests.id')
                ->get();
            //->paginate($pages);

            // $list = [
            //     "current_page" => $inboxDetails->currentPage(),
            //     "last_page" => $inboxDetails->lastPage(),
            //     "data" => $inboxDetails->items(),
            //     "total" => $inboxDetails->total(),
            // ];
            // return responseMsgs(true, "List of Appication!", $list, "", "01", "723 ms", "POST", "");

            return responseMsgs(true, "Successfully listed consumer req inbox details!", $inboxDetails, "", "01", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], '', '01', responseTime(), "POST", $req->deviceId);
        }
    }

    public function specialInbox(Request $req)
    {
        // $validated = Validator::make(
        //     $req->all(),
        //     [
        //         'perPage' => 'nullable|integer',
        //     ]
        // );
        // if ($validated->fails())
        //     return validationError($validated);

        try {
            $user = authUser($req);
            $pages = $req->perPage ?? 10;
            $userId = $user->id;
            $ulbId = $user->ulb_id;
            $mWfWorkflowRoleMaps = new WfWorkflowrolemap();
            $commonFunction = new CommonFunction();

            $occupiedWards = $this->getWardByUserId($userId)->pluck('ward_id');
            $roleId = $this->getRoleIdByUserId($userId)->pluck('wf_role_id');
            $workflowIds = $mWfWorkflowRoleMaps->getWfByRoleId($roleId)->pluck('workflow_id');
            $refWorkflow = Config::get('workflow-constants.WATER_DISCONNECTION');
            // $roleId[] = $commonFunction->getUserRoll($userId, $ulbId, $refWorkflow)->role_id;

            $inboxDetails = $this->getConsumerWfBaseQuerry($workflowIds, $ulbId)
                ->whereIn('water_consumer_active_requests.current_role', $roleId)
                ->whereIn('water_consumer_active_requests.ward_mstr_id', $occupiedWards)
                ->where('water_consumer_active_requests.is_escalate', true)
                ->where('water_consumer_active_requests.parked', false)
                ->orderByDesc('water_consumer_active_requests.id')
                ->get();
            //->paginate($pages);

            // $list = [
            //     "current_page" => $inboxDetails->currentPage(),
            //     "last_page" => $inboxDetails->lastPage(),
            //     "data" => $inboxDetails->items(),
            //     "total" => $inboxDetails->total(),
            // ];
            // return responseMsgs(true, "List of Appication!", $list, "", "01", "723 ms", "POST", "");

            return responseMsgs(true, "Successfully listed consumer req inbox details!", $inboxDetails, "", "01", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], '', '01', responseTime(), "POST", $req->deviceId);
        }
    }

    public function btcInbox(Request $req)
    {
        // $validated = Validator::make(
        //     $req->all(),
        //     [
        //         'perPage' => 'nullable|integer',
        //     ]
        // );
        // if ($validated->fails())
        //     return validationError($validated);

        try {
            $user = authUser($req);
            $pages = $req->perPage ?? 10;
            $userId = $user->id;
            $ulbId = $user->ulb_id;
            $mWfWorkflowRoleMaps = new WfWorkflowrolemap();
            $commonFunction = new CommonFunction();

            $occupiedWards = $this->getWardByUserId($userId)->pluck('ward_id');
            $roleId = $this->getRoleIdByUserId($userId)->pluck('wf_role_id');
            $workflowIds = $mWfWorkflowRoleMaps->getWfByRoleId($roleId)->pluck('workflow_id');
            $refWorkflow = Config::get('workflow-constants.WATER_DISCONNECTION');
            // $roleId[] = $commonFunction->getUserRoll($userId, $ulbId, $refWorkflow)->role_id;

            $inboxDetails = $this->getConsumerWfBaseQuerry($workflowIds, $ulbId)
                ->whereIn('water_consumer_active_requests.current_role', $roleId)
                ->whereIn('water_consumer_active_requests.ward_mstr_id', $occupiedWards)
                ->where('water_consumer_active_requests.is_escalate', false)
                ->where('water_consumer_active_requests.parked', TRUE)
                ->orderByDesc('water_consumer_active_requests.id')
                ->get();
            //->paginate($pages);

            // $list = [
            //     "current_page" => $inboxDetails->currentPage(),
            //     "last_page" => $inboxDetails->lastPage(),
            //     "data" => $inboxDetails->items(),
            //     "total" => $inboxDetails->total(),
            // ];
            // return responseMsgs(true, "List of Appication!", $list, "", "01", "723 ms", "POST", "");

            return responseMsgs(true, "Successfully listed consumer req inbox details!", $inboxDetails, "", "01", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], '', '01', responseTime(), "POST", $req->deviceId);
        }
    }

    public function getConsumerDetails(ReqApplicationId $request)
    {
        try {
            $users = Auth()->user();
            $refUserId = $users->id;
            $refUlbId = $users->ulb_id;
            $forwardBackward = new WorkflowMap();
            $mWorkflowTracks = new WorkflowTrack();
            $mCustomDetails = new CustomDetail();
            $mUlbNewWardmap = new UlbWardMaster();
            $mwaterConsumerActive = new WaterConsumerActiveRequest();
            $mwaterOwner = new WaterConsumerOwner();
            $refConsumerCharges = Collect(Config::get('waterConstaint.CONSUMER_CHARGE_CATAGORY'))->flip();
            $_MODEL_CustomDetail = new CustomDetail();
            $_MODEL_WorkflowTrack = new WorkflowTrack();
            $_MODEL_WorkflowMap = new WorkflowMap();
            $mRefTable = "water_consumer_active_requests.id";
            $refWorkflowId = Config::get('workflow-constants.WATER_DISCONNECTION');
            $_COMMON_FUNCTION = new CommonFunction();
            # applicatin details
            // $applicationDetails = $mwaterConsumerActive->fullWaterDisconnection($request)->get();
            // if (collect($applicationDetails)->first() == null) {
            //     return responseMsg(false, "Application Data Not found!", $request->applicationId);
            // }

            #----------------------------------------------
            // $data = WaterConsumerActiveRequest::find($request->applicationId);

            $data = $mwaterConsumerActive->getConsumerAllDetails($request->applicationId);

            // dd($data,$request->all(),DB::connection("pgsql_water")->getQueryLog());
            if (!$data) {
                throw new Exception("Data not found");
            }

            $role = $_COMMON_FUNCTION->getUserRoll($refUserId, $refUlbId, $refWorkflowId);
            $data->application_type = $refConsumerCharges[$data->charge_catagory_id] ?? "";
            $consumerDetails = $data->getConserDtls();


            $applicantDetals = $consumerDetails->getWaterApplication();
            $wards = UlbWardMaster::where("id", $consumerDetails->ward_mstr_id)->first();
            $consumerDetails->wrad_no = $wards->ward_name ?? null;
            $consumerDetails->property_type = $consumerDetails->getPropType()->property_type ?? null;
            $consumerDetails->pipelien_type = $consumerDetails->getPipelineType()->pipelien_type ?? null;

            $consumerDetails = $consumerDetails;
            $owners = $consumerDetails->getOwners() ?? new Collection();

            $wards = UlbWardMaster::where("id", $data->ward_mstr_id)->first();
            $ulb = ModelsUlbMaster::where("id", $data->ulb_id)->first();
            $data->ward_no = $wards->ward_name ?? null;
            $data->ulb_name = $ulb->ulb_name ?? null;
            $data->apply_date = Carbon::parse($data->apply_date)->format("d-m-Y ");

            $cardDetails = $this->generateCardDetails($data, $owners);
            $cardElement = [
                'headerTitle' => "About Request",
                'data' => $cardDetails
            ];
            # DataArray
            $basicDetails = $this->generateBasicDetails($data);

            $basicElement = [
                'headerTitle' => 'Basic Details',
                'data' => $basicDetails
            ];

            $propDetails = $this->generatePropertyDetails($data);
            $propElement = [
                'headerTitle' => 'Property Details',
                'data' => $propDetails
            ];

            $fullDetailsData = array();

            $ownerDetailsTable = $this->generateOwnerDetails($owners);
            $ownerElement = [
                'headerTitle' => 'Owner Details',
                'tableHead' => ["#", "Owner Name", "Guardian Name", "Mobile No", "Email",],
                'tableData' => $ownerDetailsTable
            ];
            $fullDetailsData["water_application_id"] = $applicantDetals->id ?? 0;
            $fullDetailsData["propId"] = $consumerDetails->property_id;
            $fullDetailsData["workflowId"] = $data->workflow_id;
            $fullDetailsData['application_no'] = $data->application_no;
            $fullDetailsData['apply_date'] = $data->application_date;
            $fullDetailsData['fullDetailsData']['dataArray'] = new Collection([$basicElement, $propElement]);
            $fullDetailsData['fullDetailsData']['tableArray'] = new Collection([$ownerElement]);
            $fullDetailsData['fullDetailsData']['cardArray'] = $cardElement;

            $metaReqs['customFor'] = 'Trade';
            $metaReqs['wfRoleId'] = ($role && $role->is_initiator && $data->is_parked) ? $role->role_id : $data->current_role;
            $metaReqs['workflowId'] = $data->workflow_id;
            $metaReqs['lastRoleId'] = $data->last_role_id;
            $levelComment = $_MODEL_WorkflowTrack->getTracksByRefId($mRefTable, $data->id)->map(function ($val) {
                $val->forward_date = $val->forward_date ? Carbon::parse($val->forward_date)->format("d-m-Y") : "";
                $val->track_date = $val->track_date ? Carbon::parse($val->track_date)->format("d-m-Y") : "";
                $val->duration = (Carbon::parse($val->forward_date)->diffInDays(Carbon::parse($val->track_date))) . " Days";
                return $val;
            });
            $fullDetailsData['levelComment'] = $levelComment;

            $citizenComment = $_MODEL_WorkflowTrack->getCitizenTracks($mRefTable, $data->id, $data->user_id);
            $fullDetailsData['citizenComment'] = $citizenComment;

            $request->merge($metaReqs);

            $forwardBackward = $_MODEL_WorkflowMap->getRoleDetails($request);
            $fullDetailsData['roleDetails'] = collect($forwardBackward)['original']['data'];

            $fullDetailsData['timelineData'] = collect($request);

            $custom = $_MODEL_CustomDetail->getCustomDetails($request);
            $fullDetailsData['departmentalPost'] = collect($custom)['original']['data'];

            return responseMsgs(true, 'Data Fetched', remove_null($fullDetailsData), "010104", "1.0", "303ms", "POST", $request->deviceId);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    public function getRequestDocLists($application)
    {
        $mRefReqDocs = new RefRequiredDocument();
        $mWaterApplication = new WaterConsumerActiveRequest();
        $refWaterApplication = $application; #$mWaterApplication->getApplicationById($application)->first();

        $moduleId = Config::get('module-constants.WATER_MODULE_ID');
        $documentList = $mRefReqDocs->getDocsByDocCode($moduleId, "DISCONNECTION_APPLICATION_FORM")->requirements;

        // if (!$refWaterApplication->citizen_id)         // Holding No, SAF No // Static
        // {
        //     $documentList .= $mRefReqDocs->getDocsByDocCode($moduleId, "DISCONNECTION_APPLICATION_FORM")->requirements;
        // }
        $documentList = $this->filterDocument($documentList, $application);
        return $documentList;
    }

    public function filterDocument($documentList, $refApplication, $ownerId = null)
    {
        $mWfActiveDocument = new WfActiveDocument();
        $applicationId = $refApplication->id;
        $workflowId = $refApplication->workflow_id;
        $moduleId = Config::get('module-constants.WATER_MODULE_ID');
        DB::connection("pgsql_master");
        $uploadedDocs = $mWfActiveDocument->getDocByRefIds($applicationId, $workflowId, $moduleId);
        // dd($applicationId,$workflowId,$moduleId,DB::connection("pgsql_master")->getQueryLog());
        $explodeDocs = collect(explode('#', $documentList))->filter();

        $filteredDocs = $explodeDocs->map(function ($explodeDoc) use ($uploadedDocs, $ownerId) {
            $document = explode(',', $explodeDoc);
            $key = array_shift($document);
            $docName = array_shift($document);
            $docName = str_replace("{", "", str_replace("}", "", $docName));
            $documents = collect();
            collect($document)->map(function ($item) use ($uploadedDocs, $documents, $ownerId, $docName) {
                $docUpload = new DocUpload();
                $uploadedDoc = $uploadedDocs->where('doc_category', $docName)
                    ->where('owner_dtl_id', $ownerId)
                    ->first();
                if ($uploadedDoc) {
                    // $uploadedDoc->reference_no = "REF1694609691049";
                    $api = $docUpload->getSingleDocUrl($uploadedDoc);
                    $response = [
                        "api" => $api ?? "",
                        "uploadedDocId" => $uploadedDoc->id ?? "",
                        "documentCode" => $item,
                        "ownerId" => $uploadedDoc->owner_dtl_id ?? "",
                        // "docPath" => $uploadedDoc->doc_path ?? "",
                        "docPath" => $api["doc_path"] ?? "",
                        "verifyStatus" => $uploadedDoc->verify_status ?? "",
                        "remarks" => $uploadedDoc->remarks ?? "",
                    ];
                    $documents->push($response);
                }
            });
            $reqDoc['docType'] = $key;
            $reqDoc['docName'] = $docName;
            $reqDoc['uploadedDoc'] = $documents->first();

            $reqDoc['masters'] = collect($document)->map(function ($doc) use ($uploadedDocs) {
                $uploadedDoc = $uploadedDocs->where('doc_code', $doc)->first();
                $strLower = strtolower($doc);
                $strReplace = str_replace('_', ' ', $strLower);
                $arr = [
                    "documentCode" => $doc,
                    "docVal" => ucwords($strReplace),
                    "uploadedDoc" => $uploadedDoc->doc_path ?? "",
                    "uploadedDocId" => $uploadedDoc->id ?? "",
                    "verifyStatus'" => $uploadedDoc->verify_status ?? "",
                    "remarks" => $uploadedDoc->remarks ?? "",
                ];
                return $arr;
            });
            return $reqDoc;
        });
        return collect($filteredDocs)->values() ?? [];
    }

    public function getDocList(Request $req)
    {
        $validated = Validator::make(
            $req->all(),
            [
                'applicationId' => 'required|numeric'
            ]
        );
        if ($validated->fails())
            return validationError($validated);

        try {
            $mWaterApplication = new WaterConsumerActiveRequest();

            $refWaterApplication = $mWaterApplication->getApplicationById($req->applicationId)->first();                      // Get Saf Details
            if (!$refWaterApplication) {
                throw new Exception("Application Not Found for this id");
            }
            $documentList = $this->getRequestDocLists($refWaterApplication);
            $totalDocLists['listDocs'] = $documentList;
            //$totalDocLists['docList'] = $documentList;
            $totalDocLists['docUploadStatus'] = $refWaterApplication->doc_upload_status;
            $totalDocLists['docVerifyStatus'] = $refWaterApplication->doc_status;
            return responseMsgs(true, "", remove_null($totalDocLists), "010203", "", "", 'POST', "");
        } catch (Exception $e) {
            return responseMsgs(false, [$e->getMessage(), $e->getFile(), $e->getLine()], "", "010203", "1.0", "", 'POST', "");
        }
    }
    public function checkParamForDocUpload($isCitizen, $applicantDetals, $user)
    {
        $refWorkFlowMaster = Config::get('workflow-constants.WATER_MASTER_ID');
        switch ($isCitizen) {
            # For citizen 
            case (true):
                if (!is_null($applicantDetals->current_role) && $applicantDetals->parked == true) {
                    return true;
                }
                if (!is_null($applicantDetals->current_role)) {
                    throw new Exception("You aren't allowed to upload document!");
                }
                break;
            # For user
            case (false):
                $userId = $user->id;
                $ulbId = $applicantDetals->ulb_id;
                $role = $this->getUserRoll($userId, $ulbId, $refWorkFlowMaster);
                if (is_null($role)) {
                    throw new Exception("You dont have any role!");
                }
                if ($role->can_upload_document != true) {
                    throw new Exception("You dont have permission to upload Document!");
                }
                break;
        }
    }
    public function checkParamForDocUploadv1($isCitizen, $applicantDetals, $user)
    {
        $refWorkFlowMaster = Config::get('workflow-constants.WATER_MASTER_DIS_ID');
        // switch ($isCitizen) {
        //         # For citizen 
        //     case (true):
        //         if (!is_null($applicantDetals->current_role) && $applicantDetals->parked == true) {
        //             return true;
        //         }
        //         if (!is_null($applicantDetals->current_role)) {
        //             throw new Exception("You aren't allowed to upload document!");
        //         }
        //         break;
        //         # For user
        //     case (false):
        //         $userId = $user->id;
        //         $ulbId = $applicantDetals->ulb_id;
        //         $role = $this->getUserRoll($userId, $ulbId, $refWorkFlowMaster);
        //         if (is_null($role)) {
        //             throw new Exception("You dont have any role!");
        //         }
        //         if ($role->can_upload_document != true) {
        //             throw new Exception("You dont have permission to upload Document!");
        //         }
        //         break;
        // }
    }

    public function uploadDocuments(Request $req)
    {
        $validated = Validator::make(
            $req->all(),
            [
                "applicationId" => "required|numeric",
                "document" => "required|mimes:pdf,jpeg,png,jpg|max:2048",
                "docCode" => "required",
                "docCategory" => "required",
                "ownerId" => "nullable|numeric"
            ]
        );
        if ($validated->fails())
            return validationError($validated);

        try {
            $user = Auth()->user();
            $metaReqs = array();
            $applicationId = $req->applicationId;
            $docUpload = new DocUpload;
            $mWfActiveDocument = new WfActiveDocument();
            $mWaterApplication = new WaterConsumerActiveRequest();
            $relativePath = Config::get('waterConstaint.WATER_RELATIVE_PATH');
            $refmoduleId = Config::get('module-constants.WATER_MODULE_ID');

            $getWaterDetails = $mWaterApplication->getConsumerByApplication($applicationId)->first();
            if ($getWaterDetails) {
                $refImageName = $req->docRefName;
                $refImageName = $getWaterDetails->id . '-' . str_replace(' ', '_', $refImageName);
            }
            $docDetail = $docUpload->checkDoc($req);
            $metaReqs = [
                'moduleId' => $refmoduleId,
                'activeId' => $applicationId,
                'workflowId' => $getWaterDetails->workflow_id,
                'ulbId' => $getWaterDetails->ulb_id,
                'relativePath' => $relativePath,
                'docCode' => $req->docCode,
                'ownerDtlId' => $req->ownerId,
                'docCategory' => $req->docCategory,
                'auth' => $user,
                'uniqueId' => $docDetail['data']['uniqueId'],
                'referenceNo' => $docDetail['data']['ReferenceNo'],

            ];

            if ($user->user_type == "Citizen") {                                                // Static
                $isCitizen = true;
                $this->checkParamForDocUpload($isCitizen, $getWaterDetails, $user);
            } else {
                $isCitizen = false;
                $this->checkParamForDocUpload($isCitizen, $getWaterDetails, $user);
            }

            $this->begin();
            if ($getWaterDetails->parked != true) {
                $ifDocExist = $mWfActiveDocument->isDocCategoryExists($getWaterDetails->id, $getWaterDetails->workflow_id, $refmoduleId, $req->docCategory, $req->ownerId)->first();   // Checking if the document is already existing or not
                $metaReqs = new Request($metaReqs);
                if (collect($ifDocExist)->isEmpty()) {
                    $mWfActiveDocument->postDocuments($metaReqs);
                }
                if (collect($ifDocExist)->isNotEmpty()) {
                    $mWfActiveDocument->editDocuments($ifDocExist, $metaReqs);
                }
            }
            # if the application is parked and btc 
            if ($getWaterDetails->parked == true) {
                # check the doc Existence for updation and post
                $metaReqs = new Request($metaReqs);
                $mWfActiveDocument->postDocuments($metaReqs);
                $mWfActiveDocument->deactivateRejectedDoc($metaReqs);
                $refReq = new Request([
                    'applicationId' => $applicationId
                ]);
                $documentList = $this->getUploadDocuments($refReq);
                $DocList = collect($documentList)['original']['data'];
                $refVerifyStatus = $DocList->where('doc_category', '!=', $req->docCategory)->pluck('verify_status');
                if (!in_array(2, $refVerifyStatus->toArray())) {                                    // Static "2"
                    $status = false;
                    $mWaterApplication->updateParkedstatus($status, $applicationId);
                }
            }
            // #check full doc upload
            $refCheckDocument = $this->checkFullDocUpload($applicationId);

            # Update the Doc Upload Satus in Application Table

            if ($refCheckDocument == 1) {                                        // Doc Upload Status Update
                $getWaterDetails->doc_upload_status = 1;
                if ($getWaterDetails->parked == true)                                // Case of Back to Citizen
                    $getWaterDetails->parked = false;

                $getWaterDetails->save();
            }

            $this->commit();
            return responseMsgs(true, "Document Uploadation Successful", "", "", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            $this->rollback();
            return responseMsgs(false, $e->getMessage(), $e->getFile(), "", "", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    public function getUserRoll($user_id, $ulb_id, $workflow_id)
    {
        try {
            DB::enableQueryLog();
            $data = WfRole::select(
                DB::raw(
                    "wf_roles.id as role_id,wf_roles.role_name,
                    wf_workflowrolemaps.is_initiator, wf_workflowrolemaps.is_finisher,
                    wf_workflowrolemaps.forward_role_id,forword.role_name as forword_name,
                    wf_workflowrolemaps.backward_role_id,backword.role_name as backword_name,
                    wf_workflowrolemaps.allow_full_list,wf_workflowrolemaps.can_escalate,
                    wf_workflowrolemaps.serial_no,wf_workflowrolemaps.is_btc,
                    wf_workflowrolemaps.can_upload_document,
                    wf_workflowrolemaps.can_verify_document,
                    wf_workflowrolemaps.can_backward,
                    wf_workflowrolemaps.can_edit,
                    wf_workflows.id as workflow_id,wf_masters.workflow_name,
                    ulb_masters.id as ulb_id, ulb_masters.ulb_name,
                    ulb_masters.ulb_type"
                )
            )
                ->join("wf_roleusermaps", function ($join) {
                    $join->on("wf_roleusermaps.wf_role_id", "=", "wf_roles.id")
                        ->where("wf_roleusermaps.is_suspended", "=", FALSE);
                })
                ->join("users", "users.id", "=", "wf_roleusermaps.user_id")
                ->join("wf_workflowrolemaps", function ($join) {
                    $join->on("wf_workflowrolemaps.wf_role_id", "=", "wf_roleusermaps.wf_role_id")
                        ->where("wf_workflowrolemaps.is_suspended", "=", FALSE);
                })
                ->leftjoin("wf_roles AS forword", "forword.id", "=", "wf_workflowrolemaps.forward_role_id")
                ->leftjoin("wf_roles AS backword", "backword.id", "=", "wf_workflowrolemaps.backward_role_id")
                ->join("wf_workflows", function ($join) {
                    $join->on("wf_workflows.id", "=", "wf_workflowrolemaps.workflow_id")
                        ->where("wf_workflows.is_suspended", "=", FALSE);
                })
                ->join("wf_masters", function ($join) {
                    $join->on("wf_masters.id", "=", "wf_workflows.wf_master_id")
                        ->where("wf_masters.is_suspended", "=", FALSE);
                })
                ->join("ulb_masters", "ulb_masters.id", "=", "wf_workflows.ulb_id")
                ->where("wf_roles.is_suspended", false)
                ->where("wf_roleusermaps.user_id", $user_id)
                ->where("wf_workflows.ulb_id", $ulb_id)
                ->where("wf_workflows.wf_master_id", $workflow_id)
                ->orderBy("wf_roleusermaps.id", "desc")
                ->first();
            return $data;
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    public function getUploadDocuments(Request $req)
    {
        $validated = Validator::make(
            $req->all(),
            [
                'applicationId' => 'required|numeric'
            ]
        );
        if ($validated->fails())
            return validationError($validated);

        try {
            $mWfActiveDocument = new WfActiveDocument();
            $mWaterApplication = new WaterConsumerActiveRequest();
            $docUpload = new DocUpload;
            $moduleId = Config::get('module-constants.WATER_MODULE_ID');

            $waterDetails = $mWaterApplication->getApplicationById($req->applicationId)->first();
            if (!$waterDetails)
                throw new Exception("Application Not Found for this application Id");

            $workflowId = $waterDetails->workflow_id;
            $documents = $mWfActiveDocument->getConsumerDocsByAppNo($req->applicationId, $workflowId, $moduleId);
            $data = $docUpload->getDocUrl($documents);           #_Calling BLL for Document Path from DMS

            return responseMsgs(true, "Uploaded Documents", remove_null($data), "010102", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "010202", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    public function documentVerifyOld(Request $request)
    {
        $request->validate([
            'id' => 'required|digits_between:1,9223372036854775807',
            'applicationId' => 'required|digits_between:1,9223372036854775807',
            'docRemarks' => $request->docStatus == "Rejected" ? 'required|regex:/^[a-zA-Z1-9][a-zA-Z1-9\. \s]+$/' : "nullable",
            'docStatus' => 'required|in:Verified,Rejected'
        ]);
        try {

            $user = Auth()->user();
            $user_id = $user->id;
            $ulb_id = $user->ulb_id;

            $_REF_TABLE = $mRefTable = "water_consumer_active_requests.id";
            $_WF_MASTER_Id = Config::get('workflow-constants.WATER_DISCONNECTION');
            $_MODULE_ID = Config::get('module-constants.WATER_MODULE_ID');

            $workflow_id = $refWorkflowId = $_WF_MASTER_Id;
            $_COMMON_FUNCTION = new CommonFunction();
            $_TRADE_CONSTAINT = Config::get("TradeConstant");
            $mWfDocument = new WfActiveDocument();
            $role = $_COMMON_FUNCTION->getUserRoll($user_id, $ulb_id, $refWorkflowId);
            if ((!$_COMMON_FUNCTION->checkUsersWithtocken("users"))) {
                throw new Exception("Citizen Not Allowed");
            }
            $rolles = $_COMMON_FUNCTION->getUserRoll($user_id, $ulb_id, $workflow_id);
            if (!$rolles || !$rolles->can_verify_document) {
                throw new Exception("You are Not Authorized For Document Verify");
            }
            $wfDocId = $request->id;
            $applicationId = $request->applicationId;
            $this->begin();
            if ($request->docStatus == "Verified") {
                $status = 1;
            }
            if ($request->docStatus == "Rejected") {
                $status = 2;
            }

            $myRequest = [
                'remarks' => $request->docRemarks,
                'verify_status' => $status,
                'action_taken_by' => $user_id
            ];
            $mWfDocument->docVerifyReject($wfDocId, $myRequest);
            $this->commit();
            $doc = $this->getDocList($request); //dd($doc);
            $docVerifyStatus = $doc->original["data"]["docVerifyStatus"] ?? 0;

            return responseMsgs(true, ["docVerifyStatus" => $docVerifyStatus], "", "tc7.1", "1.0", "", "POST", $request->deviceId ?? "");
        } catch (Exception $e) {
            $this->rollBack();
            return responseMsgs(false, $e->getMessage(), "", "tc7.1", "1.0", "", "POST", $request->deviceId ?? "");
        }
    }

    public function documentVerify(Request $req)
    {
        $validated = Validator::make(
            $req->all(),
            [
                'id' => 'required|digits_between:1,9223372036854775807',
                'applicationId' => 'required|digits_between:1,9223372036854775807',
                'docRemarks' => $req->docStatus == "Rejected" ? 'required|regex:/^[a-zA-Z1-9][a-zA-Z1-9\. \s]+$/' : "nullable",
                'docStatus' => 'required|in:Verified,Rejected'
            ]
        );
        if ($validated->fails())
            return validationError($validated);
        try {
            # Variable Assignments
            $mWfDocument = new WfActiveDocument();
            $mWaterActiveReqApplication = new WaterConsumerActiveRequest();
            $mWfRoleusermap = new WfRoleusermap();
            $wfDocId = $req->id;
            $applicationId = $req->applicationId;
            $userId = authUser($req)->id;
            $wfLevel = Config::get('waterConstaint.ROLE-LABEL');

            # validating application
            $waterApplicationDtl = $mWaterActiveReqApplication->getActiveRequest($applicationId)
                ->firstOrFail();
            if (!$waterApplicationDtl || collect($waterApplicationDtl)->isEmpty())
                throw new Exception("Application Details Not Found");

            # validating roles
            $waterReq = new Request([
                'userId' => $userId,
                'workflowId' => $waterApplicationDtl['workflow_id']
            ]);
            $senderRoleDtls = $mWfRoleusermap->getRoleByUserWfId($waterReq);
            if (!$senderRoleDtls || collect($senderRoleDtls)->isEmpty())
                throw new Exception("Role Not Available");

            # validating role for DA
            $senderRoleId = $senderRoleDtls->wf_role_id;
            // $authorizedRoles = [$wfLevel['DA'], $wfLevel['JE']];
            // if (!in_array($senderRoleId, $authorizedRoles)) {                                    // Authorization for Dealing Assistant Only
            //     throw new Exception("You are not Authorized");
            // }

            # validating if full documet is uploaded
            $ifFullDocVerified = $this->ifFullDocVerified($applicationId);                          // (Current Object Derivative Function 0.1)
            if ($ifFullDocVerified == 1)
                throw new Exception("Document Fully Verified");

            $this->begin();
            if ($req->docStatus == "Verified") {
                $status = 1;
            }
            if ($req->docStatus == "Rejected") {
                # For Rejection Doc Upload Status and Verify Status will disabled 
                $status = 2;
                // $waterApplicationDtl->doc_upload_status = 0;
                $waterApplicationDtl->doc_verify_status = false;
                $waterApplicationDtl->save();
            }
            $reqs = [
                'remarks' => $req->docRemarks,
                'verify_status' => $status,
                'action_taken_by' => $userId
            ];

            $mWfDocument->docVerifyReject($wfDocId, $reqs);
            if ($req->docStatus == 'Verified')
                $ifFullDocVerifiedV1 = $this->ifFullDocVerified($applicationId);
            else
                $ifFullDocVerifiedV1 = 0;

            if ($ifFullDocVerifiedV1 == 1) {                                        // If The Document Fully Verified Update Verify Status
                $mWaterActiveReqApplication->updateAppliVerifyStatus($applicationId);
            }
            $this->commit();
            return responseMsgs(true, $req->docStatus . " Successfully", "", "010204", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            $this->rollback();
            return responseMsgs(false, $e->getMessage(), "", "010204", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Check if the Document is Fully Verified or Not (0.1) | up
     * | @param
     * | @var 
     * | @return
        | Serial No :  
        | Working 
     */
    public function ifFullDocVerified($applicationId)
    {
        $mWaterApplication = new WaterConsumerActiveRequest();
        $mWfActiveDocument = new WfActiveDocument();
        $refapplication = $mWaterApplication->getActiveRequest($applicationId)
            ->firstOrFail();

        $refReq = [
            'activeId' => $applicationId,
            'workflowId' => $refapplication['workflow_id'],
            'moduleId' => Config::get('module-constants.WATER_MODULE_ID')
        ];

        $req = new Request($refReq);
        $refDocList = $mWfActiveDocument->getDocsByActiveId($req);
        $ifPropDocUnverified = $refDocList->contains('verify_status', 0);
        if ($ifPropDocUnverified == true)
            return 0;
        else
            return 1;
    }

    public function checkFullDocUpload($applicationId)
    {
        $mWaterApplication = new WaterConsumerActiveRequest();
        $mWfActiveDocument = new WfActiveDocument();
        $waterDetails = $mWaterApplication->getApplicationById($applicationId)->first();
        $ReqwaterDetails = [
            'activeId' => $applicationId,
            'workflowId' => $waterDetails->workflow_id,
            'moduleId' => 2
        ];
        $req = new Request($ReqwaterDetails);
        $refDocList = $mWfActiveDocument->getDocsByActiveId($req);
        return $this->isAllDocs($applicationId, $refDocList, $waterDetails);
    }

    public function isAllDocs($applicationId, $refDocList, $refSafs)
    {
        $docList = array();
        $verifiedDocList = array();
        $waterListDocs = $this->getRequestDocLists($refSafs);
        $docList['waterDocs'] = explode('#', $waterListDocs);
        $verifiedDocList['waterDocs'] = $refDocList->values();
        $collectUploadDocList = collect();
        collect($verifiedDocList['waterDocs'])->map(function ($item) use ($collectUploadDocList) {
            return $collectUploadDocList->push($item['doc_code']);
        });
        $mwaterDocs = collect($docList['waterDocs']);
        // water List Documents
        $flag = 1;
        foreach ($mwaterDocs as $item) {
            $explodeDocs = explode(',', $item);
            array_shift($explodeDocs);
            foreach ($explodeDocs as $explodeDoc) {
                $changeStatus = 0;
                if (in_array($explodeDoc, $collectUploadDocList->toArray())) {
                    $changeStatus = 1;
                    break;
                }
            }
            if ($changeStatus == 0) {
                $flag = 0;
                break;
            }
        }

        if ($flag == 0)
            return 0;
    }

    public function postNextLevelRequestV1(Request $request)
    {

        $user = Auth()->user();
        $user_id = $user->id;
        $ulb_id = $user->ulb_id;

        $_REF_TABLE = $mRefTable = "water_consumer_active_requests.id";
        $_WF_MASTER_Id = Config::get('workflow-constants.WATER_DISCONNECTION');
        $_MODULE_ID = Config::get('module-constants.WATER_MODULE_ID');

        $refWorkflowId = $_WF_MASTER_Id;
        $_COMMON_FUNCTION = new CommonFunction();
        $_TRADE_CONSTAINT = Config::get("TradeConstant");
        $role = $_COMMON_FUNCTION->getUserRoll($user_id, $ulb_id, $refWorkflowId);
        $rules = [
            "action" => 'required|in:forward,backward',
            'applicationId' => 'required|digits_between:1,9223372036854775807',
            'senderRoleId' => 'nullable|integer',
            'receiverRoleId' => 'nullable|integer',
            'comment' => ($role->is_initiator ?? false) ? "nullable" : 'required',
        ];


        try {
            $request->validate($rules);
            if (!$request->senderRoleId) {
                $request->merge(["senderRoleId" => $role->role_id ?? 0]);
            }
            if (!$request->receiverRoleId) {
                if ($request->action == 'forward') {
                    $request->merge(["receiverRoleId" => $role->forward_role_id ?? 0]);
                }
                if ($request->action == 'backward') {
                    $request->merge(["receiverRoleId" => $role->backward_role_id ?? 0]);
                }
            }


            #if finisher forward then
            if (($role->is_finisher ?? 0) && $request->action == 'forward') {
                $request->merge(["status" => 1]);
                return $this->approveReject($request);
            }

            if (!$_COMMON_FUNCTION->checkUsersWithtocken("users")) {
                throw new Exception("Citizen Not Allowed");
            }

            #Trade Application Update Current Role Updation

            $workflowId = WfWorkflow::where('wf_master_id', $refWorkflowId)
                ->where('ulb_id', $ulb_id)
                ->first();
            if (!$workflowId) {
                throw new Exception("Workflow Not Available");
            }

            $waterConsumerActive = WaterConsumerActiveRequest::find($request->applicationId);
            ;
            if (!$waterConsumerActive) {
                throw new Exception("Data Not Found");
            }
            // if($licence->is_parked && $request->action=='forward')
            // {
            //      $request->request->add(["receiverRoleId"=>$licence->current_role??0]);
            // }
            $allRolse = collect($_COMMON_FUNCTION->getAllRoles($user_id, $ulb_id, $refWorkflowId, 0, true));

            $initFinish = $_COMMON_FUNCTION->iniatorFinisher($user_id, $ulb_id, $refWorkflowId);
            $receiverRole = array_values(objToArray($allRolse->where("id", $request->receiverRoleId)))[0] ?? [];
            $senderRole = array_values(objToArray($allRolse->where("id", $request->senderRoleId)))[0] ?? [];

            if ($waterConsumerActive->payment_status != 1 && ($role->serial_no < $receiverRole["serial_no"] ?? 0)) {
                throw new Exception("Payment Not Clear");
            }

            if ((!$role->is_finisher ?? 0) && $request->action == 'backward' && $receiverRole["id"] == $initFinish['initiator']['id']) {
                $request->merge(["currentRoleId" => $request->senderRoleId]);
                return $this->backToCitizen($request);
            }

            if ($waterConsumerActive->current_role != $role->role_id && !$role->is_initiato && (!$waterConsumerActive->parked)) {
                throw new Exception("You Have Not Pending This Application");
            }
            if ($waterConsumerActive->parked && !$role->is_initiator) {
                throw new Exception("You Aer Not Authorized For Forword BTC Application");
            }

            $sms = "Application BackWord To " . $receiverRole["role_name"] ?? "";

            if ($role->serial_no < $receiverRole["serial_no"] ?? 0) {
                $sms = "Application Forward To " . $receiverRole["role_name"] ?? "";
            }
            $documents = $this->checkWorckFlowForwardBackord($request);

            if ((($senderRole["serial_no"] ?? 0) < ($receiverRole["serial_no"] ?? 0)) && !$documents) {
                if (($role->can_upload_document ?? false) && $waterConsumerActive->parked) {
                    throw new Exception("Rejected Document Are Not Uploaded");
                }
                if (($role->can_upload_document ?? false)) {
                    throw new Exception("No Every Madetry Documents are Uploaded");
                }
                if ($role->can_verify_document ?? false) {
                    throw new Exception("Not able to forward application because documents not fully verified");
                }
                throw new Exception("Not Every Actoin Are Performed");
            }
            if ($role->can_upload_document) {
                if (($role->serial_no < $receiverRole["serial_no"] ?? 0)) {
                    $waterConsumerActive->doc_upload_status = true;
                    // $waterConsumerActive->pending_status = 1;
                    $waterConsumerActive->parked = false;
                }
                if (($role->serial_no > $receiverRole["serial_no"] ?? 0)) {
                    $waterConsumerActive->doc_upload_status = false;
                }
            }
            if ($role->can_verify_document) {
                if (($role->serial_no < $receiverRole["serial_no"] ?? 0)) {
                    $waterConsumerActive->doc_verify_status = true;
                    // $waterConsumerActive->doc_verified_by = $user_id;
                    // $waterConsumerActive->doc_verify_date = Carbon::now()->format("Y-m-d");
                }
                if (($role->serial_no > $receiverRole["serial_no"] ?? 0)) {
                    $waterConsumerActive->is_doc_verified = false;
                }
            }
            $lastRole = collect($allRolse)->where("id", $waterConsumerActive->last_role_id)->first();
            $max_level_attained = $lastRole->serial_no ?? 0;
            $this->begin();
            $waterConsumerActive->last_role_id = ($max_level_attained < ($receiverRole["serial_no"] ?? 0)) ? ($receiverRole["serial_no"] ?? 0) : $waterConsumerActive->last_role_id;
            $waterConsumerActive->current_role = $request->receiverRoleId;
            if ($waterConsumerActive->parked && $request->action == 'forward') {
                $waterConsumerActive->parked = false;
            }
            $waterConsumerActive->update();

            $track = new WorkflowTrack();
            $lastworkflowtrack = $track->select("*")
                ->where('ref_table_id_value', $request->applicationId)
                ->where('module_id', $_MODULE_ID)
                ->where('ref_table_dot_id', $_REF_TABLE)
                ->whereNotNull('sender_role_id')
                ->orderBy("track_date", 'DESC')
                ->first();


            $metaReqs['moduleId'] = $_MODULE_ID;
            $metaReqs['workflowId'] = $waterConsumerActive->workflow_id;
            $metaReqs['refTableDotId'] = $_REF_TABLE;
            $metaReqs['refTableIdValue'] = $request->applicationId;
            $metaReqs['user_id'] = $user_id;
            $metaReqs['ulb_id'] = $ulb_id;
            $metaReqs['trackDate'] = $lastworkflowtrack && $lastworkflowtrack->forward_date ? ($lastworkflowtrack->forward_date . " " . $lastworkflowtrack->forward_time) : Carbon::now()->format('Y-m-d H:i:s');
            $metaReqs['forwardDate'] = Carbon::now()->format('Y-m-d');
            $metaReqs['forwardTime'] = Carbon::now()->format('H:i:s');
            $metaReqs['verificationStatus'] = ($request->action == 'forward') ? $_TRADE_CONSTAINT["VERIFICATION-STATUS"]["VERIFY"] : $_TRADE_CONSTAINT["VERIFICATION-STATUS"]["BACKWARD"];
            $request->merge($metaReqs);
            $track->saveTrack($request);
            $this->commit();
            return responseMsgs(true, $sms, "", "010109", "1.0", "286ms", "POST", $request->deviceId);
        } catch (Exception $error) {
            $this->rollBack();
            return responseMsg(false, $error->getMessage(), "");
        }
    }



    #====================[📝📖 BTC THE APPLICATION | S.L (16.0) 📖📝]========================================================
    public function backToCitizen(Request $req)
    {
        $user = Auth()->user();
        $user_id = $user->id;
        $ulb_id = $user->ulb_id;

        $_REF_TABLE = $mRefTable = "water_consumer_active_requests.id";
        $_WF_MASTER_Id = Config::get('workflow-constants.WATER_DISCONNECTION');
        $_MODULE_ID = Config::get('module-constants.WATER_MODULE_ID');

        $refWorkflowId = $_WF_MASTER_Id;
        $_COMMON_FUNCTION = new CommonFunction();
        $_TRADE_CONSTAINT = Config::get("TradeConstant");
        $role = $_COMMON_FUNCTION->getUserRoll($user_id, $ulb_id, $refWorkflowId);

        try {
            $req->validate([
                'applicationId' => 'required|digits_between:1,9223372036854775807',
                'currentRoleId' => 'required|integer',
                'comment' => 'required|string'
            ]);

            $waterConsumerActive = WaterConsumerActiveRequest::find($req->applicationId);
            ;
            if (!$waterConsumerActive) {
                throw new Exception("Data Not Found");
            }

            $req->merge(["workflowId" => $waterConsumerActive->workflow_id]);
            if ($waterConsumerActive->parked) {
                throw new Exception("Application Already BTC");
            }

            if (!$_COMMON_FUNCTION->checkUsersWithtocken("users")) {
                throw new Exception("Citizen Not Allowed");
            }
            if (!$req->senderRoleId) {
                $req->merge(["senderRoleId" => $role->role_id ?? 0]);
            }
            if (!$req->receiverRoleId) {
                $req->merge(["receiverRoleId" => $role->backward_role_id ?? 0]);
            }
            if ($waterConsumerActive->current_role != $req->senderRoleId) {
                throw new Exception("Application Access Forbiden");
            }
            $track = new WorkflowTrack();
            $lastworkflowtrack = $track->select("*")
                ->where('ref_table_id_value', $req->applicationId)
                ->where('module_id', $_MODULE_ID)
                ->where('ref_table_dot_id', $_REF_TABLE)
                ->whereNotNull('sender_role_id')
                ->orderBy("track_date", 'DESC')
                ->first();
            $this->begin();
            $initiatorRoleId = $waterConsumerActive->initiator_role;
            // $activeLicence->current_role = $initiatorRoleId;
            $waterConsumerActive->parked = true;
            $waterConsumerActive->save();

            $metaReqs['moduleId'] = $_MODULE_ID;
            $metaReqs['workflowId'] = $waterConsumerActive->workflow_id;
            $metaReqs['refTableDotId'] = $_REF_TABLE;
            $metaReqs['refTableIdValue'] = $req->applicationId;
            $metaReqs['trackDate'] = $lastworkflowtrack && $lastworkflowtrack->forward_date ? ($lastworkflowtrack->forward_date . " " . $lastworkflowtrack->forward_time) : Carbon::now()->format('Y-m-d H:i:s');
            $metaReqs['forwardDate'] = Carbon::now()->format('Y-m-d');
            $metaReqs['forwardTime'] = Carbon::now()->format('H:i:s');
            $metaReqs['verificationStatus'] = $_TRADE_CONSTAINT["VERIFICATION-STATUS"]["BTC"]; #2
            $metaReqs['user_id'] = $user_id;
            $metaReqs['ulb_id'] = $ulb_id;
            $req->merge($metaReqs);
            $track->saveTrack($req);
            $this->commit();
            return responseMsgs(true, "BTC Successfully Done", "", "010111", "1.0", "350ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            $this->rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }


    public function checkWorckFlowForwardBackord(Request $request)
    {
        $_COMMON_FUNCTION = new CommonFunction();
        $_TRADE_CONSTAINT = Config::get("TradeConstant");
        $user = Auth()->user();
        $user_id = $user->id ?? $request->user_id;
        $ulb_id = $user->ulb_id ?? $request->ulb_id;
        $_WF_MASTER_Id = Config::get('workflow-constants.WATER_DISCONNECTION');
        $refWorkflowId = $_WF_MASTER_Id;
        $allRolse = collect($_COMMON_FUNCTION->getAllRoles($user_id, $ulb_id, $refWorkflowId, 0, true));
        $mUserType = $_COMMON_FUNCTION->userType($refWorkflowId, $ulb_id);
        $fromRole = [];
        if (!empty($allRolse)) {
            $fromRole = array_values(objToArray($allRolse->where("id", $request->senderRoleId)))[0] ?? [];
        }
        if (strtoupper($mUserType) == $_TRADE_CONSTAINT["USER-TYPE-SHORT-NAME"][""] || ($fromRole["can_upload_document"] ?? false) || ($fromRole["can_verify_document"] ?? false)) {
            $documents = $this->getDocList($request);
            if (!$documents->original["status"]) {
                return false;
            }
            $applicationDoc = $documents->original["data"]["listDocs"];
            $ownerDoc = $documents->original["data"]["ownerDocs"] ?? collect([]);
            $appMandetoryDoc = $applicationDoc->whereIn("docType", ["R", "OR"]);
            $appUploadedDoc = $applicationDoc->whereNotNull("uploadedDoc");
            $appUploadedDocVerified = collect();
            $appUploadedDocRejected = collect();
            $appMadetoryDocRejected = collect();
            $appUploadedDoc->map(function ($val) use ($appUploadedDocVerified, $appUploadedDocRejected, $appMadetoryDocRejected) {

                $appUploadedDocVerified->push(["is_docVerify" => (!empty($val["uploadedDoc"]) ? (((collect($val["uploadedDoc"])->all())["verifyStatus"]) ? true : false) : true)]);
                $appUploadedDocRejected->push(["is_docRejected" => (!empty($val["uploadedDoc"]) ? (((collect($val["uploadedDoc"])->all())["verifyStatus"] == 2) ? true : false) : false)]);
                if (in_array($val["docType"], ["R", "OR"])) {
                    $appMadetoryDocRejected->push(["is_docRejected" => (!empty($val["uploadedDoc"]) ? (((collect($val["uploadedDoc"])->all())["verifyStatus"] == 2) ? true : false) : false)]);
                }
            });
            $is_appUploadedDocVerified = $appUploadedDocVerified->where("is_docVerify", false);
            $is_appUploadedDocRejected = $appUploadedDocRejected->where("is_docRejected", true);
            $is_appUploadedMadetoryDocRejected = $appMadetoryDocRejected->where("is_docRejected", true);
            // $is_appMandUploadedDoc              = $appMandetoryDoc->whereNull("uploadedDoc");
            $is_appMandUploadedDoc = $appMandetoryDoc->filter(function ($val) {
                return ($val["uploadedDoc"] == "" || $val["uploadedDoc"] == null);
            });
            $Wdocuments = collect();
            $ownerDoc->map(function ($val) use ($Wdocuments) {
                $ownerId = $val["ownerDetails"]["ownerId"] ?? "";
                $val["documents"]->map(function ($val1) use ($Wdocuments, $ownerId) {
                    $val1["ownerId"] = $ownerId;
                    $val1["is_uploded"] = (in_array($val1["docType"], ["R", "OR"])) ? ((!empty($val1["uploadedDoc"])) ? true : false) : true;
                    $val1["is_docVerify"] = !empty($val1["uploadedDoc"]) ? (((collect($val1["uploadedDoc"])->all())["verifyStatus"]) ? true : false) : true;
                    $val1["is_docRejected"] = !empty($val1["uploadedDoc"]) ? (((collect($val1["uploadedDoc"])->all())["verifyStatus"] == 2) ? true : false) : false;
                    $val1["is_madetory_docRejected"] = (!empty($val1["uploadedDoc"]) && in_array($val1["docType"], ["R", "OR"])) ? (((collect($val1["uploadedDoc"])->all())["verifyStatus"] == 2) ? true : false) : false;
                    $Wdocuments->push($val1);
                });
            });

            $ownerMandetoryDoc = $Wdocuments->whereIn("docType", ["R", "OR"]);
            $is_ownerUploadedDoc = $Wdocuments->where("is_uploded", false);
            $is_ownerDocVerify = $Wdocuments->where("is_docVerify", false);
            $is_ownerDocRejected = $Wdocuments->where("is_docRejected", true);
            $is_ownerMadetoryDocRejected = $Wdocuments->where("is_madetory_docRejected", true);
            if (($fromRole["can_upload_document"] ?? false) || strtoupper($mUserType) == $_TRADE_CONSTAINT["USER-TYPE-SHORT-NAME"][""]) {
                return (empty($is_ownerUploadedDoc->all()) && empty($is_ownerDocRejected->all()) && empty($is_appMandUploadedDoc->all()) && empty($is_appUploadedDocRejected->all()));
            }
            if ($fromRole["can_verify_document"] ?? false) {
                return (empty($is_ownerDocVerify->all()) && empty($is_appUploadedDocVerified->all()) && empty($is_ownerMadetoryDocRejected->all()) && empty($is_appUploadedMadetoryDocRejected->all()));
            }
        }
        return true;
    }

    public function approveRejectv1(Request $req)
    {
        try {
            $user = Auth()->user();
            $user_id = $user->id;
            $ulb_id = $user->ulb_id;
            $_REF_TABLE = $mRefTable = "water_consumer_active_requests.id";
            $_WF_MASTER_Id = Config::get('workflow-constants.WATER_DISCONNECTION');
            $_MODULE_ID = Config::get('module-constants.WATER_MODULE_ID');

            $refWorkflowId = $_WF_MASTER_Id;
            $_COMMON_FUNCTION = new CommonFunction();
            $_TRADE_CONSTAINT = Config::get("TradeConstant");
            $role = $_COMMON_FUNCTION->getUserRoll($user_id, $ulb_id, $refWorkflowId);

            $req->validate([
                "applicationId" => "required",
                "status" => "required",
                "comment" => $req->status == 0 ? "required" : "nullable",
            ]);
            if (!$_COMMON_FUNCTION->checkUsersWithtocken("users")) {
                throw new Exception("Citizen Not Allowed");
            }


            $refWorkflowId = $_WF_MASTER_Id;

            $waterConsumerActive = WaterConsumerActiveRequest::find($req->applicationId);
            ;
            if (!$waterConsumerActive) {
                throw new Exception("Data Not Found");
            }
            if ($waterConsumerActive->finisher != $role->role_id) {
                throw new Exception("Forbidden Access");
            }
            if (!$req->senderRoleId) {
                $req->merge(["senderRoleId" => $role->role_id ?? 0]);
            }
            if (!$req->receiverRoleId) {
                if ($req->action == 'forward') {
                    $req->merge(["receiverRoleId" => $role->forward_role_id ?? 0]);
                }
                if ($req->action == 'backward') {
                    $req->merge(["receiverRoleId" => $role->backward_role_id ?? 0]);
                }
            }
            $track = new WorkflowTrack();
            $lastworkflowtrack = $track->select("*")
                ->where('ref_table_id_value', $req->applicationId)
                ->where('module_id', $_MODULE_ID)
                ->where('ref_table_dot_id', "active_trade_licences")
                ->whereNotNull('sender_role_id')
                ->orderBy("track_date", 'DESC')
                ->first();
            $metaReqs['moduleId'] = $_MODULE_ID;
            $metaReqs['workflowId'] = $waterConsumerActive->workflow_id;
            $metaReqs['refTableDotId'] = 'active_trade_licences';
            $metaReqs['refTableIdValue'] = $req->applicationId;
            $metaReqs['user_id'] = $user_id;
            $metaReqs['ulb_id'] = $ulb_id;
            $metaReqs['trackDate'] = $lastworkflowtrack && $lastworkflowtrack->forward_date ? ($lastworkflowtrack->forward_date . " " . $lastworkflowtrack->forward_time) : Carbon::now()->format('Y-m-d H:i:s');
            $metaReqs['forwardDate'] = Carbon::now()->format('Y-m-d');
            $metaReqs['forwardTime'] = Carbon::now()->format('H:i:s');
            $metaReqs['verificationStatus'] = ($req->status == 1) ? $_TRADE_CONSTAINT["VERIFICATION-STATUS"]["APROVE"] : $_TRADE_CONSTAINT["VERIFICATION-STATUS"]["REJECT"];
            $req->merge($metaReqs);

            $this->begin();

            $track->saveTrack($req);
            // Approval
            if ($req->status == 1) {
                $refUlbDtl = ModelsUlbMaster::find($waterConsumerActive->ulb_id);
                // Objection Application replication
                $approvedConsumerActive = $waterConsumerActive->replicate();
                $approvedConsumerActive->setTable('water_consumer_approval_requests');
                // $approvedLicence->pending_status = 5;
                $approvedConsumerActive->id = $waterConsumerActive->id;
                $approvedConsumerActive->save();
                $waterConsumerActive->delete();
                $msg = "Application Successfully Approved !!. Your License No Is ";
            }

            // Rejection
            if ($req->status == 0) {
                // Objection Application replication
                $approvedConsumerActive = $waterConsumerActive->replicate();
                $approvedConsumerActive->setTable('water_consumer_rejects_requests');
                $approvedConsumerActive->id = $waterConsumerActive->id;
                // $approvedLicence->pending_status = 4;
                $approvedConsumerActive->save();
                $waterConsumerActive->delete();
                $msg = "Application Successfully Rejected !!";
            }
            $this->commit();

            return responseMsgs(true, $msg, "", '010811', '01', '474ms-573', 'Post', '');
        } catch (Exception $e) {
            $this->rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    public function getDisApplicationsDetails($request)
    {
        # object assigning
        $waterObj = new WaterConsumerActiveRequest();
        $ownerObj = new WaterConsumerOwner();
        $forwardBackward = new WorkflowMap;
        $mWorkflowTracks = new WorkflowTrack();
        $mCustomDetails = new CustomDetail();
        $mUlbNewWardmap = new UlbWardMaster();

        # application details
        $applicationDetails = $waterObj->getApplicationByUserV1($request->applicationId)->get();
        if (collect($applicationDetails)->first() == null) {
            return responseMsg(false, "Application Data Not found!", $request->applicationId);
        }

        # Ward Name
        $refApplication = collect($applicationDetails)->first();
        $wardDetails = $mUlbNewWardmap->getWard($refApplication->ward_id);
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
        $basicDetails = $this->getBasicDetails($applicationDetails, $wardDetails);
        $propertyDetails = $this->getpropertyDetails($applicationDetails, $wardDetails);
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
        $cardDetails = $this->getCardDetails($applicationDetails, $ownerDetails, $wardDetails);
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
        $levelComment['levelComment'] = $mWorkflowTracks->getTracksByRefId($mRefTable, $mtableId)->map(function ($val) {
            $val->forward_date = $val->forward_date ? Carbon::parse($val->forward_date)->format("d-m-Y") : "";
            $val->track_date = $val->track_date ? Carbon::parse($val->track_date)->format("d-m-Y") : "";
            $val->duration = (Carbon::parse($val->forward_date)->diffInDays(Carbon::parse($val->track_date))) . " Days";
            return $val;
        });

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

        # Payments Details
        $returnValues = array_merge($aplictionList, $fullDetailsData, $levelComment, $citizenComment, $roleDetails, $timelineData, $departmentPost);
        return responseMsgs(true, "listed Data!", remove_null($returnValues), "", "02", ".ms", "POST", "");
    }


    public function getApplicationDetails(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'applicationId' => 'required|integer',
            ]
        );
        if ($validated->fails())
            return validationError($validated);

        try {
            $user = authUser($request);
            $mWaterSiteInspectionsScheduling = new WaterSiteInspectionsScheduling();
            $mWaterConsumerCharge = new WaterConsumerCharge();
            $mWaterConsumerActiveApplication = new WaterConsumerActiveRequest();
            $mWaterConsumerOwner = new WaterConsumerOwner();
            $mWaterTran = new WaterTran();
            $roleDetails = Config::get('waterConstaint.ROLE-LABEL');

            # Application Details
            $applicationDetails['applicationDetails'] = $mWaterConsumerActiveApplication->getApplicationByUserV1($request->applicationId)->first();

            # Document Details
            $metaReqs = [
                'userId' => $user->id,
                'ulbId' => $user->ulb_id ?? $applicationDetails['applicationDetails']['ulb_id'],
            ];
            $request->request->add($metaReqs);
            // $document = $this->getDocToUpload($request);                                                    // get the doc details
            // $documentDetails['documentDetails'] = collect($document)['original']['data'];

            # Property Details
            // $propertyDetails['propertyDetails'] = $mWaterConsumerActiveApplication->getPropertyByConsumerId($applicationDetails['applicationDetails']['consumer_id'])->get();


            # owner details
            $ownerDetails['ownerDetails'] = $mWaterConsumerOwner->ownerByApplication($applicationDetails['applicationDetails']['consumer_id'])->get();

            # Payment Details 
            $refAppDetails = collect($applicationDetails)->first();
            $waterTransaction = $mWaterTran->getTransNo($refAppDetails->id, $refAppDetails->connection_type)->get();
            $waterTransDetail['waterTransDetail'] = $waterTransaction;

            # calculation details
            $charges = $mWaterConsumerCharge->getConsumerChargesByConsumerId($applicationDetails['applicationDetails']['consumer_id'])
                ->where('paid_status', 0)
                ->first();
            if ($charges) {
                $calculation['calculation'] = [
                    // 'connectionFee'     => $charges['conn_fee'],
                    'penalty' => $charges['penalty'],
                    'totalAmount' => $charges['amount'],
                    'chargeCatagory' => $charges['charge_category'],
                    'paidStatus' => $charges['paid_status']
                ];
                $waterTransDetail = array_merge($waterTransDetail, $calculation);
            }

            # Site inspection schedule time/date Details 
            if ($applicationDetails['applicationDetails']['current_role'] == $roleDetails['JE']) {
                $inspectionTime = $mWaterSiteInspectionsScheduling->getInspectionData($applicationDetails['applicationDetails']['id'])->first();
                $applicationDetails['applicationDetails']['scheduledTime'] = $inspectionTime->inspection_time ?? null;
                $applicationDetails['applicationDetails']['scheduledDate'] = $inspectionTime->inspection_date ?? null;
            }

            $returnData = array_merge($applicationDetails, $ownerDetails, $waterTransDetail); //$documentDetails,
            return responseMsgs(true, "Application Data!", remove_null($returnData), "", "", "", "Post", "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    public function getDocListForJe(Request $req)
    {
        $validated = Validator::make(
            $req->all(),
            [
                'applicationId' => 'required|numeric'
            ]
        );
        if ($validated->fails())
            return validationError($validated);

        try {
            $mWaterApplication = new WaterConsumerActiveRequest();
            $refWaterApplication = $mWaterApplication->getActiveReqById($req->applicationId)->first();                      // Get Saf Details
            if (!$refWaterApplication) {
                throw new Exception("Application Not Found for this id");
            }
            // $refWaterApplicant = $mWaterApplicant->getOwnerList($req->applicationId)->get();
            $documentList = $this->getWaterDocLists($refWaterApplication, $req);
            $waterTypeDocs['listDocs'] = collect($documentList)->map(function ($value, $key) use ($refWaterApplication) {
                return $this->filterDocument($value, $refWaterApplication)->first();
            });

            // $waterOwnerDocs['ownerDocs'] = collect($refWaterApplicant)->map(function ($owner) use ($refWaterApplication) {
            //     return $this->getOwnerDocLists($owner, $refWaterApplication);
            // });
            // $waterOwnerDocs;

            $totalDocLists = collect($waterTypeDocs); //->merge($waterOwnerDocs);
            $totalDocLists['docUploadStatus'] = $refWaterApplication->doc_upload_status;
            $totalDocLists['docVerifyStatus'] = $refWaterApplication->doc_status;
            return responseMsgs(true, "", remove_null($totalDocLists), "010203", "", "", 'POST', "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "010203", "1.0", "", 'POST', "");
        }
    }

    /** 
     * |---------------------------- List of the doc to upload For Je ----------------------------|
     * | Calling function
     * | 01.01
        | Serial No :  
     */
    public function getWaterDocLists($application, $req)
    {
        $user = authUser($req);
        $mRefReqDocs = new RefRequiredDocument();
        $moduleId = Config::get('module-constants.WATER_MODULE_ID');
        $refUserType = Config::get('waterConstaint.REF_USER_TYPE');
        // return $application;
        $type = [];
        if ($application->charge_catagory_id == 2) {
            $type = ["INSPECTION_REPORT"];
        }
        return $mRefReqDocs->getCollectiveDocByCode($moduleId, $type);
    }

    // upload documnet by je to site inspection of prperty water connections 
    public function uploadWaterDocJe(Request $req)
    {
        $validated = Validator::make(
            $req->all(),
            [
                "applicationId" => "required|numeric",
                "document" => "required|mimes:pdf,jpeg,png,jpg|max:2048",
                "docCode" => "required",
                "docCategory" => "required",                                  // Recheck in case of undefined
                // "ownerId"       => "nullable|numeric"
            ]
        );
        if ($validated->fails())
            return validationError($validated);

        try {
            $user = authUser($req);
            $metaReqs = array();
            $applicationId = $req->applicationId;
            $document = $req->document;
            $docUpload = new DocUpload;
            $mWfActiveDocument = new WfActiveDocument();
            $mWaterApplication = new WaterConsumerActiveRequest();
            $relativePath = Config::get('waterConstaint.WATER_RELATIVE_PATH');
            $refmoduleId = Config::get('module-constants.WATER_MODULE_ID');

            $getWaterDetails = $mWaterApplication->fullWaterDetails($req)->firstOrFail();
            $refImageName = $req->docRefName;
            $refImageName = $getWaterDetails->id . '-' . str_replace(' ', '_', $refImageName);
            $docDetail = $docUpload->checkDoc($req);
            $metaReqs = [
                'moduleId' => $refmoduleId,
                'activeId' => $applicationId,
                'workflowId' => $getWaterDetails->workflow_id,
                'ulbId' => $getWaterDetails->ulb_id,
                'relativePath' => $relativePath,
                'docCode' => $req->docCode,
                'ownerDtlId' => $req->ownerId,
                'docCategory' => $req->docCategory,
                'auth' => $user,
                'uniqueId' => $docDetail['data']['uniqueId'],
                'referenceNo' => $docDetail['data']['ReferenceNo'],

            ];

            # Check the diff in user and citizen
            if ($user->user_type == "Citizen") {                                                // Static
                $isCitizen = true;
                $this->checkParamForDocUploadv1($isCitizen, $getWaterDetails, $user);
            } else {
                $isCitizen = false;
                $this->checkParamForDocUploadv1($isCitizen, $getWaterDetails, $user);
            }

            $this->begin();
            $ifDocExist = $mWfActiveDocument->isDocCategoryExists($getWaterDetails->id, $getWaterDetails->workflow_id, $refmoduleId, $req->docCategory, $req->ownerId)->first();   // Checking if the document is already existing or not
            $metaReqs = new Request($metaReqs);
            if (collect($ifDocExist)->isEmpty()) {
                $mWfActiveDocument->postDocuments($metaReqs);
            }
            if (collect($ifDocExist)->isNotEmpty()) {
                $mWfActiveDocument->editDocuments($ifDocExist, $metaReqs);
            }
            #update Diconnection Report  doc upload status and field verify
            $mWaterApplication->updateJeVarifications($applicationId);
            # if the application is parked and btc s
            if ($getWaterDetails->parked == true) {
                $mWfActiveDocument->deactivateRejectedDoc($metaReqs);
                $refReq = new Request([
                    'applicationId' => $applicationId
                ]);
                $documentList = $this->getDocListForJe($refReq);
                $DocList = collect($documentList)['original']['data'];
                $refVerifyStatus = $DocList->where('doc_category', '!=', $req->docCategory)->pluck('verify_status');
                if (!in_array(2, $refVerifyStatus->toArray())) {                                    // Static "2"
                    $status = false;
                    $mWaterApplication->updateParkedstatus($status, $applicationId);
                }
            }
            $this->commit();
            return responseMsgs(true, "Document Uploadation Successful", "", "", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            $this->rollback();
            return responseMsgs(false, $e->getMessage(), "", "", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * water disconnection approval or reject 
     */
    public function consumerApprovalRejection(Request $request)
    {
        $request->validate([
            "applicationId" => "required|INTEGER",
            "status" => "required|IN:0,1",
            "comment" => "required|STRING"
        ]);
        try {
            $mWfRoleUsermap = new WfRoleusermap();
            $waterDetails = WaterConsumerActiveRequest::find($request->applicationId);

            # check the login user is AE or not
            $userId = authUser($request)->id;
            $workflowId = $waterDetails->workflow_id;
            $getRoleReq = new Request([                                                 // make request to get role id of the user
                'userId' => $userId,
                'workflowId' => $workflowId
            ]);
            $readRoleDtls = $mWfRoleUsermap->getRoleByUserWfId($getRoleReq);
            $roleId = $readRoleDtls->wf_role_id;
            if ($roleId != $waterDetails->finisher) {
                throw new Exception("You are not the Finisher!");
            }
            $this->begin();
            $this->approveReject($request, $roleId);
            $this->commit();
            return responseMsg(true, "Request approved/rejected successfully", "");
            ;
        } catch (Exception $e) {
            $this->rollback();
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Function for Final Approval Or Rejection 
     */
    public function approveReject($request, $roleId)
    {
        $mWaterConsumerActive = new WaterConsumerActiveRequest();
        $consumerParamId = Config::get("waterConstaint.PARAM_IDS.DISC");
        $refJe = Config::get("waterConstaint.ROLE-LABEL.JE");
        $refWaterDetails = $this->preApprovalConditionCheck($request, $roleId);

        # Approval of water application 
        if ($request->status == 1) {
            $this->finalApproval($request, $refJe);
            $msg = "Application Successfully Approved !!";

        }

        # Rejection of water application
        if ($request->status == 0) {
            $this->finalRejectionOfAppication($request);
            $msg = "Application Successfully Rejected !!";

        }
        return responseMsgs(true, $msg, $request ?? "Empty", '', 01, '.ms', 'Post', $request->deviceId);
    }

    /**
  
     * |------------------- Final Approval of the water disconnection application -------------------|
  
     * | @param request
  
     * | @param consumerNo
  
     */

    public function finalApproval($request, $refJe)
    {
        # object creation

        $mwaterConsumerActiveRequest = new WaterConsumerActiveRequest();
        $mWaterSiteInspection = new WaterSiteInspection();
        $mWaterConsumer = new WaterConsumer();
        $mWaterConsumerMeter = new WaterConsumerMeter();
        $mWaterConsumerDemand = new WaterConsumerDemand();
        $mWaterConsumerOwner = new WaterConsumerOwner();
        $waterTrack = new WorkflowTrack();

        # checking if consumer already exist 
        $approvedWater = WaterConsumerActiveRequest::query()
            ->where('id', $request->applicationId)
            ->first();

        $checkExist = $mwaterConsumerActiveRequest->getApproveApplication($approvedWater->id);
        
        if (!$checkExist) {
            throw new Exception("Application Not Found");
        } elseif ($checkExist->verify_status == 1) {
            throw new Exception('Already Approve Application');
        } elseif ($checkExist->verify_status == 2) {
            throw new Exception('Already Rejected Applications');
        }

        $checkconsumer = $mWaterConsumer->getConsumerById($approvedWater->consumer_id);

        if (!$checkconsumer) {
            throw new Exception("Consumer Not Found");
        }

        # data formating for save the consumer details 
        $siteDetails = $mWaterSiteInspection->getSiteDetails($request->applicationId)
            // ->where('payment_status', 1)
            ->where('order_officer', $refJe)
            ->first();

        if (isset($siteDetails)) {
            $refData = [
                'connection_type_id' => $siteDetails['connection_type_id'],
                'connection_through' => $siteDetails['connection_through'],
                'pipeline_type_id' => $siteDetails['pipeline_type_id'],
                'property_type_id' => $siteDetails['property_type_id'],
                'category' => $siteDetails['category'],
                'area_sqft' => $siteDetails['area_sqft'],
                'area_asmt' => sqFtToSqMt($siteDetails['area_sqft'])
            ];

            $approvedWaterRep = collect($approvedWater)->merge($refData);

        }

        # dend record in the track table 
        $metaReqs = [

            'moduleId' => Config::get("module-constants.WATER_MODULE_ID"),
            'workflowId' => $approvedWater->workflow_id,
            'refTableDotId' => 'water_applications.id',
            'refTableIdValue' => $approvedWater->id,
            'user_id' => authUser($request)->id,
        ];

        $request->request->add($metaReqs);

        $waterTrack->saveTrack($request);

        # update verify status
        $mwaterConsumerActiveRequest->updateVerifystatus($metaReqs, $request->status);
        $consumerOwnedetails = $mWaterConsumerOwner->getConsumerOwner($checkExist->consumer_id)->first();

        // Here update all the entities related to request of consumers

        if ($checkExist->charge_catagory_id == 2) { // This for Disconnection
            $mWaterConsumer->dissconnetConsumer($consumerOwnedetails->consumer_id, 0);

        }

    }

    /**

   * |------------------- Final rejection of the Application -------------------|

   * | Transfer the data to new table

   */

    public function finalRejectionOfAppication($request)
    {

        $userId = authUser($request)->id;

        $mWaterConsumerActive = new WaterConsumerActiveRequest();
        $rejectedWater = WaterConsumerActiveRequest::query()
            ->where('id', $request->applicationId)
            ->first();

        # save record in track table 
        $waterTrack = new WorkflowTrack();
        $metaReqs['moduleId'] = Config::get("module-constants.WATER_MODULE_ID");
        $metaReqs['workflowId'] = $rejectedWater->workflow_id;
        $metaReqs['refTableDotId'] = 'water_consumer_active_requests.id';
        $metaReqs['refTableIdValue'] = $rejectedWater->id;
        $metaReqs['user_id'] = authUser($request)->id;
        $request->request->add($metaReqs);
        $waterTrack->saveTrack($request);

        #update Verify Status
        $mWaterConsumerActive->updateVerifyComplainRequest($request, $userId);

    }

}
