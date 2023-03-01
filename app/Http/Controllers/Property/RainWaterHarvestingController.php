<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\MicroServices\DocUpload;
use App\Models\CustomDetail;
use App\Models\Masters\RefRequiredDocument;
use App\Models\Property\PropActiveHarvesting;
use App\Models\Property\PropFloor;
use App\Models\Property\PropOwner;
use App\Models\Property\PropProperty;
use App\Models\Property\PropRwhVerification;
use App\Models\Property\RefPropDocsRequired;
use App\Models\Workflows\WfActiveDocument;
use App\Models\Workflows\WfRoleusermap;
use App\Models\Workflows\WfWardUser;
use App\Models\Workflows\WfWorkflow;
use App\Models\Workflows\WfWorkflowrolemap;
use App\Models\WorkflowTrack;
use App\Repository\Property\Concrete\PropertyBifurcation;
use App\Repository\WorkflowMaster\Concrete\WorkflowMap;
use App\Traits\Property\Concession;
use Illuminate\Http\Request;
use App\Traits\Property\SAF;
use App\Traits\Property\SafDetailsTrait;
use App\Traits\Ward;
use App\Traits\Workflow\Workflow;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Redis;

/**
 * | Created On - 18-11-2022
 * | Created By -  Mrinal Kumar
 * | Property RainWaterHarvesting apply
 */

class RainWaterHarvestingController extends Controller
{
    use SAF;
    use Workflow;
    use Ward;
    use SafDetailsTrait;
    use Concession;
    private $_todayDate;
    private $_bifuraction;
    private $_workflowId;

    public function __construct()
    {
        $this->_todayDate = Carbon::now();
        $this->_bifuraction = new PropertyBifurcation();
        $this->_workflowId  = Config::get('workflow-constants.RAIN_WATER_HARVESTING_ID');
    }


    /**
     * |----------------------- getWardMasterData --------------------------
     * |Query cost => 400-438 ms 
     * |@param request
     * |@var ulbId
     * |@var wardList
     * | Rating : 1
     */
    public function getWardMasterData(Request $request)
    {
        try {
            $ulbId = auth()->user()->ulb_id;
            $wardList = $this->getAllWard($ulbId);
            return responseMsg(true, "List of wards", $wardList);
        } catch (Exception $error) {
            return responseMsg(false, "Error!", $error->getMessage());
        }
    }

    /**
     * |----------------------- postWaterHarvestingApplication 1 --------------------------
     * |  Query cost => 350 - 490 ms 
     * | @param request
     * | @var ulbId
     * | @var wardList
     * | request : propertyId, isWaterHarvestingBefore , dateOfCompletion
     * | Rating :2
     */
    public function waterHarvestingApplication(Request $request)
    {
        try {
            $request->validate([
                'isWaterHarvestingBefore' => 'required',
                'dateOfCompletion' => 'required|date',
                'propertyId' => 'required',
                'ulbId' => 'required'
            ]);

            $ulbId = $request->ulbId;
            $userId = auth()->user()->id;
            $userType = auth()->user()->user_type;

            $ulbWorkflowId = WfWorkflow::where('wf_master_id', $this->_workflowId)
                ->where('ulb_id', $ulbId)
                ->first();

            $refInitiatorRoleId = $this->getInitiatorId($ulbWorkflowId->id);                // Get Current Initiator ID
            $refFinisherRoleId = $this->getFinisherId($ulbWorkflowId->id);
            $finisherRoleId = DB::select($refFinisherRoleId);
            $initiatorRoleId = DB::select($refInitiatorRoleId);

            $mPropActiveHarvesting = new PropActiveHarvesting();
            $waterHaravesting  = $mPropActiveHarvesting->saves($request, $ulbWorkflowId, $initiatorRoleId, $finisherRoleId,  $userId);

            if ($userType == 'Citizen') {
                $waterHaravesting->current_role = collect($initiatorRoleId)->first()->forward_role_id;
                $waterHaravesting->initiator_role_id = collect($initiatorRoleId)->first()->forward_role_id;      // Send to DA in Case of Citizen
                $waterHaravesting->last_role_id = collect($initiatorRoleId)->first()->forward_role_id;
                $waterHaravesting->user_id = null;
                $waterHaravesting->citizen_id = $userId;
                $waterHaravesting->doc_upload_status = 1;
            }
            $waterHaravesting->save();

            $propHarvesting = new PropActiveHarvesting();
            $harvestingNo = $propHarvesting->harvestingNo($waterHaravesting->id);

            PropActiveHarvesting::where('id', $waterHaravesting->id)
                ->update(['application_no' => $harvestingNo]);

            if ($userType == 'Citizen') {
                $metaReqs = array();
                $docUpload = new DocUpload;
                $mWfActiveDocument = new WfActiveDocument();
                $mPropActiveHarvesting = new PropActiveHarvesting();
                $relativePath = Config::get('PropertyConstaint.HARVESTING_RELATIVE_PATH');
                // $getHarvestingDtls = $mPropActiveHarvesting->getHarvestingNo($request->applicationId);
                $refImageName = $request->docCode;
                $refImageName = $waterHaravesting->id . '-' . $refImageName;
                $document = $request->document;

                $imageName = $docUpload->upload($refImageName, $document, $relativePath);

                $metaReqs['moduleId'] = Config::get('module-constants.PROPERTY_MODULE_ID');
                $metaReqs['activeId'] = $waterHaravesting->id;
                $metaReqs['workflowId'] = $waterHaravesting->workflow_id;
                $metaReqs['ulbId'] = $waterHaravesting->ulb_id;
                $metaReqs['relativePath'] = $relativePath;
                $metaReqs['document'] = $imageName;
                $metaReqs['docCode'] = $request->docCode;

                $metaReqs = new Request($metaReqs);
                $mWfActiveDocument->postDocuments($metaReqs);
            }

            //level pending
            if (isset($waterHaravesting->application_no)) {

                $track = new WorkflowTrack();
                $wfReqs['workflowId'] = $ulbWorkflowId->id;
                $wfReqs['refTableDotId'] = 'prop_active_harvestings.id';
                $wfReqs['refTableIdValue'] = $waterHaravesting->id;
                $wfReqs['moduleId'] = Config::get('module-constants.PROPERTY_MODULE_ID');

                $request->request->add($wfReqs);
                $track->saveTrack($request);
            }
            return responseMsg(true, "Application applied!", $harvestingNo);
        } catch (Exception $error) {
            return responseMsg(false, "Error!", $error->getMessage());
        }
    }

    /**
     * |----------------------- function for the Inbox  --------------------------
     * |@param ulbId
     * |@param userId
     * |@var applicationId
     * | Rating : 2
     * |status :closed
     */
    public function harvestingInbox()
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

            $harvestingList = new PropActiveHarvesting();
            $harvesting = $harvestingList->getHarvestingList($ulbId)
                ->whereIn('prop_active_harvestings.current_role', $roleId)
                ->whereIn('a.ward_mstr_id', $occupiedWards)
                ->orderByDesc('prop_active_harvestings.id')
                ->get();

            return responseMsgs(true, "Inbox List", remove_null($harvesting), '011108', 01, '364ms', 'Post', '');
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * |----------------------- function for the Special Inbox (Escalated Applications) for harvesting --------------------------
     * |@param ulbId
     * | Rating : 2
     */
    public function specialInbox()
    {
        try {
            $harvestingList = new PropActiveHarvesting();
            $auth = auth()->user();
            $userId = $auth->id;
            $ulbId = $auth->ulb_id;
            $wardId = $this->getWardByUserId($userId);

            $occupiedWards = collect($wardId)->map(function ($ward) {                               // Get Occupied Ward of the User
                return $ward->ward_id;
            });

            $harvesting = $harvestingList->getHarvestingList($ulbId)                                         // Get harvesting
                ->where('prop_active_harvestings.is_escalated', true)
                ->whereIn('a.ward_mstr_id', $occupiedWards)
                ->orderByDesc('prop_active_harvestings.id')
                ->get();

            return responseMsg(true, "Inbox List", remove_null($harvesting));
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Fields Verified Inbox
     */
    public function fieldVerifiedInbox(Request $req)
    {
        try {
            $mWfRoleUser = new WfRoleusermap();
            $mWfWardUser = new WfWardUser();
            $mharvestingList = new PropActiveHarvesting();

            $mUserId = authUser()->id;
            $mUlbId = authUser()->ulb_id;
            $mDeviceId = $req->deviceId ?? "";

            $readWards = $mWfWardUser->getWardsByUserId($mUserId);                  // Model function to get ward list
            $occupiedWardsId = collect($readWards)->map(function ($ward) {          // Collection filteration
                return $ward->ward_id;
            });

            $readRoles = $mWfRoleUser->getRoleIdByUserId($mUserId);                 // Model function to get Role By User Id
            $roleIds = $readRoles->map(function ($role, $key) {
                return $role->wf_role_id;
            });

            $harvesting = $mharvestingList->getHarvestingList($mUlbId)                 // Repository function getSAF
                ->where('is_field_verified', true)
                ->whereIn('prop_active_harvestings.current_role', $roleIds)
                ->whereIn('a.ward_mstr_id', $occupiedWardsId)
                ->orderByDesc('prop_active_harvestings.id')
                ->get();

            return responseMsgs(true, "field Verified Inbox!", remove_null($harvesting), 010125, 1.0, "", "POST", $mDeviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", 010125, 1.0, "", "POST", $mDeviceId);
        }
    }




    /**
     * |----------------------- function for the Outbox --------------------------
     * |@param ulbId
     * |@param userId
     * |@var applicationId
     * | Rating : 2
     * | status :closed
     */
    public function harvestingOutbox()
    {
        try {
            $auth = auth()->user();
            $userId = $auth->id;
            $ulbId = $auth->ulb_id;
            $harvestingList = new PropActiveHarvesting();

            $workflowRoles = $this->getRoleIdByUserId($userId);
            $roleId = $workflowRoles->map(function ($value, $key) {                         // Get user Workflow Roles
                return $value->wf_role_id;
            });

            $refWard = $this->getWardByUserId($userId);                                     // Get Ward List by user Id
            $occupiedWards = $refWard->map(function ($value, $key) {
                return $value->ward_id;
            });
            $harvesting = $harvestingList->getHarvestingList($ulbId)
                ->whereNotIn('prop_active_harvestings.current_role', $roleId)
                ->whereIn('a.ward_mstr_id', $occupiedWards)
                ->orderByDesc('prop_active_harvestings.id')
                ->get();

            return responseMsg(true, "Outbox List", remove_null($harvesting), '011109', 01, '446ms', 'Post', '');
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }


    /**
     * |----------------------- function for the escalate Application for harvesting --------------------------
     * |@param ulbId
     * |@param userId
     * |@var applicationId
     * | Rating : 2
     */
    public function postEscalate(Request $req)
    {
        $req->validate([
            'applicationId' => 'required|integer',
            'escalateStatus' => 'required|bool',
        ]);
        try {
            $userId = auth()->user()->id;
            if ($req->escalateStatus == 1) {
                $harvesting = PropActiveHarvesting::find($req->applicationId);
                $harvesting->is_escalated = 1;
                $harvesting->escalated_by = $userId;
                $harvesting->save();
                return responseMsg(true, "Successfully Escalated the application", "");
            }
            if ($req->escalateStatus == 0) {
                $harvesting = PropActiveHarvesting::find($req->id);
                $harvesting->is_escalated = 0;
                $harvesting->escalated_by = null;
                $harvesting->save();
                return responseMsg(true, "Successfully De-Escalated the application", "");
            }
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }


    /**
     * | Static details
     */
    public function staticDetails(Request $req)
    {
        $req->validate([
            'applicationId' => 'required|integer',
        ]);
        try {
            $mPropActiveHarvesting = new PropActiveHarvesting();
            $mWfActiveDocument =  new WfActiveDocument();
            $moduleId = Config::get('module-constants.PROPERTY_MODULE_ID');

            $details = $mPropActiveHarvesting->getDetailsById($req->applicationId);

            $docs = $mWfActiveDocument->getDocsByAppId($details->id, $details->workflow_id, $moduleId);
            $data = [
                'id' => $details->id,
                'applicationNo' => $details->application_no,
                'harvestingBefore2017' => $details->harvesting_status,
                'holdingNo' => $details->holding_no,
                'newHoldingNo' => $details->new_holding_no,
                'guardianName' => $details->guardian_name,
                'applicantName' => $details->owner_name,
                'wardNo' => $details->new_ward_no,
                'propertyAddress' => $details->prop_address,
                'mobileNo' => $details->mobile_no,
                'dateOfCompletion' => $details->date_of_completion,
                'harvestingImage' => $docs[0]->doc_path,

            ];

            return responseMsgs(true, "Static Details!", remove_null($data), 010125, 1.0, "", "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", 010125, 1.0, "", "POST", $req->deviceId);
        }
    }

    /**
     * | Harvesting Details
     */
    public function getDetailsById(Request $req)
    {
        $req->validate([
            'applicationId' => 'required'
        ]);
        try {
            $mPropActiveHarvesting = new PropActiveHarvesting();
            $mPropOwners = new PropOwner();
            $mPropFloors = new PropFloor();
            $mWorkflowTracks = new WorkflowTrack();
            $mCustomDetails = new CustomDetail();
            $mForwardBackward = new WorkflowMap();
            $mRefTable = Config::get('PropertyConstaint.SAF_HARVESTING_REF_TABLE');
            $details = $mPropActiveHarvesting->getDetailsById($req->applicationId);

            if (!$details)
                throw new Exception("Application Not Found for this id");

            // Data Array
            $basicDetails = $this->generateBasicDetails($details);         // (Basic Details) Trait function to get Basic Details
            $basicElement = [
                'headerTitle' => "Basic Details",
                "data" => $basicDetails
            ];

            $propertyDetails = $this->generatePropertyDetails($details);   // (Property Details) Trait function to get Property Details
            $propertyElement = [
                'headerTitle' => "Property Details & Address",
                'data' => $propertyDetails
            ];

            // $corrDetails = $this->generateCorrDtls($details);              // (Corresponding Address Details) Trait function to generate corresponding address details
            // $corrElement = [
            //     'headerTitle' => 'Corresponding Address',
            //     'data' => $corrDetails,
            // ];

            // $electDetails = $this->generateElectDtls($details);            // (Electricity & Water Details) Trait function to generate Electricity Details
            // $electElement = [
            //     'headerTitle' => 'Electricity & Water Details',
            //     'data' => $electDetails
            // ];

            $fullDetailsData['application_no'] = $details->application_no;
            $fullDetailsData['apply_date'] = Carbon::parse($details->created_at)->format('Y-m-d');
            $fullDetailsData['fullDetailsData']['dataArray'] = new Collection([$basicElement, $propertyElement]);

            // Table Array
            $ownerList = $mPropOwners->getOwnersByPropId($details->property_id);
            $ownerList = json_decode(json_encode($ownerList), true);       // Convert Std class to array
            $ownerDetails = $this->generateOwnerDetails($ownerList);
            $ownerElement = [
                'headerTitle' => 'Owner Details',
                'tableHead' => ["#", "Owner Name", "Gender", "DOB", "Guardian Name", "Relation", "Mobile No", "Aadhar", "PAN", "Email", "IsArmedForce", "isSpeciallyAbled"],
                'tableData' => $ownerDetails
            ];

            // $floorList = $mPropFloors->getPropFloors($details->property_id);    // Model Function to Get Floor Details
            // $floorDetails = $this->generateFloorDetails($floorList);
            // $floorElement = [
            //     'headerTitle' => 'Floor Details',
            //     'tableHead' => ["#", "Floor", "Usage Type", "Occupancy Type", "Construction Type", "Build Up Area", "From Date", "Upto Date"],
            //     'tableData' => $floorDetails
            // ];

            $fullDetailsData['fullDetailsData']['tableArray'] = new Collection([$ownerElement]);
            // Card Details
            $cardElement = $this->generateHarvestingCardDtls($details, $ownerList);
            $fullDetailsData['fullDetailsData']['cardArray'] = $cardElement;

            $levelComment = $mWorkflowTracks->getTracksByRefId($mRefTable, $req->id);
            $fullDetailsData['levelComment'] = $levelComment;

            $citizenComment = $mWorkflowTracks->getCitizenTracks($mRefTable, $req->id, $details->citizen_user_id);
            $fullDetailsData['citizenComment'] = $citizenComment;

            $metaReqs['customFor'] = 'PROPERTY-HARVESTING';
            $metaReqs['wfRoleId'] = $details->current_role;
            $metaReqs['workflowId'] = $details->workflow_id;
            $metaReqs['lastRoleId'] = $details->last_role_id;
            $req->request->add($metaReqs);

            $forwardBackward = $mForwardBackward->getRoleDetails($req);
            $fullDetailsData['roleDetails'] = collect($forwardBackward)['original']['data'];

            $fullDetailsData['timelineData'] = collect($req);

            $custom = $mCustomDetails->getCustomDetails($req);
            $fullDetailsData['departmentalPost'] = collect($custom)['original']['data'];

            return responseMsg(true, "", remove_null($fullDetailsData));
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * |--------------------------- Post Next Level Application(forward or backward application) ------------------------------------------------|
     * | Rating-
     * | Status - Closed
     * | Query Cost - 446ms
     */
    public function postNextLevel(Request $req)
    {
        $wfLevels = Config::get('PropertyConstaint.SAF-LABEL');
        try {
            $req->validate([
                'applicationId' => 'required|integer',
                'senderRoleId' => 'required|integer',
                'receiverRoleId' => 'required|integer',
                'comment' => $req->senderRoleId == $wfLevels['BO'] ? 'nullable' : 'required',
                'action' => 'required|In:forward,backward'
            ]);

            DB::beginTransaction();

            $track = new WorkflowTrack();
            $harvesting = PropActiveHarvesting::find($req->applicationId);
            $senderRoleId = $req->senderRoleId;

            if ($req->action == 'forward') {
                $this->checkPostCondition($senderRoleId, $wfLevels, $harvesting);          // Check Post Next level condition
                $harvesting->last_role_id = $req->receiverRoleId;                      // Update Last Role Id
                $metaReqs['verificationStatus'] = 1;
            }

            $metaReqs['moduleId'] = Config::get('module-constants.PROPERTY_MODULE_ID');
            $metaReqs['workflowId'] = $harvesting->workflow_id;
            $metaReqs['refTableDotId'] = 'prop_active_harvestings.id';
            $metaReqs['refTableIdValue'] = $req->applicationId;
            $metaReqs['verificationStatus'] = $req->verificationStatus;
            $metaReqs['comment'] = $req->comment;

            $req->request->add($metaReqs);
            $track->saveTrack($req);


            // harvesting Application Update Current Role Updation
            $harvesting->current_role = $req->receiverRoleId;
            $harvesting->save();


            DB::commit();
            return responseMsgs(true, "Successfully Forwarded The Application!!", "", '011110', 01, '446ms', 'Post', $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * |-------------------------------------Final Approval and Rejection of the Application ------------------------------------------------|
     * | Rating-
     * | Status- Closed
     */
    public function finalApprovalRejection(Request $req)
    {
        try {
            $req->validate([
                'roleId' => 'required',
                'applicationId' => 'required',
                'status' => 'required',

            ]);
            // Check if the Current User is Finisher or Not         
            $activeHarvesting = PropActiveHarvesting::query()
                ->where('id', $req->applicationId)
                ->first();

            $propProperties = PropProperty::where('id', $activeHarvesting->property_id)
                ->first();

            $getFinisherQuery = $this->getFinisherId($activeHarvesting->workflow_id);                                 // Get Finisher using Trait
            $refGetFinisher = collect(DB::select($getFinisherQuery))->first();
            if ($refGetFinisher->role_id != $req->roleId) {
                return responseMsg(false, " Access Forbidden", "");
            }

            DB::beginTransaction();
            // Approval
            if ($req->status == 1) {
                // Harvesting Application replication

                $approvedHarvesting = $activeHarvesting->replicate();
                $approvedHarvesting->setTable('prop_harvestings');
                $approvedHarvesting->id = $activeHarvesting->id;
                $approvedHarvesting->save();
                $activeHarvesting->delete();

                $approvedProperties = $propProperties->replicate();
                $approvedProperties->setTable('log_prop_properties');
                $approvedProperties->id = $propProperties->id;
                $approvedProperties->save();

                $propProperties->is_water_harvesting = true;
                $propProperties->save();

                $msg = "Application Successfully Approved !!";
            }
            // Rejection
            if ($req->status == 0) {
                // Harvesting Application replication
                $rejectedHarvesting = $activeHarvesting->replicate();
                $rejectedHarvesting->setTable('prop_rejected_harvestings');
                $rejectedHarvesting->id = $activeHarvesting->id;
                $rejectedHarvesting->save();
                $activeHarvesting->delete();
                $msg = "Application Successfully Rejected !!";
            }



            DB::commit();
            return responseMsgs(true, $msg, "", '011111', 01, '391ms', 'Post', $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * |-------------------------------------  Rejection of the Harvesting ------------------------------------------------|
     * | Rating- 
     * | Status - open
     */
    public function rejectionOfHarvesting(Request $req)
    {
        try {
            $req->validate([
                'applicationId' => 'required',
            ]);
            $userId = authUser()->id;
            $getRole = $this->getRoleIdByUserId($userId);
            $roleId = $getRole->map(function ($value, $key) {                         // Get user Workflow Roles
                return $value->wf_role_id;
            });

            if (collect($roleId)->first() != $req->roleId) {
                return responseMsg(false, " Access Forbidden!", "");
            }

            $activeHarvesting = PropActiveHarvesting::query()
                ->where('id', $req->applicationId)
                ->first();

            $rejectedHarvesting = $activeHarvesting->replicate();
            $rejectedHarvesting->setTable('prop_rejected_harvestings');
            $rejectedHarvesting->id = $activeHarvesting->id;
            $rejectedHarvesting->save();
            $activeHarvesting->delete();

            return responseMsgs(true, "Application Rejected !!", "", '011112', 01, '348ms', 'Post', $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    // //harvesting doc by id
    // public function harvestingDocList(Request $req)
    // {
    //     try {
    //         $list = PropHarvestingDoc::select(
    //             'id',
    //             'doc_type as docName',
    //             'relative_path',
    //             'doc_name as docUrl',
    //             'verify_status as docStatus',
    //             'remarks as docRemarks'
    //         )
    //             ->where('prop_harvesting_docs.status', 1)
    //             ->where('prop_harvesting_docs.harvesting_id', $req->id)
    //             ->get();

    //         $list = $list->map(function ($val) {
    //             $path = $this->_bifuraction->readDocumentPath($val->relative_path . $val->docUrl);
    //             $val->docUrl = $path;
    //             return $val;
    //         });

    //         if ($list == Null) {
    //             return responseMsg(false, "No Data Found", '');
    //         } else
    //             return responseMsgs(true, "Success", remove_null($list), '011105', 01, '311ms - 379ms', 'Post', $req->deviceId);
    //     } catch (Exception $e) {
    //         echo $e->getMessage();
    //     }
    // }

    //doc upload
    // public function docUpload(Request $req)
    // {
    //     try {
    //         if ($file = $req->file('rwhImage')) {
    //             $docName = "rwhImage";
    //             $name = $this->moveFile($docName, $file);

    //             $checkExisting = PropHarvestingDoc::where('harvesting_id', $req->id)
    //                 ->where('doc_type', $docName)
    //                 ->get()
    //                 ->first();

    //             if ($checkExisting) {
    //                 $updateDocument = new PropHarvestingDoc();
    //                 $updateDocument->updateDocument($req, $docName, $name);
    //             } else {

    //                 $harvestingDoc = new PropHarvestingDoc();
    //                 $harvestingDoc->harvesting_id = $req->id;
    //                 $harvestingDoc->citizenDocUpload($harvestingDoc, $name, $docName);
    //             }
    //         }

    //         if ($file = $req->file('rwhForm')) {
    //             $docName = "rwhForm";
    //             $name = $this->moveFile($docName, $file);

    //             $checkExisting = PropHarvestingDoc::where('harvesting_id', $req->id)
    //                 ->where('doc_type', $docName)
    //                 ->get()
    //                 ->first();

    //             if ($checkExisting) {
    //                 $updateDocument = new PropHarvestingDoc();
    //                 $updateDocument->updateDocument($req, $docName, $name);
    //             } else {

    //                 $harvestingDoc = new PropHarvestingDoc();
    //                 $harvestingDoc->harvesting_id = $req->id;
    //                 $harvestingDoc->citizenDocUpload($harvestingDoc, $name, $docName);
    //             }
    //         }
    //         return responseMsgs(true, "Successfully Uploaded", '', '011106', 01, '313ms - 354ms', 'Post', $req->deviceId);
    //     } catch (Exception $e) {
    //         echo $e->getMessage();
    //     }
    // }

    /**
     * | Independent Comments
     */
    public function commentIndependent(Request $req)
    {
        $req->validate([
            'comment' => 'required',
            'applicationId' => 'required|integer',
            'senderRoleId' => 'nullable|integer'
        ]);

        try {
            $workflowTrack = new WorkflowTrack();
            $harvesting = PropActiveHarvesting::find($req->applicationId);                // SAF Details
            $mModuleId = Config::get('module-constants.PROPERTY_MODULE_ID');
            $metaReqs = array();
            DB::beginTransaction();
            // Save On Workflow Track For Level Independent
            $metaReqs = [
                'workflowId' => $harvesting->workflow_id,
                'moduleId' => $mModuleId,
                'refTableDotId' => "prop_active_harvestings.id",
                'refTableIdValue' => $harvesting->id,
                'message' => $req->comment
            ];
            // For Citizen Independent Comment
            if (!$req->senderRoleId) {
                $metaReqs = array_merge($metaReqs, ['citizenId' => $harvesting->user_id]);
            }

            $req->request->add($metaReqs);
            $workflowTrack->saveTrack($req);

            DB::commit();
            return responseMsgs(true, "You Have Commented Successfully!!", ['Comment' => $req->comment], "010108", "1.0", "", "POST", "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     *  get uploaded documents
     */
    public function getUploadedDocuments(Request $req)
    {
        $req->validate([
            'applicationId' => 'required|numeric'
        ]);
        try {
            $mWfActiveDocument = new WfActiveDocument();
            $mPropActiveHarvesting = new PropActiveHarvesting();
            $moduleId = Config::get('module-constants.PROPERTY_MODULE_ID');

            $harvestingDetails = $mPropActiveHarvesting->getHarvestingNo($req->applicationId);
            if (!$harvestingDetails)
                throw new Exception("Application Not Found for this application Id");

            $workflowId = $harvestingDetails->workflow_id;
            $documents = $mWfActiveDocument->getDocsByAppId($req->applicationId, $workflowId, $moduleId);
            return responseMsgs(true, "Uploaded Documents", remove_null($documents), "010102", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "010202", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }


    /**
     * to upload documenr
     */
    public function uploadDocument(Request $req)
    {
        $req->validate([
            "applicationId" => "required|numeric",
            "document" => "required|mimes:pdf,jpeg,png,jpg,gif",
            "docCode" => "required",
        ]);

        try {
            $metaReqs = array();
            $docUpload = new DocUpload;
            $mWfActiveDocument = new WfActiveDocument();
            $mPropActiveHarvesting = new PropActiveHarvesting();
            $relativePath = Config::get('PropertyConstaint.HARVESTING_RELATIVE_PATH');
            $getHarvestingDtls = $mPropActiveHarvesting->getHarvestingNo($req->applicationId);
            $refImageName = $req->docCode;
            $refImageName = $getHarvestingDtls->id . '-' . $refImageName;
            $document = $req->document;
            $imageName = $docUpload->upload($refImageName, $document, $relativePath);

            $metaReqs['moduleId'] = Config::get('module-constants.PROPERTY_MODULE_ID');
            $metaReqs['activeId'] = $getHarvestingDtls->id;
            $metaReqs['workflowId'] = $getHarvestingDtls->workflow_id;
            $metaReqs['ulbId'] = $getHarvestingDtls->ulb_id;
            $metaReqs['relativePath'] = $relativePath;
            $metaReqs['document'] = $imageName;
            $metaReqs['docCode'] = $req->docCode;

            $metaReqs = new Request($metaReqs);
            $mWfActiveDocument->postDocuments($metaReqs);
            return responseMsgs(true, "Document Uploadation Successful", "", "010201", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "010201", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }


    /**
     *  send back to citizen
     */
    public function backToCitizen(Request $req)
    {
        $req->validate([
            'applicationId' => "required"
        ]);
        try {
            $redis = Redis::connection();
            $harvesting = PropActiveHarvesting::find($req->applicationId);

            $workflowId = $harvesting->workflow_id;
            $backId = json_decode(Redis::get('workflow_initiator_' . $workflowId));
            if (!$backId) {
                $backId = WfWorkflowrolemap::where('workflow_id', $workflowId)
                    ->where('is_initiator', true)
                    ->first();
                $redis->set('workflow_initiator_' . $workflowId, json_encode($backId));
            }

            $harvesting->current_role = $backId->wf_role_id;
            $harvesting->parked = 1;
            $harvesting->save();

            $metaReqs['moduleId'] = Config::get('module-constants.PROPERTY_MODULE_ID');
            $metaReqs['workflowId'] = $harvesting->workflow_id;
            $metaReqs['refTableDotId'] = 'prop_active_concessions.id';
            $metaReqs['refTableIdValue'] = $req->concessionId;
            $metaReqs['verificationStatus'] = $req->verificationStatus;
            $metaReqs['senderRoleId'] = $req->currentRoleId;
            $req->request->add($metaReqs);
            $track = new WorkflowTrack();
            $track->saveTrack($req);

            return responseMsgs(true, "Successfully Done", "", "", '010710', '01', '358ms', 'Post', '');
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Back To Citizen Inbox
     */
    public function btcInboxList()
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

            $harvestingList = new PropActiveHarvesting();
            $harvesting = $harvestingList->getHarvestingList($ulbId)
                ->whereIn('prop_active_harvestings.current_role', $roleId)
                ->whereIn('a.ward_mstr_id', $occupiedWards)
                ->where('parked', true)
                ->orderByDesc('prop_active_harvestings.id')
                ->get();

            return responseMsgs(true, "BTC Inbox List", remove_null($harvesting), 010717, 1.0, "271ms", "POST", "", "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", 010717, 1.0, "271ms", "POST", "", "");
        }
    }

    /**
     * 
     */
    public function getDocList(Request $req)
    {
        try {
            $mPropActiveHarvesting = new PropActiveHarvesting();

            $refApplication = $mPropActiveHarvesting->getHarvestingNo($req->applicationId);
            if (!$refApplication)
                throw new Exception("Application Not Found for this id");

            $harvestingDoc['listDocs'] = $this->getHarvestingDoc($refApplication);

            return responseMsgs(true, "Doc List", remove_null($harvestingDoc), 010717, 1.0, "271ms", "POST", "", "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "010203", "1.0", "", 'POST', "");
        }
    }


    public function getHarvestingDoc($refApplication)
    {
        $mRefReqDocs = new RefRequiredDocument();
        $mWfActiveDocument = new WfActiveDocument();
        $applicationId = $refApplication->id;
        $workflowId = $refApplication->workflow_id;
        $moduleId = Config::get('module-constants.PROPERTY_MODULE_ID');
        $documentList = $mRefReqDocs->getDocsByDocCode($moduleId, "PROP_RAIN_WATER_HARVESTING")->requirements;

        $uploadedDocs = $mWfActiveDocument->getDocByRefIds($applicationId, $workflowId, $moduleId);
        $explodeDocs = collect(explode('#', $documentList));

        $filteredDocs = $explodeDocs->map(function ($explodeDoc) use ($uploadedDocs) {
            $document = explode(',', $explodeDoc);
            $key = array_shift($document);
            $label = array_shift($document);
            $documents = collect();

            collect($document)->map(function ($item) use ($uploadedDocs, $documents) {
                $uploadedDoc = $uploadedDocs->where('doc_code', $item)->first();
                if ($uploadedDoc) {
                    $response = [
                        "documentCode" => $item,
                        "ownerId" => $uploadedDoc->owner_dtl_id ?? "",
                        "docPath" => $uploadedDoc->doc_path ?? ""
                    ];
                    $documents->push($response);
                }
            });
            $reqDoc['docType'] = $key;
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
        return $filteredDocs;
    }

    /**
     * citizen document list
     */
    public function citizenDocList()
    {
        $data =  RefRequiredDocument::where('code', 'PROP_RAIN_WATER_HARVESTING')
            ->first();

        $document = explode(',', $data->requirements);
        $key = array_shift($document);
        $code = collect($document);
        $label = array_shift($document);
        $documents = collect();

        $reqDoc['docType'] = $key;
        $reqDoc['docName'] = substr($label, 1, -1);
        $reqDoc['uploadedDoc'] = $documents->first();

        $reqDoc['masters'] = collect($document)->map(function ($doc) {
            $strLower = strtolower($doc);
            $strReplace = str_replace('_', ' ', $strLower);
            $arr = [
                "documentCode" => $doc,
                "docVal" => ucwords($strReplace),
            ];
            return $arr;
        });

        return responseMsgs(true, "Citizen Doc List", remove_null($reqDoc), 010717, 1.0, "413ms", "POST", "", "");
    }

    /**
     * | Document Verify Reject
     */
    public function docVerifyReject(Request $req)
    {
        $req->validate([
            'id' => 'required|digits_between:1,9223372036854775807',
            'applicationId' => 'required|digits_between:1,9223372036854775807',
            'docRemarks' =>  $req->docStatus == "Rejected" ? 'required|regex:/^[a-zA-Z1-9][a-zA-Z1-9\. \s]+$/' : "nullable",
            'docStatus' => 'required|in:Verified,Rejected'
        ]);

        try {
            // Variable Assignments
            $mWfDocument = new WfActiveDocument();
            $mPropActiveHarvesting = new PropActiveHarvesting();
            $mWfRoleusermap = new WfRoleusermap();
            $wfDocId = $req->id;
            $userId = authUser()->id;
            $applicationId = $req->applicationId;
            $wfLevel = Config::get('PropertyConstaint.SAF-LABEL');
            // Derivative Assigments
            $harvestingDtl = $mPropActiveHarvesting->getHarvestingNo($applicationId);
            $safReq = new Request([
                'userId' => $userId,
                'workflowId' => $harvestingDtl->workflow_id
            ]);
            $senderRoleDtls = $mWfRoleusermap->getRoleByUserWfId($safReq);
            if (!$senderRoleDtls || collect($senderRoleDtls)->isEmpty())
                throw new Exception("Role Not Available");

            $senderRoleId = $senderRoleDtls->wf_role_id;

            if ($senderRoleId != $wfLevel['UTC'])                                // Authorization for Dealing Assistant Only
                throw new Exception("You are not Authorized");

            if (!$harvestingDtl || collect($harvestingDtl)->isEmpty())
                throw new Exception("Application Details Not Found");

            $ifFullDocVerified = $this->ifFullDocVerified($applicationId);       // (Current Object Derivative Function 4.1)

            if ($ifFullDocVerified == 1)
                throw new Exception("Document Fully Verified");

            DB::beginTransaction();
            if ($req->docStatus == "Verified") {
                $status = 1;
            }
            if ($req->docStatus == "Rejected") {
                $status = 2;
                // For Rejection Doc Upload Status and Verify Status will disabled
                $harvestingDtl->doc_upload_status = 0;
                $harvestingDtl->doc_verify_status = 0;
                $harvestingDtl->save();
            }

            $reqs = [
                'remarks' => $req->docRemarks,
                'verify_status' => $status,
                'action_taken_by' => $userId
            ];
            $mWfDocument->docVerifyReject($wfDocId, $reqs);
            $ifFullDocVerifiedV1 = $this->ifFullDocVerified($applicationId);

            if ($ifFullDocVerifiedV1 == 1) {                                     // If The Document Fully Verified Update Verify Status
                $harvestingDtl->doc_verify_status = 1;
                $harvestingDtl->save();
            }

            DB::commit();
            return responseMsgs(true, $req->docStatus . " Successfully", "", "010204", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "010204", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Check if the Document is Fully Verified or Not (4.1)
     */
    public function ifFullDocVerified($applicationId)
    {
        $mPropActiveHarvesting = new PropActiveHarvesting();
        $mWfActiveDocument = new WfActiveDocument();
        $refSafs = $mPropActiveHarvesting->getHarvestingNo($applicationId);                      // Get Saf Details
        $refReq = [
            'activeId' => $applicationId,
            'workflowId' => $refSafs->workflow_id,
            'moduleId' => Config::get('module-constants.PROPERTY_MODULE_ID')
        ];
        $req = new Request($refReq);
        $refDocList = $mWfActiveDocument->getDocsByActiveId($req);
        // Property List Documents
        $ifPropDocUnverified = $refDocList->contains('verify_status', 0);
        if ($ifPropDocUnverified == 1)
            return 0;
        else
            return 1;
    }


    /**
     * | Site Verification
     * | @param req requested parameter
     */
    public function siteVerification(Request $req)
    {
        try {
            $taxCollectorRole = Config::get('PropertyConstaint.SAF-LABEL.TC');
            $ulbTaxCollectorRole = Config::get('PropertyConstaint.SAF-LABEL.UTC');
            $verificationStatus = $req->verificationStatus;                                             // Verification Status true or false
            $propActiveHarvesting = new PropActiveHarvesting();
            $verification = new PropRwhVerification();
            $mWfRoleUsermap = new WfRoleusermap();
            $userId = authUser()->id;
            $ulbId = authUser()->ulb_id;


            $applicationDtls = $propActiveHarvesting->getHarvestingNo($req->applicationId);
            $workflowId = $applicationDtls->workflow_id;
            $getRoleReq = new Request([                                                 // make request to get role id of the user
                'userId' => $userId,
                'workflowId' => $workflowId
            ]);

            $readRoleDtls = $mWfRoleUsermap->getRoleByUserWfId($getRoleReq);
            $roleId = $readRoleDtls->wf_role_id;

            switch ($roleId) {
                case $taxCollectorRole;                                                                  // In Case of Agency TAX Collector
                    if ($verificationStatus == 1) {
                        $req->agencyVerification = true;
                        $msg = "Site Successfully Verified";
                    }
                    if ($verificationStatus == 0) {
                        $req->agencyVerification = false;
                        $msg = "Site Successfully rebuted";
                    }
                    break;
                    DB::beginTransaction();
                case $ulbTaxCollectorRole;                                                                // In Case of Ulb Tax Collector
                    if ($verificationStatus == 1) {
                        $req->ulbVerification = true;
                        $msg = "Site Successfully Verified";
                    }
                    if ($verificationStatus == 0) {
                        $req->ulbVerification = false;
                        $msg = "Site Successfully rebuted";
                    }
                    $propActiveHarvesting->verifyFieldStatus($req->applicationId);                                         // Enable Fields Verify Status
                    break;

                default:
                    return responseMsg(false, "Forbidden Access", "");
            }

            // return $applicationDtls;

            $req->merge([
                'propertyId' => $applicationDtls->property_id,
                'harvestingId' => $applicationDtls->id,
                'harvestingStatus' => $applicationDtls->harvesting_status,
                'userId' => $userId,
                'ulbId' => $ulbId,
            ]);
            $verificationId = $verification->store($req);

            DB::commit();
            return responseMsgs(true, $msg, "", "010118", "1.0", "310ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }


    // Get TC Verifications
    public function getTcVerifications(Request $req)
    {
        try {
            $data = array();
            $mPropRwhVerification = new PropRwhVerification();

            $data = $mPropRwhVerification->getVerificationsData($req->applicationId);           // <--------- Prop Saf Verification Model Function to Get Prop Saf Verifications Data 

            return responseMsgs(true, "TC Verification Details", remove_null($data), "010120", "1.0", "258ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }
}
