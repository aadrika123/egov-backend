<?php

namespace App\Repository\Property\Concrete;

use App\Models\Property\PropActiveHarvesting;
use App\Models\Property\PropHarvestingLevelpending;
use App\Models\Workflows\WfWorkflow;
use App\Repository\Property\Interfaces\iRainWaterHarvesting;
use App\Traits\Property\SAF;
use App\Traits\Ward;
use App\Traits\Workflow\Workflow;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

/**
 * | Created On - 22-11-2022
 * | Created By - Sam Kerketta
 * | Property RainWaterHarvesting apply
 */

class RainWaterHarvestingRepo implements iRainWaterHarvesting
{
    use SAF;
    use Workflow;
    use Ward;

    /**
     * |----------------------- getWardMasterData --------------------------
     * |  Query cost => 400-438 ms 
     * |@param request
     * |@var ulbId
     * |@var wardList
     * | Rating : 1
     */
    public function getWardMasterData($request)
    {
        try {
            $ulbId = auth()->user()->ulb_id;
            $wardList = $this->getAllWard($ulbId);
            return responseMsg(true, "List of wards", $wardList);
        } catch (Exception $error) {
            return responseMsg(false, "Error!", $error->getMessage());
        }
    }


    /**
     * |----------------------- postWaterHarvestingApplication 1 --------------------------
     * |  Query cost => 350 - 490 ms 
     * |@param request
     * |@var ulbId
     * |@var wardList
     * | Rating :2
     */
    public function waterHarvestingApplication($request)
    {
        try {
            $userId = auth()->user()->id;
            $ulbId = auth()->user()->ulb_id;
            $waterHaravesting = new PropActiveHarvesting();
            return  $this->waterApplicationSave($waterHaravesting, $request, $ulbId, $userId);
        } catch (Exception $error) {
            return responseMsg(false, "Error!", $error->getMessage());
        }
    }


    /**
     * |----------------------- function for the savindg the application details 1.1 --------------------------
     * |@param waterHaravesting
     * |@param request
     * |@param ulbId
     * |@param userId
     * |@var applicationNo
     * | Rating : 1
     */
    public function waterApplicationSave($waterHaravesting, $request, $ulbId, $userId)
    {
        try {
            $waterHaravesting->harvesting_status = $request->isWaterHarvestingBefore;
            $waterHaravesting->name  =  $request->name;
            $waterHaravesting->guardian_name  =  $request->guardianName;
            $waterHaravesting->ward_id  =  $request->wardNo;
            $waterHaravesting->mobile_no  =  $request->mobileNo;
            $waterHaravesting->holding_no  =  $request->holdingNo;
            $waterHaravesting->building_address  =  $request->buildingAddress;
            $waterHaravesting->date_of_completion  =  $request->dateOfCompletion;
            $waterHaravesting->user_id = $userId;
            $waterHaravesting->ulb_id = $ulbId;

            $applicationNo = $this->generateApplicationNo($ulbId, $userId);
            $waterHaravesting->application_no = $applicationNo;

            $ulbWorkflowId = WfWorkflow::where('wf_master_id', Config::get('workflow-constants.RAIN_WATER_HARVESTING_ID'))
                ->where('ulb_id', $ulbId)
                ->first();
            $refInitiatorRoleId = $this->getInitiatorId($ulbWorkflowId->id);                // Get Current Initiator ID
            $initiatorRoleId = DB::select($refInitiatorRoleId);

            $waterHaravesting->workflow_id = $ulbWorkflowId->id;
            $waterHaravesting->current_role = collect($initiatorRoleId)->first()->role_id;
            $waterHaravesting->save();

            return responseMsg(true, "Application applied!", $applicationNo);
        } catch (Exception $error) {
            return responseMsg(false, "Data not saved", $error->getMessage());
        }
    }

    /**
     * |----------------------- function for generating application no 1.1.1 --------------------------
     * |@param ulbId
     * |@param userId
     * |@var applicationId
     * | Rating : 0.1
     */
    public function generateApplicationNo($ulbId, $userId)
    {
        $applicationId = "RWH-" . $ulbId . "-" . $userId . "-" . rand(0, 99999999999999);
        return $applicationId;
    }


    /**
     * |----------------------- function for the Inbox  --------------------------
     * |@param ulbId
     * |@param userId
     * |@var applicationId
     * | Rating : 2
     */
    public function harvestingInbox()
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

            $harvesting = $this->getHarvestingList($ulbId)
                ->whereIn('prop_active_harvestings.current_role', $roleId)
                ->whereIn('a.ward_mstr_id', $occupiedWards)
                ->orderByDesc('prop_active_harvestings.id')
                ->get();
            return responseMsg(true, "Inbox List", remove_null($harvesting));
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }



    /**
     * |----------------------- function for the harvesting list according to ulb/user details --------------------------
     * | @param ulbId
     * | Rating : 2
     */
    public function getHarvestingList($ulbId)
    {
        return PropActiveHarvesting::select(
            'prop_active_harvestings.id',
            'prop_active_harvestings.name as owner_name',
            'a.ward_mstr_id',
            'u.ward_name as ward_no',
            'a.holding_no',
            'a.prop_type_mstr_id',
            'p.property_type',
            'prop_active_harvestings.workflow_id',
            'prop_active_harvestings.current_role as role_id'
        )
            ->leftJoin('prop_properties as a', 'a.holding_no', '=', 'prop_active_harvestings.holding_no')
            ->join('ref_prop_types as p', 'p.id', '=', 'a.prop_type_mstr_id')
            ->join('ulb_ward_masters as u', 'u.id', '=', 'a.ward_mstr_id')
            ->where('prop_active_harvestings.status', 1)
            ->where('prop_active_harvestings.ulb_id', $ulbId);
    }


    /**
     * |----------------------- function for the Outbox --------------------------
     * |@param ulbId
     * |@param userId
     * |@var applicationId
     * | Rating : 2
     */
    public function harvestingOutbox()
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

            $harvesting = $this->getHarvestingList($ulbId)
                ->whereNotIn('prop_active_harvestings.current_role', $roleId)
                ->whereIn('a.ward_mstr_id', $occupiedWards)
                ->orderByDesc('prop_active_harvestings.id')
                ->get();

            return responseMsg(true, "Outbox List", remove_null($harvesting));
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }


    /**
     * |----------------------- function for the escalate Application for harvesting --------------------------
     * |@param ulbId
     * |@param userId
     * |@var applicationId
     * | Rating : 2
     */
    public function escalateApplication($req)
    {
        try {
            $userId = auth()->user()->id;
            if ($req->escalateStatus == 1) {
                $harvesting = PropActiveHarvesting::find($req->id);
                $harvesting->is_escalate = 1;
                $harvesting->escalated_by = $userId;
                $harvesting->save();
                return responseMsg(true, "Successfully Escalated the application", "");
            }
            if ($req->escalateStatus == 0) {
                $harvesting = PropActiveHarvesting::find($req->id);
                $harvesting->is_escalate = 0;
                $harvesting->escalated_by = null;
                $harvesting->save();
                return responseMsg(true, "Successfully De-Escalated the application", "");
            }
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }


    /**
     * |----------------------- function for the Special Inbox (Escalated Applications) for harvesting --------------------------
     * |@param ulbId
     * | Rating : 2
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

            $harvesting = $this->getHarvestingList($ulbId)                                         // Get harvesting
                ->where('prop_active_harvestings.is_escalate', true)
                ->whereIn('a.ward_mstr_id', $occupiedWards)
                ->orderByDesc('prop_active_harvestings.id')
                ->get();

            return responseMsg(true, "Inbox List", remove_null($harvesting));
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }


    /**
     * |------------------------------------- Post Next Level Application(forward or backward application) ------------------------------------------------|
     * | Rating-
     */
    public function postNextLevel($req)
    {
        try {
            DB::beginTransaction();

            // previous level pending verification enabling
            $preLevelPending = PropHarvestingLevelpending::where('harvesting_id', $req->harvestingId)
                ->orderByDesc('id')
                ->limit(1)
                ->first();
            $preLevelPending->verification_status = '1';
            $preLevelPending->save();

            $levelPending = new PropHarvestingLevelpending();
            $levelPending->harvesting_id = $req->harvestingId;
            $levelPending->sender_role_id = $req->senderRoleId;
            $levelPending->receiver_role_id = $req->receiverRoleId;
            $levelPending->sender_user_id = auth()->user()->id;
            $levelPending->save();

            // harvesting Application Update Current Role Updation
            $harvesting = PropActiveHarvesting::find($req->harvestingId);
            $harvesting->current_role = $req->receiverRoleId;
            $harvesting->save();

            // Add Comment On Prop Level Pending
            $receiverLevelPending = new PropHarvestingLevelpending();
            $commentOnlevel = $receiverLevelPending->getReceiverLevel($req->harvestingId, $req->senderRoleId);
            $commentOnlevel->remarks = $req->comment;
            $commentOnlevel->forward_date = $this->_todayDate->format('Y-m-d');
            $commentOnlevel->forward_time = $this->_todayDate->format('H:i:m');
            $commentOnlevel->save();

            DB::commit();
            return responseMsg(true, "Successfully Forwarded The Application!!", "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }


    /**
     * |-------------------------------------  Rejection of the Harvesting ------------------------------------------------|
     * | Rating-
     */
    public function rejectionOfHarvesting($req)
    {
        try {
            $activeHarvesting = PropActiveHarvesting::query()
                ->where('id', $req->harvestingId)
                ->first();

            $rejectedHarvesting = $activeHarvesting->replicate();
            $rejectedHarvesting->setTable('prop_rejected_harvestings');
            $rejectedHarvesting->id = $activeHarvesting->id;
            $rejectedHarvesting->save();
            $activeHarvesting->delete();

            return responseMsg(true, "Application Successfully Rejected !!", "");
        } catch (Exception $error) {
            return responseMsg(false, "ERROR!", $error->getMessage());
        }
    }


    /**
     * |------------------------------------- Fina Approval and Rejection of the Application ------------------------------------------------|
     * | Rating-
     */
    public function finalApprovalRejection($req)
    {
        try {
            // Check if the Current User is Finisher or Not
            $getFinisherQuery = $this->getFinisherId($req->workflowId);                                 // Get Finisher using Trait
            $refGetFinisher = collect(DB::select($getFinisherQuery))->first();
            if ($refGetFinisher->role_id != $req->roleId) {
                return responseMsg(false, " Access Forbidden", "");
            }

            DB::beginTransaction();
            // Approval
            if ($req->status == 1) {
                // Harvesting Application replication
                $activeHarvesting = PropActiveHarvesting::query()
                    ->where('id', $req->harvestingId)
                    ->first();

                $approvedHarvesting = $activeHarvesting->replicate();
                $approvedHarvesting->setTable('prop_harvestings');
                $approvedHarvesting->id = $activeHarvesting->id;
                $approvedHarvesting->save();
                $activeHarvesting->delete();

                $msg = "Application Successfully Approved !!";
            }
            // Rejection
            if ($req->status == 0) {
                // Harvesting Application replication
                $activeHarvesting = PropActiveHarvesting::query()
                    ->where('id', $req->harvestingId)
                    ->first();

                $rejectedHarvesting = $activeHarvesting->replicate();
                $rejectedHarvesting->setTable('prop_rejected_harvestings');
                $rejectedHarvesting->id = $activeHarvesting->id;
                $rejectedHarvesting->save();
                $activeHarvesting->delete();
                $msg = "Application Successfully Rejected !!";
            }
            DB::commit();
            return responseMsg(true, $msg, "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }
}
