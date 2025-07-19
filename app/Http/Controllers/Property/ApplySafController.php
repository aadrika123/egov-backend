<?php

namespace App\Http\Controllers\Property;

use App\BLL\Property\CalculateSafById;
use App\BLL\Property\GenerateSafApplyDemandResponse;
use App\BLL\Property\PostSafPropTaxes;
use App\EloquentClass\Property\InsertTax;
use App\EloquentClass\Property\PenaltyRebateCalculation;
use App\EloquentClass\Property\SafCalculation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Property\reqApplySaf;
use App\Http\Requests\Property\ReqPayment;
use App\Http\Requests\Property\ReqSiteVerification;
use App\Http\Requests\ReqGBSaf;
use App\Models\Property\Logs\SafAmalgamatePropLog;
use App\Models\Property\PropActiveGbOfficer;
use App\Models\Property\PropActiveSaf;
use App\Models\Property\PropActiveSafsFloor;
use App\Models\Property\PropActiveSafsOwner;
use App\Models\Property\PropDemand;
use App\Models\Property\PropFloor;
use App\Models\Property\PropProperty;
use App\Models\Property\PropSafsDemand;
use App\Models\Workflows\WfRoleusermap;
use App\Models\Workflows\WfWorkflow;
use App\Models\WorkflowTrack;
use App\Repository\Auth\EloquentAuthRepository;
use App\Traits\Property\SAF;
use App\Traits\Workflow\Workflow;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

/**
 * | Created On-16-03-2023 
 * | Created By-Anshu Kumar
 * | Created For
 *      - Apply Saf 
 *      - Apply GB Saf
 * | Status-Closed
 */

class ApplySafController extends Controller
{
    use SAF;
    use Workflow;

    protected $_todayDate;
    protected $_REQUEST;
    protected $_safDemand;
    public $_generatedDemand;
    protected $_propProperty;
    public $_holdingNo;
    protected $_citizenUserType;
    protected $_currentFYear;
    protected $_penaltyRebateCalc;
    protected $_currentQuarter;
    private $_demandAdjustAssessmentTypes;

    public function __construct()
    {
        $this->_todayDate = Carbon::now();
        $this->_safDemand = new PropSafsDemand();
        $this->_propProperty = new PropProperty();
        $this->_citizenUserType = Config::get('workflow-constants.USER_TYPES.1');
        $this->_currentFYear = getFY();
        $this->_penaltyRebateCalc = new PenaltyRebateCalculation;
        $this->_currentQuarter = calculateQtr($this->_todayDate->format('Y-m-d'));
        $this->_demandAdjustAssessmentTypes = Config::get('PropertyConstaint.REASSESSMENT_TYPES');
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
     * | Main Function A1
     */
    public function applySaf(reqApplySaf $request)
    {
        try {
            // Variable Assignments
            $mApplyDate = Carbon::now()->format("Y-m-d");
            $user = authUser($request);
            $user_id = $user->id;
            $ulb_id = $request->ulbId ?? $user->ulb_id;
            $userType = $user->user_type ?? 'Citizen'; // Default to Citizen if not set
            $metaReqs = array();
            $saf = new PropActiveSaf();


            $mOwner = new PropActiveSafsOwner();
            $safCalculation = new SafCalculation();
            $calculateSafById = new CalculateSafById;
            $generateSafApplyDemandResponse = new GenerateSafApplyDemandResponse;
            // Derivative Assignments
            $ulbWorkflowId = $this->readAssessUlbWfId($request, $ulb_id);
            $roadWidthType = $this->readRoadWidthType($request->roadType);          // Read Road Width Type

            $request->request->add(['road_type_mstr_id' => $roadWidthType]);

            $refInitiatorRoleId = $this->getInitiatorId($ulbWorkflowId->id);                                // Get Current Initiator ID
            $initiatorRoleId = collect(DB::select($refInitiatorRoleId))->first();
            if (is_null($initiatorRoleId))
                throw new Exception("Initiator Role Not Available");
            $refFinisherRoleId = $this->getFinisherId($ulbWorkflowId->id);
            $finisherRoleId = collect(DB::select($refFinisherRoleId))->first();
            if (is_null($finisherRoleId))
                throw new Exception("Finisher Role Not Available");

            $metaReqs['roadWidthType'] = $roadWidthType;
            $metaReqs['workflowId'] = $ulbWorkflowId->id;
            $metaReqs['ulbId'] = $ulb_id;
            $metaReqs['userId'] = $user_id;
            $metaReqs['initiatorRoleId'] = collect($initiatorRoleId)['role_id'];
            if ($userType == $this->_citizenUserType) {
                $metaReqs['initiatorRoleId'] = collect($initiatorRoleId)['forward_role_id'];         // Send to DA in Case of Citizen
                $metaReqs['userId'] = null;
                $metaReqs['citizenId'] = $user_id;
            } elseif ($userType == 'TC') {
                // TC: Retrieve TC role from workflow and set as initiator
                $wfRole = new WfRoleusermap();

                $request->merge([
                    'userId' => $user_id,
                    'workflowId' => $ulbWorkflowId->id
                ]);

                $getRole = $wfRole->getRoleByUserWfId($request);

                if (empty($getRole))
                    throw new Exception("Workflow role mapping not found for this TC user.");

                $metaReqs['initiatorRoleId'] = $getRole->wf_role_id; // ✅ Use TC role as initiator
                $metaReqs['tcId'] = $user_id;                     // Optional: track TC ID
            }
            $metaReqs['finisherRoleId'] = collect($finisherRoleId)['role_id'];
            $safTaxes = $safCalculation->calculateTax($request);

            if ($safTaxes->original['status'] == false)
                throw new Exception($safTaxes->original['message']);


            $metaReqs['isTrust'] = $this->isPropTrust($request['floor']);
            $metaReqs['holdingType'] = $this->holdingType($request['floor']);
            $request->merge($metaReqs);
            $this->_REQUEST = $request;
            $this->mergeAssessedExtraFields();                                          // Merge Extra Fields for Property Reassessment,Mutation,Bifurcation & Amalgamation(2.2)
            // Generate Calculation
            $calculateSafById->_calculatedDemand = $safTaxes->original['data'];
            $calculateSafById->_safDetails['assessment_type'] = $request->assessmentType;
            $calculateSafById->_safDetails['previous_holding_id'] = $request->previousHoldingId;

            if (isset($request->holdingNo))
                $calculateSafById->_holdingNo = $request->holdingNo;
            $calculateSafById->_currentQuarter = calculateQtr($mApplyDate);
            $firstOwner = collect($request['owner'])->first();
            $calculateSafById->_firstOwner = [
                'gender' => $firstOwner['gender'],
                'dob' => $firstOwner['dob'],
                'is_armed_force' => $firstOwner['isArmedForce'],
                'is_specially_abled' => $firstOwner['isSpeciallyAbled'],
            ];
            $demandResponse = null;
            $calculateSafById->generateSafDemand();

            $generatedDemand = $calculateSafById->_generatedDemand;
            $isResidential = $safTaxes->original['data']['demand']['isResidential'];
            $demandResponse = $generateSafApplyDemandResponse->generateResponse($generatedDemand, $isResidential);

            DB::beginTransaction();
            DB::connection('pgsql_master')->beginTransaction();

            if ($request->assessmentType == 'Bifurcation' || $request->assessmentType == 'Amalgamation') { // Bifurcation and Amalgamation
                $request->merge(['paymentStatus' => '1']);
            } else {
                $request->merge(['paymentStatus' => '0']);
            }
            $createSaf = $saf->store($request, $userType);                                         // Store SAF Using Model function 
            if ($request->assessmentType == 5 || $request->assessmentType == "Amalgamation") {
                $request->merge(["safId" => $createSaf->original['safId']]);
                $SafAmalgamatePropLog = new SafAmalgamatePropLog();
                $SafAmalgamatePropLog->store($request);
            }
            $safId = $createSaf->original['safId'];
            $safNo = $createSaf->original['safNo'];

            // SAF Owner Details
            if ($request['owner']) {
                $ownerDetail = $request['owner'];
                if ($request->assessmentType == 'Mutation')                             // In Case of Mutation Avert Existing Owner Detail
                    $ownerDetail = collect($ownerDetail)->where('propOwnerDetailId', null);
                foreach ($ownerDetail as $ownerDetails) {
                    $mOwner->addOwner($ownerDetails, $safId, $user_id);
                }
            }

            // Floor Details
            if ($request->propertyType != 4 && !empty($request['floor'])) {
                $floorDetail = $request['floor'];
                if ($request['floor']) {
                    $floorDetail = $request['floor'];
                    $this->checkBifurcationFloorCondition($floorDetail);
                    foreach ($floorDetail as $floorDetails) {
                        $floor = new PropActiveSafsFloor();
                        $floorId = $floor->addfloor($floorDetails, $safId, $user_id, $request->assessmentType, $request['biDateOfPurchase']);
                        // Add the new floorId to the floor data
                        $floorDetails['floorId'] = $floorId;

                        // Store updated floor record
                        $updatedFloorData[] = $floorDetails;
                    }
                }
            }
            if ($userType == 'TC') {
                $activeSafController = app()->make(ActiveSafController::class);

                // Build the payload array
                $verificationPayload = [
                    'safId' => $safId,
                    'propertyType' => $request->propertyType,
                    'roadWidth' => $request->roadType,
                    'areaOfPlot' => $request->areaOfPlot ?? 0,
                    'wardId' => $request->ward ?? 1,

                    'isMobileTower' => (bool) $request->isMobileTower,
                    'mobileTower' => [
                        'area' => $request->mobileTower['area'] ?? null,
                        'dateFrom' => $request->mobileTower['dateFrom'] ?? null,
                    ],

                    'isHoardingBoard' => (bool) $request->isHoardingBoard,
                    'hoardingBoard' => [
                        'area' => $request->hoardingBoard['area'] ?? null,
                        'dateFrom' => $request->hoardingBoard['dateFrom'] ?? null,
                    ],

                    'isPetrolPump' => (bool) $request->isPetrolPump,
                    'petrolPump' => [
                        'area' => $request->petrolPump['area'] ?? null,
                        'dateFrom' => $request->petrolPump['dateFrom'] ?? null,
                    ],

                    'isWaterHarvesting' => (bool) $request->isWaterHarvesting,
                    'rwhDateFrom' => $request->rwhDateFrom ?? null,
                    'deviceId' => $request->deviceId ?? null,
                ];

                // Add floor details if propertyType is not 4
                if ($request->propertyType != 4) {
                    $verificationPayload['floor'] = $updatedFloorData ?? [];
                }
                // ✅ Merge deeply so verificationPayload overrides $request values
                // Final payload: only 'auth' + verification data
                $finalPayload = [
                    'auth' => $request->auth,
                ] + $verificationPayload;

                // Create new internal request
                $verReq = ReqSiteVerification::create(
                    '/site-verification',
                    'POST',
                    $finalPayload
                );

                // Call the siteVerification method
                $siteVerifyResponse = $activeSafController->siteVerification($verReq);
            }


            // Citizen Notification
            if ($userType == 'Citizen') {
                $mreq['userType']  = 'Citizen';
                $mreq['citizenId'] = $user_id;
                $mreq['category']  = 'Recent Application';
                $mreq['ulbId']     = $ulb_id;
                $mreq['ephameral'] = 0;
                $mreq['notification'] = "Successfully Submitted Your Application Your SAF No. $safNo";
                $rEloquentAuthRepository = new EloquentAuthRepository();
                $rEloquentAuthRepository->addNotification($mreq);
            }
            // return $demandResponse;
            // dd();
            DB::commit();
            DB::connection('pgsql_master')->commit();
            return responseMsgs(true, "Successfully Submitted Your Application Your SAF No. $safNo", [
                "safNo" => $safNo,
                "applyDate" => ymdToDmyDate($mApplyDate),
                "safId" => $safId,
                "demand" => $demandResponse
            ], "010101", "1.0", "1s", "POST", $request->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            DB::connection('pgsql_master')->rollBack();
            return responseMsgs(false, $e->getMessage(), $e->getFile(), "010101", "1.0", responseTime(), "POST", $request->deviceId);
        }
    }
    public function applySafv1(reqApplySaf $request)
    {
        try {
            // ------------------------------------------------------
            // ✅ Initialization
            // ------------------------------------------------------


            $mApplyDate = Carbon::now()->format("Y-m-d");

            $user = authUser($request); // 🔧 Ensure user is properly fetched
            $user_id = $user->id ?? 203;
            $ulb_id = $request->ulbId ?? $user->ulb_id ?? 2;
            $userType = $user->user_type ?? 'Citizen'; // Default to Citizen

            // Model Instances
            $saf = new PropActiveSaf();
            $mOwner = new PropActiveSafsOwner();
            $safCalculation = new SafCalculation();
            $calculateSafById = new CalculateSafById();
            $generateSafApplyDemandResponse = new GenerateSafApplyDemandResponse();

            // ------------------------------------------------------
            // ✅ Workflow IDs and Role Mapping
            // ------------------------------------------------------
            $ulbWorkflowId = $this->readAssessUlbWfId($request, $ulb_id);
            $roadWidthType = $this->readRoadWidthType($request->roadType);
            $request->request->add(['road_type_mstr_id' => $roadWidthType]);

            $refInitiatorRoleId = $this->getInitiatorId($ulbWorkflowId->id);
            $initiatorRoleId = collect(DB::select($refInitiatorRoleId))->first();
            if (is_null($initiatorRoleId)) throw new Exception("Initiator Role Not Available");

            $refFinisherRoleId = $this->getFinisherId($ulbWorkflowId->id);
            $finisherRoleId = collect(DB::select($refFinisherRoleId))->first();
            if (is_null($finisherRoleId)) throw new Exception("Finisher Role Not Available");

            // ------------------------------------------------------
            // ✅ Meta Request Preparation
            // ------------------------------------------------------
            $metaReqs = [
                'roadWidthType'   => $roadWidthType,
                'workflowId'      => $ulbWorkflowId->id,
                'ulbId'           => $ulb_id,
                'userId'          => $user_id,
                'initiatorRoleId' => collect($initiatorRoleId)['role_id'],
                'finisherRoleId'  => collect($finisherRoleId)['role_id'],
            ];

            if ($userType == $this->_citizenUserType) {
                $metaReqs['initiatorRoleId'] = collect($initiatorRoleId)['forward_role_id'];
                $metaReqs['userId'] = null;
                $metaReqs['citizenId'] = $user_id;
            } elseif ($userType == 'TC') {
                $wfRole = new WfRoleusermap();
                $request->merge([
                    'userId' => $user_id,
                    'workflowId' => $ulbWorkflowId->id
                ]);
                $getRole = $wfRole->getRoleByUserWfId($request);
                if (empty($getRole)) throw new Exception("Workflow role mapping not found for this TC user.");

                $metaReqs['initiatorRoleId'] = $getRole->wf_role_id;
                $metaReqs['tcId'] = $user_id;
            }

            // ------------------------------------------------------
            // ✅ SAF Tax Calculation
            // ------------------------------------------------------
            $safTaxes = $safCalculation->calculateTax($request);
            if ($safTaxes->original['status'] == false)
                throw new Exception($safTaxes->original['message']);

            // ------------------------------------------------------
            // ✅ Add Extra Meta Fields
            // ------------------------------------------------------
            $metaReqs['isTrust'] = $this->isPropTrust($request['floor']);
            $metaReqs['holdingType'] = $this->holdingType($request['floor']);
            $request->merge($metaReqs);
            $this->_REQUEST = $request;

            $this->mergeAssessedExtraFields();

            // ------------------------------------------------------
            // ✅ Demand Calculation
            // ------------------------------------------------------
            $calculateSafById->_calculatedDemand = $safTaxes->original['data'];
            $calculateSafById->_safDetails['assessment_type'] = $request->assessmentType;
            $calculateSafById->_safDetails['previous_holding_id'] = $request->previousHoldingId;

            if (isset($request->holdingNo))
                $calculateSafById->_holdingNo = $request->holdingNo;

            $calculateSafById->_currentQuarter = calculateQtr($mApplyDate);

            $firstOwner = collect($request['owner'])->first();
            $calculateSafById->_firstOwner = [
                'gender'             => $firstOwner['gender'],
                'dob'                => $firstOwner['dob'],
                'is_armed_force'     => $firstOwner['isArmedForce'],
                'is_specially_abled' => $firstOwner['isSpeciallyAbled'],
            ];

            $calculateSafById->generateSafDemand();

            $generatedDemand = $calculateSafById->_generatedDemand;
            $isResidential = $safTaxes->original['data']['demand']['isResidential'];

            // ✅ Move this AFTER all logic
            $demandResponse = $generateSafApplyDemandResponse->generateResponse($generatedDemand, $isResidential);

            // ------------------------------------------------------
            // ✅ SAF Save
            // ------------------------------------------------------
            DB::beginTransaction();
            DB::connection('pgsql_master')->beginTransaction();
            // $request->merge(['paymentStatus' => ($request->assessmentType == 'Bifurcation' || $request->assessmentType == 'Amalgamation') ? '1' : '0']);

            $createSaf = $saf->store($request, $userType);
            $safId = $createSaf->original['safId'];
            $safNo = $createSaf->original['safNo'];

            // ✅ Amalgamation Log
            if ($request->assessmentType == 5 || $request->assessmentType == "Amalgamation") {
                $request->merge(["safId" => $safId]);
                (new SafAmalgamatePropLog())->store($request);
            }

            // ✅ Owner Save
            if ($request['owner']) {
                $ownerDetail = $request['owner'];
                if ($request->assessmentType == 'Mutation') {
                    $ownerDetail = collect($ownerDetail)->where('propOwnerDetailId', null);
                }
                foreach ($ownerDetail as $ownerDetails) {
                    $mOwner->addOwner($ownerDetails, $safId, $user_id);
                }
            }

            // ✅ Floor Save
            $updatedFloorData = [];
            if ($request->propertyType != 4 && !empty($request['floor'])) {
                $this->checkBifurcationFloorCondition($request['floor']);
                foreach ($request['floor'] as $floorDetails) {
                    $floor = new PropActiveSafsFloor();
                    $floorId = $floor->addfloor($floorDetails, $safId, $user_id, $request->assessmentType, $request['biDateOfPurchase']);
                    $floorDetails['floorId'] = $floorId;
                    $updatedFloorData[] = $floorDetails;
                }
            }

            // ------------------------------------------------------
            // ✅ TC Site Verification Call
            // ------------------------------------------------------
            if ($userType == 'TC') {
                $activeSafController = app()->make(ActiveSafController::class);
                $verificationPayload = [
                    'auth'             => $request->auth,
                    'safId'            => $safId,
                    'propertyType'     => $request->propertyType,
                    'roadWidth'        => $request->roadType,
                    'areaOfPlot'       => $request->areaOfPlot ?? 0,
                    'wardId'           => $request->ward ?? 1,
                    'isMobileTower'    => (bool)$request->isMobileTower,
                    'mobileTower'      => $request->mobileTower ?? [],
                    'isHoardingBoard'  => (bool)$request->isHoardingBoard,
                    'hoardingBoard'    => $request->hoardingBoard ?? [],
                    'isPetrolPump'     => (bool)$request->isPetrolPump,
                    'petrolPump'       => $request->petrolPump ?? [],
                    'isWaterHarvesting' => (bool)$request->isWaterHarvesting,
                    'rwhDateFrom'      => $request->rwhDateFrom ?? null,
                    'deviceId'         => $request->deviceId ?? null,
                    'floor'            => $updatedFloorData,
                ];
                $verReq = ReqSiteVerification::create('/site-verification', 'POST', $verificationPayload);
                $activeSafController->siteVerification($verReq);
            }

            // ------------------------------------------------------
            // ✅ Citizen Notification
            // ------------------------------------------------------
            if ($userType == 'Citizen') {
                $notify = [
                    'userType'     => 'Citizen',
                    'citizenId'    => $user_id,
                    'category'     => 'Recent Application',
                    'ulbId'        => $ulb_id,
                    'ephameral'    => 0,
                    'notification' => "Successfully Submitted Your Application. Your SAF No. $safNo",
                ];
                (new EloquentAuthRepository())->addNotification($notify);
            }

            // ------------------------------------------------------
            // ✅ Offline Payment Call (Optional / If Required)
            // ------------------------------------------------------
            if (isset($demandResponse['amounts']['payableAmount']) && $demandResponse['amounts']['payableAmount'] == 0) {
                $paymentPayload = [
                    'auth'             => $request->auth,
                    "id"             => $safId,
                    "paymentMode"    => "CASH",
                    "ulbId"          => $ulb_id,
                    "chequeDate"     => "",
                    "bankName"       => "",
                    "branchName"     => "",
                    "chequeNo"       => "",
                    "advanceAmount"  => ""
                ];

                // Create internal Laravel request from ReqPayment
                $paymentRequest = ReqPayment::create(
                    '/offline-payment-saf',
                    'POST',
                    $paymentPayload
                );

                // Call controller method
                $activeSafController = app()->make(ActiveSafController::class);
                $response = $activeSafController->offlinePaymentSaf($paymentRequest);
            }
            // ✅ Commit All Transactions
            DB::commit();
            DB::connection('pgsql_master')->commit();

            // ------------------------------------------------------
            // ✅ Final Response
            // ------------------------------------------------------
            return responseMsgs(true, "Successfully Submitted Your Application. Your SAF No. $safNo", [
                "safNo"     => $safNo,
                "applyDate" => ymdToDmyDate($mApplyDate),
                "safId"     => $safId,
                "demand"    => $demandResponse
            ], "010101", "1.0", "1s", "POST", $request->deviceId);
        } catch (Exception $e) {
            // Rollback on Failure
            DB::rollBack();
            DB::connection('pgsql_master')->rollBack();
            return responseMsgs(false, $e->getMessage(), $e->getFile(), "010101", "1.0", responseTime(), "POST", $request->deviceId);
        }
    }


    /**
     * | Read Assessment Type and Ulb Workflow Id
     * | 
     * | A1.1
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
     * | 
     * | A1.2
     */
    public function mergeAssessedExtraFields()
    {
        $mPropProperty = new PropProperty();
        $req = $this->_REQUEST;
        $assessmentType = $req->assessmentType;

        if (in_array($assessmentType, $this->_demandAdjustAssessmentTypes)) {           // Reassessment,Mutation and Others
            $property = $mPropProperty->getPropById($req->previousHoldingId);
            if (collect($property)->isEmpty())
                throw new Exception("Property Not Found For This Holding");
            $req->holdingNo = $property->new_holding_no ?? $property->holding_no;
            $propId = $property->id;
            $req->merge([
                'hasPreviousHoldingNo' => true,
                'previousHoldingId' => $propId
            ]);
            switch ($assessmentType) {
                case "Reassessment":                                 // Bifurcation
                    $req->merge([
                        'propDtl' => $propId
                    ]);
                    break;

                case "Bifurcation":                                 // Bifurcation
                    $req->landOccupationDate = $req->biDateOfPurchase;
                    $req->areaOfPlot         = $this->checkBifurcationCondition($property, $req);
                    break;
            }
        }

        // // Amalgamation
        // if (in_array($assessmentType, ["Amalgamation"])) {
        //     $previousHoldingIds = array();
        //     $previousHoldingLists = array();

        //     foreach ($req->holdingNoLists as $holdingNoList) {
        //         $propDtls = $mPropProperty->getPropertyId($holdingNoList);
        //         if (!$propDtls)
        //             throw new Exception("Property Not Found For the holding");
        //         $propId = $propDtls->id;
        //         array_push($previousHoldingIds, $propId);
        //         array_push($previousHoldingLists, $holdingNoList);
        //     }

        //     $req->merge([
        //         'hasPreviousHoldingNo' => true,
        //         'previousHoldingId' => implode(",", $previousHoldingIds),
        //         'holdingNo' => implode(",", $req->holdingNoLists)
        //     ]);
        // }
    }

    /**
     * | Check Bifurcation Condition
     * | 
     * | A1.2.1
     */
    public function checkBifurcationCondition($propDtls, $activeSafReqs)
    {
        $mPropActiveSaf = new PropActiveSaf();
        $propertyId = $propDtls->id;
        $propertyPlotArea = $propDtls->area_of_plot;
        $currentSafPlotArea = $activeSafReqs->bifurcatedPlot;
        $activeSafDetail = $mPropActiveSaf->where('previous_holding_id', $propertyId)->where('assessment_type', 'Bifurcation')->where('status', 1)->get();
        $activeSafPlotArea = collect($activeSafDetail)->sum('area_of_plot');
        $newAreaOfPlot = $propertyPlotArea - $activeSafPlotArea;
        if (($activeSafPlotArea + $currentSafPlotArea) > $propertyPlotArea)
            throw new Exception("You have excedeed the plot area. Please insert plot area below " . $newAreaOfPlot);
        if (($activeSafPlotArea + $currentSafPlotArea) == $propertyPlotArea)
            throw new Exception("You Can't apply for Bifurcation. Please Apply Mutation.");
        return $newAreaOfPlot;
    }

    /**
     * | Check Bifurcation Floor Condition
     * | 
     * | A1.3
     */
    public function checkBifurcationFloorCondition($floorDetail)
    {
        $req = $this->_REQUEST;
        $mPropFloors = new PropFloor();
        $assessmentType = $req->assessmentType;
        if ($assessmentType == 'Bifurcation') {
            $floorDetail = collect($floorDetail)->whereNotNull('propFloorDetailId');

            foreach ($floorDetail as $index => $requestFloor) {
                $propFloorDtls = $mPropFloors::find($requestFloor['propFloorDetailId']);
                $safFloorDtls  = PropActiveSafsFloor::where('prop_floor_details_id', $requestFloor['propFloorDetailId'])->where('status', 1)->get();
                $currentFloorArea  = $requestFloor['biBuildupArea'];
                $propFloorArea  = $propFloorDtls->builtup_area;
                $safFloorArea   = $safFloorDtls->sum('builtup_area');
                $newAreaOfPlot  = $propFloorArea - $safFloorArea;

                if (($safFloorArea + $currentFloorArea) > $propFloorArea)
                    throw new Exception("You have excedeed the floor area. Please insert floor area below " . $newAreaOfPlot . " of floor " . $index + 1);
            }
        }
    }

    /**
     * | This API handles the submission of GB SAF (Government Building Self Assessment Form) applications.
     * | It calculates property tax, saves building and officer details, and initiates the workflow process.
     * | 
     * | Main Function M1
     * | Modified by Arshad
     */
    public function applyGbSaf(ReqGBSaf $req)
    {
        try {
            // Variable Assignments
            $user = authUser($req);
            $userId = $user->id;
            $userType = $user->user_type;
            $ulbId = $req->ulbId ?? $user->ulb_id;
            $propActiveSafs = new PropActiveSaf();
            $safCalculation = new SafCalculation;
            $mPropFloors = new PropActiveSafsFloor();
            $mPropGbOfficer = new PropActiveGbOfficer();
            $safReq = array();
            $reqFloors = $req->floors;
            $applicationDate = $this->_todayDate->format('Y-m-d');
            $assessmentId = $req->assessmentType;
            $calculateSafById = new CalculateSafById;
            $generateSafApplyDemandResponse = new GenerateSafApplyDemandResponse;
            $insertTax = new InsertTax;
            $postSafPropTax = new PostSafPropTaxes;

            // Derivative Assignments
            $ulbWfId = $this->readGbAssessUlbWfId($req, $ulbId);
            $roadWidthType = $this->readRoadWidthType($req->roadWidth);                               // Read Road Width Type
            $refInitiatorRoleId = $this->getInitiatorId($ulbWfId->id);                                // Get Current Initiator ID
            $initiatorRoleId = collect(DB::select($refInitiatorRoleId))->first();

            $refFinisherRoleId = $this->getFinisherId($ulbWfId->id);
            $finisherRoleId = collect(DB::select($refFinisherRoleId))->first();
            $req = $req->merge(
                [
                    'road_type_mstr_id' => $roadWidthType,
                    'ward' => $req->wardId,
                    'propertyType' => 1,
                    'roadType' => $req->roadWidth,
                    'floor' => $req->floors,
                    'isGBSaf' => true
                ]
            );

            $safTaxes = $safCalculation->calculateTax($req);
            // Generate Calculation
            $calculateSafById->_calculatedDemand = $safTaxes->original['data'];
            $calculateSafById->_safDetails['assessment_type'] = $assessmentId;

            if (isset($req->holdingNo))
                $calculateSafById->_holdingNo = $req->holdingNo;
            $calculateSafById->_currentQuarter = calculateQtr($applicationDate);
            $calculateSafById->generateSafDemand();
            $generatedDemand = $calculateSafById->_generatedDemand;
            $isResidential = $safTaxes->original['data']['demand']['isResidential'];
            $demandResponse = $generateSafApplyDemandResponse->generateResponse($generatedDemand, $isResidential);
            $demandToBeSaved = $demandResponse['details']->values()->collapse();
            $lateAssessmentPenalty = $demandResponse['amounts']['lateAssessmentPenalty'];
            $lateAssessmentPenalty = ($lateAssessmentPenalty > 0) ? $lateAssessmentPenalty : null;
            // Send to Workflow
            $currentRole = ($userType == $this->_citizenUserType) ? $initiatorRoleId->role_id : $initiatorRoleId->role_id;
            $isTrust = $this->isPropTrust($req['floor']);

            $safReq = [
                'assessment_type' => $req->assessmentType,
                'ulb_id' => $ulbId,
                // 'prop_type_mstr_id' => 2,               // Independent Building
                'prop_type_mstr_id' => $req->propertyType,               // modify by Arshad 
                'building_name' => $req->buildingName,
                'gb_office_name' => $req->nameOfOffice,
                'ward_mstr_id' => $req->wardId,
                'new_ward_mstr_id' => $req->newWardId,
                'prop_address' => $req->buildingAddress,
                'gb_usage_types' => $req->gbUsageTypes,
                'gb_prop_usage_types' => $req->gbPropUsageTypes,
                'zone_mstr_id' => $req->zone,
                'road_width' => $req->roadWidth,
                'road_type_mstr_id' => $roadWidthType,
                'is_mobile_tower' => $req->isMobileTower,
                'tower_area' => $req->mobileTower['area'] ?? null,
                'tower_installation_date' => $req->mobileTower['dateFrom'] ?? null,

                'is_hoarding_board' => $req->isHoardingBoard,
                'hoarding_area' => $req->hoardingBoard['area'] ?? null,
                'hoarding_installation_date' => $req->hoardingBoard['dateFrom'] ?? null,


                'is_petrol_pump' => $req->isPetrolPump,
                'under_ground_area' => $req->petrolPump['area'] ?? null,
                'petrol_pump_completion_date' => $req->petrolPump['dateFrom'] ?? null,

                'is_water_harvesting' => $req->isWaterHarvesting,
                'area_of_plot' => $req->areaOfPlot,
                'is_gb_saf' => true,
                'application_date' => $applicationDate,
                'initiator_role_id' => $currentRole,
                'current_role' => $currentRole,
                'finisher_role_id' => $finisherRoleId->role_id,
                'workflow_id' => $ulbWfId->wf_master_id,
                'is_trust' => $isTrust,
                'trust_type' => $req->trustType ?? null,
                'khata_no' => $req->khataNo ?? null,
                'plot_no' => $req->plotNo ?? null,
                'corr_dist' => $req->district ?? null,
                'is_water_harvesting' => $req->isWaterHarvesting,
                'rwh_date_from' => ($req->isWaterHarvesting == 1) ? $req->rwhDateFrom : null,
                'village_mauja_name' => $req->villageName,
                'elect_consumer_no' => $req->electricityCustNo,
                'elect_acc_no' => $req->electricityAccNo,
                'elect_bind_book_no' => $req->electricityBindBookNo,
                'elect_cons_category' => $req->electricityConsCategory,
                'user_id' => $userId,
                'citizen_id' => ($userType == $this->_citizenUserType) ? $userId : null,
                'user_type' => $userType,
                'holding_no' => $req->holdingNo ?? null,
                'location' => $req->location ?? null,
                'landmark' => $req->landmark ?? null,
                'holding_no' => $req->holdingNo ?? null,
                'road_width' => $req->roadType,
                'corr_city' => $req->city,
                'prop_city' => $req->city,
                'prop_dist' => $req->district,
                'prop_state' => $req->state,
                'corr_state' => $req->state,
                'prop_pin_code' => $req->pinCode ?? null,
                'street_name' => $req->streetName ?? null,

            ];
            DB::beginTransaction();
            DB::connection('pgsql_master')->beginTransaction();

            $createSaf = $propActiveSafs->storeGBSaf($safReq);           // Store Saf
            $safId = $createSaf->original['safId'];
            $safNo = $createSaf->original['safNo'];

            // Store Floors
            foreach ($reqFloors as $floor) {
                $mPropFloors->addfloor($floor, $safId, $userId, $req->assessmentType,);
            }


            // Insert Officer Details
            $gbOfficerReq = [
                'saf_id' => $safId,
                'officer_name' => strtoupper($req->officerName),
                'designation' => strtoupper($req->designation),
                'mobile_no' => $req->officerMobile,
                'email' => $req->officerEmail,
                'address' => $req->address,
                'ulb_id' => $ulbId
            ];
            $mPropGbOfficer->store($gbOfficerReq);
            $this->sendToWorkflow($createSaf, $userId);
            // Demand Saved
            $insertTax->insertTax($safId, $ulbId, $demandToBeSaved, $userId);
            $postSafPropTax->postSafTaxes($safId, $generatedDemand['details']->toArray(), $ulbId);                        // Saf Tax Generation



            DB::commit();
            DB::connection('pgsql_master')->commit();

            return responseMsgs(true, "Successfully Submitted Your Application Your SAF No. $safNo", [
                "safNo" => $safNo,
                "applyDate" => Carbon::parse($applicationDate)->format('d-m-Y'),
                "safId" => $safId,
                "demand" => $demandResponse
            ], "010102", "1.0", "1s", "POST", $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            DB::connection('pgsql_master')->rollBack();

            return responseMsgs(false, $e->getMessage(), "", "010102", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Read GB Assessment Type and Ulb Workflow Id
     * | 
     * | M1.1
     */
    public function readGbAssessUlbWfId($request, $ulb_id)
    {
        if ($request->assessmentType == 1) {                                                    // New Assessment 
            $workflow_id = Config::get('workflow-constants.GBSAF_NEW_ASSESSMENT');
            $request->assessmentType = Config::get('PropertyConstaint.ASSESSMENT-TYPE.1');
        }

        if ($request->assessmentType == 2) {                                                    // Reassessment
            $workflow_id = Config::get('workflow-constants.GBSAF_REASSESSMENT');
            $request->assessmentType = Config::get('PropertyConstaint.ASSESSMENT-TYPE.2');
        }

        return WfWorkflow::where('wf_master_id', $workflow_id)
            ->where('ulb_id', $ulb_id)
            ->first();
    }

    /**
     * | Send to Workflow Level
     * | 
     * | M1.2
     */
    public function sendToWorkflow($activeSaf, $userId)
    {
        $mWorkflowTrack = new WorkflowTrack();
        $todayDate = $this->_todayDate;
        $refTable = Config::get('PropertyConstaint.SAF_REF_TABLE');
        $reqWorkflow = [
            'workflow_id' => $activeSaf->original['workflow_id'],
            'ref_table_dot_id' => $refTable,
            'ref_table_id_value' => $activeSaf->original['safId'],
            'track_date' => $todayDate->format('Y-m-d h:i:s'),
            'module_id' => Config::get('module-constants.PROPERTY_MODULE_ID'),
            'user_id' => $userId,
            'receiver_role_id' => $activeSaf->original['current_role'],
            'ulb_id' => $activeSaf->original['ulb_id'],
        ];
        $mWorkflowTrack->store($reqWorkflow);
    }

    #_#_#_#_#_#_#_#_#_#_#_#_#_#_#_#_#_#_#_#_#_#_#_#_#_#_#_#_#_#_#_#_#_#_#_#_#_#_#_#_#_#_#_#
    /**
     * | Demand Adjustment In Case of Reassessment
     * | 
       | Note: This function is not in use currently (No Route is assigned to this function)
       | Date: 09-06-2025
     */
    public function adjustDemand()
    {
        $propDemandList = array();
        $mSafDemand = $this->_safDemand;
        $generatedDemand = $this->_generatedDemand;
        $propProperty = $this->_propProperty;
        $holdingNo = $this->_holdingNo;
        $mPropDemands = new PropDemand();
        $propDtls = $propProperty->getSafIdByHoldingNo($holdingNo);
        $propertyId = $propDtls->id;
        $safDemandList = $mSafDemand->getFullDemandsBySafId($propDtls->saf_id);
        if ($safDemandList->isEmpty())
            throw new Exception("Previous Saf Demand is Not Available");

        $propDemandList = $mPropDemands->getPaidDemandByPropId($propertyId);
        $fullDemandList = $safDemandList->merge($propDemandList);
        $generatedDemand = $generatedDemand->sortBy('due_date');

        // Demand Adjustment
        foreach ($generatedDemand as $item) {
            $demand = $fullDemandList->where('due_date', $item['dueDate'])->first();
            if (collect($demand)->isEmpty())
                $item['adjustAmount'] = 0;
            else
                $item['adjustAmount'] = $demand->amount - $demand->balance;
            $item['balance'] = roundFigure($item['totalTax'] - $item['adjustAmount']);
            if ($item['balance'] == 0)
                $item['onePercPenaltyTax'] = 0;
        }
        return $generatedDemand;
    }
    //  public function applySaf(reqApplySaf $request)
    // {
    //     try {
    //         // Variable Assignments
    //         $mApplyDate = Carbon::now()->format("Y-m-d");
    //         $user = authUser($request);
    //         $user_id = $user->id;
    //         $ulb_id = $request->ulbId ?? $user->ulb_id;
    //         $userType = $user->user_type ?? 'Citizen'; // Default to Citizen if not set
    //         $metaReqs = array();
    //         $saf = new PropActiveSaf();


    //         $mOwner = new PropActiveSafsOwner();
    //         $safCalculation = new SafCalculation();
    //         $calculateSafById = new CalculateSafById;
    //         $generateSafApplyDemandResponse = new GenerateSafApplyDemandResponse;
    //         // Derivative Assignments
    //         $ulbWorkflowId = $this->readAssessUlbWfId($request, $ulb_id);
    //         $roadWidthType = $this->readRoadWidthType($request->roadType);          // Read Road Width Type

    //         $request->request->add(['road_type_mstr_id' => $roadWidthType]);

    //         $refInitiatorRoleId = $this->getInitiatorId($ulbWorkflowId->id);                                // Get Current Initiator ID
    //         $initiatorRoleId = collect(DB::select($refInitiatorRoleId))->first();
    //         if (is_null($initiatorRoleId))
    //             throw new Exception("Initiator Role Not Available");
    //         $refFinisherRoleId = $this->getFinisherId($ulbWorkflowId->id);
    //         $finisherRoleId = collect(DB::select($refFinisherRoleId))->first();
    //         if (is_null($finisherRoleId))
    //             throw new Exception("Finisher Role Not Available");

    //         $metaReqs['roadWidthType'] = $roadWidthType;
    //         $metaReqs['workflowId'] = $ulbWorkflowId->id;
    //         $metaReqs['ulbId'] = $ulb_id;
    //         $metaReqs['userId'] = $user_id;
    //         $metaReqs['initiatorRoleId'] = collect($initiatorRoleId)['role_id'];
    //         if ($userType == $this->_citizenUserType) {
    //             $metaReqs['initiatorRoleId'] = collect($initiatorRoleId)['forward_role_id'];         // Send to DA in Case of Citizen
    //             $metaReqs['userId'] = null;
    //             $metaReqs['citizenId'] = $user_id;
    //         } elseif ($userType == 'TC') {
    //             // TC: Retrieve TC role from workflow and set as initiator
    //             $wfRole = new WfRoleusermap();

    //             $request->merge([
    //                 'userId' => $user_id,
    //                 'workflowId' => $ulbWorkflowId->id
    //             ]);

    //             $getRole = $wfRole->getRoleByUserWfId($request);

    //             if (empty($getRole))
    //                 throw new Exception("Workflow role mapping not found for this TC user.");

    //             $metaReqs['initiatorRoleId'] = $getRole->wf_role_id; // ✅ Use TC role as initiator
    //             $metaReqs['tcId'] = $user_id;                     // Optional: track TC ID
    //         }
    //         $metaReqs['finisherRoleId'] = collect($finisherRoleId)['role_id'];

    //         // if ($request['assessmentType'] != 4 && $request['assessmentType'] != 5) { // Bifurcation and Amalgamation
    //         $safTaxes = $safCalculation->calculateTax($request);

    //         if ($safTaxes->original['status'] == false)
    //             throw new Exception($safTaxes->original['message']);
    //         // } else {
    //         //     $safTaxes = (object)[
    //         //         'original' => [
    //         //             'status' => true,
    //         //             'data' => [
    //         //                 'demand' => [
    //         //                     'isResidential' => true
    //         //                 ]
    //         //             ]
    //         //         ]
    //         //     ];
    //         // }


    //         $metaReqs['isTrust'] = $this->isPropTrust($request['floor']);
    //         $metaReqs['holdingType'] = $this->holdingType($request['floor']);
    //         $request->merge($metaReqs);
    //         $this->_REQUEST = $request;
    //         $this->mergeAssessedExtraFields();                                          // Merge Extra Fields for Property Reassessment,Mutation,Bifurcation & Amalgamation(2.2)
    //         // Generate Calculation
    //         $calculateSafById->_calculatedDemand = $safTaxes->original['data'];
    //         $calculateSafById->_safDetails['assessment_type'] = $request->assessmentType;
    //         $calculateSafById->_safDetails['previous_holding_id'] = $request->previousHoldingId;

    //         if (isset($request->holdingNo))
    //             $calculateSafById->_holdingNo = $request->holdingNo;
    //         $calculateSafById->_currentQuarter = calculateQtr($mApplyDate);
    //         $firstOwner = collect($request['owner'])->first();
    //         $calculateSafById->_firstOwner = [
    //             'gender' => $firstOwner['gender'],
    //             'dob' => $firstOwner['dob'],
    //             'is_armed_force' => $firstOwner['isArmedForce'],
    //             'is_specially_abled' => $firstOwner['isSpeciallyAbled'],
    //         ];
    //         $demandResponse = null;
    //         // if ($request['assessmentType'] != 4 && $request['assessmentType'] != 5) { // Bifurcation and Amalgamation
    //         $calculateSafById->generateSafDemand();

    //         $generatedDemand = $calculateSafById->_generatedDemand;
    //         $isResidential = $safTaxes->original['data']['demand']['isResidential'];
    //         $demandResponse = $generateSafApplyDemandResponse->generateResponse($generatedDemand, $isResidential);
    //         // }

    //         DB::beginTransaction();
    //         DB::connection('pgsql_master')->beginTransaction();

    //         if ($request->assessmentType == 'Bifurcation' || $request->assessmentType == 'Amalgamation') { // Bifurcation and Amalgamation
    //             $request->merge(['paymentStatus' => '1']);
    //         } else {
    //             $request->merge(['paymentStatus' => '0']);
    //         }
    //         $createSaf = $saf->store($request, $userType);                                         // Store SAF Using Model function 
    //         if ($request->assessmentType == 5 || $request->assessmentType == "Amalgamation") {
    //             $request->merge(["safId" => $createSaf->original['safId']]);
    //             $SafAmalgamatePropLog = new SafAmalgamatePropLog();
    //             $SafAmalgamatePropLog->store($request);
    //         }
    //         $safId = $createSaf->original['safId'];
    //         $safNo = $createSaf->original['safNo'];

    //         // SAF Owner Details
    //         if ($request['owner']) {
    //             $ownerDetail = $request['owner'];
    //             if ($request->assessmentType == 'Mutation')                             // In Case of Mutation Avert Existing Owner Detail
    //                 $ownerDetail = collect($ownerDetail)->where('propOwnerDetailId', null);
    //             foreach ($ownerDetail as $ownerDetails) {
    //                 $mOwner->addOwner($ownerDetails, $safId, $user_id);
    //             }
    //         }

    //         // Floor Details
    //         if ($request->propertyType != 4 && !empty($request['floor'])) {
    //             $floorDetail = $request['floor'];
    //             if ($request['floor']) {
    //                 $floorDetail = $request['floor'];
    //                 $this->checkBifurcationFloorCondition($floorDetail);
    //                 foreach ($floorDetail as $floorDetails) {
    //                     $floor = new PropActiveSafsFloor();
    //                     $floorId = $floor->addfloor($floorDetails, $safId, $user_id, $request->assessmentType, $request['biDateOfPurchase']);
    //                     // Add the new floorId to the floor data
    //                     $floorDetails['floorId'] = $floorId;

    //                     // Store updated floor record
    //                     $updatedFloorData[] = $floorDetails;
    //                 }
    //             }
    //         }
    //         if ($userType == 'TC') {
    //             $activeSafController = app()->make(ActiveSafController::class);

    //             // Build the payload array
    //             $verificationPayload = [
    //                 'safId' => $safId,
    //                 'propertyType' => $request->propertyType,
    //                 'roadWidth' => $request->roadType,
    //                 'areaOfPlot' => $request->areaOfPlot ?? 0,
    //                 'wardId' => $request->ward ?? 1,

    //                 'isMobileTower' => (bool) $request->isMobileTower,
    //                 'mobileTower' => [
    //                     'area' => $request->mobileTower['area'] ?? null,
    //                     'dateFrom' => $request->mobileTower['dateFrom'] ?? null,
    //                 ],

    //                 'isHoardingBoard' => (bool) $request->isHoardingBoard,
    //                 'hoardingBoard' => [
    //                     'area' => $request->hoardingBoard['area'] ?? null,
    //                     'dateFrom' => $request->hoardingBoard['dateFrom'] ?? null,
    //                 ],

    //                 'isPetrolPump' => (bool) $request->isPetrolPump,
    //                 'petrolPump' => [
    //                     'area' => $request->petrolPump['area'] ?? null,
    //                     'dateFrom' => $request->petrolPump['dateFrom'] ?? null,
    //                 ],

    //                 'isWaterHarvesting' => (bool) $request->isWaterHarvesting,
    //                 'rwhDateFrom' => $request->rwhDateFrom ?? null,
    //                 'deviceId' => $request->deviceId ?? null,
    //             ];

    //             // Add floor details if propertyType is not 4
    //             if ($request->propertyType != 4) {
    //                 $verificationPayload['floor'] = $updatedFloorData ?? [];
    //             }
    //             // ✅ Merge deeply so verificationPayload overrides $request values
    //             // Final payload: only 'auth' + verification data
    //             $finalPayload = [
    //                 'auth' => $request->auth,
    //             ] + $verificationPayload;

    //             // Create new internal request
    //             $verReq = ReqSiteVerification::create(
    //                 '/site-verification',
    //                 'POST',
    //                 $finalPayload
    //             );

    //             // Call the siteVerification method
    //             $siteVerifyResponse = $activeSafController->siteVerification($verReq);
    //         }


    //         // Citizen Notification
    //         if ($userType == 'Citizen') {
    //             $mreq['userType']  = 'Citizen';
    //             $mreq['citizenId'] = $user_id;
    //             $mreq['category']  = 'Recent Application';
    //             $mreq['ulbId']     = $ulb_id;
    //             $mreq['ephameral'] = 0;
    //             $mreq['notification'] = "Successfully Submitted Your Application Your SAF No. $safNo";
    //             $rEloquentAuthRepository = new EloquentAuthRepository();
    //             $rEloquentAuthRepository->addNotification($mreq);
    //         }
    //         // return $demandResponse;
    //         // dd();
    //         DB::commit();
    //         DB::connection('pgsql_master')->commit();
    //         return responseMsgs(true, "Successfully Submitted Your Application Your SAF No. $safNo", [
    //             "safNo" => $safNo,
    //             "applyDate" => ymdToDmyDate($mApplyDate),
    //             "safId" => $safId,
    //             "demand" => $demandResponse
    //         ], "010101", "1.0", "1s", "POST", $request->deviceId);
    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         DB::connection('pgsql_master')->rollBack();
    //         return responseMsgs(false, $e->getMessage(), $e->getFile(), "010101", "1.0", responseTime(), "POST", $request->deviceId);
    //     }
    // }
}
