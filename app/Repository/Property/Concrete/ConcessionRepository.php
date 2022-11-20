<?php

namespace App\Repository\Property\Concrete;

use App\Models\Property\ActiveSafsOwnerDtl;
use App\Models\Property\PropOwnerDtl;
use App\Repository\Property\Interfaces\iConcessionRepository;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use App\Models\Property\PropConcession;
use App\Models\Property\PropProperty;
use App\Models\Property\PropActiveConcession;
use App\Traits\Workflow\Workflow as WorkflowTrait;
use Illuminate\Support\Facades\DB;
use App\Models\Workflows\WfWorkflow;


class ConcessionRepository implements iConcessionRepository
{

    //wf_master_id = 35;
    //workflow_id = 106;
    use WorkflowTrait;


    //apply concession
    public function applyConcession(Request $request)
    {
        $user_id = auth()->user()->id;
        $ulb_id = auth()->user()->ulb_id;
        // workflows


        try {

            $workflow_id = 35;
            $device = new PropActiveConcession;
            $device->property_id = $request->propertyId;
            $device->saf_id = $request->safId;
            $device->application_no = $request->applicationNo;
            $device->applicant_name = $request->applicantName;
            $device->gender = $request->gender;
            $device->dob = $request->dob;
            $device->is_armed_force = $request->armedForce;
            $device->is_specially_abled = $request->speciallyAbled;
            $device->doc_type = $request->docType;
            $device->remarks = $request->remarks;
            $device->status = $request->status;
            $device->user_id = $user_id;
            $device->ulb_id = $ulb_id;



            $ulbWorkflowId = WfWorkflow::where('wf_master_id', $workflow_id)
                ->where('ulb_id', $ulb_id)
                ->first();




            $refInitiatorRoleId = $this->getInitiatorId($ulbWorkflowId->id);                // Get Current Initiator ID
            $initiatorRoleId = DB::select($refInitiatorRoleId);

            $device->workflow_id = $ulbWorkflowId->id;
            $device->current_role = $initiatorRoleId[0]->role_id;
            $device->created_at = Carbon::now();
            $device->date = Carbon::now();

            $device->save();


            //gender Doc
            if ($file = $request->file('genderDoc')) {

                $name = time() . $file . '.' . $file->getClientOriginalExtension();
                $path = public_path('concession/genderDoc');
                $file->move($path, $name);
            }

            // dob Doc
            if ($file = $request->file('dobDoc')) {

                $name = time() . $file . '.' . $file->getClientOriginalExtension();
                $path = public_path('concession/dobDoc');
                $file->move($path, $name);
            }

            // specially abled Doc
            if ($file = $request->file('speciallyAbledDoc')) {

                $name = time() . $file . '.' . $file->getClientOriginalExtension();
                $path = public_path('concession/speciallyAbledDoc');
                $file->move($path, $name);
            }

            // Armed force Doc
            if ($file = $request->file('armedForceDoc')) {

                $name = time() . $file . '.' . $file->getClientOriginalExtension();
                $path = public_path('concession/armedForceDoc');
                $file->move($path, $name);
            }

            return responseMsg('200', 'Successfully Applied', $device);
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    ////get owner details
    public function postHolding(Request $request)
    {
        try {

            $request->validate([
                'holdingNo' => 'required'
            ]);
            $user = PropProperty::where('holding_no', $request->holdingNo)
                ->get();
            if (!empty($user['0'])) {
                return responseMsg(true, 'True', $user);
            }
            return responseMsg(false, "False", "");
            // return $user['0'];
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }
}
