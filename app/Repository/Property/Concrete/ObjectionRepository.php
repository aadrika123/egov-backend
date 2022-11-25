<?php

namespace App\Repository\Property\Concrete;

use App\Models\Property\PropOwner;
use Exception;
use Illuminate\Http\Request;
use App\Repository\Property\Interfaces\iObjectionRepository;
use App\Models\UlbWardMaster;
use App\Models\Property\PropObjection;
use Illuminate\Support\Carbon;
use App\Models\ObjectionTypeMstr;
use Illuminate\Support\Facades\DB;
use App\Traits\Workflow\Workflow as WorkflowTrait;
use Illuminate\Support\Facades\Config;
use App\Models\Property\PropActiveObjection;
use App\Models\Property\RefPropObjectionType;
use App\Models\Property\PropObjectionOwnerDtl;
use App\Traits\Property\Objection;
use App\Models\Workflows\WfWorkflow;
use App\Models\Property\PropObjectionDtl;
use App\Repository\Property\Concrete\SafRepository;
use App\Models\Property\PropProperty;
use App\Models\Property\PropObjectionFloor;



class ObjectionRepository implements iObjectionRepository
{
    use Objection;
    use WorkflowTrait;
    private  $_objectionNo;


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

            $objectionType = $request->id;
            $workflow_id = Config::get('workflow-constants.PROPERTY_OBJECTION_ID');
            $clericalMistake = Config::get('workflow-constants.CLERICAL_MISTAKE_ID');
            $forgery = Config::get('workflow-constants.FORGERY_ID');


            if ($objectionType == $clericalMistake) {
                DB::beginTransaction();

                $objection = new PropActiveObjection;
                $objection->ulb_id = $ulbId;
                $objection->user_id = $userId;

                $this->commonFunction($request, $objection);
                $objection->save();

                foreach ($request->safMember as $safMembers) {
                    $objectionOwner = new PropObjectionOwnerDtl;
                    $objectionOwner->name = $request->name;
                    $objectionOwner->address = $request->address;
                    $objectionOwner->mobile = $request->mobileNo;
                    $objectionOwner->members = $safMembers;
                    $objectionOwner->objection_id = $objection->id;
                    $objectionOwner->created_at = Carbon::now();
                    $objectionOwner->updated_at = Carbon::now();
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

                $objectionNo = $this->objectionNo($id, $ulbId);
                DB::commit();
            }

            //objection for forgery 
            if ($objectionType == $forgery) {

                $objection = new PropActiveObjection;
                $objection->ulb_id = $ulbId;
                $objection->user_id = $userId;

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
            if ($objectionType !== $clericalMistake  && $objectionType !== $forgery) {
                $objectionTypeId = $request->objectionTypeId;
                $objection = new PropActiveObjection;
                $objection->ulb_id = $ulbId;
                $objection->user_id = $userId;

                $this->commonFunction($request, $objection);
                $objection->save();

                $assement_error = new PropObjectionDtl;
                $assement_error->objection_id = $objection->id;
                $assement_error->objection_type_id = $objectionTypeId;

                //RWH
                if ($objectionTypeId == 2) {
                    $assement_error->data_ref_type = 'boolean';
                }
                //road width
                if ($objectionTypeId == 3) {
                    $assement_error->data_ref_type = 'ref_prop_road_types.id';
                }
                //property_types
                if ($objectionTypeId == 4) {
                    $assement_error->data_ref_type = 'ref_prop_types.id';
                }
                //area off plot
                if ($objectionTypeId == 5) {
                    $assement_error->data_ref_type = 'area';
                }
                //mobile tower
                if ($objectionTypeId == 6) {
                    $assement_error->data_ref_type = 'boolean';
                }
                //hoarding board
                if ($objectionTypeId == 7) {
                    $assement_error->data_ref_type = 'boolean';
                }
                $assement_error->assesment_data =  $request->assesmentData;
                $assement_error->applicant_data =  $request->applicantData;
                $assement_error->save();


                //floor entry
                $assement_floor = new PropObjectionFloor;
                // $assement_floor = ;


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

            return responseMsg(true, "Successfully Saved", '');
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }


    //objection number generation
    public function objectionNo($property_id)
    {

        try {
            $count = PropActiveObjection::where('id', $id)
                ->where('ulb_id', $ulbId)
                ->count() + 1;
            $ward_no = UlbWardMaster::select("ward_name")->where('id', $ward_id)->first()->ward_name;
            $_objectionNo = 'OBJ' . str_pad($ward_no, 3, '0', STR_PAD_LEFT) . "/" . str_pad($count, 5, '0', STR_PAD_LEFT);

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
                'is_hoarding_board',
                // 'hoarding_area',
                // 'hoarding_installation_date',
                'is_water_harvesting',
                'is_mobile_tower',
                // 'tower_area',
                // 'tower_installation_date',
                'area_of_plot',
                'prop_type_mstr_id',
                'road_type_mstr_id',
                // 'prop_floors.*'
            )
                ->where('prop_properties.id', $request->propId)
                ->join('prop_floors', 'prop_floors.property_id', '=', 'prop_properties.id')
                ->get();
            foreach ($assesmentDetails as $assesmentDetailss) {
                $assesmentDetailss['floor'] = PropProperty::select(
                    'prop_floors.*'
                )
                    ->where('prop_properties.id', $request->propId)
                    ->join('prop_floors', 'prop_floors.property_id', '=', 'prop_properties.id')
                    ->get();
            }
            return responseMsg(true, "Successfully Retrieved", $assesmentDetails);
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    //Inbox 
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

            $objection = $this->getObjectionList($ulbId)
                ->whereIn('prop_active_objections.current_role', $roleId)
                ->whereIn('a.ward_mstr_id', $occupiedWards)
                ->get();
            return responseMsg(true, "Inbox List", remove_null($objection));
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    //outbox
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

            $concessions = $this->getObjectionList($ulbId)
                ->whereNotIn('prop_active_objections.current_role', $roleId)
                ->whereIn('a.ward_mstr_id', $occupiedWards)
                ->get();

            return responseMsg(true, "Outbox List", remove_null($concessions));
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    //common function
    public function commonFunction($request, $objection)
    {
        $ulbId = auth()->user()->ulb_id;
        $workflow_id = Config::get('workflow-constants.PROPERTY_OBJECTION_ID');

        $ulbWorkflowId = WfWorkflow::where('wf_master_id', $workflow_id)
            ->where('ulb_id', $ulbId)
            ->first();

        $refInitiatorRoleId = $this->getInitiatorId($ulbWorkflowId->id);            // Get Current Initiator ID
        $initiatorRoleId = DB::select($refInitiatorRoleId);

        $objection->workflow_id = $ulbWorkflowId->id;
        $objection->current_role = $initiatorRoleId[0]->role_id;
        $this->postObjection($objection, $request);
    }
}
