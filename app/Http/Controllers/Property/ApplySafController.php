<?php

namespace App\Http\Controllers\Property;

use App\EloquentClass\Property\InsertTax;
use App\EloquentClass\Property\SafCalculation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Property\reqApplySaf;
use App\Models\Property\PropActiveSaf;
use App\Models\Property\PropActiveSafsFloor;
use App\Models\Property\PropActiveSafsOwner;
use App\Models\Property\PropProperty;
use App\Models\Workflows\WfWorkflow;
use App\Traits\Property\SAF;
use App\Traits\Workflow\Workflow;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class ApplySafController extends Controller
{
    use SAF;
    use Workflow;

    protected $_workflowIds;
    protected $_todayDate;
    protected $_REQUEST;
    public function __construct()
    {
        $this->_workflowIds = Config::get('PropertyConstaint.SAF_WORKFLOWS');
        $this->_todayDate = Carbon::now();
    }
    /**
     * | Created On-17-02-2022 
     * | Created By-Anshu Kumar
     * | --------------------------- Workflow Parameters ---------------------------------------
     * |                                 # SAF New Assessment
     * | wf_master id=4 
     * | wf_workflow_id=4
     * |                                 # SAF Reassessment 
     * | wf_mstr_id=5
     * | wf_workflow_id=3
     * |                                 # SAF Mutation
     * | wf_mstr_id=9
     * | wf_workflow_id=5
     * |                                 # SAF Bifurcation
     * | wf_mstr_id=25
     * | wf_workflow_id=182
     * |                                 # SAF Amalgamation
     * | wf_mstr_id=373
     * | wf_workflow_id=381
     * | Created For- Apply for New Assessment, Reassessment, Mutation, Bifurcation and Amalgamation
     * | Status-Open
     */
    /**
     * | Apply for New Application(2)
     * | Status-Closed
     * | Query Costing-500 ms
     * | Rating-5
     */
    public function applySaf(reqApplySaf $request)
    {
        try {
            // Variable Assignments
            $mApplyDate = Carbon::now()->format("Y-m-d");
            $user_id = auth()->user()->id;
            $ulb_id = $request->ulbId ?? auth()->user()->ulb_id;
            $userType = auth()->user()->user_type;
            $demand = array();
            $metaReqs = array();
            $saf = new PropActiveSaf();
            $safCalculation = new SafCalculation();
            $tax = new InsertTax();

            // Derivative Assignments
            $ulbWorkflowId = $this->readAssessUlbWfId($request, $ulb_id);           // (2.1)
            $roadWidthType = $this->readRoadWidthType($request->roadType);          // Read Road Width Type

            $request->request->add(['road_type_mstr_id' => $roadWidthType]);
            $safTaxes = $safCalculation->calculateTax($request);

            $refInitiatorRoleId = $this->getInitiatorId($ulbWorkflowId->id);                                // Get Current Initiator ID
            $initiatorRoleId = DB::select($refInitiatorRoleId);

            $refFinisherRoleId = $this->getFinisherId($ulbWorkflowId->id);
            $finisherRoleId = DB::select($refFinisherRoleId);

            $metaReqs['roadWidthType'] = $roadWidthType;
            $metaReqs['workflowId'] = $ulbWorkflowId->id;
            $metaReqs['ulbId'] = $ulb_id;
            $metaReqs['userId'] = $user_id;
            $metaReqs['initiatorRoleId'] = collect($initiatorRoleId)->first()->role_id;
            if ($userType == 'Citizen') {
                $metaReqs['initiatorRoleId'] = collect($initiatorRoleId)->first()->forward_role_id;         // Send to DA in Case of Citizen
                $metaReqs['userId'] = null;
                $metaReqs['citizenId'] = $user_id;
            }
            $metaReqs['finisherRoleId'] = collect($finisherRoleId)->first()->role_id;

            $request->merge($metaReqs);
            $this->_REQUEST = $request;
            $this->mergeAssessedExtraFields();                                          // Merge Extra Fields for Property Reassessment,Mutation,Bifurcation & Amalgamation(2.2)
            DB::beginTransaction();
            $createSaf = $saf->store($request);                                         // Store SAF Using Model function 
            $safId = $createSaf->original['safId'];
            $safNo = $createSaf->original['safNo'];

            // SAF Owner Details
            if ($request['owner']) {
                $owner_detail = $request['owner'];
                foreach ($owner_detail as $owner_details) {
                    $owner = new PropActiveSafsOwner();
                    $owner->addOwner($owner_details, $safId, $user_id);
                }
            }

            // Floor Details
            if ($request['floor']) {
                $floor_detail = $request['floor'];
                foreach ($floor_detail as $floor_details) {
                    $floor = new PropActiveSafsFloor();
                    $floor->addfloor($floor_details, $safId, $user_id);
                }
            }

            // Insert Tax
            $demand['amounts'] = $safTaxes->original['data']['demand'];
            $demand['details'] = $this->generateSafDemand($safTaxes->original['data']['details']);

            $detailsByRulesets = collect($safTaxes->original['data']['details'])->groupBy('ruleSet');
            $demandResponse['amounts'] = $safTaxes->original['data']['demand'];
            $demandResponse['details'] = $detailsByRulesets;
            $tax->insertTax($safId, $ulb_id, $safTaxes);                                               // Insert SAF Tax

            DB::commit();
            return responseMsgs(true, "Successfully Submitted Your Application Your SAF No. $safNo", [
                "safNo" => $safNo,
                "applyDate" => $mApplyDate,
                "safId" => $safId,
                "demand" => $demandResponse
            ], "010102", "1.0", "1s", "POST", $request->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "010102", "1.0", "1s", "POST", $request->deviceId);
        }
    }

    /**
     * | Read Assessment Type and Ulb Workflow Id(2.1)
     */
    public function readAssessUlbWfId($request, $ulb_id)
    {
        if ($request->assessmentType == 1) {                                                    // New Assessment 
            $workflow_id = Config::get('workflow-constants.SAF_WORKFLOW_ID');
            $request->assessmentType = Config::get('PropertyConstaint.ASSESSMENT-TYPE.1');
        }

        if ($request->assessmentType == 2) {                                                    // Reassessment
            $workflow_id = Config::get('workflow-constants.SAF_REASSESSMENT_ID');
            $request->assessmentType = Config::get('PropertyConstaint.ASSESSMENT-TYPE.2');
        }

        if ($request->assessmentType == 3) {                                                    // Mutation
            $workflow_id = Config::get('workflow-constants.SAF_MUTATION_ID');
            $request->assessmentType = Config::get('PropertyConstaint.ASSESSMENT-TYPE.3');
        }

        if ($request->assessmentType == 4) {                                                    // Bifurcation
            $workflow_id = Config::get('workflow-constants.SAF_BIFURCATION_ID');
            $request->assessmentType = Config::get('PropertyConstaint.ASSESSMENT-TYPE.4');
        }

        if ($request->assessmentType == 5) {                                                    // Amalgamation
            $workflow_id = Config::get('workflow-constants.SAF_AMALGAMATION_ID');
            $request->assessmentType = Config::get('PropertyConstaint.ASSESSMENT-TYPE.5');
        }

        return WfWorkflow::where('wf_master_id', $workflow_id)
            ->where('ulb_id', $ulb_id)
            ->first();
    }

    /**
     * | Merge Extra Fields in request for Reassessment,Mutation,Etc
     */
    public function mergeAssessedExtraFields()
    {
        $mPropProperty = new PropProperty();
        $req = $this->_REQUEST;
        $assessmentType = $req->assessmentType;

        if (in_array($assessmentType, ["Re Assessment", "Mutation", "Bifurcation"])) {
            $propertyDtls = $mPropProperty->getPropertyId($req->holdingNo);

            if (collect($propertyDtls)->isEmpty())
                throw new Exception("Property Not Found For This Holding");

            $propId = $propertyDtls->id;
            $req->merge([
                'hasPreviousHoldingNo' => true,
                'previousHoldingId' => $propId
            ]);
            switch ($assessmentType) {
                case "Re Assessment":                                 // Bifurcation
                    $req->merge([
                        'propDtl' => $propId
                    ]);
                    break;
            }
        }

        if (in_array($assessmentType, ["Amalgamation"])) {
            $previousHoldingIds = array();
            $previousHoldingLists = array();

            foreach ($req->holdingNoLists as $holdingNoList) {
                $propDtls = $mPropProperty->getPropertyId($holdingNoList);
                if (!$propDtls)
                    throw new Exception("Property Not Found For the holding");
                $propId = $propDtls->id;
                array_push($previousHoldingIds, $propId);
                array_push($previousHoldingLists, $holdingNoList);
            }

            $req->merge([
                'hasPreviousHoldingNo' => true,
                'previousHoldingId' => implode(",", $previousHoldingIds),
                'holdingNo' => implode(",", $req->holdingNoLists)
            ]);
        }
    }
}
