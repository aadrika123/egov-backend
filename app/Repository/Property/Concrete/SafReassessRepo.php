<?php

namespace App\Repository\Property\Concrete;

use App\EloquentClass\Property\InsertTax;
use App\EloquentClass\Property\SafCalculation;
use App\Models\Property\ActiveSaf;
use App\Models\Property\ActiveSafsFloorDtls;
use App\Models\Property\ActiveSafsOwnerDtl;
use App\Models\Property\PropLevelPending;
use App\Models\Workflows\WfWorkflow;
use App\Repository\Property\Interfaces\iSafReassessRepo;
use App\Traits\Property\SAF;
use App\Traits\Workflow\Workflow;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

/**
 * | Created On - 17-11-2022
 * | Created By - Anshu Kumar
 * | Property SAF Reassessment Repository
 */

class SafReassessRepo implements iSafReassessRepo
{
    use SAF;
    use Workflow;
    /**
     * | Apply for Reassessment
     */
    public function applyReassessment($req)
    {
        $user_id = auth()->user()->id;
        $ulb_id = auth()->user()->ulb_id;

        try {
            $workflow_id = Config::get('workflow-constants.SAF_REASSESSMENT_ID');
            $ulbWorkflowId = WfWorkflow::where('wf_master_id', $workflow_id)
                ->where('ulb_id', $ulb_id)
                ->first();

            if ($req->roadType <= 0)
                $req->roadType = 4;
            elseif ($req->roadType > 0 && $req->roadType < 20)
                $req->roadType = 3;
            elseif ($req->roadType >= 20 && $req->roadType <= 39)
                $req->roadType = 2;
            elseif ($req->roadType > 40)
                $req->roadType = 1;

            $safCalculation = new SafCalculation();
            $safTaxes = $safCalculation->calculateTax($req);

            DB::beginTransaction();
            $assessmentTypeId = Config::get("PropertyConstaint.ASSESSMENT-TYPE.ReAssessment");

            $safNo = $this->safNo($req->ward, $assessmentTypeId, $ulb_id);
            $saf = new ActiveSaf();
            $this->tApplySaf($saf, $req, $safNo, $assessmentTypeId);                        // Trait SAF Apply
            // workflows
            $saf->user_id = $user_id;
            $saf->workflow_id = $ulbWorkflowId->id;
            $saf->ulb_id = $ulb_id;
            $refInitiatorRoleId = $this->getInitiatorId($ulbWorkflowId->id);                // Get Current Initiator ID
            $initiatorRoleId = DB::select($refInitiatorRoleId);
            $saf->current_role = $initiatorRoleId[0]->role_id;
            $saf->save();

            // SAF Owner Details
            if ($req['owner']) {
                $owner_detail = $req['owner'];
                foreach ($owner_detail as $owner_details) {
                    $owner = new ActiveSafsOwnerDtl();
                    $this->tApplySafOwner($owner, $saf, $owner_details);                    // Trait Owner Details
                    $owner->save();
                }
            }

            // Floor Details
            if ($req['floor']) {
                $floor_detail = $req['floor'];
                foreach ($floor_detail as $floor_details) {
                    $floor = new ActiveSafsFloorDtls();
                    $this->tApplySafFloor($floor, $saf, $floor_details);
                    $floor->save();
                }
            }

            // Property SAF Label Pendings
            $refSenderRoleId = $this->getInitiatorId($ulbWorkflowId->id);
            $SenderRoleId = DB::select($refSenderRoleId);
            $labelPending = new PropLevelPending();
            $labelPending->saf_id = $saf->id;
            $labelPending->receiver_role_id = $SenderRoleId[0]->role_id;
            $labelPending->save();

            // Insert Tax
            $tax = new InsertTax();
            $tax->insertTax($saf->id, $user_id, $safTaxes);                                         // Insert SAF Tax

            DB::commit();
            return responseMsg(true, "Successfully Submitted Your Re Assessment Application Your SAF No. $safNo", ["safNo" => $safNo]);
        } catch (Exception $e) {
            DB::rollBack();
            return $e;
        }
    }
}
