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



class ObjectionRepository implements iObjectionRepository
{
    private  $_objectionNo;

    public function ClericalMistake(Request $request)
    {
        $data = $this->getOwnerDetails($request->id);
    }

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
        if ($request->objectionType == 10) {
            DB::beginTransaction();
            try {
                $device = new ObjectionOwnerDetail;
                $device->name = $request->name;
                $device->address = $request->address;
                $device->mobile = $request->mobileNo;
                $device->members = $request->safMember;
                $device->created_at = Carbon::now();
                $device->updated_at = Carbon::now();
                $device->save();

                $data = new PropObjection;
                $data->property_id = $request->propertyId;
                $data->objection_no = $this->_objectionNo;
                $data->objection_form = $request->objectionForm;
                $data->remark_on_status = $request->remarks;
                $data->evidence_document = $request->evidenceDoc;
                $data->user_id = $request->userId;
                $data->objection_type_id = 10;
                $data->objection_owner_id = $device->id;
                $data->created_at = Carbon::now();
                $data->updated_at = Carbon::now();
                $data->save();



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
}
