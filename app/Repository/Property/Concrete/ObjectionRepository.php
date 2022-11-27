<?php

namespace App\Repository\Property\Concrete;

use App\Models\Property\PropOwner;
use Exception;
use Illuminate\Http\Request;
use App\Repository\Property\Interfaces\iObjectionRepository;
use App\Models\UlbWardMaster;
use App\Models\Property\PropObjection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Traits\Workflow\Workflow as WorkflowTrait;
use Illuminate\Support\Facades\Config;
use App\Models\Property\PropActiveObjection;
use App\Models\Property\PropActiveObjectionOwner;
use App\Models\Property\RefPropObjectionType;
use App\Traits\Property\Objection;
use App\Models\Workflows\WfWorkflow;
use App\Models\Property\PropObjectionDtl;
use App\Repository\Property\Concrete\SafRepository;
use App\Models\Property\PropProperty;
use App\Models\Property\PropObjectionFloor;
use App\Models\Property\PropObjectionLevelpending;
use Illuminate\Support\Facades\Redis;
use App\Models\Workflows\WfWorkflowrolemap;




class ObjectionRepository implements iObjectionRepository
{
    use Objection;
    use WorkflowTrait;
    private  $_objectionNo;

    /**
     * | CLERICAL Workflow ID=36                            | Assesment Workflow ID=56
     * | CLERICAL Ulb WorkflowID=169                        | Assesment Ulb Workflow ID=183          
     */

    //get owner details
    public function ownerDetails($request)
    {
        try {
            $ownerDetails = PropOwner::select('owner_name as name', 'mobile_no as mobileNo', 'prop_address as address')
                ->where('prop_properties.id', $request->propId)
                ->join('prop_properties', 'prop_properties.id', '=', 'prop_owners.property_id')
                ->get();
            return $ownerDetails;
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    //apply objection
    public function applyObjection($request)
    {
        try {
            $userId = auth()->user()->id;
            $ulbId = auth()->user()->ulb_id;
            $userType = auth()->user()->user_type;


            if ($userType == "JSK") {
                $obj  = new SafRepository();
                $data = $obj->getPropByHoldingNo($request);
            }

            $objectionFor = $request->objectionFor;
            $objectionType = $request->id;
            $workflow_id_clerical = Config::get('workflow-constants.PROPERTY_OBJECTION_CLERICAL');
            $workflow_id_assesment = Config::get('workflow-constants.PROPERTY_OBJECTION_ASSESSMENT');


            if ($objectionFor == 'CLERICAL MISTAKE') {
                DB::beginTransaction();

                $objection = new PropActiveObjection;
                $objection->ulb_id = $ulbId;
                $objection->user_id = $userId;
                $objection->objection_for =  $objectionFor;

                $this->commonFunction($request, $objection);
                $objection->save();

                foreach ($request->safMember as $safMembers) {
                    $objectionOwner = new PropActiveObjectionOwner;
                    $objectionOwner->name = $request->name;
                    $objectionOwner->address = $request->address;
                    $objectionOwner->mobile = $request->mobileNo;
                    $objectionOwner->members = $safMembers;
                    $objectionOwner->objection_id = $objection->id;
                    $objectionOwner->created_at = Carbon::now();
                    $objectionOwner->save();
                }

                //name
                if ($file = $request->file('nameDoc')) {

                    $name = time() . '.' . $file->getClientOriginalExtension();
                    $path = public_path('objection/name');
                    $file->move($path, $name);
                }


                //address
                if ($file = $request->file('addressDoc')) {

                    $name = time() . '.' . $file->getClientOriginalExtension();
                    $path = public_path('objection/address');
                    $file->move($path, $name);
                }

                //saf doc
                if ($file = $request->file('safMemberDoc')) {

                    $name = time() . '.' . $file->getClientOriginalExtension();
                    $path = public_path('objection/safMembers');
                    $file->move($path, $name);
                }

                // $objectionNo = $this->objectionNo($id);
                DB::commit();
            }

            //objection for forgery 
            if ($objectionFor == 'Forgery') {

                $objection = new PropActiveObjection;
                $objection->ulb_id = $ulbId;
                $objection->user_id = $userId;
                $objection->objection_for =  $objectionFor;

                $this->commonFunction($request, $objection);
                $objection->save();

                //objection_form
                if ($file = $request->file('objectionForm')) {

                    $name = time() . '.' . $file->getClientOriginalExtension();
                    $path = public_path('objection/objectionForm');
                    $file->move($path, $name);
                }

                //Evidence Doc
                if ($file = $request->file('evidenceDoc')) {

                    $name = time() . '.' . $file->getClientOriginalExtension();
                    $path = public_path('objection/evidenceDoc');
                    $file->move($path, $name);
                }
                return responseMsg(true, "Successfully Saved", $name);
            }

            // objection against assesment
            if ($objectionFor == 'Assessment Error') {
                $objectionTypeId = $request->id;

                $objection = new PropActiveObjection;
                $objection->objection_for =  $objectionFor;
                $objection->ulb_id = $ulbId;
                $objection->user_id = $userId;

                // $this->commonFunction($request, $objection);

                $ulbWorkflowId = WfWorkflow::where('wf_master_id', $workflow_id_assesment)
                    ->where('ulb_id', $ulbId)
                    ->first();

                $refInitiatorRoleId = $this->getInitiatorId($ulbWorkflowId->id);            // Get Current Initiator ID
                $initiatorRoleId = DB::select($refInitiatorRoleId);

                $objection->workflow_id = $ulbWorkflowId->id;
                $objection->current_role = $initiatorRoleId[0]->role_id;
                $objection->save();


                foreach ($objectionTypeId as $otid) {
                    $assement_error = new PropObjectionDtl;
                    $assement_error->objection_id = $objection->id;
                    $assement_error->objection_type_id = $otid;

                    //RWH
                    if ($otid == 2) {
                        $assement_error->data_ref_type = 'boolean';
                    }
                    //road width
                    if ($otid == 3) {
                        $assement_error->data_ref_type = 'ref_prop_road_types.id';
                    }
                    //property_types
                    if ($otid == 4) {
                        $assement_error->data_ref_type = 'ref_prop_types.id';
                    }
                    //area off plot
                    if ($otid == 5) {
                        $assement_error->data_ref_type = 'area';
                    }
                    //mobile tower
                    if ($otid == 6) {
                        $assement_error->data_ref_type = 'boolean';
                    }
                    //hoarding board
                    if ($otid == 7) {
                        $assement_error->data_ref_type = 'boolean';
                    }
                    $assement_error->assesment_data =  $request->assesmentData;
                    $assement_error->applicant_data =  $request->applicantData;
                    $assement_error->save();
                }

                //floor entry
                $assement_floor = new PropObjectionFloor;
                $assement_floor->property_id = $request->propId;
                $assement_floor->objection_id = $request->objectionId;
                $assement_floor->prop_floor_id = $request->propFloorId;
                $assement_floor->floor_mstr_id = $request->floorMstrId;
                $assement_floor->usage_type_mstr_id = $request->usageTypeMstrId;
                $assement_floor->occupancy_type_mstr_id = $request->occupancyTypeMstrId;
                $assement_floor->const_type_mstr_id = $request->constTypeMstrId;
                $assement_floor->builtup_area = $request->builtUpArea;
                $assement_floor->carpet_area = $request->carpetArea;
                $assement_floor->save();


                //objection_form
                if ($file = $request->file('objectionForm')) {

                    $name = time() . '.' . $file->getClientOriginalExtension();
                    $path = public_path('objection/objectionForm');
                    $file->move($path, $name);
                }

                //Evidence Doc
                if ($file = $request->file('evidenceDoc')) {

                    $name = time() . '.' . $file->getClientOriginalExtension();
                    $path = public_path('objection/evidenceDoc');
                    $file->move($path, $name);
                }
            }

            $objectionNo = $this->objectionNo($objection->id);

            // $ulbWorkflowId = WfWorkflow::where('wf_master_id', $workflow_id_clerical)
            //     ->where('ulb_id', $ulbId)
            //     ->first();

            // $refInitiatorRoleId = $this->getInitiatorId($ulbWorkflowId->id);            // Get Current Initiator ID
            // $initiatorRoleId = DB::select($refInitiatorRoleId);

            // $objection->workflow_id = $ulbWorkflowId->id;
            // $objection->current_role = $initiatorRoleId[0]->role_id;
            //level pending
            $labelPending = new PropObjectionLevelpending();
            $labelPending->objection_id = $objection->id;
            $labelPending->receiver_role_id = $initiatorRoleId[0]->role_id;
            $labelPending->save();


            return responseMsg(true, "Successfully Saved", $objectionNo);
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }


    //objection number generation
    public function objectionNo($id)
    {
        try {
            $count = PropActiveObjection::where('id', $id)
                ->count() + 1;

            $_objectionNo = 'OBJ' . "/" . str_pad($count, 5, '0', STR_PAD_LEFT);

            return $_objectionNo;
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }


    //objection type list
    public function objectionType()
    {
        try {
            $objectionType = RefPropObjectionType::where('status', 1)
                ->select('id', 'type')
                ->get();
            return $objectionType;
        } catch (Exception $e) {
            echo $e->getMessage();
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
                'road_type as roadType',
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
            return responseMsg(true, "Successfully Retrieved", $assesmentDetailss);
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
            $wardId = $this->getWardByUserId($userId);

            $occupiedWards = collect($wardId)->map(function ($ward) {                               // Get Occupied Ward of the User
                return $ward->ward_id;
            });

            $roles = $this->getRoleIdByUserId($userId);

            $roleId = collect($roles)->map(function ($role) {                                       // get Roles of the user
                return $role->wf_role_id;
            });

            $objection = $this->getObjectionList($ulbId)                                            // Objection List
                ->whereIn('prop_active_objections.current_role', $roleId)
                ->whereIn('p.ward_mstr_id', $occupiedWards)
                ->get();

            return responseMsg(true, "Inbox List", remove_null($objection));
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
                ->get();

            return responseMsg(true, "Outbox List", remove_null($objections));
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
                'prop_active_objections.objection_type_id',
                'ot.type as objection_type',
                'prop_active_objections.objection_no',
                'prop_active_objections.workflow_id',
                'prop_active_objections.current_role',
                'p.*',
                'at.assessment_type as assessment',
                'w.ward_name as old_ward_no',
                'o.ownership_type',
                'pt.property_type'
            )

            ->join('prop_properties as p', 'p.id', '=', 'prop_active_objections.property_id')
            ->join('prop_safs as s', 's.id', '=', 'p.saf_id')
            ->join('ulb_ward_masters as w', 'w.id', '=', 's.ward_mstr_id')
            ->leftJoin('ulb_ward_masters as nw', 'nw.id', '=', 's.new_ward_mstr_id')
            ->join('ref_prop_ownership_types as o', 'o.id', '=', 's.ownership_type_mstr_id')
            ->leftJoin('prop_ref_assessment_types as at', 'at.id', '=', 's.assessment_type')
            ->leftJoin('ref_prop_types as pt', 'pt.id', '=', 's.property_assessment_id')
            ->join('ref_prop_objection_types as ot', 'ot.id', '=', 'prop_active_objections.objection_type_id')
            ->where('p.status', 1)
            ->where('prop_active_objections.id', $req->id)
            ->first();
        return responseMsg(true, "Objection Details", remove_null($details));
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
            return responseMsg(true, $req->escalateStatus == 1 ? 'Objection is Escalated' : "Objection is removed from Escalated", '');
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
            return responseMsg(true, "Data Fetched", remove_null($safData));
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
            return responseMsg(true, "Successfully Forwarded The Application!!", "");
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
            return responseMsg(true, $msg, "");
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
            return responseMsg(true, "Successfully Done", "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    //common function
    public function commonFunction($request, $objection)
    {
        $ulbId = auth()->user()->ulb_id;
        // $workflow_id_clerical = Config::get('workflow-constants.PROPERTY_OBJECTION_CLERICAL');

        // $ulbWorkflowId = WfWorkflow::where('wf_master_id', $workflow_id_clerical)
        //     ->where('ulb_id', $ulbId)
        //     ->first();

        // $refInitiatorRoleId = $this->getInitiatorId($ulbWorkflowId->id);            // Get Current Initiator ID
        // $initiatorRoleId = DB::select($refInitiatorRoleId);

        // $objection->workflow_id = $ulbWorkflowId->id;
        // $objection->current_role = $initiatorRoleId[0]->role_id;
        $this->postObjection($objection, $request);
    }
}
