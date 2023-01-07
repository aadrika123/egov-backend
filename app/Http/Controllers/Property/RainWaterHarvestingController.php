<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Models\Property\PropActiveHarvesting;
use App\Models\Property\PropHarvestingDoc;
use App\Models\Property\PropHarvestingLevelpending;
use App\Models\Workflows\WfWorkflow;
use App\Models\WorkflowTrack;
use App\Repository\Property\Concrete\PropertyBifurcation;
use Illuminate\Http\Request;
use App\Traits\Property\SAF;
use App\Traits\Ward;
use App\Traits\Workflow\Workflow;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Exception;

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
            $moduleId = 2;
            $request->validate([
                'isWaterHarvestingBefore' => 'required',
                'dateOfCompletion' => 'required|date',
                'propertyId' => 'required',
            ]);
            $userId = auth()->user()->id;
            $ulbId = auth()->user()->ulb_id;

            $ulbWorkflowId = WfWorkflow::where('wf_master_id', $this->_workflowId)
                ->where('ulb_id', $ulbId)
                ->first();

            $applicationNo = $this->generateApplicationNo($ulbId, $userId);
            $refInitiatorRoleId = $this->getInitiatorId($ulbWorkflowId->id);                // Get Current Initiator ID
            $refFinisherRoleId = $this->getFinisherId($ulbWorkflowId->id);
            $finisherRoleId = DB::select($refFinisherRoleId);
            $initiatorRoleId = DB::select($refInitiatorRoleId);

            $save = new PropActiveHarvesting();
            $waterHaravestingId  = $save->saves($request, $ulbWorkflowId, $initiatorRoleId, $finisherRoleId, $applicationNo);

            if ($file = $request->file('rwhImage')) {
                $docName = "rwhImage";
                $name = $this->moveFile($docName, $file);

                $harvestingDoc = new PropHarvestingDoc();
                $harvestingDoc->harvesting_id = $waterHaravestingId;
                $harvestingDoc->citizenDocUpload($harvestingDoc, $name, $docName);
            }

            if ($file = $request->file('rwhForm')) {
                $docName = "rwhForm";
                $name = $this->moveFile($docName, $file);

                $harvestingDoc = new PropHarvestingDoc();
                $harvestingDoc->harvesting_id = $waterHaravestingId;
                $harvestingDoc->citizenDocUpload($harvestingDoc, $name, $docName);
            }

            /**
             to be removed
             */
            //level pending
            if (isset($applicationNo)) {

                // $labelPending = new PropHarvestingLevelpending();
                // $labelPending->harvesting_id = $waterHaravestingId;
                // $labelPending->receiver_role_id = collect($initiatorRoleId)->first()->role_id;
                // $labelPending->sender_user_id = $userId;
                // $labelPending->save();

                $track = new WorkflowTrack();
                $metaReqs['workflowId'] = $ulbWorkflowId->id;
                $metaReqs['refTableDotId'] = 'prop_active_harvestings.id';
                $metaReqs['refTableIdValue'] = $waterHaravestingId;
                $metaReqs['moduleId'] = $moduleId;

                $request->request->add($metaReqs);
                $track->saveTrack($request);
            }
            return responseMsg(true, "Application applied!", $applicationNo);
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

            $workflowRoles = $this->getRoleIdByUserId($userId);
            $roleId = $workflowRoles->map(function ($value, $key) {                         // Get user Workflow Roles
                return $value->wf_role_id;
            });

            $refWard = $this->getWardByUserId($userId);                                     // Get Ward List by user Id
            $occupiedWards = $refWard->map(function ($value, $key) {
                return $value->ward_id;
            });

            $harvestingList = new PropActiveHarvesting();
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
    public function escalateApplication($req)
    {
        try {
            $userId = auth()->user()->id;
            if ($req->escalateStatus == 1) {
                $harvesting = PropActiveHarvesting::find($req->id);
                $harvesting->is_escalate = 1;
                $harvesting->escalated_by = $userId;
                $harvesting->save();
                return responseMsg(true, "Successfully Escalated the application", "");
            }
            if ($req->escalateStatus == 0) {
                $harvesting = PropActiveHarvesting::find($req->id);
                $harvesting->is_escalate = 0;
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
            $auth = auth()->user();
            $userId = $auth->id;
            $ulbId = $auth->ulb_id;
            $wardId = $this->getWardByUserId($userId);

            $occupiedWards = collect($wardId)->map(function ($ward) {                               // Get Occupied Ward of the User
                return $ward->ward_id;
            });

            $harvesting = $this->getHarvestingList($ulbId)                                         // Get harvesting
                ->where('prop_active_harvestings.is_escalate', true)
                ->whereIn('a.ward_mstr_id', $occupiedWards)
                ->orderByDesc('prop_active_harvestings.id')
                ->get();

            return responseMsg(true, "Inbox List", remove_null($harvesting));
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
                'harvestingId' => 'required',
                'senderRoleId' => 'required',
                'receiverRoleId' => 'required',
                'message' => 'required'
            ]);
            DB::beginTransaction();

            $track = new WorkflowTrack();
            $metaReqs['moduleId'] = Config::get('module-constants.PROPERTY_MODULE_ID');
            $metaReqs['workflowId'] = Config::get('workflow-constants.RAIN_WATER_HARVESTING_ID');
            $metaReqs['refTableDotId'] = 'prop_active_harvestings.id';
            $metaReqs['refTableIdValue'] = $req->harvestingId;
            // $metaReqs['senderRoleId'] = $req->senderRoleId;
            // $metaReqs['receiverRoleId'] = $req->receiverRoleId;
            // $metaReqs['verificationStatus'] = $req->verificationStatus;
            // $metaReqs['message'] = $req->message;

            // return $metaReqs;
            $req->request->add($metaReqs);
            $track->saveTrack($req);


            // harvesting Application Update Current Role Updation
            $harvesting = PropActiveHarvesting::find($req->harvestingId);
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
                'workflowId' => 'required',
                'roleId' => 'required',
                'harvestingId' => 'required',
                'status' => 'required',

            ]);
            // Check if the Current User is Finisher or Not                                                                                 
            $getFinisherQuery = $this->getFinisherId($req->workflowId);                                 // Get Finisher using Trait
            $refGetFinisher = collect(DB::select($getFinisherQuery))->first();
            if ($refGetFinisher->role_id != $req->roleId) {
                return responseMsg(false, " Access Forbidden", "");
            }

            DB::beginTransaction();
            // Approval
            if ($req->status == 1) {
                // Harvesting Application replication
                $activeHarvesting = PropActiveHarvesting::query()
                    ->where('id', $req->harvestingId)
                    ->first();

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
                $activeHarvesting = PropActiveHarvesting::query()
                    ->where('id', $req->harvestingId)
                    ->first();

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
}
