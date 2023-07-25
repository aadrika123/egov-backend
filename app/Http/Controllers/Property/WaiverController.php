<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\MicroServices\DocUpload;
use App\Models\Property\PropActiveSaf;
use App\Models\Property\PropActiveWaiver;
use App\Models\Property\PropProperty;
use App\Models\Waiver;
use App\Models\Workflows\WfRoleusermap;
use App\Models\Workflows\WfWorkflow;
use App\Models\Workflows\WfWorkflowrolemap;
use App\Models\WorkflowTrack;
use App\Traits\Property\SafDetailsTrait;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class WaiverController extends Controller
{
    use SafDetailsTrait;

    /**
     * | For apply waiver application
     */
    public function apply(Request $request)
    {
        $validation = Validator::make($request->all(), [
            "isBillWaiver" => "required",
            "isOnePercentPenalty" => "required",
            "isRwhPenalty" => "required",
            "isLateAssessmentPenalty" => "required",
            "billAmount" => "nullable",
            "billWaiverAmount" => "nullable",
            "onePercentPenaltyAmount" => "nullable",
            "onePercentPenaltyWaiverAmount" => "nullable",
            "rwhAmount" => "nullable",
            "rwhWaiverAmount" => "nullable",
            "lateAssessmentPenaltyAmount" => "nullable",
            "lateAssessmentPenaltyWaiverAmount" => "nullable",
            "propertyId" => "nullable",
            "safId" => "nullable",
            "waiverDocument" => "required",
            "description" => "required",
        ]);

        if ($validation->fails()) {
            return validationError($validation);
        }

        try {
            $user = authUser($request);
            $mPropActiveWaiver = new PropActiveWaiver();
            $docUpload = new DocUpload();
            $path = "Uploads/Property/Waiver";
            $refImageName = "WaiverDocuments";
            $document = $request->waiverDocument;
            $imageName = $docUpload->upload($refImageName, $document, $path);
            $request->merge([
                "waiverDocument" => $imageName,
                "userId" => $user->id,
                "workflowId" => 195,
                "currentRole" => 3,
            ]);

            $data = $mPropActiveWaiver->addWaiver($request);

            return responseMsgs(true, "Data Saved", $data);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "");
        }
    }

    /**
     * | Post Next Level Application
     */
    // public function postNextLevel(Request $req)
    // {
    //     $wfLevels = Config::get('PropertyConstaint.CONCESSION-LABEL');
    //     $req->validate([
    //         'applicationId' => 'required|integer',
    //         'receiverRoleId' => 'nullable|integer',
    //         'action' => 'required|In:forward,backward',
    //     ]);
    //     try {
    //         $userId = authUser($req)->id;
    //         $track = new WorkflowTrack();
    //         $mWfWorkflows = new WfWorkflow();
    //         $mWfRoleMaps = new WfWorkflowrolemap();
    //         $mPropActiveWaiver = PropActiveWaiver::find($req->applicationId);
    //         $senderRoleId = $mPropActiveWaiver->current_role;
    //         $ulbWorkflowId = $mPropActiveWaiver->workflow_id;
    //         $ulbWorkflowMaps = $mWfWorkflows->getWfDetails($ulbWorkflowId);
    //         $roleMapsReqs = new Request([
    //             'workflowId' => $ulbWorkflowMaps->id,
    //             'roleId' => $senderRoleId
    //         ]);
    //         $forwardBackwardIds = $mWfRoleMaps->getWfBackForwardIds($roleMapsReqs);

    //         DB::beginTransaction();
    //         if ($req->action == 'forward') {
    //             $this->checkPostCondition($senderRoleId, $wfLevels, $mPropActiveWaiver);          // Check Post Next level condition
    //             $mPropActiveWaiver->current_role = $forwardBackwardIds->forward_role_id;
    //             // $mPropActiveWaiver->last_role_id =  $forwardBackwardIds->forward_role_id;         // Update Last Role Id
    //             $metaReqs['verificationStatus'] = 1;
    //             $metaReqs['receiverRoleId'] = $forwardBackwardIds->forward_role_id;
    //         }

    //         $mPropActiveWaiver->save();

    //         $metaReqs['moduleId'] = Config::get('module-constants.PROPERTY_MODULE_ID');
    //         $metaReqs['workflowId'] = $mPropActiveWaiver->workflow_id;
    //         $metaReqs['refTableDotId'] = 'prop_active_waivers.id';
    //         $metaReqs['refTableIdValue'] = $req->applicationId;
    //         $metaReqs['senderRoleId'] = $senderRoleId;
    //         $metaReqs['user_id'] = $userId;

    //         $req->request->add($metaReqs);
    //         $track->saveTrack($req);

    //         // Updation of Received Date
    //         $preWorkflowReq = [
    //             'workflowId' => $mPropActiveWaiver->workflow_id,
    //             'refTableDotId' => 'prop_active_mPropActiveWaivers.id',
    //             'refTableIdValue' => $req->applicationId,
    //             'receiverRoleId' => $senderRoleId
    //         ];
    //         $previousWorkflowTrack = $track->getWfTrackByRefId($preWorkflowReq);
    //         $previousWorkflowTrack->update([
    //             'forward_date' => Carbon::now()->format('Y-m-d'),
    //             'forward_time' => Carbon::now()->format('H:i:s')
    //         ]);

    //         DB::commit();
    //         return responseMsgs(true, "Successfully Forwarded The Application!!", "", "", '010708', '01', '', 'Post', '');
    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         return responseMsg(false, $e->getMessage(), "");
    //     }
    // }

    /**
     * | Final Approval
     */
    public function approvalRejection(Request $req)
    {
        try {
            $req->validate([
                "applicationId" => "required",
                "status" => "required"
            ]);
            // Check if the Current User is Finisher or Not
            $mWfRoleUsermap = new WfRoleusermap();
            $mPropActiveWaiver = new PropActiveWaiver();
            $track = new WorkflowTrack();

            $activeWaiver = PropActiveWaiver::findorFail($req->applicationId);
            $userId = authUser($req)->id;
            // $getFinisherQuery = $this->getFinisherId($req->workflowId);                                 // Get Finisher using Trait
            // $refGetFinisher = collect(DB::select($getFinisherQuery))->first();

            $workflowId = $activeWaiver->workflow_id;
            $senderRoleId = $activeWaiver->current_role;
            $getRoleReq = new Request([                                                 // make request to get role id of the user
                'userId' => $userId,
                'workflowId' => $workflowId
            ]);
            // $readRoleDtls = $mWfRoleUsermap->getRoleByUserWfId($getRoleReq);
            // $roleId = $readRoleDtls->wf_role_id;

            // if ($refGetFinisher->role_id != $roleId) {
            //     return responseMsg(false, "Forbidden Access", "");
            // }
            DB::beginTransaction();

            // Approval
            if ($req->status == 1) {

                $activeWaiver->is_approved = true;
                $activeWaiver->save();
                $msg =  "Application Successfully Approved !!";
                $metaReqs['verificationStatus'] = 1;
            }
            // Rejection
            if ($req->status == 0) {
                $activeWaiver->is_approved = false;
                $activeWaiver->save();
                $msg =  "Application Successfully Rejected !!";
                $metaReqs['verificationStatus'] = 0;
            }

            $metaReqs['moduleId'] = Config::get('module-constants.PROPERTY_MODULE_ID');
            $metaReqs['workflowId'] = $activeWaiver->workflow_id;
            $metaReqs['refTableDotId'] = 'prop_active_waivers.id';
            $metaReqs['refTableIdValue'] = $req->applicationId;
            $metaReqs['senderRoleId'] = $senderRoleId;
            $metaReqs['user_id'] = $userId;
            $metaReqs['trackDate'] = Carbon::now()->format('Y-m-d H:i:s');
            $req->request->add($metaReqs);
            $track->saveTrack($req);

            // Updation of Received Date
            $preWorkflowReq = [
                'workflowId' => $activeWaiver->workflow_id,
                'refTableDotId' => 'prop_active_waivers.id',
                'refTableIdValue' => $req->applicationId,
                'receiverRoleId' => $senderRoleId
            ];
            // $previousWorkflowTrack = $track->getWfTrackByRefId($preWorkflowReq);
            // $previousWorkflowTrack->update([
            //     'forward_date' => Carbon::now()->format('Y-m-d'),
            //     'forward_time' => Carbon::now()->format('H:i:s')
            // ]);
            // dd();
            DB::commit();
            return responseMsgs(true, $msg, "", "", '010709', '01', '376ms', 'Post', '');
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Aprroved List
     */
    public function approvedApplication(Request $req)
    {
        try {
            $approvedList = PropActiveWaiver::where('is_approved', true)
                ->get();

            return responseMsgs(true, "Approved Application", $approvedList, "", '010709', '01', '376ms', 'Post', '');
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * |
     */
    public function applicationDetails(Request $req)
    {
        $validation = Validator::make($req->all(), [
            "applicationId" => "required|integer",
        ]);

        if ($validation->fails()) {
            return validationError($validation);
        }
        try {
            $applicationDtl = PropActiveWaiver::find($req->applicationId);
            $propertyDetail = PropProperty::find($applicationDtl->property_id);
            $safDetail      = PropActiveSaf::find($applicationDtl->saf_id);

            if (!$applicationDtl)
                throw new Exception("Application Not Found for this id");

            // Data Array
            $propertyDetails = $this->generatePropertyDetails($propertyDetail);   // (Property Details) Trait function to get Property Details
            $propertyElement = [
                'headerTitle' => "Property Details & Address",
                'data' => $propertyDetails
            ];

            $fullDetailsData['application_no'] = $applicationDtl->application_no;
            $fullDetailsData['apply_date'] = ($applicationDtl->created_at)->format('d-m-Y');
            $fullDetailsData['fullDetailsData']['dataArray'] = new Collection([$propertyElement]);
            $waiverList = collect();

            switch ($applicationDtl) {
                case ($applicationDtl->is_bill_waiver && $applicationDtl->is_one_percent_penalty && $applicationDtl->is_rwh_penalty):
                    $waiverList['is_bill_waiver'] = true;
                    $waiverList['bill_amount'] = $applicationDtl->bill_amount;
                    $waiverList['bill_waiver_amount'] = $applicationDtl->bill_waiver_amount;
                    $waiverList['is_one_percent_penalty'] = true;
                    $waiverList['one_percent_penalty_amount'] = $applicationDtl->one_percent_penalty_amount;
                    $waiverList['one_percent_penalty_waiver_amount'] = $applicationDtl->one_percent_penalty_waiver_amount;
                    $waiverList['is_rwh_penalty'] = true;
                    $waiverList['rwh_amount'] = $applicationDtl->rwh_amount;
                    $waiverList['rwh_waiver_amount'] = $applicationDtl->rwh_waiver_amount;
                    break;

                case ($applicationDtl->is_bill_waiver && $applicationDtl->is_one_percent_penalty):
                    $waiverList['is_bill_waiver'] = true;
                    $waiverList['bill_amount'] = $applicationDtl->bill_amount;
                    $waiverList['bill_waiver_amount'] = $applicationDtl->bill_waiver_amount;
                    $waiverList['is_one_percent_penalty'] = true;
                    $waiverList['one_percent_penalty_amount'] = $applicationDtl->one_percent_penalty_amount;
                    $waiverList['one_percent_penalty_waiver_amount'] = $applicationDtl->one_percent_penalty_waiver_amount;
                    break;

                case ($applicationDtl->is_one_percent_penalty && $applicationDtl->is_rwh_penalty):
                    $waiverList['is_one_percent_penalty'] = true;
                    $waiverList['one_percent_penalty_amount'] = $applicationDtl->one_percent_penalty_amount;
                    $waiverList['one_percent_penalty_waiver_amount'] = $applicationDtl->one_percent_penalty_waiver_amount;
                    $waiverList['is_rwh_penalty'] = true;
                    $waiverList['rwh_amount'] = $applicationDtl->rwh_amount;
                    $waiverList['rwh_waiver_amount'] = $applicationDtl->rwh_waiver_amount;
                    break;

                case ($applicationDtl->is_bill_waiver && $applicationDtl->is_rwh_penalty):
                    $waiverList['is_bill_waiver'] = true;
                    $waiverList['bill_amount'] = $applicationDtl->bill_amount;
                    $waiverList['bill_waiver_amount'] = $applicationDtl->bill_waiver_amount;
                    $waiverList['is_rwh_penalty'] = true;
                    $waiverList['rwh_amount'] = $applicationDtl->rwh_amount;
                    $waiverList['rwh_waiver_amount'] = $applicationDtl->rwh_waiver_amount;
                    break;

                case ($applicationDtl->is_bill_waiver):
                    $waiverList['is_bill_waiver'] = true;
                    $waiverList['bill_amount'] = $applicationDtl->bill_amount;
                    $waiverList['bill_waiver_amount'] = $applicationDtl->bill_waiver_amount;
                    break;

                case ($applicationDtl->is_one_percent_penalty):
                    $waiverList['is_one_percent_penalty'] = true;
                    $waiverList['one_percent_penalty_amount'] = $applicationDtl->one_percent_penalty_amount;
                    $waiverList['one_percent_penalty_waiver_amount'] = $applicationDtl->one_percent_penalty_waiver_amount;
                    break;

                case ($applicationDtl->is_rwh_penalty):
                    $waiverList['is_rwh_penalty'] = true;
                    $waiverList['rwh_amount'] = $applicationDtl->rwh_amount;
                    $waiverList['rwh_waiver_amount'] = $applicationDtl->rwh_waiver_amount;
                    break;

                case ($applicationDtl->is_lateassessment_penalty):
                    $waiverList['is_lateassessment_penalty'] = true;
                    $waiverList['lateassessment_penalty_amount'] = $applicationDtl->lateassessment_penalty_amount;
                    $waiverList['lateassessment_penalty_waiver_amount'] = $applicationDtl->lateassessment_penalty_waiver_amount;
                    break;
            }



            $waiverList = json_decode(json_encode($waiverList), true);       // Convert Std class to array

            $waiverDetails = $this->waiverDetails($waiverList);
            return  $waiverElement = [
                'headerTitle' => 'Waiver Details',
                'tableHead' => [
                    "Is Bill Waiver", "Bill Amount", "Bill Waiver Amount",
                    "is_one_percent_penalty", "one_percent_penalty_amount", "one_percent_penalty_waiver_amount",
                    "is_rwh_penalty", "rwh_amount", "rwh_waiver_amount"
                ],
                'tableData' => $waiverDetails
            ];
            $fullDetailsData['fullDetailsData']['tableArray'] = new Collection([$objectionElement]);

            $fullDetailsData['fullDetailsData']['tableArray'] = new Collection([$ownerElement]);
            // Card Details
            $cardElement = $this->generateConcessionCardDtls($details, $ownerList);
            $fullDetailsData['fullDetailsData']['cardArray'] = $cardElement;

            $levelComment = $mWorkflowTracks->getTracksByRefId($mRefTable, $req->applicationId);
            $fullDetailsData['levelComment'] = $levelComment;

            $citizenComment = $mWorkflowTracks->getCitizenTracks($mRefTable, $req->applicationId, $details->user_id);
            $fullDetailsData['citizenComment'] = $citizenComment;

            $metaReqs['customFor'] = 'PROPERTY-CONCESSION';
            $metaReqs['wfRoleId'] = $details->current_role;
            $metaReqs['workflowId'] = $details->workflow_id;
            // $metaReqs['lastRoleId'] = $details->last_role_id;
            $req->request->add($metaReqs);

            $forwardBackward = $mForwardBackward->getRoleDetails($req);
            $fullDetailsData['roleDetails'] = collect($forwardBackward)['original']['data'];

            $fullDetailsData['timelineData'] = collect($req);

            $custom = $mCustomDetails->getCustomDetails($req);
            $fullDetailsData['departmentalPost'] = collect($custom)['original']['data'];

            return responseMsgs(true, "Application Details", $fullDetailsData, "", '010709', '01', responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }
}
