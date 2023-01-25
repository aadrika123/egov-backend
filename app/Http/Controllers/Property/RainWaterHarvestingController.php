<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\MicroServices\DocUpload;
use App\Models\CustomDetail;
use App\Models\Property\PropActiveHarvesting;
use App\Models\Property\PropFloor;
use App\Models\Property\PropHarvestingDoc;
use App\Models\Property\PropHarvestingLevelpending;
use App\Models\Property\PropOwner;
use App\Models\Property\RefPropDocsRequired;
use App\Models\Workflows\WfActiveDocument;
use App\Models\Workflows\WfWorkflow;
use App\Models\Workflows\WfWorkflowrolemap;
use App\Models\WorkflowTrack;
use App\Repository\Property\Concrete\PropertyBifurcation;
use App\Repository\WorkflowMaster\Concrete\WorkflowMap;
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
 * | Created On - 22-11-2022
 * | Created By -  Mrinal Kumar
 * | Property RainWaterHarvesting apply
 */

class RainWaterHarvestingController extends Controller
{
    use SAF;
    use Workflow;
    use Ward;
    use SafDetailsTrait;
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
            $userType = auth()->user()->user_type;

            if ($userType == 'Citizen') {
                $userId = auth()->user()->id;
            }

            if ($userType != 'Citizen') {
                $userId = auth()->user()->id;
            }

            $ulbWorkflowId = WfWorkflow::where('wf_master_id', $this->_workflowId)
                ->where('ulb_id', $ulbId)
                ->first();

            // $applicationNo = $this->generateApplicationNo($ulbId, $userId);
            $refInitiatorRoleId = $this->getInitiatorId($ulbWorkflowId->id);                // Get Current Initiator ID
            $refFinisherRoleId = $this->getFinisherId($ulbWorkflowId->id);
            $finisherRoleId = DB::select($refFinisherRoleId);
            $initiatorRoleId = DB::select($refInitiatorRoleId);

            $mPropActiveHarvesting = new PropActiveHarvesting();
            $waterHaravesting  = $mPropActiveHarvesting->saves($request, $ulbWorkflowId, $initiatorRoleId, $finisherRoleId,  $userId);


            if ($file = $request->file('document')) {

                $docreq =  RefPropDocsRequired::select('id', 'doc_name')
                    ->where('doc_type', 'water_harvesting')
                    ->first();
                $docName = $docreq->doc_name;

                $name = $this->moveFile($docName, $file);
                $harvestingDoc = new PropHarvestingDoc();
                $harvestingDoc->harvesting_id = $waterHaravesting->id;
                $harvestingDoc->citizenDocUpload($harvestingDoc, $name, $docName);
            }

            if ($file = $request->file('rwhForm')) {
                $docName = "rwhForm";
                $name = $this->moveFile($docName, $file);

                $harvestingDoc = new PropHarvestingDoc();
                $harvestingDoc->harvesting_id = $waterHaravesting->id;
                $harvestingDoc->citizenDocUpload($harvestingDoc, $name, $docName);
            }

            /**
             to be removed
             */
            //level pending
            if (isset($waterHaravesting->application_no)) {

                $track = new WorkflowTrack();
                $metaReqs['workflowId'] = $ulbWorkflowId->id;
                $metaReqs['refTableDotId'] = 'prop_active_harvestings.id';
                $metaReqs['refTableIdValue'] = $waterHaravesting->id;
                $metaReqs['moduleId'] = Config::get('module-constants.PROPERTY_MODULE_ID');

                $request->request->add($metaReqs);
                $track->saveTrack($request);
            }
            return responseMsg(true, "Application applied!", $waterHaravesting->application_no);
        } catch (Exception $error) {
            return responseMsg(false, "Error!", $error->getMessage());
        }
    }

    /**
     * |----------------------- function for generating application no 1.1.1 --------------------------
     * |@param ulbId
     * |@param userId
     * |@var applicationId
     * | Rating : 0.1
     */
    public function generateApplicationNo($ulbId, $userId)
    {
        $applicationId = "RWH-" . $ulbId . "-" . $userId . "-" . rand(0, 99999999999999);
        return $applicationId;
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
            'id' => 'required|integer',
            'escalateStatus' => 'required|bool',
        ]);
        try {
            $userId = auth()->user()->id;
            if ($req->escalateStatus == 1) {
                $harvesting = PropActiveHarvesting::find($req->id);
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

            $corrDetails = $this->generateCorrDtls($details);              // (Corresponding Address Details) Trait function to generate corresponding address details
            $corrElement = [
                'headerTitle' => 'Corresponding Address',
                'data' => $corrDetails,
            ];

            $electDetails = $this->generateElectDtls($details);            // (Electricity & Water Details) Trait function to generate Electricity Details
            $electElement = [
                'headerTitle' => 'Electricity & Water Details',
                'data' => $electDetails
            ];
            $fullDetailsData['application_no'] = $details->application_no;
            $fullDetailsData['apply_date'] = Carbon::parse($details->created_at)->format('Y-m-d');
            $fullDetailsData['fullDetailsData']['dataArray'] = new Collection([$basicElement, $propertyElement, $corrElement, $electElement]);

            // Table Array
            $ownerList = $mPropOwners->getOwnersByPropId($details->property_id);
            $ownerList = json_decode(json_encode($ownerList), true);       // Convert Std class to array
            $ownerDetails = $this->generateOwnerDetails($ownerList);
            $ownerElement = [
                'headerTitle' => 'Owner Details',
                'tableHead' => ["#", "Owner Name", "Gender", "DOB", "Guardian Name", "Relation", "Mobile No", "Aadhar", "PAN", "Email", "IsArmedForce", "isSpeciallyAbled"],
                'tableData' => $ownerDetails
            ];
            $floorList = $mPropFloors->getPropFloors($details->property_id);    // Model Function to Get Floor Details
            $floorDetails = $this->generateFloorDetails($floorList);
            $floorElement = [
                'headerTitle' => 'Floor Details',
                'tableHead' => ["#", "Floor", "Usage Type", "Occupancy Type", "Construction Type", "Build Up Area", "From Date", "Upto Date"],
                'tableData' => $floorDetails
            ];
            $fullDetailsData['fullDetailsData']['tableArray'] = new Collection([$ownerElement, $floorElement]);
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
        try {
            $req->validate([
                'harvestingId' => 'required|integer',
                'senderRoleId' => 'required|integer',
                'receiverRoleId' => 'required|integer',
                'comment' => 'required'
            ]);

            DB::beginTransaction();

            $track = new WorkflowTrack();
            $harvesting = PropActiveHarvesting::find($req->harvestingId);
            $metaReqs['moduleId'] = Config::get('module-constants.PROPERTY_MODULE_ID');
            $metaReqs['workflowId'] = $harvesting->workflow_id;
            $metaReqs['refTableDotId'] = 'prop_active_harvestings.id';
            $metaReqs['refTableIdValue'] = $req->harvestingId;
            $metaReqs['senderRoleId'] = $req->senderRoleId;
            $metaReqs['receiverRoleId'] = $req->receiverRoleId;
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
                'harvestingId' => 'required',
                'status' => 'required',

            ]);
            // Check if the Current User is Finisher or Not         
            $activeHarvesting = PropActiveHarvesting::query()
                ->where('id', $req->harvestingId)
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
                'harvestingId' => 'required',
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
                ->where('id', $req->harvestingId)
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

    //applied harvestig list
    public function waterHarvestingList()
    {
        try {
            $list = PropActiveHarvesting::select(
                'prop_active_harvestings.id',
                'a.applicant_name as owner_name',
                'a.ward_mstr_id',
                'u.ward_name as ward_no',
                'a.holding_no',
                'a.prop_type_mstr_id',
                'p.property_type',
            )
                ->join('prop_properties as a', 'a.id', 'prop_active_harvestings.property_id')
                ->join('ref_prop_types as p', 'p.id', '=', 'a.prop_type_mstr_id')
                ->join('ulb_ward_masters as u', 'u.id', '=', 'a.ward_mstr_id')
                ->where('prop_active_harvestings.status', 1)
                ->orderByDesc('prop_active_harvestings.id')
                ->get();

            return responseMsgs(true, "Success", $list, '011103', 01, '300ms - 359ms', 'Post', '');
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    //applied harvesting list by id
    public function harvestingListById(Request $req)
    {
        try {
            $list = PropActiveHarvesting::select(
                'prop_active_harvestings.*',
                'a.applicant_name as owner_name',
                'a.ward_mstr_id',
                'u.ward_name as ward_no',
                'a.holding_no',
                'a.prop_type_mstr_id',
                'p.property_type',
            )
                ->join('prop_properties as a', 'a.id', 'prop_active_harvestings.property_id')
                ->join('ref_prop_types as p', 'p.id', 'a.prop_type_mstr_id')
                ->join('ulb_ward_masters as u', 'u.id', 'a.ward_mstr_id')
                ->where('prop_active_harvestings.status', 1)
                ->where('prop_active_harvestings.id', $req->id)
                ->first();

            if (is_null($list)) {
                return responseMsg(false, "No Data Found", '');
            } else
                return responseMsgs(true, "Success", $list, '011104', 01, '315ms - 342ms', 'Post', $req->deviceId);
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    //harvesting doc by id
    public function harvestingDocList(Request $req)
    {
        try {
            $list = PropHarvestingDoc::select(
                'id',
                'doc_type as docName',
                'relative_path',
                'doc_name as docUrl',
                'verify_status as docStatus',
                'remarks as docRemarks'
            )
                ->where('prop_harvesting_docs.status', 1)
                ->where('prop_harvesting_docs.harvesting_id', $req->id)
                ->get();

            $list = $list->map(function ($val) {
                $path = $this->_bifuraction->readDocumentPath($val->relative_path . $val->docUrl);
                $val->docUrl = $path;
                return $val;
            });

            if ($list == Null) {
                return responseMsg(false, "No Data Found", '');
            } else
                return responseMsgs(true, "Success", remove_null($list), '011105', 01, '311ms - 379ms', 'Post', $req->deviceId);
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    //doc upload
    public function docUpload(Request $req)
    {
        try {
            if ($file = $req->file('rwhImage')) {
                $docName = "rwhImage";
                $name = $this->moveFile($docName, $file);

                $checkExisting = PropHarvestingDoc::where('harvesting_id', $req->id)
                    ->where('doc_type', $docName)
                    ->get()
                    ->first();

                if ($checkExisting) {
                    $updateDocument = new PropHarvestingDoc();
                    $updateDocument->updateDocument($req, $docName, $name);
                } else {

                    $harvestingDoc = new PropHarvestingDoc();
                    $harvestingDoc->harvesting_id = $req->id;
                    $harvestingDoc->citizenDocUpload($harvestingDoc, $name, $docName);
                }
            }

            if ($file = $req->file('rwhForm')) {
                $docName = "rwhForm";
                $name = $this->moveFile($docName, $file);

                $checkExisting = PropHarvestingDoc::where('harvesting_id', $req->id)
                    ->where('doc_type', $docName)
                    ->get()
                    ->first();

                if ($checkExisting) {
                    $updateDocument = new PropHarvestingDoc();
                    $updateDocument->updateDocument($req, $docName, $name);
                } else {

                    $harvestingDoc = new PropHarvestingDoc();
                    $harvestingDoc->harvesting_id = $req->id;
                    $harvestingDoc->citizenDocUpload($harvestingDoc, $name, $docName);
                }
            }
            return responseMsgs(true, "Successfully Uploaded", '', '011106', 01, '313ms - 354ms', 'Post', $req->deviceId);
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    //doc status
    public function docStatus(Request $req)
    {
        try {
            $status = new PropHarvestingDoc();
            $status->docStatus($req);

            return responseMsgs(true, "Successfully Verified", '', '011107', 01, '290ms - 342ms', 'Post', $req->deviceId);
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    //moving function to location
    public function moveFile($docName, $file)
    {
        $name = time() . $docName . '.' . $file->getClientOriginalExtension();
        $path = storage_path('app/public/harvesting/' . $docName . '/');
        $file->move($path, $name);
        return $name;
    }

    /**
     * | Independent Comments
     */
    public function commentIndependent(Request $req)
    {
        $req->validate([
            'comment' => 'required',
            'harvestingId' => 'required|integer',
            'senderRoleId' => 'nullable|integer'
        ]);

        try {
            $workflowTrack = new WorkflowTrack();
            $harvesting = PropActiveHarvesting::find($req->harvestingId);                // SAF Details
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
    public function getUploadDocuments(Request $req)
    {
        $req->validate([
            'applicationId' => 'required|numeric'
        ]);
        try {
            $mWfActiveDocument = new WfActiveDocument();
            $mPropActiveHarvesting = new PropActiveHarvesting();

            $harvestingDetails = $mPropActiveHarvesting->getHarvestingNo($req->applicationId);
            if (!$harvestingDetails)
                throw new Exception("Application Not Found for this application Id");

            $appNo = $harvestingDetails->application_no;
            $documents = $mWfActiveDocument->getDocsByAppNo($appNo);
            return responseMsgs(true, "Uploaded Documents", remove_null($documents), "010102", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "010202", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }


    /**
     * 
     */
    public function uploadDocument(Request $req)
    {
        $req->validate([
            "applicationId" => "required|numeric",
            "document" => "required|mimes:pdf,jpeg,png,jpg,gif",
            "docMstrId" => "required|numeric",
            "docRefName" => "required"
        ]);

        try {
            $metaReqs = array();
            $docUpload = new DocUpload;
            $mWfActiveDocument = new WfActiveDocument();
            $mPropActiveHarvesting = new PropActiveHarvesting();
            $relativePath = Config::get('PropertyConstaint.HARVESTING_RELATIVE_PATH');
            $getHarvestingDtls = $mPropActiveHarvesting->getHarvestingNo($req->applicationId);
            $refImageName = $req->docRefName;
            $refImageName = $getHarvestingDtls->id . '-' . str_replace(' ', '_', $refImageName);
            $document = $req->document;

            $imageName = $docUpload->upload($refImageName, $document, $relativePath);

            $metaReqs['moduleId'] = Config::get('module-constants.PROPERTY_MODULE_ID');
            $metaReqs['activeId'] = $getHarvestingDtls->application_no;
            $metaReqs['workflowId'] = $getHarvestingDtls->workflow_id;
            $metaReqs['ulbId'] = $getHarvestingDtls->ulb_id;
            $metaReqs['relativePath'] = $relativePath;
            $metaReqs['image'] = $imageName;
            $metaReqs['docMstrId'] = $req->docMstrId;


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

            return responseMsgs(true, "BTC Inbox List", remove_null($harvesting), 010717, 1.0, "271ms", "POST", "", "");;
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", 010717, 1.0, "271ms", "POST", "", "");
        }
    }
}
