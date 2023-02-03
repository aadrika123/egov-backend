<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\MicroServices\DocUpload;
use App\Models\CustomDetail;
use App\Models\Masters\RefRequiredDocument;
use App\Models\Property\PropActiveConcession;
use App\Models\Property\PropConcessionDocDtl;
use App\Models\Property\PropFloor;
use App\Models\Property\PropOwner;
use App\Models\Property\PropProperty;
use App\Models\Property\RefPropDocsRequired;
use App\Models\Workflows\WfActiveDocument;
use App\Models\Workflows\WfWorkflow;
use App\Models\Workflows\WfWorkflowrolemap;
use App\Models\WorkflowTrack;
use App\Repository\Property\Concrete\PropertyBifurcation;
use App\Repository\Property\Interfaces\iConcessionRepository;
use App\Repository\WorkflowMaster\Concrete\WorkflowMap;
use Illuminate\Http\Request;
use App\Traits\Workflow\Workflow as WorkflowTrait;
use App\Traits\Property\Concession;
use App\Traits\Property\SafDetailsTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redis;

/**
 * | Created On-15-11-2022 
 * | Created By-Mrinal Kumar
 * --------------------------------------------------------------------------------------
 * | Controller for Concession
 * | --------------------------- Workflow Parameters ---------------------------------------
 * | Concession Master ID   = 35                
 * | Concession WorkflowID  = 106 
 * */


class ConcessionController extends Controller
{
    use WorkflowTrait;
    use Concession;
    use SafDetailsTrait;

    private $_todayDate;
    private $_bifuraction;
    private $_workflowId;

    protected $concession_repository;
    protected $Repository;
    public function __construct(iConcessionRepository $concession_repository)
    {
        $this->Repository = $concession_repository;
        $this->_todayDate = Carbon::now();
        $this->_bifuraction = new PropertyBifurcation();
        $this->_workflowId = Config::get('workflow-constants.PROPERTY_CONCESSION_ID');
    }


    /**
     * | Query Costing-464ms 
     * | Rating-3
     * | Status-Closed
     */
    public function applyConcession(Request $request)
    {

        $request->validate([
            'propId' => "required"
        ]);

        try {
            // $userId = auth()->user()->id;
            $ulbId = $request->ulbId;
            $userType = auth()->user()->user_type;
            $concessionNo = "";

            if ($userType == 'Citizen') {
                $citizenId = auth()->user()->id;
            }

            if ($userType != 'Citizen') {
                $userId = auth()->user()->id;
            }



            // $applicantName = $this->getOwnerName($request->propId);
            // $ownerName = $applicantName->ownerName;

            $ulbWorkflowId = WfWorkflow::where('wf_master_id', $this->_workflowId)
                ->where('ulb_id', $ulbId)
                ->first();

            $refInitiatorRoleId = $this->getInitiatorId($ulbWorkflowId->id);                // Get Current Initiator ID
            $initiatorRoleId = DB::select($refInitiatorRoleId);

            $refFinisherRoleId = $this->getFinisherId($ulbWorkflowId->id);
            $finisherRoleId = DB::select($refFinisherRoleId);

            if ($userType == "JSK") {
                // $obj  = new SafRepository();
                // $data = $obj->getPropByHoldingNo($request);
            }

            DB::beginTransaction();
            $concession = new PropActiveConcession;
            $concession->property_id = $request->propId;
            $concession->applicant_name = $request->applicantName;
            $concession->gender = $request->gender;
            $concession->dob = $request->dob;
            $concession->is_armed_force = $request->armedForce;
            $concession->is_specially_abled = $request->speciallyAbled;
            $concession->specially_abled_percentage = $request->speciallyAbledPercentage;
            $concession->remarks = $request->remarks;
            $concession->user_id = $userId ?? null;
            $concession->citizen_id = $citizenId ?? null;
            $concession->ulb_id = $ulbId;
            $concession->workflow_id = $ulbWorkflowId->id;
            $concession->current_role = collect($initiatorRoleId)->first()->role_id;
            $concession->initiator_role_id = collect($initiatorRoleId)->first()->role_id;
            $concession->finisher_role_id = collect($finisherRoleId)->first()->role_id;
            $concession->created_at = Carbon::now();
            $concession->date = Carbon::now();
            $concession->save();

            //concession number generate in model
            $conNo = new PropActiveConcession();
            $concessionNo = $conNo->concessionNo($concession->id);

            PropActiveConcession::where('id', $concession->id)
                ->update(['application_no' => $concessionNo]);

            // return $this->uploadDocument($request, $concession, $concessionNo);

            //saving document 
            if ($file = $request->file('genderDoc')) {

                $docUpload = new DocUpload;
                $mWfActiveDocument = new WfActiveDocument();
                $relativePath = Config::get('PropertyConstaint.CONCESSION_RELATIVE_PATH');
                $refImageName = $request->genderCode;
                $refImageName = $concession->id . '-' . str_replace(' ', '_', $refImageName);
                $document = $request->genderDoc;

                $imageName = $docUpload->upload($refImageName, $document, $relativePath);
                $metaReqs['moduleId'] = Config::get('module-constants.PROPERTY_MODULE_ID');
                $metaReqs['activeId'] = $concession->id;
                $metaReqs['workflowId'] = $concession->workflow_id;
                $metaReqs['ulbId'] = $concession->ulb_id;
                $metaReqs['document'] = $imageName;
                $metaReqs['relativePath'] = $relativePath;
                $metaReqs['docCode'] = $request->genderCode;

                $metaReqs = new Request($metaReqs);
                $mWfActiveDocument->postDocuments($metaReqs);
            }

            // dob Doc
            if ($file = $request->file('dobDoc')) {

                $docUpload = new DocUpload;
                $mWfActiveDocument = new WfActiveDocument();
                $relativePath = Config::get('PropertyConstaint.CONCESSION_RELATIVE_PATH');
                $refImageName = $request->dobCode;
                $refImageName = $concession->id . '-' . str_replace(' ', '_', $refImageName);
                $document = $request->dobDoc;

                $imageName = $docUpload->upload($refImageName, $document, $relativePath);
                $docReqs['moduleId'] = Config::get('module-constants.PROPERTY_MODULE_ID');
                $docReqs['activeId'] = $concession->id;
                $docReqs['workflowId'] = $concession->workflow_id;
                $docReqs['ulbId'] = $concession->ulb_id;
                $docReqs['relativePath'] = $relativePath;
                $docReqs['document'] = $imageName;
                $docReqs['docCode'] = $request->dobCode;

                $docReqs = new Request($docReqs);
                $mWfActiveDocument->postDocuments($docReqs);

                // $name = $this->moveFile($docName, $file);
                // $concessionDoc = new PropConcessionDocDtl();
                // $concessionDoc->concession_id = $concession->id;
                // $this->citizenDocUpload($concessionDoc, $name, $docName);
            }

            // specially abled Doc
            if ($file = $request->file('speciallyAbledDoc')) {

                $docUpload = new DocUpload;
                $mWfActiveDocument = new WfActiveDocument();
                $relativePath = Config::get('PropertyConstaint.CONCESSION_RELATIVE_PATH');
                $refImageName = $request->speciallyAbledCode;
                $refImageName = $concession->id . '-' . str_replace(' ', '_', $refImageName);
                $document = $request->speciallyAbledDoc;

                $imageName = $docUpload->upload($refImageName, $document, $relativePath);
                $speciallyAbledReqs['moduleId'] = Config::get('module-constants.PROPERTY_MODULE_ID');
                $speciallyAbledReqs['activeId'] = $concession->id;
                $speciallyAbledReqs['workflowId'] = $concession->workflow_id;
                $speciallyAbledReqs['ulbId'] = $concession->ulb_id;
                $speciallyAbledReqs['relativePath'] = $relativePath;
                $speciallyAbledReqs['document'] = $imageName;
                $speciallyAbledReqs['docCode'] = $request->speciallyAbledCode;

                $speciallyAbledReqs = new Request($speciallyAbledReqs);
                $mWfActiveDocument->postDocuments($speciallyAbledReqs);
            }

            // Armed force Doc
            if ($file = $request->file('armedForceDoc')) {

                $docUpload = new DocUpload;
                $mWfActiveDocument = new WfActiveDocument();
                $relativePath = Config::get('PropertyConstaint.CONCESSION_RELATIVE_PATH');
                $refImageName = $request->armedForceCode;
                $refImageName = $concession->id . '-' . str_replace(' ', '_', $refImageName);
                $document = $request->armedForceDoc;

                $imageName = $docUpload->upload($refImageName, $document, $relativePath);
                $armedForceReqs['moduleId'] = Config::get('module-constants.PROPERTY_MODULE_ID');
                $armedForceReqs['activeId'] = $concession->id;
                $armedForceReqs['workflowId'] = $concession->workflow_id;
                $armedForceReqs['ulbId'] = $concession->ulb_id;
                $armedForceReqs['relativePath'] = $relativePath;
                $armedForceReqs['document'] = $imageName;
                $armedForceReqs['docCode'] = $request->armedForceCode;

                $armedForceReqs = new Request($armedForceReqs);
                $mWfActiveDocument->postDocuments($armedForceReqs);
            }

            DB::commit();
            return responseMsgs(true, 'Successfully Applied The Application', $concessionNo, '010701', '01', '382ms-547ms', 'Post', '');
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    //post Holding
    public function postHolding(Request $request)
    {
        $request->validate([
            'holdingNo' => 'required'
        ]);
        try {
            $user = PropProperty::where('holding_no', $request->holdingNo)
                ->get();
            if (!empty($user['0'])) {
                return responseMsgs(true, 'True', $user, '010702', '01', '334ms-401ms', 'Post', '');
            }
            return responseMsg(false, "False", "");
            // return $user['0'];
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
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

            $concessions = $this->getConcessionList($ulbId)
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
            'applicationId' => 'required'
        ]);

        try {
            $details = array();
            $mPropActiveConcession = new PropActiveConcession();
            $mPropOwners = new PropOwner();
            $mPropFloors = new PropFloor();
            $mWorkflowTracks = new WorkflowTrack();
            $mCustomDetails = new CustomDetail();
            $mForwardBackward = new WorkflowMap();
            $mRefTable = Config::get('PropertyConstaint.SAF_CONCESSION_REF_TABLE');
            $details = $mPropActiveConcession->getDetailsById($req->applicationId);

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
            $cardElement = $this->generateConcessionCardDtls($details, $ownerList);
            $fullDetailsData['fullDetailsData']['cardArray'] = $cardElement;

            $levelComment = $mWorkflowTracks->getTracksByRefId($mRefTable, $req->applicationId);
            $fullDetailsData['levelComment'] = $levelComment;

            $citizenComment = $mWorkflowTracks->getCitizenTracks($mRefTable, $req->applicationId, $details->user_id);
            $fullDetailsData['citizenComment'] = $citizenComment;

            $metaReqs['customFor'] = 'PROPERTY-CONCESSION';
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

            return responseMsgs(true, "Concession Details", remove_null($fullDetailsData), '010705', '01', '', 'POST', '');
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", '010705', '01', '', 'POST', '');
        }
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

            $escalate = new PropActiveConcession();
            $msg = $escalate->escalate($req);

            return responseMsgs(true, $msg, "", '010706', '01', '400ms', 'Post', '');
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

    /**
     * | Back To Citizen Inbox
     */
    public function btcInbox(Request $req)
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

            $concessions = $this->getConcessionList($ulbId)
                ->whereIn('prop_active_concessions.current_role', $roleId)
                ->whereIn('a.ward_mstr_id', $occupiedWards)
                ->where('parked', true)
                ->orderByDesc('prop_active_concessions.id')
                ->get();
            return responseMsgs(true, "BTC Inbox List", remove_null($concessions), 010717, 1.0, "271ms", "POST", "", "");;
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", 010717, 1.0, "271ms", "POST", "", "");
        }
    }

    // Post Next Level Application
    public function postNextLevel(Request $req)
    {
        $req->validate([
            'applicationId' => 'required|integer',
            'senderRoleId' => 'required|integer',
            'receiverRoleId' => 'required|integer',
            'comment' => 'required'
        ]);
        try {
            DB::beginTransaction();

            // Concession Application Update Current Role Updation
            $concession = PropActiveConcession::find($req->applicationId);
            $concession->current_role = $req->receiverRoleId;
            $concession->save();

            $metaReqs['moduleId'] = Config::get('module-constants.PROPERTY_MODULE_ID');
            $metaReqs['workflowId'] = $concession->workflow_id;
            $metaReqs['refTableDotId'] = 'prop_active_concessions.id';
            $metaReqs['refTableIdValue'] = $req->applicationId;
            $metaReqs['verificationStatus'] = $req->verificationStatus;
            $req->request->add($metaReqs);
            $track = new WorkflowTrack();
            $track->saveTrack($req);

            DB::commit();
            return responseMsgs(true, "Successfully Forwarded The Application!!", "", "", '010708', '01', '', 'Post', '');
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Concession Application Approval or Rejected 
     * | @param req
     * | Status-closed
     * | Query Costing-376 ms
     * | Rating-2
     * | Status-Closed
     */
    public function approvalRejection(Request $req)
    {
        try {
            $req->validate([
                "applicationId" => "required",
                "status" => "required"
            ]);
            // Check if the Current User is Finisher or Not
            $getFinisherQuery = $this->getFinisherId($req->workflowId);                                 // Get Finisher using Trait
            $refGetFinisher = collect(DB::select($getFinisherQuery))->first();
            if ($refGetFinisher->role_id != $req->roleId) {
                return responseMsg(false, "Forbidden Access", "");
            }
            DB::beginTransaction();

            // Approval
            if ($req->status == 1) {
                // Concession Application replication
                $activeConcession = PropActiveConcession::query()
                    ->where('id', $req->applicationId)
                    ->first();

                $approvedConcession = $activeConcession->replicate();
                $approvedConcession->setTable('prop_concessions');
                $approvedConcession->id = $activeConcession->id;
                $approvedConcession->save();
                $activeConcession->delete();

                $msg =  "Application Successfully Approved !!";
            }
            // Rejection
            if ($req->status == 0) {
                // Concession Application replication
                $activeConcession = PropActiveConcession::query()
                    ->where('id', $req->applicationId)
                    ->first();

                $approvedConcession = $activeConcession->replicate();
                $approvedConcession->setTable('prop_rejected_concessions');
                $approvedConcession->id = $activeConcession->id;
                $approvedConcession->save();
                $activeConcession->delete();
                $msg =  "Application Successfully Rejected !!";
            }

            DB::commit();
            return responseMsgs(true, $msg, "", "", '010709', '01', '376ms', 'Post', '');
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Back to Citizen
     * | @param req
     * | Status-Closed
     * | Query Costing-358 ms 
     * | Rating-2
     * | Status-Closed
     */
    public function backToCitizen(Request $req)
    {
        $req->validate([
            'applicationId' => "required"
        ]);
        try {
            $redis = Redis::connection();
            $concession = PropActiveConcession::find($req->applicationId);

            $workflowId = $concession->workflow_id;
            $backId = json_decode(Redis::get('workflow_initiator_' . $workflowId));
            if (!$backId) {
                $backId = WfWorkflowrolemap::where('workflow_id', $workflowId)
                    ->where('is_initiator', true)
                    ->first();
                $redis->set('workflow_initiator_' . $workflowId, json_encode($backId));
            }

            $concession->current_role = $backId->wf_role_id;
            $concession->parked = 1;
            $concession->save();

            $metaReqs['moduleId'] = Config::get('module-constants.PROPERTY_MODULE_ID');
            $metaReqs['workflowId'] = $concession->workflow_id;
            $metaReqs['refTableDotId'] = 'prop_active_concessions.id';
            $metaReqs['refTableIdValue'] = $req->applicationId;
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

    // get owner details by propId
    public function getOwnerDetails(Request $request)
    {
        try {
            $request->validate([
                'propId' => "required"
            ]);
            $ownerDetails = PropOwner::select(
                'owner_name as ownerName',
                'prop_owners.id as ownerId',
                'ulb_id as ulbId'
            )
                ->join('prop_properties', 'prop_properties.id', 'prop_owners.property_id')
                ->where('property_id', $request->propId)
                ->orderBy('prop_owners.id')
                ->first();

            $checkExisting = PropActiveConcession::where('property_id', $request->propId)
                ->where('status', 1)
                ->first();

            if ($checkExisting) {
                $checkExisting->property_id = $request->propId;
                $checkExisting->save();
                return responseMsgs(1, "User Already Applied", $ownerDetails, "", '010711', '01', '303ms-406ms', 'Post', '');
            } else return responseMsgs(0, "User Not Exist", $ownerDetails, "", '010711', '01', '303ms-406ms', 'Post', '');
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    //concesssion list
    // public function concessionList()
    // {
    //     try {
    //         $list = PropActiveConcession::select(
    //             'prop_active_concessions.id',
    //             'prop_active_concessions.applicant_name as ownerName',
    //             'holding_no as holdingNo',
    //             'ward_name as wardId',
    //             'property_type as propertyType'
    //         )
    //             ->join('prop_properties', 'prop_properties.id', 'prop_active_concessions.property_id')
    //             ->join('ref_prop_types', 'ref_prop_types.id', 'prop_properties.prop_type_mstr_id')
    //             ->join('ulb_ward_masters', 'ulb_ward_masters.id', 'prop_properties.ward_mstr_id')
    //             ->where('prop_active_concessions.status', 1)
    //             ->orderByDesc('prop_active_concessions.id')
    //             ->get();

    //         return responseMsgs(true, "Successfully Done", $list, "", '010712', '01', '308ms-396ms', 'Post', '');
    //     } catch (Exception $e) {
    //         echo $e->getMessage();
    //     }
    // }

    // //concesion list  by id
    // public function concessionByid(Request $req)
    // {
    //     try {
    //         $list = PropActiveConcession::select(
    //             'prop_active_concessions.id',
    //             'prop_active_concessions.applicant_name as ownerName',
    //             'holding_no as holdingNo',
    //             'ward_name as wardId',
    //             'property_type as propertyType',
    //             'dob',
    //             'gender',
    //             'is_armed_force as armedForce',
    //             'is_specially_abled as speciallyAbled'
    //         )
    //             ->where('prop_active_concessions.id', $req->id)
    //             ->where('prop_active_concessions.status', 1)
    //             ->join('prop_properties', 'prop_properties.id', 'prop_active_concessions.property_id')
    //             ->join('ref_prop_types', 'ref_prop_types.id', 'prop_properties.prop_type_mstr_id')
    //             ->join('ulb_ward_masters', 'ulb_ward_masters.id', 'prop_properties.ward_mstr_id')
    //             ->orderByDesc('prop_active_concessions.id')
    //             ->first();

    //         return responseMsgs(true, "Successfully Done", $list, "", '010713', '01', '312ms-389ms', 'Post', '');
    //     } catch (Exception $e) {
    //         echo $e->getMessage();
    //     }
    // }

    //doc upload
    public function concessionDocUpload(Request $req)
    {
        $req->validate([
            'id' => 'required|integer'
        ]);
        try {
            //gender doc
            if ($file = $req->file('genderDoc')) {
                $docName = "genderDoc";
                $name = $this->moveFile($docName, $file);

                $checkExisting = PropConcessionDocDtl::where('concession_id', $req->id)
                    ->where('doc_type', $docName)
                    ->get()
                    ->first();
                if ($checkExisting) {
                    $this->updateDocument($req, $name, $docName);
                } else {
                    $this->saveConcessionDoc($req, $name, $docName);
                }
            }

            //dob doc
            if ($file = $req->file('dobDoc')) {
                $docName = "dobDoc";
                $name = $this->moveFile($docName, $file);

                $checkExisting = PropConcessionDocDtl::where('concession_id', $req->id)
                    ->where('doc_type', $docName)
                    ->get()
                    ->first();
                if ($checkExisting) {
                    $this->updateDocument($req, $name, $docName);
                } else {
                    $this->saveConcessionDoc($req, $name, $docName);
                }
            }

            //specially abled doc
            if ($file = $req->file('speciallyAbledDoc')) {
                $docName = "speciallyAbledDoc";
                $name = $this->moveFile($docName, $file);

                $checkExisting = PropConcessionDocDtl::where('concession_id', $req->id)
                    ->where('doc_type', $docName)
                    ->get()
                    ->first();
                if ($checkExisting) {
                    $this->updateDocument($req, $name, $docName);
                } else {
                    $this->saveConcessionDoc($req, $name, $docName);
                }
            }

            //armed forcce doc
            if ($file = $req->file('armedForceDoc')) {
                $docName = "armedForceDoc";
                $name = $this->moveFile($docName, $file);

                $checkExisting = PropConcessionDocDtl::where('concession_id', $req->id)
                    ->where('doc_type', $docName)
                    ->get()
                    ->first();
                if ($checkExisting) {
                    $this->updateDocument($req, $name, $docName);
                } else {
                    $this->saveConcessionDoc($req, $name, $docName);
                }
            }

            //concession doc
            if ($file = $req->file('concessionFormDoc')) {
                $docName = "concessionFormDoc";
                $name = $this->moveFile($docName, $file);

                $checkExisting = PropConcessionDocDtl::where('concession_id', $req->id)
                    ->where('doc_type', $docName)
                    ->get()
                    ->first();
                if ($checkExisting) {
                    $this->updateDocument($req, $name, $docName);
                } else {
                    $this->saveConcessionDoc($req, $name, $docName);
                }
            }

            return responseMsgs(true, "Successfully Uploaded", '', "", '010715', '01', '434ms', 'Post', '');
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    //post document status
    public function concessionDocStatus(Request $req)
    {
        try {
            $docStatus = new PropConcessionDocDtl();
            $docStatus->docVerify($req);

            return responseMsgs(true, "Successfully Done", '', "", '010716', '01', '308ms-431ms', 'Post', '');
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    //citizen doc upload
    public function citizenDocUpload($concessionDoc, $name, $docName)
    {
        $userId = auth()->user()->id;

        $concessionDoc->doc_type = $docName;
        $concessionDoc->relative_path = '/concession/' . $docName . '/';
        $concessionDoc->doc_name = $name;
        $concessionDoc->status = '1';
        $concessionDoc->user_id = $userId;
        $concessionDoc->date = Carbon::now();
        $concessionDoc->created_at = Carbon::now();
        $concessionDoc->save();
    }

    //save documents
    public function saveConcessionDoc($req, $name, $docName)
    {
        $userId = auth()->user()->id;
        $concessionDoc = new PropConcessionDocDtl();
        $concessionDoc->concession_id = $req->id;

        $concessionDoc->doc_type = $docName;
        $concessionDoc->relative_path = '/concession/' . $docName . '/';
        $concessionDoc->doc_name = $name;
        $concessionDoc->status = '1';
        $concessionDoc->user_id = $userId;
        $concessionDoc->date = Carbon::now();
        $concessionDoc->created_at = Carbon::now();
        $concessionDoc->save();
    }

    //update documents
    public function updateDocument($req, $name, $docName)
    {
        PropConcessionDocDtl::where('concession_id', $req->id)
            ->where('doc_type', $docName)
            ->update([
                'concession_id' => $req->id,
                'doc_type' => $docName,
                'relative_path' => ('/concession/' . $docName . '/'),
                'doc_name' => $name,
                'status' => 1,
                'verify_status' => 0,
                'remarks' => '',
                'updated_at' => Carbon::now()
            ]);
    }

    //move file to location
    public function moveFile($docName, $file)
    {
        $name = time() . $docName . '.' . $file->getClientOriginalExtension();
        $path = storage_path('app/public/concession/' . $docName . '/');
        $file->move($path, $name);

        return $name;
    }



    /**
     * | Citizen and Level Pendings Independent Comments
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
            $concession = PropActiveConcession::find($req->applicationId);                         // Concessions
            $mModuleId = Config::get('module-constants.PROPERTY_MODULE_ID');
            $metaReqs = array();
            DB::beginTransaction();
            // Save On Workflow Track For Level Independent
            $metaReqs = [
                'workflowId' => $concession->workflow_id,
                'moduleId' => $mModuleId,
                'refTableDotId' => "prop_active_concessions.id",
                'refTableIdValue' => $concession->id,
                'message' => $req->comment
            ];
            // For Citizen Independent Comment
            if (!$req->senderRoleId) {
                $metaReqs = array_merge($metaReqs, ['citizenId' => $concession->user_id]);
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
     * get owner name
     */
    public function getOwnerName($propId)
    {
        $ownerDetails = PropProperty::select('applicant_name as ownerName')
            ->where('prop_properties.id', $propId)
            ->first();

        return $ownerDetails;
    }

    /**
     *  get document ttype
     */
    public function getDocType(Request $req)
    {
        switch ($req->doc) {
            case ('gender'):
                $data =  RefRequiredDocument::select('*')
                    ->where('code', 'OWNER_EXTRA_DOCUMENT')
                    ->first();
                break;

            case ('seniorCitizen'):
                $data =  RefRequiredDocument::select('*')
                    ->where('code', 'OWNER_EXTRA_DOCUMENT')
                    ->first();
                break;

            case ('speciallyAbled'):
                $data =  RefRequiredDocument::select('*')
                    ->where('code', 'CONCESSION_SPECIALLY_ABLED')
                    ->first();
                break;

            case ('armedForce'):
                $data =  RefRequiredDocument::select('*')
                    ->where('code', 'CONCESSION_ARMED_FORCE')
                    ->first();
                break;
        }

        return responseMsgs(true, "Citizen Doc List", remove_null($data), 010717, 1.0, "413ms", "POST", "", "");
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
            $mPropActiveConcession = new PropActiveConcession();
            $moduleId = Config::get('module-constants.PROPERTY_MODULE_ID');

            $concessionDetails = $mPropActiveConcession->getConcessionNo($req->applicationId);
            if (!$concessionDetails)
                throw new Exception("Application Not Found for this application Id");

            $workflowId = $concessionDetails->workflow_id;
            $documents = $mWfActiveDocument->getDocsByAppId($req->applicationId, $workflowId, $moduleId);
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
            "docCode" => "required",
            "ownerId" => "nullable|numeric"
        ]);

        try {
            $metaReqs = array();
            $docUpload = new DocUpload;
            $mWfActiveDocument = new WfActiveDocument();
            $mPropActiveConcession = new PropActiveConcession();
            $relativePath = Config::get('PropertyConstaint.CONCESSION_RELATIVE_PATH');
            $getConcessionDtls = $mPropActiveConcession->getConcessionNo($req->applicationId);
            $refImageName = $req->docCode;
            $refImageName = $getConcessionDtls->id . '-' . $refImageName;
            $document = $req->document;
            $imageName = $docUpload->upload($refImageName, $document, $relativePath);

            $metaReqs['moduleId'] = Config::get('module-constants.PROPERTY_MODULE_ID');
            $metaReqs['activeId'] = $getConcessionDtls->id;
            $metaReqs['workflowId'] = $getConcessionDtls->workflow_id;
            $metaReqs['ulbId'] = $getConcessionDtls->ulb_id;
            $metaReqs['relativePath'] = $relativePath;
            $metaReqs['document'] = $imageName;
            $metaReqs['docCode'] = $req->docCode;
            $metaReqs['ownerDtlId'] = $req->ownerId;

            $metaReqs = new Request($metaReqs);
            $mWfActiveDocument->postDocuments($metaReqs);
            return responseMsgs(true, "Document Uploadation Successful", "", "010201", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "010201", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * 
     */
    public function concessionDocList(Request $req)
    {
        try {
            $mPropActiveConcession = new PropActiveConcession();
            $refApplication = $mPropActiveConcession->getConcessionNo($req->applicationId);                      // Get Saf Details
            if (!$refApplication)
                throw new Exception("Application Not Found for this id");
            $concessionDoc['listDocs'] = $this->getDocList($refApplication);             // Current Object(Saf Docuement List)

            return responseMsgs(true, "Successfully Done", remove_null($concessionDoc), "", '010714', '01', '314ms-451ms', 'Post', '');
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }


    /**
     * get Doc List
     */
    public function getDocList($refApplication)
    {
        $mRefReqDocs = new RefRequiredDocument();
        $moduleId = Config::get('module-constants.PROPERTY_MODULE_ID');
        $isSpeciallyAbled = $refApplication->is_specially_abled;
        $isArmedForce = $refApplication->is_armed_force;
        $gender = $refApplication->gender;
        $dob = $refApplication->dob;
        $documentList = "";

        if ($isSpeciallyAbled == true)
            $documentList = $mRefReqDocs->getDocsByDocCode($moduleId, "CONCESSION_SPECIALLY_ABLED")->requirements;
        if ($isArmedForce == true)
            $documentList .= $mRefReqDocs->getDocsByDocCode($moduleId, "CONCESSION_ARMED_FORCE")->requirements;
        if (isset($gender))
            $documentList .= $mRefReqDocs->getDocsByDocCode($moduleId, "CONCESSION_GENDER")->requirements;
        if (isset($dob))
            $documentList .= $mRefReqDocs->getDocsByDocCode($moduleId, "CONCESSION_DOB")->requirements;

        if (!empty($documentList))
            $filteredDocs = $this->filterDocument($documentList, $refApplication);                                     // function(1.2)
        else
            $filteredDocs = [];
        return $filteredDocs;
    }

    /**
     *  filterring
     */

    public function filterDocument($documentList, $refApplication)
    {
        $mWfActiveDocument = new WfActiveDocument();
        $safId = $refApplication->id;
        $workflowId = $refApplication->workflow_id;
        $moduleId = Config::get('module-constants.PROPERTY_MODULE_ID');
        $uploadedDocs = $mWfActiveDocument->getDocByRefIds($safId, $workflowId, $moduleId);
        $explodeDocs = collect(explode('#', $documentList));

        $filteredDocs = $explodeDocs->map(function ($explodeDoc) use ($uploadedDocs) {
            $document = explode(',', $explodeDoc);
            $key = array_shift($document);

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
                    "uploadedDoc'" => $uploadedDoc->doc_path ?? null
                ];
                return $arr;
            });
            return $reqDoc;
        });
        return $filteredDocs;
    }
}
