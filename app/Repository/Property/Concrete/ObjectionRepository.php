<?php

namespace App\Repository\Property\Concrete;

use App\Models\Property\PropOwner;
use Exception;
use Illuminate\Http\Request;
use App\Repository\Property\Interfaces\iObjectionRepository;
use App\Models\UlbWardMaster;
use App\Models\Property\PropObjection;
use Illuminate\Support\Carbon;
use  App\Models\Property\ObjectionOwnerDetail;
use App\Models\ObjectionTypeMstr;
use Illuminate\Support\Facades\DB;
use App\Traits\Workflow\Workflow as WorkflowTrait;
use Illuminate\Support\Facades\Config;
use App\Models\Property\PropActiveObjection;
use App\Models\Property\RefPropObjectionType;
use App\Models\Property\PropObjectionOwnerDtl;
use App\Traits\Property\Objection;
use App\Models\Workflows\WfWorkflow;
use App\Models\Property\PropObjectionDtlsCopy;




class ObjectionRepository implements iObjectionRepository
{
    use Objection;
    use WorkflowTrait;
    private  $_objectionNo;


    //get owner details
    public function getOwnerDetails(Request $request)
    {
        try {
            $ownerDetails = PropOwner::select('owner_name as name', 'mobile_no as mobileNo', 'prop_address as address')
                ->where('prop_properties.holding_no', $request->holdingNo)
                ->join('prop_properties', 'prop_properties.id', '=', 'prop_owner_dtls.property_id')
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
            $objectionType = $request->id;
            $workflow_id = Config::get('workflow-constants.PROPERTY_OBJECTION_ID');
            $clericalMistake = Config::get('workflow-constants.CLERICAL_MISTAKE_ID');
            $forgery = Config::get('workflow-constants.FORGERY_ID');


            if ($objectionType == $clericalMistake) {
                DB::beginTransaction();

                $objectionOwner = new PropObjectionOwnerDtl;
                $objectionOwner->name = $request->name;
                $objectionOwner->address = $request->address;
                $objectionOwner->mobile = $request->mobileNo;
                $objectionOwner->members = $request->safMember;
                $objectionOwner->created_at = Carbon::now();
                $objectionOwner->updated_at = Carbon::now();
                $objectionOwner->save();

                $objection = new PropActiveObjection;
                $objection->objection_owner_id = $objectionOwner->id;
                $objection->ulb_id = $ulbId;
                $objection->user_id = $userId;

                $this->commonFunction($request, $objection);
                $objection->save();

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

                // $objectionNo = $this->objectionNo($propertyId);
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
                $objection = new PropActiveObjection;
                $objection->ulb_id = $ulbId;
                $objection->user_id = $userId;

                $this->commonFunction($request, $objection);
                $objection->save();

                $assement_error = new PropObjectionDtlsCopy;
                $assement_error->objection_id = $objection->id;
                $assement_error->objection_type = $request->objectionType;
                $assement_error->previous = $request->previous;
                $assement_error->current =  $request->current;
                $assement_error->save();

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
    public function objectionNo($id)
    {

        try {
            $count = PropActiveObjection::where('id', $id)
                // ->where('ulb_id', $ulbId)
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
        $objectionType = RefPropObjectionType::where('status', 1)
            ->select('id', 'type')
            ->get();
        return $objectionType;
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

            $concessions = $this->getConcessionList($ulbId)
                ->whereIn('prop_active_concessions.current_role', $roleId)
                ->whereIn('a.ward_mstr_id', $occupiedWards)
                ->get();
            return responseMsg(true, "Inbox List", remove_null($concessions));
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

        $refInitiatorRoleId = $this->getInitiatorId($ulbWorkflowId->id);                // Get Current Initiator ID
        $initiatorRoleId = DB::select($refInitiatorRoleId);

        $objection->workflow_id = $ulbWorkflowId->id;
        $objection->current_role = $initiatorRoleId[0]->role_id;
        $this->postObjection($objection, $request);
    }
}
