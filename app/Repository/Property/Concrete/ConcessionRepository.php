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
use App\Traits\Property\Concession;

class ConcessionRepository implements iConcessionRepository
{

    //wf_master_id = 35;
    //workflow_id = 106;
    use WorkflowTrait;
    use Concession;

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

    //get owner details
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

    /**
     * | Property Concession Inbox List
     * | @var auth autheticated user data
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
                ->get();
            return responseMsg(true, "Inbox List", remove_null($concessions));
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Outbox List
     * | @var auth authenticated user list
     * | @var ulbId authenticated user ulb
     * | @var userid authenticated user id
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
                ->get();

            return responseMsg(true, "Outbox List", remove_null($concessions));
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Get Concession Details by Concession ID
     */
    public function getDetailsById($req)
    {
        try {
            $details = DB::table('prop_active_concessions')
                ->select(
                    'prop_active_concessions.*',
                    'prop_active_concessions.applicant_name as owner_name',
                    's.holding_no',
                    's.ward_mstr_id',
                    'u.ward_name as ward_no',
                    's.prop_type_mstr_id',
                    'p.property_type'
                )
                ->join('safs as s', 's.id', '=', 'prop_active_concessions.property_id')
                ->join('ulb_ward_masters as u', 'u.id', '=', 's.ward_mstr_id')
                ->join('prop_m_property_types as p', 'p.id', '=', 's.prop_type_mstr_id')
                ->where('prop_active_concessions.id', $req->id)
                ->first();
            return responseMsg(true, "Concession Details", remove_null($details));
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }
}
