<?php

namespace App\Http\Controllers\Property;

use App\Repository\Property\Interfaces\iObjectionRepository;
use App\Http\Controllers\Controller;
use App\Models\CustomDetail;
use App\Models\PropActiveObjectionDocdtl;
use App\Models\Property\PropActiveObjection;
use App\Models\Property\PropFloor;
use Illuminate\Http\Request;
use App\Traits\Workflow\Workflow as WorkflowTrait;
use App\Traits\Property\Objection;
use App\Models\Property\RefPropObjectionType;
use App\Models\Property\PropOwner;
use App\Models\Workflows\WfWorkflowrolemap;
use App\Models\WorkflowTrack;
use App\Repository\WorkflowMaster\Concrete\WorkflowMap;
use App\Traits\Property\SafDetailsTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Redis;

class ObjectionController extends Controller
{
    use WorkflowTrait;
    use Objection;
    use SafDetailsTrait;

    protected $objection;
    protected $Repository;
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

            return responseMsg(true, "Successfully Retrieved", remove_null($ownerDetails));
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
    public function outbox()
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

        try {
            $mPropActiveObjection = new PropActiveObjection();
            $mPropOwners = new PropOwner();
            $mPropFloors = new PropFloor();
            $mCustomDetails = new CustomDetail();
            $mForwardBackward = new WorkflowMap();
            $mWorkflowTracks = new WorkflowTrack();
            $mRefTable = Config::get('PropertyConstaint.SAF_OBJECTION_REF_TABLE');
            $details = $mPropActiveObjection->getObjectionById($req->id);
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
            $fullDetailsData['application_no'] = $details->objection_no;
            $fullDetailsData['apply_date'] = $details->date;
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
            $cardElement = $this->generateObjCardDtls($details, $ownerList);
            $fullDetailsData['fullDetailsData']['cardArray'] = $cardElement;

            $levelComment = $mWorkflowTracks->getTracksByRefId($mRefTable, $req->id);
            $fullDetailsData['levelComment'] = $levelComment;

            $citizenComment = $mWorkflowTracks->getCitizenTracks($mRefTable, $req->id, $details->user_id);
            $fullDetailsData['citizenComment'] = $citizenComment;

            $metaReqs['customFor']  = 'Objection';
            $metaReqs['wfRoleId']   =  $details->current_role;
            $metaReqs['workflowId'] =  $details->workflow_id;
            $metaReqs['lastRoleId'] =  $details->last_role_id;
            $req->request->add($metaReqs);

            //role details
            $forwardBackward = $mForwardBackward->getRoleDetails($req);
            $fullDetailsData['roleDetails'] = collect($forwardBackward)['original']['data'];
            $fullDetailsData['timelineData'] = collect($req);

            $custom = $mCustomDetails->getCustomDetails($req);
            $fullDetailsData['departmentalPost'] = collect($custom)['original']['data'];

            $custom = $mCustomDetails->getCustomDetails($req);
            $fullDetailsData['departmentalPost'] = collect($custom)['original']['data'];

            return responseMsgs(true, "Objection Details", remove_null($fullDetailsData), '010807', '01', '', 'Post', '');
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", '010807', '01', '', 'Post', '');
        }
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
            return responseMsgs(true, $req->escalateStatus == 1 ? 'Objection is Escalated' : "Objection is removed from Escalated", '', '010808', '01', '', 'Post', '');
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
            $workflowRoles = $this->getRoleIdByUserId($userId);                             // Get all The roles of the Users

            $roleId = $workflowRoles->map(function ($value) {                               // Get user Workflow Roles
                return $value->wf_role_id;
            });
            $wardId = $occupiedWard->map(function ($item, $key) {                   // Filter All ward_id in an array using laravel collections
                return $item->ward_id;
            });
            $safData = $this->getObjectionList($ulbId)
                ->where('prop_active_objections.is_escalated', true)
                ->whereIn('prop_active_objections.current_role', $roleId)
                ->whereIn('p.ward_mstr_id', $wardId)
                ->get();
            return responseMsgs(true, "Data Fetched", remove_null($safData), '010809', '01', '', 'Post', '');
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Back to Citizen List
     */
    public function btcInboxList(Request $req)
    {
        try {
            $userId = authUser()->id;
            $ulbId = authUser()->ulb_id;
            $occupiedWard = $this->getWardByUserId($userId);                        // Get All Occupied Ward By user id using trait
            $wardId = $occupiedWard->map(function ($item, $key) {                   // Filter All ward_id in an array using laravel collections
                return $item->ward_id;
            });
            $safData = $this->getObjectionList($ulbId)
                ->whereIn('p.ward_mstr_id', $wardId)
                ->where('prop_active_objections.parked', true)
                ->get();
            return responseMsgs(true, "Data Fetched", remove_null($safData), '010809', '01', '', 'Post', '');
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    // Post Next Level Application
    public function postNextLevel(Request $req)
    {
        try {
            $req->validate([
                'objectionId' => 'required',
                'senderRoleId' => 'required',
                'receiverRoleId' => 'required',
                'comment' => 'required'
            ]);
            $mRefTable = Config::get('PropertyConstaint.SAF_OBJECTION_REF_TABLE');
            // objection Application Update Current Role Updation
            $objection = PropActiveObjection::find($req->objectionId);
            $objection->current_role = $req->receiverRoleId;
            $objection->save();

            $metaReqs['moduleId'] = Config::get('module-constants.PROPERTY_MODULE_ID');
            $metaReqs['workflowId'] = $objection->workflow_id;
            $metaReqs['refTableDotId'] = $mRefTable;
            $metaReqs['refTableIdValue'] = $req->objectionId;
            $req->request->add($metaReqs);

            DB::beginTransaction();
            $track = new WorkflowTrack();
            $track->saveTrack($req);

            DB::commit();

            return responseMsgs(true, "Successfully Forwarded The Application!!", "", '010810', '01', '', 'Post', '');
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
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
            $activeObjection = PropActiveObjection::query()
                ->where('id', $req->objectionId)
                ->first();
            // Check if the Current User is Finisher or Not
            $getFinisherQuery = $this->getFinisherId($activeObjection->workflow_id);                 // Get Finisher using Trait
            $refGetFinisher = collect(DB::select($getFinisherQuery))->first();
            if ($refGetFinisher->role_id != $req->roleId) {
                return responseMsg(false, "Forbidden Access", "");
            }
            DB::beginTransaction();

            // Approval
            if ($req->status == 1) {
                // Objection Application replication
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
            'objectionId' => "required"
        ]);
        try {
            $redis = Redis::connection();
            $objection = PropActiveObjection::find($req->objectionId);
            $workflowId = $objection->workflow_id;
            $mRefTable = Config::get('PropertyConstaint.SAF_OBJECTION_REF_TABLE');
            $backId = json_decode(Redis::get('workflow_initiator_' . $workflowId));
            if (!$backId) {
                $backId = WfWorkflowrolemap::where('workflow_id', $workflowId)
                    ->where('is_initiator', true)
                    ->first();
                $redis->set('workflow_initiator_' . $workflowId, json_encode($backId));
            }
            DB::beginTransaction();
            $objection->current_role = $backId->wf_role_id;
            $objection->parked = 1;
            $objection->save();

            $metaReqs['moduleId'] = Config::get('module-constants.PROPERTY_MODULE_ID');
            $metaReqs['workflowId'] = $objection->workflow_id;
            $metaReqs['refTableDotId'] = $mRefTable;
            $metaReqs['refTableIdValue'] = $req->objectionId;
            $metaReqs['verificationStatus'] = $req->verificationStatus;
            $metaReqs['senderRoleId'] = $req->currentRoleId;
            $req->request->add($metaReqs);
            $track = new WorkflowTrack();
            $track->saveTrack($req);

            DB::commit();
            return responseMsgs(true, "Successfully Done", "", '010812', '01', '', 'Post', '');
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "", "", '010812', '01', '', 'Post', '');
        }
    }

    //objection list
    public function objectionList()
    {
        try {
            $list  = new PropActiveObjection();
            $ojectionlist = $list->objectionList()
                ->orderByDesc('prop_active_objections.id')
                ->get();

            return responseMsgs(true, "", remove_null($ojectionlist), '010813', '01', '', 'Post', '');
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    //objection list  by id
    public function objectionByid(Request $req)
    {
        try {
            $list  = new PropActiveObjection();
            $ojectionlist = $list->objectionList()
                ->where('prop_active_objections.id', $req->id)
                ->get();

            return responseMsgs(true, "", remove_null($ojectionlist), '010813', '01', '', 'Post', '');
        } catch (Exception $e) {
            echo $e->getMessage();
        }
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

            return responseMsgs(true, "Successfully Done", '', '010817', '01', '', 'Post', '');
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * | Independent Comment
     */
    public function commentIndependent(Request $req)
    {
        $req->validate([
            'comment' => 'required',
            'objectionId' => 'required|integer',
            'senderRoleId' => 'nullable|integer'
        ]);

        try {
            $workflowTrack = new WorkflowTrack();
            $objection = PropActiveObjection::find($req->objectionId);                // SAF Details
            $mModuleId = Config::get('module-constants.PROPERTY_MODULE_ID');
            $mRefTable = Config::get('PropertyConstaint.SAF_OBJECTION_REF_TABLE');
            $metaReqs = array();
            DB::beginTransaction();
            // Save On Workflow Track For Level Independent
            $metaReqs = [
                'workflowId' => $objection->workflow_id,
                'moduleId' => $mModuleId,
                'refTableDotId' => $mRefTable,
                'refTableIdValue' => $objection->id,
                'message' => $req->comment
            ];
            // For Citizen Independent Comment
            if (!$req->senderRoleId) {
                $metaReqs = array_merge($metaReqs, ['citizenId' => $objection->user_id]);
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
}
