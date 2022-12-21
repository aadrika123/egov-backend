<?php

namespace App\Repository\Water\Concrete;

use App\Models\Payment\WebhookPaymentData;
use App\Models\Property\PropActiveSaf;
use App\Models\Property\PropProperty;
use App\Models\Water\WaterApplicant;
use App\Models\Water\WaterApplicantDoc;
use App\Models\Water\WaterApplication;
use App\Models\Water\WaterConnectionCharge;
use App\Models\Water\WaterConnectionThroughMstrs;
use App\Models\Water\WaterConnectionTypeMstr;
use App\Models\Water\WaterLevelpending;
use App\Models\Water\WaterOwnerTypeMstr;
use App\Models\Water\WaterPenaltyInstallment;
use App\Models\Water\WaterPropertyTypeMstr;
use App\Models\Workflows\WfWorkflow;
use App\Repository\Water\Interfaces\iNewConnection;
use App\Traits\Payment\Razorpay;
use App\Traits\Ward;
use App\Traits\Workflow\Workflow;
use App\Traits\Property\SAF;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use League\CommonMark\Node\Block\Document;
use Symfony\Component\CssSelector\Node\FunctionNode;

use function PHPUnit\Framework\isNull;

/**
 * | -------------- Repository for the New Water Connection Operations ----------------------- |
 * | Created On-07-10-2022 
 * | Created By-Anshu Kumar
 * | Updated By-Sam kerketta
 */

class NewConnectionRepository implements iNewConnection
{
    use SAF;
    use Workflow;
    use Ward;
    use Razorpay;

    /**
     * | ---------------------------- Get Owner Type / Water ------------------------------- |
     * | @var ulbId 
     * | @var ward
     * | @return ward : return the list of ward according to ulbID.
     * | Operation : data fetched by table ulb_ward_masters / Calling(->getAllWards($ulbID) on Model) for the list of ward
        | Serial No :01
     */
    public function getWardNo()
    {
        try {
            $ulbId = auth()->user()->ulb_id;
            $ward = $this->getAllWard($ulbId);
            return responseMsgs(true, "Ward List!", $ward,);
        } catch (Exception $error) {
            return responseMsg(false, "ERROR!", $error->getMessage());
        }
    }


    /**
     * | ------------------------------------- Get Owner Type / Water ------------------------------- |
     * | @var ownerType 
     * | @return ownerType : return the master data of the owner type for Water. 
     * | Operation : data fetched by table water_owner_type_mstrs 
        | Serila No : 02
     */
    public function getOwnerType()
    {
        try {
            $ownerType = new WaterOwnerTypeMstr();
            $ownerType = $ownerType->getallOwnwers();
            return response()->json(['status' => true, 'message' => 'data of the ownerType', 'data' => $ownerType]);
        } catch (Exception $error) {
            return responseMsg(false, "ERROR!", $error->getMessage());
        }
    }


    /**
     * | ----------------- Get Connection Type / Water ------------------------------- |
     * | @var connectionTypes 
     * | @return connectionType : return the master data of the connectin type for  Water.
     * | Operation : data fetched by table water_connection_type_mstrs 
        | Serila No : 03
     */
    public function getConnectionType()
    {
        try {
            $connectionTypes = new WaterConnectionTypeMstr();       //<---------------- make object
            $connectionTypes = $connectionTypes->getConnectionType();
            return response()->json(['status' => true, 'message' => 'data of the connectionType', 'data' => $connectionTypes]);
        } catch (Exception $error) {
            return responseMsg(false, "ERROR!", $error->getMessage());
        }
    }



    /**
     * | ----------------- Get Connection Through / Water ------------------------------- |
     * | @var connectionThrough 
     * | @return connectionThrough : return the master data of the connection through for water.
     * | Operation : data fetched by table water_connection_through_mstrs 
         | Serila No : 04
     */
    public function getConnectionThrough()
    {
        try {
            $connectionThrough = new WaterConnectionThroughMstrs();     //<---------------- make object
            $connectionThrough = $connectionThrough->getAllThrough();
            return response()->json(['status' => true, 'message' => 'data of the connectionThrough', 'data' => $connectionThrough]);
        } catch (Exception $error) {
            return responseMsg(false, "ERROR!", $error->getMessage());
        }
    }


    /**
     * | ----------------- Get Property Type / Water ------------------------------- |
     * | @var propertyType 
     * | @return propertyType : return the master data of the property type for Water
     * | Operation : data fetched by table water_property_type_mstrs 
        | Serila No : 05
     */
    public function getPropertyType()
    {
        try {
            $propertyType = new WaterPropertyTypeMstr();        //<---------------- make object
            $propertyType = $propertyType->getAllPropertyType();
            return response()->json(['status' => true, 'message' => 'data of the propertyType', 'data' => $propertyType]);
        } catch (Exception $error) {
            return responseMsg(false, "ERROR!", $error->getMessage());
        }
    }


    /**
     * | -------------------------  Apply for the new Application for Water Application  --------------------- |
     * | @param req
     * | @var vacantLand
     * | @var workflowID
     * | @var ulbId
     * | @var ulbWorkflowObj : object for the model (WfWorkflow)
     * | @var ulbWorkflowId : calling the function on model:WfWorkflow 
     * | @var objCall : object for the model (WaterNewConnection)
     * | @var newConnectionCharges :
     * | Post the value in Water Application table
     * | post the value in Water Applicants table by loop
     * | 
     * | time : 
     * | rating : 5 
     * ------------------------------------------------------------------------------------
     * | Generating the demand amount for the applicant in Water Connection Charges Table 
        | Serila No : 06
     */
    public function store(Request $req)
    {
        try {
            # ref variables
            $vacantLand = Config::get('PropertyConstaint.VACANT_LAND');
            $workflowID = Config::get('workflow-constants.WATER_MASTER_ID');
            $ulbId = auth()->user()->ulb_id;

            # get initiater and finisher
            $ulbWorkflowObj = new WfWorkflow();
            $ulbWorkflowId = $ulbWorkflowObj->getulbWorkflowId($workflowID, $ulbId);
            $refInitiatorRoleId = $this->getInitiatorId($ulbWorkflowId->id);
            $refFinisherRoleId = $this->getFinisherId($ulbWorkflowId->id);
            $finisherRoleId = DB::select($refFinisherRoleId);
            $initiatorRoleId = DB::select($refInitiatorRoleId);

            # Generating Demand 
            $objCall = new WaterNewConnection();
            $newConnectionCharges = objToArray($objCall->calWaterConCharge($req));
            if (!$newConnectionCharges['status']) {
                throw new Exception($newConnectionCharges['errors']
                );
            }
            $installment = $newConnectionCharges['installment_amount'];
            $waterFeeId = $newConnectionCharges['water_fee_mstr_id'];

            # Generating Application No
            $now = Carbon::now();
            $applicationNo = 'APP' . $now->getTimeStamp();

            # check the property type on vacant land
            if ($req->connection_through != '3') {
                $checkResponse = $this->checkVacantLand($req, $vacantLand);
                if ($checkResponse) {
                    return $checkResponse;
                }
            }

            DB::beginTransaction();
            $objNewApplication = new WaterApplication();
            $applicationId = $objNewApplication->saveWaterApplication($req, $ulbWorkflowId, $initiatorRoleId, $finisherRoleId, $ulbId, $applicationNo, $waterFeeId);

            $owner = $req['owners'];
            foreach ($owner as $owners) {
                $objApplicant = new WaterApplicant();
                $objApplicant->saveWaterApplicant($applicationId, $owners);
            }

            if (!is_null($installment)) {
                foreach ($installment as $installments) {
                    $objQuaters = new WaterPenaltyInstallment();
                    $objQuaters->saveWaterPenelty($applicationId, $installments);
                }
            }

            $charges = new WaterConnectionCharge();
            $charges->saveWaterCharge($applicationId, $req, $newConnectionCharges);
            DB::commit();

            return responseMsg(true, "Successfully Saved!", $applicationNo);
        } catch (Exception $error) {
            DB::rollBack();
            return responseMsg(false, $error->getLine(), $error->getMessage());
        }
    }


    /**
     * |--------------------------------- Check property for the vacant land ------------------------------|
     * | @param req
     * | @param vacantLand
     * | @var readPropetySafCheck
     * | @var readpropetyHoldingCheck
     * | Operation : check if the applied application is in vacant land 
        | Serial No : 06.1
     */
    public function checkVacantLand($req, $vacantLand)
    {
        if ($req->saf_no) {
            $readPropetySafCheck = PropActiveSaf::select('prop_type_mstr_id')
                ->where('saf_no', $req->saf_no)
                ->get()
                ->first();
            if ($readPropetySafCheck->prop_type_mstr_id == $vacantLand) {
                return responseMsg(false, "water cannot be applied on Vacant land!", "");
            }
        }

        # check the property type by holding no  
        elseif ($req->holdingNo) {
            $readpropetyHoldingCheck = PropProperty::select('prop_type_mstr_id')
                ->where('new_holding_no', $req->holdingNo)
                ->get()
                ->first();
            if ($readpropetyHoldingCheck->prop_type_mstr_id == $vacantLand) {
                return responseMsg(false, "water cannot be applied on Vacant land!", "");
            }
        }
    }


    /**
     * |--------------------------------------------------------- water Workflow Functions Listed Below -------------------------------------------------------------------------------------------------------|
     */


    /**
     * |------------------------------------------ water Inbox -------------------------------------|
     * | @var auth
     * | @var userId
     * | @var ulbId
     * | @var occupiedWards 
     * | @var roleId
     * | @var waterList : using the function to fetch the list of the respective water application details according to (ulbId, roleId and ward) 
     * | @var wardId : using the Workflow trait's function (getWardUserId($userId)) for respective wardId.
     * | @var roles : using the Workflow trait's function (getRoleIdByUserId($userID)) for  respective roles.
     * | @return waterList : Details to be displayed in the inbox of the offices in water workflow. 
        | Serila No : 07
        | Working
     */
    public function waterInbox()
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

            $waterList = $this->getWaterApplicatioList($ulbId)
                ->whereIn('water_applications.current_role', $roleId)
                ->whereIn('a.ward_mstr_id', $occupiedWards)
                ->orderByDesc('water_applications.id')
                ->get();
            return responseMsgs(true, "Inbox List Details!", remove_null($waterList), '', '01', '', 'Post', '');
        } catch (Exception $error) {
            return responseMsg(false, $error->getMessage(), "");
        }
    }



    /**
     * |----------------------------------------- Water Outbox ------------------------------------------------|
     * | @var auth
     * | @var userId
     * | @var ulbId
     * | @var workflowRoles : using the function to fetch the list of the respective water application details according to (ulbId, roleId and ward) 
     * | @var wardId : using the Workflow trait's function (getWardUserId($userId)) for respective wardId.
     * | @var roles : using the Workflow trait's function (getRoleIdByUserId($userID)) for  respective roles.
     * | @return waterList : Details to be displayed in the inbox of the offices in water workflow. 
        | Serial No : 08
        | Working 
     */
    public function waterOutbox()
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

            $waterList = $this->getWaterApplicatioList($ulbId)
                ->whereNotIn('water_applications.current_role', $roleId)
                ->whereIn('a.ward_mstr_id', $occupiedWards)
                ->orderByDesc('water_applications.id')
                ->get();

            return responseMsgs(true, "Outbox List", remove_null($waterList), '', '01', '.ms', 'Post', '');
        } catch (Exception $error) {
            return responseMsg(false, $error->getMessage(), "");
        }
    }



    /**
     * |------------------------------------------- Water Application List -------------------------------|
     * | @param request
     * | @var 
        | Serial No : 09
        | Working
     */
    public function getWaterApplicatioList($ulbId)
    {
        return WaterApplication::
            // DB::table('prop_active_concessions')
            select(
                'water_applications.id',
                'water_applications.applicant_name as owner_name',
                'water_applications.holding_no',
                'a.ward_mstr_id',
                'u.ward_name as ward_no',
                'a.prop_type_mstr_id',
                'p.property_type',
                'water_applications.workflow_id',
                'water_applications.current_role as role_id'
            )
            ->leftJoin('prop_properties as a', 'a.id', '=', 'water_applications.property_id')
            ->join('ref_prop_types as p', 'p.id', '=', 'a.prop_type_mstr_id')
            ->join('ulb_ward_masters as u', 'u.id', '=', 'a.ward_mstr_id')
            ->where('water_applications.status', 1)
            ->where('water_applications.ulb_id', $ulbId);
    }


    /**
     * |------------------------------------------ Post Application to the next level ---------------------------------------|
     * | @param 
     * | @var 
        | Serial No :
        | Flag : change
     */
    public function postNextLevel($req)
    {
        try {
            DB::beginTransaction();
            // previous level pending verification enabling
            $preLevelPending = WaterLevelpending::where('water_application_id', $req->waterApplicationId)
                ->orderByDesc('id')
                ->limit(1)
                ->first();
            $preLevelPending->verification_status = '1';
            $preLevelPending->save();

            $levelPending = new WaterLevelpending();
            $levelPending->water_application_id = $req->waterApplicationId;
            $levelPending->sender_role_id = $req->senderRoleId;
            $levelPending->receiver_role_id = $req->receiverRoleId;
            $levelPending->sender_user_id = auth()->user()->id;
            $levelPending->save();

            // Water Application Update Current Role
            $concession = WaterApplication::find($req->concessionId);
            $concession->current_role = $req->receiverRoleId;
            $concession->save();

            // Add Comment On Prop Level Pending
            $receiverLevelPending = new WaterLevelpending();
            $commentOnlevel = $receiverLevelPending->getReceiverLevel($req->concessionId, $req->senderRoleId);
            $commentOnlevel->remarks = $req->comment;
            $commentOnlevel->receiver_role_id = auth()->user()->id;
            $commentOnlevel->forward_date = $this->_todayDate->format('Y-m-d');
            $commentOnlevel->forward_time = $this->_todayDate->format('H:i:m');
            $commentOnlevel->save();

            DB::commit();
            return responseMsgs(true, "Successfully Forwarded The Application!!", "", "", "", '01', '.ms', 'Post', '');
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }


    /**
     * |---------------------------------------------- Special Inbox -----------------------------------------|
     * | 
        | Serial No : 
        | Working
     */
    public function waterSpecialInbox()
    {
        try {
            $auth = auth()->user();
            $userId = $auth->id;
            $ulbId = $auth->ulb_id;
            $wardId = $this->getWardByUserId($userId);

            $occupiedWards = collect($wardId)->map(function ($ward) {                               // Get Occupied Ward of the User
                return $ward->ward_id;
            });

            $waterList = $this->getWaterApplicatioList($ulbId)                                         // Get Concessions
                ->where('water_applications.is_escalate', true)
                ->whereIn('a.ward_mstr_id', $occupiedWards)
                ->orderByDesc('water_applications.id')
                ->get();

            return responseMsg(true, "Inbox List", remove_null($waterList), "", "", '01', '.ms', 'Post', '');
        } catch (Exception $error) {
            return responseMsg(false, $error->getMessage(), "");
        }
    }



    /**
     * | ----------------- Document verification processs ------------------------------- |
     * | @param Req 
     * | @var userId
     * | @var docStatus
        | Serial No :
        | working
     */
    public function waterDocStatus($req)
    {
        try {
            $userId = auth()->user()->id;

            $docStatus = WaterApplicantDoc::find($req->documentId);
            $docStatus->remarks = $req->docRemarks;
            $docStatus->verified_by_emp_id = $userId;
            $docStatus->verified_on = Carbon::now();
            $docStatus->updated_at = Carbon::now();

            if ($req->docStatus == 'Verified') {                        //<------------ (here data type small int)        
                $docStatus->verify_status = 1;
            }
            if ($req->docStatus == 'Rejected') {                        //<------------ (here data type small int)
                $docStatus->verify_status = 2;
            }

            $docStatus->save();

            return responseMsg(true, "Successfully Done", '');
        } catch (Exception $error) {
            return responseMsg(false, "ERROR!", $error->getMessage());
        }
    }
}
