<?php

namespace App\Repository\Property\Concrete;

use App\Models\Property\PropOwnerDtl;
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




class ObjectionRepository implements iObjectionRepository
{
    use WorkflowTrait;
    private  $_objectionNo;


    //get owner details
    public function getOwnerDetails(Request $request)
    {
        try {
            $ownerDetails = PropOwnerDtl::select('owner_name as name', 'mobile_no as mobileNo', 'prop_address as address')
                ->where('prop_properties.holding_no', $request->holdingNo)
                ->join('prop_properties', 'prop_properties.id', '=', 'prop_owner_dtls.property_id')
                ->get();
            return $ownerDetails;
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    //apply objection
    public function rectification(Request $request)
    {
        $user_id = auth()->user()->id;
        $ulb_id = auth()->user()->ulb_id;

        if ($request->id == 10) {
            DB::beginTransaction();
            try {

                $workflow_id = Config::get('workflow-constants.PROPERTY_OBJECTION_ID');
                $objectionOwner = new ObjectionOwnerDetail;
                $objectionOwner->name = $request->name;
                $objectionOwner->address = $request->address;
                $objectionOwner->mobile = $request->mobileNo;
                $objectionOwner->members = $request->safMember;
                $objectionOwner->created_at = Carbon::now();
                $objectionOwner->updated_at = Carbon::now();
                $objectionOwner->save();

                $objection = new PropActiveObjection;
                $objection->property_id = $request->propertyId;
                $objection->objection_type_id = $request->id;
                $objection->objection_owner_id = $objectionOwner->id;
                $objection->objection_no = $this->_objectionNo;
                $objection->objection_form = $request->objectionForm;
                $objection->evidence_doc = $request->evidenceDoc;
                $objection->ulb_id = $ulb_id;
                $objection->user_id = $user_id;
                $objection->workflow_id = $workflow_id;
                $objection->current_role = $request->currentRole;
                $objection->status = $request->status;
                $objection->remarks = $request->remarks;
                $objection->created_at = Carbon::now();
                $objection->updated_at = Carbon::now();
                $objection->save();



                //name
                if ($file = $request->file('nameDoc')) {

                    $name = time() . $file . '.' . $file->getClientOriginalExtension();
                    $path = public_path('objection/name');
                    $file->move($path, $name);
                }


                //address
                if ($file = $request->file('addressDoc')) {

                    $name = time() . $file . '.' . $file->getClientOriginalExtension();
                    $path = public_path('objection/address');
                    $file->move($path, $name);
                }

                //saf doc
                if ($file = $request->file('safMemberDoc')) {

                    $name = time() . $file . '.' . $file->getClientOriginalExtension();
                    $path = public_path('objection/safMembers');
                    $file->move($path, $name);
                }

                // $objectionNo = $this->objectionNo($propertyId);
                DB::commit();
                return responseMsg(true, "Successfully Saved", "");
            } catch (Exception $e) {
                return response()->json($e, 400);
            }
        }
    }

    //objection number generation
    public function objectionNo($propertyId)
    {
        try {
            $count = PropObjection::where('property_id', $propertyId)
                // ->where('ulb_id', $ulb_id)
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
}
