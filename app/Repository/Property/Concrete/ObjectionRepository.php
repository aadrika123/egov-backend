<?php

namespace App\Repository\Property\Concrete;

use App\Models\Property\PropOwner;
use Exception;
use App\Repository\Property\Interfaces\iObjectionRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Traits\Workflow\Workflow as WorkflowTrait;
use Illuminate\Support\Facades\Config;
use App\Models\Property\PropActiveObjection;
use App\Models\Property\PropActiveObjectionOwner;
use App\Traits\Property\Objection;
use App\Models\Workflows\WfWorkflow;
use App\Models\Property\PropProperty;
use App\Models\Property\PropObjectionLevelpending;
use Illuminate\Support\Facades\Redis;
use App\Models\Workflows\WfWorkflowrolemap;
use App\Models\PropActiveObjectionDtl;
use App\Models\PropActiveObjectionFloor;
use App\Models\PropActiveObjectionDocdtl;
use App\Repository\Property\Concrete\PropertyBifurcation;
use Illuminate\Support\Facades\Validator;

/**
 * | Created On-20-11-2022
 * | Created By-Mrinal Kumar
 * | -----------------------------------------------------------------------------------------
 * | Objection Module all operations 
 * | --------------------------- Workflow Parameters ---------------------------------------
 * | CLERICAL Master ID=36                | Assesment Master ID=56              | Forgery Master ID=79
 * | CLERICAL WorkflowID=169              | Assesment Workflow ID=183           | Forgery Workflow ID=212
 */

class ObjectionRepository implements iObjectionRepository
{
    use Objection;
    use WorkflowTrait;
    private $_objectionNo;
    private $_bifuraction;
    private $_workflow_id_assesment;
    private $_workflow_id_clerical;
    private $_workflow_id_forgery;

    public function __construct()
    {
        $this->_bifuraction = new PropertyBifurcation();
        $this->_workflow_id_clerical = Config::get('workflow-constants.PROPERTY_OBJECTION_CLERICAL');
        $this->_workflow_id_assesment = Config::get('workflow-constants.PROPERTY_OBJECTION_ASSESSMENT');
        $this->_workflow_id_forgery = Config::get('workflow-constants.PROPERTY_OBJECTION_FORGERY');
    }


    //get owner details
    public function ownerDetails($request)
    {
        try {
            $ownerDetails = PropOwner::select('owner_name as name', 'mobile_no as mobileNo', 'prop_address as address')
                ->where('prop_properties.id', $request->propId)
                ->join('prop_properties', 'prop_properties.id', '=', 'prop_owners.property_id')
                ->first();
            return responseMsg(true, "Successfully Retrieved", $ownerDetails);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    //apply objection
    public function applyObjection($request)
    {
        try {
            $userId = authUser()->id;
            $ulbId = auth()->user()->ulb_id;
            $userType = auth()->user()->user_type;
            $objectionFor = $request->objectionFor;
            $objectionNo = "";
            $objNo = "";

            $ulbWorkflowId = WfWorkflow::where('wf_master_id', $this->_workflow_id_clerical)
                ->where('ulb_id', $ulbId)
                ->first();

            $refInitiatorRoleId = $this->getInitiatorId($ulbWorkflowId->id);            // Get Current Initiator ID
            $initiatorRoleId = DB::select($refInitiatorRoleId);

            if ($objectionFor == "Clerical Mistake") {
                DB::beginTransaction();

                //saving objection details
                $objection = new PropActiveObjection();
                $objection->ulb_id = $ulbId;
                $objection->user_id = $userId;
                $objection->objection_for =  $objectionFor;
                $objection->property_id = $request->propId;
                $objection->remarks = $request->remarks;
                $objection->date = Carbon::now();
                $objection->created_at = Carbon::now();
                $objection->workflow_id = $ulbWorkflowId->id;
                $objection->current_role = $initiatorRoleId[0]->role_id;
                $objection->save();

                //objection No generation in model
                $objNo = new PropActiveObjection();
                $objectionNo = $objNo->objectionNo($objection->id);

                PropActiveObjection::where('id', $objection->id)
                    ->update(['objection_no' => $objectionNo]);

                //saving objection owner details
                $objectionOwner = new PropActiveObjectionOwner();
                $objectionOwner->objection_id = $objection->id;
                $objectionOwner->name = $request->name;
                $objectionOwner->address = $request->address;
                $objectionOwner->mobile = $request->mobileNo;
                $objectionOwner->members = $request->safMember;
                $objectionOwner->created_at = Carbon::now();
                $objectionOwner->save();

                //name document
                if ($file = $request->file('nameDoc')) {
                    $docName = "nameDoc";
                    $name = $this->moveFile($docName, $file);

                    $objectionDoc = new PropActiveObjectionDocdtl();
                    $objectionDoc->objection_id = $objection->id;
                    $this->citizenDocUpload($objectionDoc, $name, $docName);
                }


                // //address document 
                if ($file = $request->file('addressDoc')) {
                    $docName = "addressDoc";
                    $name = $this->moveFile($docName, $file);

                    $objectionDoc = new PropActiveObjectionDocdtl;
                    $objectionDoc->objection_id = $objection->id;
                    $this->citizenDocUpload($objectionDoc, $name, $docName);
                }

                // //saf doc
                if ($file = $request->file('safMemberDoc')) {
                    $docName = "safMemberDoc";
                    $name = $this->moveFile($docName, $file);

                    $objectionDoc = new PropActiveObjectionDocdtl;
                    $objectionDoc->objection_id = $objection->id;
                    $this->citizenDocUpload($objectionDoc, $name, $docName);
                }
            }

            //objection for forgery 
            if ($objectionFor == 'Forgery') {

                $ulbWorkflowId = WfWorkflow::where('wf_master_id', $this->_workflow_id_forgery)
                    ->where('ulb_id', $ulbId)
                    ->first();

                $refInitiatorRoleId = $this->getInitiatorId($ulbWorkflowId->id);            // Get Current Initiator ID
                $initiatorRoleId = DB::select($refInitiatorRoleId);

                $objection = new PropActiveObjection;
                $objection->ulb_id = $ulbId;
                $objection->user_id = $userId;
                $objection->objection_for =  $objectionFor;
                $objection->property_id = $request->propId;
                $objection->remarks = $request->remarks;
                $objection->date = Carbon::now();
                $objection->created_at = Carbon::now();
                $objection->workflow_id =  $ulbWorkflowId->id;
                $objection->current_role = collect($initiatorRoleId)->first()->role_id;
                $objection->save();

                //objection_form
                if ($file = $request->file('objectionForm')) {
                    $docName = "objectionForm";
                    $name = $this->moveFile($docName, $file);

                    $this->citizenDocUpload($objectionDoc, $name, $docName);
                }

                //Evidence Doc
                if ($file = $request->file('evidenceDoc')) {
                    $docName = "evidenceDoc";
                    $name = $this->moveFile($docName, $file);

                    $this->citizenDocUpload($objectionDoc, $name, $docName);
                }
                return responseMsg(true, "Successfully Saved", '');
            }

            // objection against assesment
            if ($objectionFor == 'Assessment Error') {

                $ulbWorkflowId = WfWorkflow::where('wf_master_id', $this->_workflow_id_assesment)
                    ->where('ulb_id', $ulbId)
                    ->first();
                $refInitiatorRoleId = $this->getInitiatorId($ulbWorkflowId->id);            // Get Current Initiator ID
                $initiatorRoleId = DB::select($refInitiatorRoleId);

                $objection = new PropActiveObjection;
                $objection->objection_for =  $objectionFor;
                $objection->ulb_id = $ulbId;
                $objection->user_id = $userId;
                $objection->property_id = $request->propId;
                $objection->remarks = $request->remarks;
                $objection->date = Carbon::now();
                $objection->created_at = Carbon::now();
                $objection->workflow_id = $ulbWorkflowId->id;
                $objection->current_role = $initiatorRoleId[0]->role_id;
                $objection->save();

                $assessmentData = collect($request->assessmentData);
                $assessmentData = collect($assessmentData)->map(function ($value, $key) {
                    $details['id'] = $value["id"];
                    $details['value'] = $value['value'];
                    return $details;
                });

                foreach ($assessmentData as $otid) {

                    $assement_error = new PropActiveObjectionDtl;
                    $assement_error->objection_id = $objection->id;
                    $assement_error->objection_type_id = $otid["id"];

                    $assesmentDetail = $this->assesmentDetails($request);
                    $assesmentData = collect($assesmentDetail);

                    //RWH
                    if ($otid["id"] == 2) {
                        $assement_error->data_ref_type = 'boolean';
                        $objection->objection_type_id = 2;
                        $assement_error->assesment_data = collect($assesmentData['original']['data']['isWaterHarvesting']);
                    }
                    //road width
                    if ($otid["id"] == 3) {
                        $assement_error->data_ref_type = 'ref_prop_road_types.id';
                        $objection->objection_type_id = 3;
                        $assement_error->assesment_data = collect($assesmentData['original']['data']['road_type_mstr_id']);
                    }
                    //property_types
                    if ($otid["id"] == 4) {
                        $assement_error->data_ref_type = 'ref_prop_types.id';
                        $objection->objection_type_id = 4;
                        $assement_error->assesment_data = collect($assesmentData['original']['data']['prop_type_mstr_id']);
                    }
                    //area off plot
                    if ($otid["id"] == 5) {
                        $assement_error->data_ref_type = 'area';
                        $objection->objection_type_id = 5;
                        $assement_error->assesment_data = collect($assesmentData['original']['data']['areaOfPlot']);
                    }
                    //mobile tower
                    if ($otid["id"] == 6) {
                        $assement_error->data_ref_type = 'boolean';
                        $objection->objection_type_id = 6;
                        $assement_error->assesment_data = collect($assesmentData['original']['data']['isMobileTower']);
                    }
                    //hoarding board
                    if ($otid["id"] == 7) {
                        $assement_error->data_ref_type = 'boolean';
                        $objection->objection_type_id = 7;
                        $assement_error->assesment_data = collect($assesmentData['original']['data']['isHoarding']);
                    }

                    $assement_error->applicant_data =  $otid["value"];
                    $assement_error->save();
                }

                //objection No generation in model
                $objNo = new PropActiveObjection();
                $objectionNo = $objNo->objectionNo($objection->id);

                PropActiveObjection::where('id', $objection->id)
                    ->update(['objection_no' => $objectionNo]);

                $floorData = $request->floorData;
                $floor = collect($floorData);

                foreach ($floor as $floors) {
                    $assement_floor = new PropActiveObjectionFloor;
                    $assement_floor->property_id = $request->propId;
                    $assement_floor->objection_id = $objection->id;
                    $assement_floor->prop_floor_id = $request->propFloorId;
                    $assement_floor->floor_mstr_id = $floors['floorNo'];
                    $assement_floor->usage_type_mstr_id = $floors['usageType'];
                    $assement_floor->occupancy_type_mstr_id = $floors['occupancyType'];
                    $assement_floor->const_type_mstr_id = $floors['constructionType'];
                    $assement_floor->builtup_area = $floors['buildupArea'];
                    $assement_floor->date_from = $floors['dateFrom'];
                    $assement_floor->date_upto = $floors['dateUpto'];
                    $assement_floor->save();
                }

                //objection_form
                if ($file = $request->file('objectionForm')) {
                    $docName = "objectionForm";
                    $name = $this->moveFile($docName, $file);

                    $this->citizenDocUpload($objectionDoc, $name, $docName);
                }

                //Evidence Doc
                if ($file = $request->file('evidenceDoc')) {
                    $docName = "evidenceDoc";
                    $name = $this->moveFile($docName, $file);

                    $this->citizenDocUpload($objectionDoc, $name, $docName);
                }
            }
            DB::commit();

            if (isset($objectionFor) && $objectionNo) {
                //level pending
                $labelPending = new PropObjectionLevelpending();
                $labelPending->objection_id = $objection->id;
                $labelPending->receiver_role_id = collect($initiatorRoleId)->first()->role_id;
                $labelPending->save();

                return responseMsgs(true, "Successfully Saved", $objectionNo, '010801', '01', '382ms-547ms', 'Post', '');
            } else {
                return responseMsg(false, "Undefined parameter supply", "");
            }
        } catch (Exception $e) {
            return response()->json($e->getMessage());
        }
    }

    //assesment detail
    public function assesmentDetails($request)
    {
        try {
            $assesmentDetails = PropProperty::select(
                'is_hoarding_board as isHoarding',
                'hoarding_area',
                'hoarding_installation_date',
                'is_water_harvesting as isWaterHarvesting',
                'is_mobile_tower as isMobileTower',
                'tower_area',
                'tower_installation_date',
                'area_of_plot as areaOfPlot',
                'property_type as propertyType',
                'road_type_mstr_id',
                'road_type as roadType',
                'prop_type_mstr_id'
                // 'prop_floors.*'
            )
                ->where('prop_properties.id', $request->propId)
                ->join('prop_floors', 'prop_floors.property_id', '=', 'prop_properties.id')
                ->join('ref_prop_types', 'ref_prop_types.id', '=', 'prop_properties.prop_type_mstr_id')
                ->join('ref_prop_road_types', 'ref_prop_road_types.id', '=', 'prop_properties.road_type_mstr_id')
                ->get();
            foreach ($assesmentDetails as $assesmentDetailss) {
                $assesmentDetailss['floor'] = PropProperty::select(
                    'ref_prop_floors.floor_name as floorNo',
                    'ref_prop_usage_types.usage_type as usageType',
                    'ref_prop_occupancy_types.occupancy_type as occupancyType',
                    'ref_prop_construction_types.construction_type as constructionType',
                    'prop_floors.builtup_area as buildupArea',
                    'prop_floors.date_from as dateFrom',
                    'prop_floors.date_upto as dateUpto',
                )
                    ->where('prop_properties.id', $request->propId)
                    ->join('prop_floors', 'prop_floors.property_id', '=', 'prop_properties.id')
                    ->join('ref_prop_floors', 'ref_prop_floors.id', '=', 'prop_floors.floor_mstr_id')
                    ->join('ref_prop_usage_types', 'ref_prop_usage_types.id', '=', 'prop_floors.usage_type_mstr_id')
                    ->join('ref_prop_occupancy_types', 'ref_prop_occupancy_types.id', '=', 'prop_floors.occupancy_type_mstr_id')
                    ->join('ref_prop_construction_types', 'ref_prop_construction_types.id', '=', 'prop_floors.const_type_mstr_id')
                    ->get();
            }
            if (isset($assesmentDetailss)) {
                return responseMsgs(true, "Successfully Retrieved", remove_null($assesmentDetailss), '010804', '01', '332ms-367ms', 'Post', '');
            } else {
                return responseMsg(false, "Supply Valid Property Id", "");
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }
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

    /**
     * | Get Objection Details by Id
     * | @param request $req
     */
    public function getDetailsById($req)
    {
        $details = DB::table('prop_active_objections')
            ->select(
                'prop_active_objections.id as objection_id',
                'objection_for',
                'prop_active_objections.objection_no',
                'prop_active_objections.workflow_id',
                'prop_active_objections.current_role',
                'p.*',
                'p.assessment_type as assessment',
                'w.ward_name as old_ward_no',
                'o.ownership_type',
                'pt.property_type'
            )

            ->join('prop_properties as p', 'p.id', '=', 'prop_active_objections.property_id')
            ->join('prop_safs as s', 's.id', '=', 'p.saf_id')
            ->join('ulb_ward_masters as w', 'w.id', '=', 's.ward_mstr_id')
            ->leftJoin('ulb_ward_masters as nw', 'nw.id', '=', 's.new_ward_mstr_id')
            ->join('ref_prop_ownership_types as o', 'o.id', '=', 's.ownership_type_mstr_id')
            ->leftJoin('ref_prop_types as pt', 'pt.id', '=', 's.property_assessment_id')
            ->where('p.status', 1)
            ->where('prop_active_objections.id', $req->id)
            ->first();
        return responseMsgs(true, "Objection Details", remove_null($details), '010807', '01', '474ms-573', 'Post', '');
    }

    /**
     * | Post Escalate the Applications
     * | @param req $req
     */
    public function postEscalate($req)
    {
        try {
            DB::beginTransaction();
            $userId = authUser()->id;
            $objId = $req->objectionId;
            $data = PropActiveObjection::find($objId);
            $data->is_escalated = $req->escalateStatus;
            $data->escalated_by = $userId;
            $data->save();
            DB::commit();
            return responseMsgs(true, $req->escalateStatus == 1 ? 'Objection is Escalated' : "Objection is removed from Escalated", '', '010808', '01', '474ms-573', 'Post', '');
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Special Inbox
     */
    public function specialInbox()
    {
        try {
            $userId = authUser()->id;
            $ulbId = authUser()->ulb_id;
            $occupiedWard = $this->getWardByUserId($userId);                        // Get All Occupied Ward By user id using trait
            $wardId = $occupiedWard->map(function ($item, $key) {                   // Filter All ward_id in an array using laravel collections
                return $item->ward_id;
            });
            $safData = $this->getObjectionList($ulbId)
                ->where('prop_active_objections.is_escalated', true)
                ->whereIn('p.ward_mstr_id', $wardId)
                ->get();
            return responseMsgs(true, "Data Fetched", remove_null($safData), '010809', '01', '474ms-573', 'Post', '');
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Forward Or BackWard Application
     * | @param $req
     */
    public function postNextLevel($req)
    {
        try {
            DB::beginTransaction();

            $levelPending = new PropObjectionLevelpending();
            $levelPending->objection_id = $req->objectionId;
            $levelPending->sender_role_id = $req->senderRoleId;
            $levelPending->receiver_role_id = $req->receiverRoleId;
            $levelPending->sender_user_id = auth()->user()->id;
            $levelPending->save();

            // objection Application Update Current Role Updation
            $objection = PropActiveObjection::find($req->objectionId);
            $objection->current_role = $req->receiverRoleId;
            $objection->save();

            // Add Comment On Prop Level Pending  and Verification Status true
            $ObjLevelPending = new PropObjectionLevelpending();
            $commentOnlevel = $ObjLevelPending->getCurrentObjByReceiver($req->objectionId, $req->senderRoleId);

            $commentOnlevel->remarks = $req->comment;
            $commentOnlevel->verification_status = 1;
            $commentOnlevel->save();

            DB::commit();
            return responseMsgs(true, "Successfully Forwarded The Application!!", "", '010810', '01', '474ms-573', 'Post', '');
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Objection Application approval Rejection
     * | @param request $req
     */
    public function approvalRejection($req)
    {
        try {
            // Check if the Current User is Finisher or Not
            $getFinisherQuery = $this->getFinisherId($req->workflowId);                                 // Get Finisher using Trait
            $refGetFinisher = collect(DB::select($getFinisherQuery))->first();
            if ($refGetFinisher->role_id != $req->roleId) {
                return responseMsg(false, "Forbidden Access", "");
            }

            DB::beginTransaction();

            // Approval
            if ($req->status == 1) {
                // Objection Application replication
                $activeObjection = PropActiveObjection::query()
                    ->where('id', $req->objectionId)
                    ->first();

                $approvedObjection = $activeObjection->replicate();
                $approvedObjection->setTable('prop_objections');
                $approvedObjection->id = $activeObjection->id;
                $approvedObjection->save();
                $activeObjection->delete();

                $msg = "Application Successfully Approved !!";
            }
            // Rejection

            if ($req->status == 0) {
                // Objection Application replication
                $activeObjection = PropActiveObjection::query()
                    ->where('id', $req->objectionId)
                    ->first();

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

    /**
     * | Back to Citizen the Application
     * | @param request $req
     */
    public function backToCitizen($req)
    {
        try {
            $redis = Redis::connection();
            $workflowId = $req->workflowId;
            $backId = json_decode(Redis::get('workflow_initiator_' . $workflowId));
            if (!$backId) {
                $backId = WfWorkflowrolemap::where('workflow_id', $workflowId)
                    ->where('is_initiator', true)
                    ->first();
                $redis->set('workflow_initiator_' . $workflowId, json_encode($backId));
            }
            DB::beginTransaction();
            $objection = PropActiveObjection::find($req->objectionId);
            $objection->current_role = $backId->wf_role_id;
            $objection->save();

            $propLevelPending = new PropObjectionLevelpending();
            $preLevelPending = $propLevelPending->getCurrentObjByReceiver($req->objectionId, $req->currentRoleId);
            $preLevelPending->remarks = $req->comment;
            $preLevelPending->save();

            $levelPending = new PropObjectionLevelpending();
            $levelPending->objection_id = $req->objectionId;
            $levelPending->sender_role_id = $req->currentRoleId;
            $levelPending->receiver_role_id = $backId->wf_role_id;
            $levelPending->user_id = authUser()->id;
            $levelPending->sender_user_id = authUser()->id;
            $levelPending->save();

            DB::commit();
            return responseMsgs(true, "Successfully Done", "", '010812', '01', '474ms-573', 'Post', '');
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }


    //objection list
    public function objectionList()
    {
        try {
            $list = PropActiveObjection::select(
                'prop_active_objections.id',
                'applicant_name as ownerName',
                'holding_no as holdingNo',
                'objection_for as objectionFor',
                'ward_name as wardId',
                'property_type as propertyType',
                'dob',
                'gender',
            )
                ->join('prop_properties', 'prop_properties.id', 'prop_active_objections.property_id')
                ->join('ref_prop_types', 'ref_prop_types.id', 'prop_properties.prop_type_mstr_id')
                ->join('ulb_ward_masters', 'ulb_ward_masters.id', 'prop_properties.ward_mstr_id')
                ->join('prop_owners', 'prop_owners.property_id', 'prop_properties.id')
                ->where('prop_active_objections.status', 1)
                ->orderByDesc('prop_active_objections.id')
                ->get();

            return responseMsgs(true, "", $list, '010813', '01', '319ms-364ms', 'Post', '');
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    //get objection list by id
    public function objectionByid($req)
    {
        try {
            $list = PropActiveObjection::select(
                'prop_active_objections.id',
                'applicant_name as ownerName',
                'holding_no as holdingNo',
                'objection_for as objectionFor',
                'ward_name as wardId',
                'property_type as propertyType',
                'dob',
                'gender',

            )
                ->where('prop_active_objections.id', $req->id)
                ->where('prop_active_objections.status', 1)
                ->orderByDesc('prop_active_objections.id')
                ->join('prop_properties', 'prop_properties.id', 'prop_active_objections.property_id')
                ->join('ref_prop_types', 'ref_prop_types.id', 'prop_properties.prop_type_mstr_id')
                ->join('ulb_ward_masters', 'ulb_ward_masters.id', 'prop_properties.ward_mstr_id')
                ->join('prop_owners', 'prop_owners.property_id', 'prop_properties.id')
                ->first();

            return responseMsgs(true, "Successfully Done", $list, '010814', '01', '315ms-352ms', 'Post', '');
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    //get objection list
    public function objectionDocList($req)
    {
        try {
            $list = PropActiveObjectionDocdtl::select(
                'id',
                'doc_type as docName',
                'doc_name as docUrl',
                'relative_path',
                'verify_status as docStatus',
                'remarks as docRemarks'
            )
                ->where('prop_active_objection_docdtls.objection_id', $req->id)
                ->orderByDesc('prop_active_objection_docdtls.id')
                ->get();

            $list = $list->map(function ($val) {
                $path = $this->_bifuraction->readDocumentPath($val->relative_path . $val->docUrl);
                $val->docUrl = $path;
                return $val;
            });

            return responseMsgs(true, "Successfully Done", remove_null($list), '010815', '01', '329ms-400ms', 'Post', '');
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }


    //objectionn document upload 
    public function objectionDocUpload($req)
    {
        try {
            $validator = Validator::make($req->all(), [
                'nameDoc' => 'max:2000',
                'addressDoc' => 'max:2000',
                'safMemberDoc' => 'max:2000',
                'objectionFormDoc' => 'max:2000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'errors' => $validator->errors()
                ], 401);
            }
            // return $req;
            // $doc['nameDoc'] = $req->nameDoc;
            // $doc['addressDoc'] = $req->addressDoc;
            // $doc['safMemberDoc'] = $req->safMemberDoc;

            // foreach ($req->doc as  $documents) {

            //     $doc = array_key_last($documents);
            //     $base64Encode = base64_encode($documents[$doc]->getClientOriginalName());
            //     $extention = $documents[$doc]->getClientOriginalExtension();
            //     $imageName = time() . '-' . $base64Encode . '.' . $extention;
            //     $documents[$doc]->storeAs('public/objection/' . $doc, $imageName);

            //     $appDoc = new PropActiveObjectionDocdtl();
            //     $appDoc->objection_id = $req->objectionId;
            //     $appDoc->doc_name = $imageName;
            //     $appDoc->relative_path = ('objection/' . $doc . '/');
            //     $appDoc->doc_type = $doc;
            //     $appDoc->save();
            // }


            //for address doc
            // return $doc = key($req->nameDoc);


            if ($file = $req->file('addressDoc')) {
                $docName = "addressDoc";
                $name = $this->moveFile($docName, $file);

                $checkExisting = PropActiveObjectionDocdtl::where('objection_id', $req->id)
                    ->where('doc_type', $docName)
                    ->get()
                    ->first();
                if ($checkExisting) {
                    $this->updateDocument($req, $name,  $docName);
                } else {
                    $this->saveObjectionDoc($req, $name,  $docName);
                }
            }

            // saf doc
            if ($file = $req->file('safMemberDoc')) {
                $docName = "safMemberDoc";
                $name = $this->moveFile($docName, $file);

                $checkExisting = PropActiveObjectionDocdtl::where('objection_id', $req->id)
                    ->where('doc_type', $docName)
                    ->get()
                    ->first();
                if ($checkExisting) {
                    $this->updateDocument($req, $name,  $docName);
                } else {
                    $this->saveObjectionDoc($req, $name,  $docName);
                }
            }

            if ($file = $req->file('nameDoc')) {
                $docName = "nameDoc";
                $name = $this->moveFile($docName, $file);

                $checkExisting = PropActiveObjectionDocdtl::where('objection_id', $req->id)
                    ->where('doc_type', $docName)
                    ->get()
                    ->first();
                if ($checkExisting) {
                    $this->updateDocument($req, $name,  $docName);
                } else {
                    $this->saveObjectionDoc($req, $name,  $docName);
                }
            }

            if ($file = $req->file('objectionFormDoc')) {
                $docName = "objectionFormDoc";
                $name = $this->moveFile($docName, $file);

                $checkExisting = PropActiveObjectionDocdtl::where('objection_id', $req->id)
                    ->where('doc_type', $docName)
                    ->get()
                    ->first();
                if ($checkExisting) {
                    $this->updateDocument($req, $name,  $docName);
                } else {
                    $this->saveObjectionDoc($req, $name,  $docName);
                }
            }
            return responseMsgs(true, "Document Successfully Uploaded!", '', '010816', '01', '364ms-389ms', 'Post', '');
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }


    //post objection status
    public function objectionDocStatus($req)
    {
        try {
            $userId = auth()->user()->id;

            $docStatus = PropActiveObjectionDocdtl::find($req->id);
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

            return responseMsgs(true, "Successfully Done", '', '010817', '01', '302ms-356ms', 'Post', '');
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    //citizen doc upload
    public function citizenDocUpload($objectionDoc, $name, $docName)
    {
        $userId = auth()->user()->id;

        $objectionDoc->doc_type = $docName;
        $objectionDoc->relative_path = ('/objection/' . $docName . '/');
        $objectionDoc->doc_name = $name;
        $objectionDoc->status = 1;
        $objectionDoc->user_id = $userId;
        $objectionDoc->date = Carbon::now();
        $objectionDoc->created_at = Carbon::now();
        $objectionDoc->save();
    }

    //save objection
    public function saveObjectionDoc($req, $name,  $docName)
    {
        $objectionDoc =  new PropActiveObjectionDocdtl();
        $objectionDoc->objection_id = $req->id;
        $objectionDoc->doc_type = $docName;
        $objectionDoc->relative_path = ('/objection/' . $docName . '/');
        $objectionDoc->doc_name = $name;
        $objectionDoc->status = 1;
        $objectionDoc->date = Carbon::now();
        $objectionDoc->created_at = Carbon::now();
        $objectionDoc->save();
    }



    public function updateDocument($req, $name,  $docName)
    {
        PropActiveObjectionDocdtl::where('objection_id', $req->id)
            ->where('doc_type', $docName)
            ->update([
                'objection_id' => $req->id,
                'doc_type' => $docName,
                'relative_path' => ('/objection/' . $docName . '/'),
                'doc_name' => $name,
                'status' => 1,
                'verify_status' => 0,
                'remarks' => '',
                'updated_at' => Carbon::now()
            ]);
    }

    //moving function to location
    public function moveFile($docName, $file)
    {
        $name = time() . $docName . $file->getClientOriginalExtension();
        $path = storage_path('app/public/objection/' . $docName . '/');
        $file->move($path, $name);

        return $name;
    }
}
