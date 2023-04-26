<?php

namespace App\Http\Controllers\Water;

use App\Http\Controllers\Controller;
use App\Http\Requests\Water\newApplyRules;
use App\Http\Requests\Water\reqSiteVerification;
use App\MicroServices\DocUpload;
use App\Models\Masters\RefRequiredDocument;
use App\Models\Payment\WebhookPaymentData;
use App\Models\Property\PropActiveObjection;
use App\Models\Property\PropActiveSaf;
use App\Models\Property\PropActiveSafsFloor;
use App\Models\Property\PropActiveSafsOwner;
use App\Models\Property\PropApartmentDtl;
use App\Models\Property\PropFloor;
use App\Models\Property\PropOwner;
use App\Models\Property\PropProperty;
use App\Models\UlbWardMaster;
use App\Models\Water\WaterApplicant;
use App\Models\Water\WaterApplication;
use App\Models\Water\WaterApprovalApplicationDetail;
use App\Models\Water\waterAudit;
use App\Models\Water\WaterConnectionCharge;
use App\Models\Water\WaterConnectionThroughMstr;
use App\Models\Water\WaterConnectionThroughMstrs;
use App\Models\Water\WaterConnectionTypeMstr;
use App\Models\Water\WaterConsumer;
use App\Models\Water\WaterConsumerDemand;
use App\Models\Water\WaterConsumerMeter;
use App\Models\Water\WaterConsumerOwner;
use App\Models\Water\WaterOwnerTypeMstr;
use App\Models\Water\WaterParamConnFee;
use App\Models\Water\WaterPenaltyInstallment;
use App\Models\Water\WaterPropertyTypeMstr;
use App\Models\Water\WaterSiteInspection;
use App\Models\Water\WaterSiteInspectionsScheduling;
use App\Models\Water\WaterTran;
use App\Models\Workflows\WfActiveDocument;
use App\Models\Workflows\WfRoleusermap;
use App\Models\Workflows\WfWardUser;
use App\Models\Workflows\WfWorkflow;
use App\Models\Workflows\WfWorkflowrolemap;
use App\Models\WorkflowTrack;
use App\Repository\Water\Concrete\NewConnectionRepository;
use App\Repository\Water\Concrete\WaterNewConnection;
use Illuminate\Http\Request;
use App\Repository\Water\Interfaces\iNewConnection;
use App\Traits\Ward;
use App\Traits\Water\WaterTrait;
use App\Traits\Workflow\Workflow;
use Carbon\Carbon;
use DateTime;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Unique;
use Ramsey\Collection\Collection as CollectionCollection;
use Symfony\Contracts\Service\Attribute\Required;

class NewConnectionController extends Controller
{
    use Ward;
    use Workflow;
    use WaterTrait;

    private iNewConnection $newConnection;
    private $_dealingAssistent;
    private $_waterRoles;
    public function __construct(iNewConnection $newConnection)
    {
        $this->newConnection = $newConnection;
        $this->_dealingAssistent = Config::get('workflow-constants.DEALING_ASSISTENT_WF_ID');
        $this->_waterRoles = Config::get('waterConstaint.ROLE-LABEL');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     * @param \ newApplyRules 
     */
    public function store(newApplyRules $request)
    {
        try {
            return $this->newConnection->store($request);
        } catch (Exception $error) {
            DB::rollBack();
            return responseMsg(false, $error->getMessage(), "");
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    /**
     * |--------------------------------------------- Water workflow -----------------------------------------------|
     */

    /**
     * | Water Inbox
     * | workflow
     * | Repositiory Call
        | Serial No :
        | Working
     */
    public function waterInbox()
    {
        try {
            return $this->newConnection->waterInbox();
        } catch (Exception $error) {
            return responseMsg(false, $error->getMessage(), "");
        }
    }

    /**
     * | Water Outbox
     * | Workflow
     * | Reposotory Call
        | Serial No :
        | Working
     */
    public function waterOutbox()
    {
        try {
            return $this->newConnection->waterOutbox();
        } catch (Exception $error) {
            return responseMsg(false, $error->getMessage(), "");
        }
    }

    /**
     * | Back to citizen Inbox
     * | Workflow
     * | @param req
     * | @var mWfWardUser
     * | @var userId
     * | @var ulbId
     * | @var mDeviceId
     * | @var workflowRoles
     * | @var roleId
     * | @var refWard
     * | @var wardId
     * | @var waterList
     * | @var filterWaterList
     * | @return filterWaterList 
        | Serial No : 
        | Use
     */
    public function btcInbox(Request $req)
    {
        try {
            $mWfWardUser = new WfWardUser();
            $mWfWorkflowRoleMaps = new WfWorkflowrolemap();
            $userId = authUser()->id;
            $ulbId = authUser()->ulb_id;
            $mDeviceId = $req->deviceId ?? "";

            $workflowRoles = $this->getRoleIdByUserId($userId);
            $roleId = $workflowRoles->map(function ($value) {                         // Get user Workflow Roles
                return $value->wf_role_id;
            });

            $refWard = $mWfWardUser->getWardsByUserId($userId);
            $wardId = $refWard->map(function ($value) {
                return $value->ward_id;
            });
            $workflowIds = $mWfWorkflowRoleMaps->getWfByRoleId($roleId)->pluck('workflow_id');

            $waterList = $this->getWaterApplicatioList($workflowIds, $ulbId)
                ->whereIn('water_applications.ward_id', $wardId)
                ->where('parked', true)
                ->orderByDesc('water_applications.id')
                ->get();

            $filterWaterList = collect($waterList)->unique('id');
            return responseMsgs(true, "BTC Inbox List", remove_null($filterWaterList), "", 1.0, "560ms", "POST", $mDeviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", 010123, 1.0, "271ms", "POST", $mDeviceId);
        }
    }

    // Water Special Inbox
    public function waterSpecialInbox(Request $request)
    {
        try {
            return $this->newConnection->waterSpecialInbox($request);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    // Post Next Level
    public function postNextLevel(Request $request)
    {
        try {
            $wfLevels = Config::get('waterConstaint.ROLE-LABEL');
            $request->validate([
                'applicationId'     => 'required',
                'senderRoleId'      => 'required',
                'receiverRoleId'    => 'required',
                'action'            => 'required|In:forward,backward',
                'comment'           => $request->senderRoleId == $wfLevels['BO'] ? 'nullable' : 'required',
            ]);
            return $this->newConnection->postNextLevel($request);
        } catch (Exception $error) {
            DB::rollBack();
            return responseMsg(false, $error->getMessage(), "");
        }
    }

    // Water Application details for the view in workflow
    public function getApplicationsDetails(Request $request)
    {
        try {
            $request->validate([
                'applicationId' => 'required'
            ]);
            return $this->newConnection->getApplicationsDetails($request);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    // Application's Post Escalated
    public function postEscalate(Request $request)
    {
        try {
            $request->validate([
                "escalateStatus" => "required|int",
                "applicationId" => "required|int",
            ]);
            return $this->newConnection->postEscalate($request);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }


    // final Approval or Rejection of the Application
    /**
        | Recheck
     */
    public function approvalRejectionWater(Request $request)
    {
        try {
            $request->validate([
                "applicationId" => "required",
                "status" => "required",
                "comment" => "required"
            ]);
            $waterDetails = WaterApplication::findOrFail($request->applicationId);
            $mWfRoleUsermap = new WfRoleusermap();

            # check the login user is EO or not
            $userId = authUser()->id;
            $workflowId = $waterDetails->workflow_id;
            $getRoleReq = new Request([                                                 // make request to get role id of the user
                'userId' => $userId,
                'workflowId' => $workflowId
            ]);
            $readRoleDtls = $mWfRoleUsermap->getRoleByUserWfId($getRoleReq);
            $roleId = $readRoleDtls->wf_role_id;
            if ($roleId != $waterDetails->finisher) {
                throw new Exception("You are not the Finisher!");
            }
            if ($waterDetails) {
                return $this->newConnection->approvalRejectionWater($request, $roleId);
            }
            throw new Exception("Application dont exist!");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    // Indipendent Comment on the Water Applications
    public function commentIndependent(Request $request)
    {
        try {
            $request->validate([
                'comment' => 'required',
                'applicationId' => 'required',
                'senderRoleId' => 'nullable|integer'
            ]);
            DB::beginTransaction();
            return $this->newConnection->commentIndependent($request);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    // Get Approved Water Appliction 
    /**
        | Recheck / Updated 
     */
    public function approvedWaterApplications(Request $request)
    {
        try {
            if ($request->id) {
                $request->validate([
                    "id" => "nullable|int",
                ]);
                $mWaterConsumerMeter = new WaterConsumerMeter();
                $refConnectionName = Config::get('waterConstaint.METER_CONN_TYPE');
                $consumerDetails = $this->newConnection->getApprovedWater($request);
                $refApplicationId['applicationId'] = $consumerDetails['consumer_id'];
                $metaRequest = new Request($refApplicationId);
                $refDocumentDetails = $this->getUploadDocuments($metaRequest);
                $documentDetails['documentDetails'] = collect($refDocumentDetails)['original']['data'];

                # meter Details 
                $refMeterData = $mWaterConsumerMeter->getMeterDetailsByConsumerId($request->id)->first();
                if (isset($refMeterData)) {
                    switch ($refMeterData['connection_type']) {
                        case (1):
                            if ($refMeterData['meter_status'] == 1) {
                                $connectionName = $refConnectionName['1'];
                            }
                            $connectionName = $refConnectionName['4'];
                            break;
                        case (2):
                            $connectionName = $refConnectionName['2'];
                            break;
                        case (3):
                            $connectionName = $refConnectionName['3'];
                            break;
                    }
                    $consumerDemand['meterDetails'] = $refMeterData;
                    $consumerDemand['connectionName'] = $connectionName;
                    $consumerDetails = $consumerDetails->merge($consumerDemand);
                }
                $consumerDetails = $consumerDetails->merge($documentDetails);
                return responseMsgs(true, "Consumer Details!", remove_null($consumerDetails), "", "01", ".ms", "POST", $request->deviceId);
            }

            # Get all consumer details 
            $mWaterConsumer = new WaterConsumer();
            $approvedWater = $mWaterConsumer->getConsumerDetails();
            $checkExist = $approvedWater->first();
            if ($checkExist) {
                return responseMsgs(true, "Approved Application Details!", $approvedWater, "", "03", "ms", "POST", "");
            }
            throw new Exception("data Not found!");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    // Get the Field fieldVerifiedInbox // recheck
    public function fieldVerifiedInbox(Request $request)
    {
        try {
            return $this->newConnection->fieldVerifiedInbox($request);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    // Back to Citizen  // Recheck
    public function backToCitizen(Request $req)
    {
        $req->validate([
            'applicationId' => 'required|integer',
            'comment' => 'required|string'
        ]);

        try {
            $mWaterApplication = WaterApplication::findOrFail($req->applicationId);
            $WorkflowTrack = new WorkflowTrack();
            $refWorkflowId = Config::get("workflow-constants.WATER_MASTER_ID");
            $metaRequest = new Request([
                "workflowId" => $refWorkflowId
            ]);
            $roleId = $this->getRole($metaRequest)->pluck('wf_role_id');
            $this->btcParamcheck($roleId, $mWaterApplication);

            DB::beginTransaction();
            $initiatorRoleId = $mWaterApplication->initiator_role_id;
            $mWaterApplication->current_role = $initiatorRoleId;
            $mWaterApplication->parked = true;                        //<------  Pending Status true
            $mWaterApplication->save();

            $metaReqs['moduleId'] = Config::get('module-constants.WATER_MODULE_ID');
            $metaReqs['workflowId'] = $mWaterApplication->workflow_id;
            $metaReqs['refTableDotId'] = 'water_applications.id';
            $metaReqs['refTableIdValue'] = $req->applicationId;
            $metaReqs['senderRoleId'] = $roleId;
            $req->request->add($metaReqs);
            $WorkflowTrack->saveTrack($req);

            DB::commit();
            return responseMsgs(true, "Successfully Done", "", "", "1.0", "350ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | check the application for back to citizen case
     * | check for the 
     */
    public function btcParamcheck($roleId, $mWaterApplication)
    {
        $refDealingAssistent = Config::get('waterConstaint.ROLE-LABEL.DA');
        if ($roleId != $refDealingAssistent) {
            throw new Exception("you are not authorized role!");
        }

        if ($mWaterApplication->current_role != $roleId) {
            throw new Exception("the application is not under your possession!");
        }
    }


    // Delete the Application
    /**
        | Caution Dont Perform Delete Operation
     */
    public function deleteWaterApplication(Request $req)
    {
        $req->validate([
            'applicationId' => 'required|integer'
        ]);
        try {
            $userId = auth()->user()->id;
            $mWaterApplication = new WaterApplication();
            $mWaterApplicant = new WaterApplicant();
            $mWaterConnectionCharge = new WaterConnectionCharge();
            $mWaterPenaltyInstallment = new WaterPenaltyInstallment();

            $applicantDetals = $mWaterApplication->getWaterApplicationsDetails($req->applicationId);

            if (!$applicantDetals) {
                throw new Exception("Relted Data or Owner not found!");
            }
            if ($applicantDetals->payment_status == 1) {
                throw new Exception("Your paymnet is done application Cannot be Deleted!");
            }
            if ($applicantDetals->user_id == $userId) {
                DB::beginTransaction();
                $mWaterApplication->deleteWaterApplication($req->applicationId);
                $mWaterApplicant->deleteWaterApplicant($req->applicationId);
                $mWaterConnectionCharge->deleteWaterConnectionCharges($req->applicationId);
                $mWaterPenaltyInstallment->deleteWaterPenelty($req->applicationId);
                DB::commit();
                return responseMsgs(true, "Application Successfully Deleted", "", "", "1.0", "", "POST", $req->deviceId);
            }
            throw new Exception("You'r not the user of this form!");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    // Edit the Water Application
    /**
        | Not / validate the payment status / Check the use / Not used
        | 00 ->
     */
    public function editWaterAppliction(Request $req)
    {
        $req->validate([
            'applicatonId' => 'required|integer',
            'owner' => 'nullable|array',
        ]);

        try {
            $mWaterApplication = new WaterApplication();
            $mWaterApplicant = new WaterApplicant();
            $mWaterConnectionCharge = new WaterConnectionCharge();
            $mWaterPenaltyInstallment = new WaterPenaltyInstallment();
            $repNewConnectionRepository = new NewConnectionRepository();
            $mwaterAudit = new waterAudit();
            $levelRoles = Config::get('waterConstaint.ROLE-LABEL');
            $refApplicationId =  $req->applicatonId;

            $refWaterApplications = $mWaterApplication->getApplicationById($refApplicationId)->firstorFail();
            $this->checkEditParameters($req, $refWaterApplications);


            DB::beginTransaction();
            if ($refWaterApplications->current_role == $levelRoles['BO']) {
                $this->boApplicationEdit($req, $refWaterApplications, $mWaterApplication);
                return responseMsgs(true, "application Modified!", "", "", "01", "ms", "POST", "");
            }

            $refConnectionCharges = $mWaterConnectionCharge->getWaterchargesById($refApplicationId)->firstOrFail();
            $Waterowner = $mWaterApplicant->getOwnerList($refApplicationId)->get();
            $refWaterowner = collect($Waterowner)->map(function ($value, $key) {
                return $value['id'];
            });
            $penaltyInstallment = $mWaterPenaltyInstallment->getPenaltyByApplicationId($refApplicationId)->get();
            $checkPenalty = collect($penaltyInstallment)->first()->values();
            if ($checkPenalty) {
                $refPenaltyInstallment = collect($penaltyInstallment)->map(function ($value) {
                    return  $value['id'];
                });
            }
            $mwaterAudit->saveUpdatedDetailsId($refWaterApplications->id, $refWaterowner, $refConnectionCharges->id, $refPenaltyInstallment);
            $this->deactivateAndUpdateWater($refWaterApplications->id);
            $repNewConnectionRepository->store($req); // here<-----------------------
            DB::commit();
            return responseMsgs(true, "Successfully Updated the Data", "", 010124, 1.0, "308ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", 010124, 1.0, "308ms", "POST", $req->deviceId);
        }
    }

    /**
     * | Check the Water parameter 
     * | @param req
        | 01<- 
        | Not used
     */
    public function checkEditParameters($request, $refApplication)
    {
        $online = Config::get('payment-constants.ONLINE');
        switch ($refApplication) {
            case ($refApplication->apply_from == $online):
                if ($refApplication->current_role) {
                    throw new Exception("Application is already in Workflow!");
                }
                if ($refApplication->user_id != authUser()->id) {
                    throw new Exception("You are not the Autherised Person!");
                }
                if ($refApplication->payment_status == 1) {
                    throw new Exception("Payment has been made Water Cannot be Modified!");
                }
                break;
        }
    }

    /**
     * | Edit the water aplication by Bo
     * | @param req
     * | @param refApplication
    | 01<-
    | Not used
     */
    public function boApplicationEdit($req, $refApplication, $mWaterApplication)
    {
        switch ($refApplication) {
            case ($refApplication->current_role != authUser()->id):
                throw new Exception("You Are Not the Valid Person!");
                break;
        }
        $mWaterApplication->editWaterApplication($req);
    }


    /**
     * | Deactivate the Water Deatils
     * | @param
     * | @param
     * | @param
     * | @param
        | 01 <-
        | Not used
     */
    public function deactivateAndUpdateWater($refWaterApplicationId)
    {
        $mWaterApplication = new WaterApplication();
        $mWaterApplicant = new WaterApplicant();
        $mWaterConnectionCharge = new WaterConnectionCharge();
        $mWaterPenaltyInstallment = new WaterPenaltyInstallment();

        $mWaterApplication->deactivateApplication($refWaterApplicationId);
        $mWaterApplicant->deactivateApplicant($refWaterApplicationId);
        $mWaterConnectionCharge->deactivateCharges($refWaterApplicationId);
        $mWaterPenaltyInstallment->deactivatePenalty($refWaterApplicationId);
    }

    // Citizen view : Get Application Details of viewind
    public function getApplicationDetails(Request $request)
    {
        $request->validate([
            'applicationId' => 'required|integer',
        ]);
        try {
            $mWaterConnectionCharge  = new WaterConnectionCharge();
            $mWaterApplication = new WaterApplication();
            $mWaterApplicant = new WaterApplicant();
            $mWaterTran = new WaterTran();

            # Application Details
            $applicationDetails['applicationDetails'] = $mWaterApplication->fullWaterDetails($request)->first();

            # Document Details
            $metaReqs = [
                'userId' => auth()->user()->id,
                'ulbId' => auth()->user()->ulb_id,
            ];
            $request->request->add($metaReqs);
            $document = $this->getDocToUpload($request);                                                    // get the doc details
            $documentDetails['documentDetails'] = collect($document)['original']['data'];

            # owner details
            $ownerDetails['ownerDetails'] = $mWaterApplicant->getOwnerList($request->applicationId)->get();

            # Payment Details 
            $refAppDetails = collect($applicationDetails)->first();
            $waterTransaction = $mWaterTran->getTransNo($refAppDetails->id, $refAppDetails->connection_type)->first();
            $waterTransDetail['waterTransDetail'] = $waterTransaction;

            # calculation details
            $charges = $mWaterConnectionCharge->getWaterchargesById($refAppDetails['id'])
                ->where('paid_status', 0)
                ->first();
            if ($charges) {
                $calculation['calculation'] = [
                    'connectionFee' => $charges['conn_fee'],
                    'penalty' => $charges['penalty'],
                    'totalAmount' => $charges['amount'],
                    'chargeCatagory' => $charges['charge_category'],
                    'paidStatus' => $charges['paid_status']
                ];
                $waterTransDetail = array_merge($waterTransDetail, $calculation);
            }
            $returnData = array_merge($applicationDetails, $ownerDetails, $documentDetails, $waterTransDetail);
            return responseMsgs(true, "Application Data!", remove_null($returnData), "", "", "", "Post", "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    // Application details
    /**
        | Working 
     */
    public function uploadWaterDoc(Request $req)
    {
        $req->validate([
            "applicationId" => "required|numeric",
            "document" => "required|mimes:pdf,jpeg,png,jpg,gif",
            "docCode" => "required",
            "docCategory" => "required|string",  # here
            "ownerId" => "nullable|numeric"
        ]);

        try {
            $metaReqs = array();
            $docUpload = new DocUpload;
            $mWfActiveDocument = new WfActiveDocument();
            $mWaterApplication = new WaterApplication();
            $relativePath = Config::get('waterConstaint.WATER_RELATIVE_PATH');
            $refmoduleId = Config::get('module-constants.WATER_MODULE_ID');

            $getWaterDetails = $mWaterApplication->getWaterApplicationsDetails($req->applicationId);
            $refImageName = $req->docRefName;
            $refImageName = $getWaterDetails->id . '-' . str_replace(' ', '_', $refImageName);
            $document = $req->document;
            $imageName = $docUpload->upload($refImageName, $document, $relativePath);

            $metaReqs = [
                'moduleId'     => $refmoduleId,
                'activeId'     => $getWaterDetails->id,
                'workflowId'   => $getWaterDetails->workflow_id,
                'ulbId'        => $getWaterDetails->ulb_id,
                'relativePath' => $relativePath,
                'document'      => $imageName,
                'docCode'      => $req->docCode,
                'ownerDtlId'  => $req->ownerId,
                'docCategory'  => $req->docCategory
            ];

            $ifDocExist = $mWfActiveDocument->ifDocExists($getWaterDetails->id, $getWaterDetails->workflow_id, $refmoduleId, $req->docCode, $req->docCategory, $req->ownerId);   // Checking if the document is already existing or not
            $metaReqs = new Request($metaReqs);
            if (collect($ifDocExist)->isEmpty())
                $mWfActiveDocument->postDocuments($metaReqs);
            else
                $mWfActiveDocument->editDocuments($ifDocExist, $metaReqs);

            #check full doc upload
            $refCheckDocument = $this->checkFullDocUpload($req);

            # Update the Doc Upload Satus in Application Table
            if ($refCheckDocument->contains(false)) {
                $mWaterApplication->deactivateUploadStatus($req->applicationId);
            } else {
                $this->updateWaterStatus($req, $getWaterDetails);
            }

            return responseMsgs(true, "Document Uploadation Successful", "", "", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }


    /**
     * | Caheck the Document if Fully Upload or not
     * | @param req
    | Up
     */
    public function checkFullDocUpload($req)
    {
        # Check the Document upload Status
        $documentList = $this->getDocToUpload($req);
        $refDoc = collect($documentList)['original']['data']['documentsList'];
        $refOwnerDoc = collect($documentList)['original']['data']['ownersDocList'];
        $checkDocument = collect($refDoc)->map(function ($value, $key) {
            if ($value['isMadatory'] == 1) {
                $doc = collect($value['uploadDoc'])->first();
                if (is_null($doc)) {
                    return false;
                }
                return true;
            }
            return true;
        });
        $checkOwnerDocument = collect($refOwnerDoc)->map(function ($value, $key) {
            if ($value['isMadatory'] == 1) {
                $doc = collect($value['uploadDoc'])->first();
                if (is_null($doc)) {
                    return false;
                }
                return true;
            }
            return true;
        });
        return $checkDocument->merge($checkOwnerDocument);
    }


    /**
     * | Updating the water Application Status
     * | @param req
     * | @param application
        | Up 
        | Check the concept of auto forward
     */
    public function updateWaterStatus($req, $application)
    {
        $mWaterApplication = new WaterApplication();
        $waterRoles = $this->_waterRoles;
        $mWaterApplication->activateUploadStatus($req->applicationId);
        # Auto forward to Bo 
        // if ($application->payment_status == 1) {
        //     $mWaterApplication->updateCurrentRoleForDa($req->applicationId, $waterRoles);
        // }
    }


    // Get the upoaded docunment
    /**
        | Working
     */
    public function getUploadDocuments(Request $req)
    {
        $req->validate([
            'applicationId' => 'required|numeric'
        ]);
        try {
            $mWfActiveDocument = new WfActiveDocument();
            $mWaterApplication = new WaterApplication();
            $moduleId = Config::get('module-constants.WATER_MODULE_ID');

            $waterDetails = $mWaterApplication->getApplicationById($req->applicationId)->first();
            if (!$waterDetails)
                throw new Exception("Application Not Found for this application Id");

            $workflowId = $waterDetails->workflow_id;
            $documents = $mWfActiveDocument->getWaterDocsByAppNo($req->applicationId, $workflowId, $moduleId);
            $returnData = collect($documents)->map(function ($value) {
                $path =  $this->readDocumentPath($value->ref_doc_path);
                $value->doc_path = !empty(trim($value->ref_doc_path)) ? $path : null;
                return $value;
            });
            return responseMsgs(true, "Uploaded Documents", remove_null($returnData), "010102", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "010202", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    // Get the document to be upoaded with list of dock uploaded 
    /**
        | Working / Citizen Upload
     */
    public function getDocToUpload(Request $request)
    {
        $request->validate([
            'applicationId' => 'required|numeric'
        ]);
        try {
            $refApplication         = (array)null;
            $refOwneres             = (array)null;
            $requiedDocs            = (array)null;
            $testOwnersDoc          = (array)null;
            $data                   = (array)null;
            $refWaterNewConnection  = new WaterNewConnection();
            $refWfActiveDocument    = new WfActiveDocument();
            $mWaterConnectionCharge = new WaterConnectionCharge();
            $moduleId               = Config::get('module-constants.WATER_MODULE_ID');

            $connectionId = $request->applicationId;
            $refApplication = WaterApplication::where("status", 1)->find($connectionId);
            if (!$refApplication) {
                throw new Exception("Application Not Found!");
            }

            $connectionCharges = $mWaterConnectionCharge->getWaterchargesById($connectionId)->first();
            $connectionCharges['type'] = Config::get('waterConstaint.New_Connection');
            $connectionCharges['applicationNo'] = $refApplication->application_no;
            $connectionCharges['applicationId'] = $refApplication->id;

            $requiedDocType = $refWaterNewConnection->getDocumentTypeList($refApplication);  # get All Related Document Type List
            $refOwneres = $refWaterNewConnection->getOwnereDtlByLId($refApplication->id);    # get Owneres List
            $ownerList = collect($refOwneres)->map(function ($value) {
                $return['applicant_name'] = $value['applicant_name'];
                $return['ownerID'] = $value['id'];
                return $return;
            });
            foreach ($requiedDocType as $val) {
                $doc = (array) null;
                $doc["ownerName"] = $ownerList;
                $doc['docName'] = $val->doc_for;
                $refDocName  = str_replace('_', ' ', $val->doc_for);
                $doc["refDocName"] = ucwords(strtolower($refDocName));
                $doc['isMadatory'] = $val->is_mandatory;
                $ref['docValue'] = $refWaterNewConnection->getDocumentList($val->doc_for);  # get All Related Document List
                $doc['docVal'] = $docFor = collect($ref['docValue'])->map(function ($value) {
                    $refDoc = $value['doc_name'];
                    $refText = str_replace('_', ' ', $refDoc);
                    $value['dispayName'] = ucwords(strtolower($refText));
                    return $value;
                });
                $docFor = collect($ref['docValue'])->map(function ($value) {
                    return $value['doc_name'];
                });

                $doc['uploadDoc'] = [];
                $uploadDoc = $refWfActiveDocument->getDocByRefIdsDocCode($refApplication->id, $refApplication->workflow_id, $moduleId, $docFor); # Check Document is Uploaded Of That Type
                if (isset($uploadDoc->first()->doc_path)) {
                    $path = $refWaterNewConnection->readDocumentPath($uploadDoc->first()->doc_path);
                    $doc["uploadDoc"]["doc_path"] = !empty(trim($uploadDoc->first()->doc_path)) ? $path : null;
                    $doc["uploadDoc"]["doc_code"] = $uploadDoc->first()->doc_code;
                    $doc["uploadDoc"]["verify_status"] = $uploadDoc->first()->verify_status;
                }
                array_push($requiedDocs, $doc);
            }
            foreach ($refOwneres as $key => $val) {
                $docRefList = ["CONSUMER_PHOTO", "ID_PROOF"];
                foreach ($docRefList as $key => $refOwnerDoc) {
                    $doc = (array) null;
                    $testOwnersDoc[] = (array) null;
                    $doc["ownerId"] = $val->id;
                    $doc["ownerName"] = $val->applicant_name;
                    $doc["docName"]   = $refOwnerDoc;
                    $refDocName  = str_replace('_', ' ', $refOwnerDoc);
                    $doc["refDocName"] = ucwords(strtolower($refDocName));
                    $doc['isMadatory'] = 1;
                    $ref['docValue'] = $refWaterNewConnection->getDocumentList([$refOwnerDoc]);   #"CONSUMER_PHOTO"
                    $doc['docVal'] = $docFor = collect($ref['docValue'])->map(function ($value) {
                        $refDoc = $value['doc_name'];
                        $refText = str_replace('_', ' ', $refDoc);
                        $value['dispayName'] = ucwords(strtolower($refText));
                        return $value;
                    });
                    $refdocForId = collect($ref['docValue'])->map(function ($value, $key) {
                        return $value['doc_name'];
                    });
                    $doc['uploadDoc'] = [];
                    $uploadDoc = $refWfActiveDocument->getOwnerDocByRefIdsDocCode($refApplication->id, $refApplication->workflow_id, $moduleId, $refdocForId, $doc["ownerId"]); # Check Document is Uploaded Of That Type
                    if (isset($uploadDoc->first()->doc_path)) {
                        $path = $refWaterNewConnection->readDocumentPath($uploadDoc->first()->doc_path);
                        $doc["uploadDoc"]["doc_path"] = !empty(trim($uploadDoc->first()->doc_path)) ? $path : null;
                        $doc["uploadDoc"]["doc_code"] = $uploadDoc->first()->doc_code;
                        $doc["uploadDoc"]["verify_status"] = $uploadDoc->first()->verify_status;
                    }
                    array_push($testOwnersDoc, $doc);
                }
            }
            $ownerDoc = collect($testOwnersDoc)->filter()->values();

            $data["documentsList"]  = $requiedDocs;
            $data["ownersDocList"]  = $ownerDoc;
            $data['doc_upload_status'] = $refApplication['doc_upload_status'];
            $data['connectionCharges'] = $connectionCharges;
            return responseMsg(true, "Document Uploaded!", $data);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }

    /**
     * | Serch the holding and the saf details
     * | Serch the property details for filling the water Application Form
     * | @param request
     * | 01
     */
    public function getSafHoldingDetails(Request $request)
    {
        $request->validate([
            'connectionThrough' => 'required|int|in:1,2',
            'id' => 'required',
            'ulbId' => 'required'
        ]);
        try {
            $mPropProperty = new PropProperty();
            $mPropOwner = new PropOwner();
            $mPropFloor = new PropFloor();
            $mPropActiveSafOwners = new PropActiveSafsOwner();
            $mPropActiveSafsFloor = new PropActiveSafsFloor();
            $mPropActiveSaf = new PropActiveSaf();
            $key = $request->connectionThrough;
            $refTenanted = Config::get('PropertyConstaint.OCCUPANCY-TYPE.TENANTED');

            switch ($key) {
                case ("1"):
                    $application = collect($mPropProperty->getPropByHolding($request->id, $request->ulbId));
                    $checkExist = collect($application)->first();
                    if (!$checkExist) {
                        throw new Exception("Data According to Holding Not Found!");
                    }
                    if (isset($application['apartment_details_id'])) {
                        $appartmentData = $this->getAppartmentDetails($key, $application);
                        return responseMsgs(true, "related Details!", $appartmentData, "", "", "", "POST", "");
                        break;
                    }
                    # collecting all data 
                    $floorDetails = $mPropFloor->getFloorsByPropId($application['id']);
                    $builtupArea = collect($floorDetails)->sum('builtup_area');
                    $areaInSqft['areaInSqFt'] = $builtupArea;
                    $propUsageType = $this->getPropUsageType($request, $application['id']);
                    $occupancyOwnerType = collect($mPropFloor->getOccupancyType($application['id'], $refTenanted));
                    $owners['owners'] = collect($mPropOwner->getOwnerByPropId($application['id']));

                    # merge all data for return 
                    $details = $application->merge($areaInSqft)->merge($owners)->merge($occupancyOwnerType)->merge($propUsageType);
                    return responseMsgs(true, "related Details!", $details, "", "", "", "POST", "");
                    break;

                case ("2"):
                    $application = collect($mPropActiveSaf->getSafDtlBySafUlbNo($request->id, $request->ulbId));
                    $checkExist = collect($application)->first();
                    if (!$checkExist) {
                        throw new Exception("Data According to SAF Not Found!");
                    }
                    if (isset($application['apartment_details_id'])) {
                        $appartmentData = $this->getAppartmentDetails($key, $application);
                        return responseMsgs(true, "related Details!", $appartmentData, "", "", "", "POST", "");
                        break;
                    }
                    # collecting all data 
                    $floorDetails = $mPropActiveSafsFloor->getSafFloorsBySafId($application['id']);
                    $areaInSqft['areaInSqFt'] = collect($floorDetails)->sum('builtup_area');
                    $safUsageType = $this->getPropUsageType($request, $application['id']);
                    $occupancyOwnerType = collect($mPropActiveSafsFloor->getOccupancyType($application['id'], $refTenanted));
                    $owners['owners'] = collect($mPropActiveSafOwners->getOwnerDtlsBySafId($application['id']));

                    # merge all data for return 
                    $details = $application->merge($areaInSqft)->merge($owners)->merge($occupancyOwnerType)->merge($safUsageType);
                    return responseMsgs(true, "related Details!", $details, "", "", "", "POST", "");
                    break;
            }
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Get Usage type according to holding
     * | Calling function : for the search of the property usage type 01.02
     */
    public function getPropUsageType($request, $id)
    {
        $refPropertyTypeId = config::get('waterConstaint.PROPERTY_TYPE');
        switch ($request->connectionThrough) {
            case ('1'):
                $mPropFloor = new PropFloor();
                $usageCatagory = $mPropFloor->getPropUsageCatagory($id);
                break;
            case ('2'):
                $mPropActiveSafsFloor = new PropActiveSafsFloor();
                $usageCatagory = $mPropActiveSafsFloor->getSafUsageCatagory($id);
        }

        $usage = collect($usageCatagory)->map(function ($value, $key) use ($id, $refPropertyTypeId) {
            $var = $value['usage_code'];
            switch (true) {
                case ($var == 'A'):
                    return [
                        'id'        => $refPropertyTypeId['Residential'],
                        'usageType' => 'Residential'
                    ];
                    break;
                case ($var == 'F'):
                    return [
                        'id'        => $refPropertyTypeId['Industrial'],
                        'usageType' => 'Industrial'
                    ];
                    break;
                case ($var == 'G' || $var == 'I'):
                    return [
                        'id'        => $refPropertyTypeId['Government'],
                        'usageType' => 'Government & PSU'
                    ];
                    break;
                case ($var == 'B' || $var == 'C' || $var == 'D' || $var == 'E'):
                    return [
                        'id'        => $refPropertyTypeId['Commercial'],
                        'usageType' => 'Commercial'
                    ];
                    break;
                case ($var == 'H' || $var == 'J' || $var == 'K' || $var == 'L'):
                    return [
                        'id'        => $refPropertyTypeId['Institutional'],
                        'usageType' => 'Institutional'
                    ];
                    break;
                case ($var == 'M'):   //<---------------- Check wether the property (M) belongs to the commercial catagory
                    return [
                        'id'        => $refPropertyTypeId['Commercial'],
                        'usageType' => 'Other / Commercial'
                    ];
                    break;
            }
        });
        $returnData['usageType'] = $usage->unique()->values();
        return $returnData;
    }

    /**
     * | Get appartment details 
     * | @param propData
     * | @param request
     */
    public function getAppartmentDetails($key, $propData)
    {
        $mPropFloor = new PropFloor();
        $mPropProperty = new PropProperty();
        $mPropOwner = new PropOwner();
        $mPropActiveSaf = new PropActiveSaf();
        $mPropActiveSafsFloor = new PropActiveSafsFloor();
        $mPropActiveSafsOwner = new PropActiveSafsOwner();
        $refPropertyTypeId = Config::get('waterConstaint.PROPERTY_TYPE');
        $apartmentId = $propData['apartment_details_id'];

        switch ($key) {
            case ('1'):
                $propertyDetails = $mPropProperty->getPropByApartmentId($apartmentId)->get();
                $propertyIds = collect($propertyDetails)->pluck('id');
                $floorDetails = $mPropFloor->getAppartmentFloor($propertyIds)->get();
                $totalBuildupArea = collect($floorDetails)->sum('builtup_area');

                $returnData['areaInSqFt'] = $totalBuildupArea;
                $returnData['usageType'][] = [
                    'id'        => $refPropertyTypeId['Apartment'],
                    'usageType' => 'Apartment'
                ];
                $returnData['tenanted'] = false;
                $returnData['owners'] = collect($mPropOwner->getOwnerByPropId($propData['id']));
                return $propData->merge($returnData);
                break;

            case ('2'):
                $safDetails = $mPropActiveSaf->getSafByApartmentId($apartmentId)->get(); # here
                $safIds = collect($safDetails)->pluck('id');
                $floorDetails = $mPropActiveSafsFloor->getSafAppartmentFloor($safIds)->get();
                $totalBuildupArea = collect($floorDetails)->sum('builtup_area');

                $returnData['areaInSqFt'] = $totalBuildupArea;
                $returnData['usageType'][] = [
                    'id'        => $refPropertyTypeId['Apartment'],
                    'usageType' => 'Apartment'
                ];
                $returnData['tenanted'] = false;
                $returnData['owners'] = collect($mPropActiveSafsOwner->getOwnerDtlsBySafId($propData['id']));
                return $propData->merge($returnData);
                break;
        }
    }

    /**
        |-------------------------------------------------------------------------------------------------------|
     */

    /**
     * |---------------------------- Get Document Lists To Upload ----------------------------|
     * | @param req "applicationId"
     * | @var mWaterApplication "Model for WaterApplication"
     * | @var mWaterApplicant "Model for WaterApplicant"
     * | @var refWaterApplication "Contain the detail of water Application"
     * | @var refWaterApplicant "Contain the list of owners"
     * | @var waterTypeDocs "contain the list of Doc to Upload"
     * | @var waterOwnerDocs "Contain the list of owner Doc to Upload"
     * | @var totalDocLists "Application's Doc details"
     * | @return totalDocLists "Collective Data of Doc is returned"
     * | Doc Upload for the Workflow
     * | 01
        | RECHECK
        | Serial No : 
     */
    public function getDocList(Request $req)
    {
        $req->validate([
            'applicationId' => 'required|numeric'
        ]);
        try {
            $mWaterApplication = new WaterApplication();
            $mWaterApplicant = new WaterApplicant();
            $refWaterApplication = $mWaterApplication->getApplicationById($req->applicationId)->first();                      // Get Saf Details
            if (!$refWaterApplication) {
                throw new Exception("Application Not Found for this id");
            }
            $refWaterApplicant = $mWaterApplicant->getOwnerList($req->applicationId)->get();
            $documentList = $this->getWaterDocLists($refWaterApplication);
            $waterTypeDocs['listDocs'] = collect($documentList)->map(function ($value, $key) use ($refWaterApplication) {
                return $filteredDocs = $this->filterDocument($value, $refWaterApplication)->first();
            });

            $waterOwnerDocs['ownerDocs'] = collect($refWaterApplicant)->map(function ($owner) use ($refWaterApplication) {
                return $this->getOwnerDocLists($owner, $refWaterApplication);
            });
            $waterOwnerDocs;

            $totalDocLists = collect($waterTypeDocs)->merge($waterOwnerDocs);
            $totalDocLists['docUploadStatus'] = $refWaterApplication->doc_upload_status;
            $totalDocLists['docVerifyStatus'] = $refWaterApplication->doc_status;
            return responseMsgs(true, "", remove_null($totalDocLists), "010203", "", "", 'POST', "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "010203", "1.0", "", 'POST', "");
        }
    }


    /**
     * |---------------------------- Filter The Document For Viewing ----------------------------|
     * | @param documentList
     * | @param refWaterApplication
     * | @param ownerId
     * | @var mWfActiveDocument
     * | @var applicationId
     * | @var workflowId
     * | @var moduleId
     * | @var uploadedDocs
     * | Calling Function 01.01.01/ 01.02.01
     */
    public function filterDocument($documentList, $refWaterApplication, $ownerId = null)
    {
        $mWfActiveDocument = new WfActiveDocument();
        $applicationId = $refWaterApplication->id;
        $workflowId = $refWaterApplication->workflow_id;
        $moduleId = Config::get('module-constants.WATER_MODULE_ID');
        $uploadedDocs = $mWfActiveDocument->getDocByRefIds($applicationId, $workflowId, $moduleId);

        $explodeDocs = collect(explode('#', $documentList->requirements));
        $filteredDocs = $explodeDocs->map(function ($explodeDoc) use ($uploadedDocs, $ownerId, $documentList) {
            $document = explode(',', $explodeDoc);
            $key = array_shift($document);
            $label = array_shift($document);
            $documents = collect();

            collect($document)->map(function ($item) use ($uploadedDocs, $documents, $ownerId, $documentList) {
                $uploadedDoc = $uploadedDocs->where('doc_code', $item)
                    ->where('owner_dtl_id', $ownerId)
                    ->first();
                if ($uploadedDoc) {
                    $path = $this->readDocumentPath($uploadedDoc->doc_path);
                    $fullDocPath = !empty(trim($uploadedDoc->doc_path)) ? $path : null;
                    $response = [
                        "uploadedDocId" => $uploadedDoc->id ?? "",
                        "documentCode" => $item,
                        "ownerId" => $uploadedDoc->owner_dtl_id ?? "",
                        "docPath" => $fullDocPath ?? "",
                        "verifyStatus" => $uploadedDoc->verify_status ?? "",
                        "remarks" => $uploadedDoc->remarks ?? "",
                    ];
                    $documents->push($response);
                }
            });
            $reqDoc['docType'] = $key;
            $reqDoc['uploadedDoc'] = $documents->last();
            $reqDoc['docName'] = substr($label, 1, -1);
            // $reqDoc['refDocName'] = substr($label, 1, -1);

            $reqDoc['masters'] = collect($document)->map(function ($doc) use ($uploadedDocs) {
                $uploadedDoc = $uploadedDocs->where('doc_code', $doc)->first();
                $strLower = strtolower($doc);
                $strReplace = str_replace('_', ' ', $strLower);
                if (isset($uploadedDoc)) {
                    $path =  $this->readDocumentPath($uploadedDoc->doc_path);
                    $fullDocPath = !empty(trim($uploadedDoc->doc_path)) ? $path : null;
                }
                $arr = [
                    "documentCode" => $doc,
                    "docVal" => ucwords($strReplace),
                    "uploadedDoc" => $fullDocPath ?? "",
                    "uploadedDocId" => $uploadedDoc->id ?? "",
                    "verifyStatus'" => $uploadedDoc->verify_status ?? "",
                    "remarks'" => $uploadedDoc->remarks ?? "",
                ];
                return $arr;
            });
            return $reqDoc;
        });
        return $filteredDocs;
    }

    /**
     * |---------------------------- List of the doc to upload ----------------------------|
     * | Calling function
     * | 01.01
     */
    public function getWaterDocLists($application)
    {
        $mRefReqDocs = new RefRequiredDocument();
        $moduleId = Config::get('module-constants.WATER_MODULE_ID');

        $type   = ["METER_BILL", "ADDRESS_PROOF", "OTHER"];
        if (in_array($application->connection_through, [1, 2]))      // Holding No, SAF No
        {
            $type[] = "HOLDING_PROOF";
        }
        if (strtoupper($application->category) == "BPL")                // FOR BPL APPLICATION
        {
            $type[] = "BPL";
        }
        if ($application->property_type_id == 2)                        // FOR COMERCIAL APPLICATION
        {
            $type[] = "COMMERCIAL";
        }
        if ($application->apply_from != "Online")                       // Online
        {
            $type[]  = "FORM_SCAN_COPY";
        }
        if ($application->owner_type == 2)                              // In case of Tanent
        {
            $type[]  = "TENANT";
        }
        if ($application->property_type_id == 7)                        // Appartment
        {
            $type[]  = "APPARTMENT";
        }
        return $documentList = $mRefReqDocs->getCollectiveDocByCode($moduleId, $type);
    }


    /**
     * |---------------------------- Get owner Doc list ----------------------------|
     * | Calling Function
     * | 01.02
     */
    public function getOwnerDocLists($refOwners, $application)
    {
        $mRefReqDocs = new RefRequiredDocument();
        $mWfActiveDocument = new WfActiveDocument();
        $moduleId = Config::get('module-constants.WATER_MODULE_ID');
        $type   = ["ID_PROOF", "CONSUMER_PHOTO"];

        $documentList = $mRefReqDocs->getCollectiveDocByCode($moduleId, $type);
        $ownerDocList['documents'] = collect($documentList)->map(function ($value, $key) use ($application, $refOwners) {
            return $filteredDocs = $this->filterDocument($value, $application, $refOwners['id'])->first();
        });
        if (!empty($documentList)) {
            $ownerPhoto = $mWfActiveDocument->getWaterOwnerPhotograph($application['id'], $application->workflow_id, $moduleId, $refOwners['id']);
            if ($ownerPhoto) {
                $path =  $this->readDocumentPath($ownerPhoto->doc_path);
                $fullDocPath = !empty(trim($ownerPhoto->doc_path)) ? $path : null;
            }
            $ownerDocList['ownerDetails'] = [
                'ownerId' => $refOwners['id'],
                'name' => $refOwners['applicant_name'],
                'mobile' => $refOwners['mobile_no'],
                'guardian' => $refOwners['guardian_name'],
                'uploadedDoc' => $fullDocPath ?? "",
                'verifyStatus' => $ownerPhoto->verify_status ?? ""
            ];
            return $ownerDocList;
        }
    }

    /**
     * |----------------------------- Read the server url ------------------------------|
     */
    public function readDocumentPath($path)
    {
        $path = (config('app.url') . "/" . $path);
        return $path;
    }


    /**
     * |---------------------------- Search Application ----------------------------|
     * | Search Application using provided condition For the Admin 
     */
    public function searchWaterConsumer(Request $request)
    {
        $request->validate([
            'filterBy' => 'required',
            'parameter' => 'required'
        ]);
        try {

            $mWaterConsumer = new WaterConsumer();
            $key = $request->filterBy;
            $paramenter = $request->parameter;
            $string = preg_replace("/([A-Z])/", "_$1", $key);
            $refstring = strtolower($string);

            switch ($key) {
                case ("consumerNo"):
                    $waterReturnDetails = $mWaterConsumer->getDetailByConsumerNo($refstring, $paramenter);
                    $checkVal = collect($waterReturnDetails)->first();
                    if (!$checkVal)
                        throw new Exception("Data Not Found!");
                    break;
                case ("holdingNo"):
                    $waterReturnDetails = $mWaterConsumer->getDetailByConsumerNo($refstring, $paramenter);
                    $checkVal = collect($waterReturnDetails)->first();
                    if (!$checkVal)
                        throw new Exception("Data Not Found!");
                    break;
                case ("safNo"):
                    $waterReturnDetails = $mWaterConsumer->getDetailByConsumerNo($refstring, $paramenter);
                    $checkVal = collect($waterReturnDetails)->first();
                    if (!$checkVal)
                        throw new Exception("Data Not Found!");
                    break;
                case ("applicantName"):
                    $waterReturnDetails = $mWaterConsumer->getDetailByOwnerDetails($refstring, $paramenter);
                    $checkVal = collect($waterReturnDetails)->first();
                    if (!$checkVal)
                        throw new Exception("Data Not Found!");
                    break;
                case ('mobileNo'):
                    $waterReturnDetails = $mWaterConsumer->getDetailByOwnerDetails($refstring, $paramenter);
                    $checkVal = collect($waterReturnDetails)->first();
                    if (!$checkVal)
                        throw new Exception("Data Not Found!");
                    break;
            }
            return responseMsgs(true, "Water Consumer Data According To Parameter!", $waterReturnDetails, "", "01", "652 ms", "POST", "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Search the Active Application 
     * | @param request
     * | @var 
     * | @return  
     */
    public function getActiveApplictaions(Request $request)
    {
        $request->validate([
            'filterBy' => 'required',
            'applicationNo' => 'required'
        ]);
        $key = $request->filterBy;
        $applicationNo = $request->applicationNo;
        $connectionTypes = Config::get('waterConstaint.CONNECTION_TYPE');
        try {
            switch ($key) {
                case ("newConnection"):
                    $mWaterApplicant = new WaterApplication();
                    $returnData = $mWaterApplicant->getDetailsByApplicationNo($connectionTypes['NEW_CONNECTION'], $applicationNo);
                    break;
                case ("regularization"):
                    $mWaterApplicant = new WaterApplication();
                    $returnData = $mWaterApplicant->getDetailsByApplicationNo($connectionTypes['REGULAIZATION'], $applicationNo);
                    break;
            }
            return responseMsgs(true, "List of Appication!", $returnData, "", "01", "723 ms", "POST", "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }


    /**
     * | Document Verify Reject
     * | @param 
     * | @var 
     * | @return 
     */
    public function docVerifyRejects(Request $req)
    {
        $req->validate([
            'id'            => 'required|digits_between:1,9223372036854775807',
            'applicationId' => 'required|digits_between:1,9223372036854775807',
            'docRemarks'    =>  $req->docStatus == "Rejected" ? 'required|regex:/^[a-zA-Z1-9][a-zA-Z1-9\. \s]+$/' : "nullable",
            'docStatus'     => 'required|in:Verified,Rejected'
        ]);

        try {
            # Variable Assignments
            $mWfDocument = new WfActiveDocument();
            $mWaterApplication = new WaterApplication();
            $mWfRoleusermap = new WfRoleusermap();
            $wfDocId = $req->id;
            $userId = authUser()->id;
            $applicationId = $req->applicationId;
            $wfLevel = Config::get('waterConstaint.ROLE-LABEL');

            # validating application
            $waterApplicationDtl = $mWaterApplication->getApplicationById($applicationId)
                ->firstOrFail();
            if (!$waterApplicationDtl || collect($waterApplicationDtl)->isEmpty())
                throw new Exception("Application Details Not Found");

            # validating roles
            $waterReq = new Request([
                'userId'        => $userId,
                'workflowId'    => $waterApplicationDtl['workflow_id']
            ]);
            $senderRoleDtls = $mWfRoleusermap->getRoleByUserWfId($waterReq);
            if (!$senderRoleDtls || collect($senderRoleDtls)->isEmpty())
                throw new Exception("Role Not Available");

            # validating role for DA
            $senderRoleId = $senderRoleDtls->wf_role_id;
            if ($senderRoleId != $wfLevel['DA'])                                    // Authorization for Dealing Assistant Only
                throw new Exception("You are not Authorized");

            # validating if full documet is uploaded
            $ifFullDocVerified = $this->ifFullDocVerified($applicationId);          // (Current Object Derivative Function 0.1)
            if ($ifFullDocVerified == 1)
                throw new Exception("Document Fully Verified");

            DB::beginTransaction();
            if ($req->docStatus == "Verified") {
                $status = 1;
            }
            if ($req->docStatus == "Rejected") {
                # For Rejection Doc Upload Status and Verify Status will disabled
                $status = 2;
                $waterApplicationDtl->doc_upload_status = 0;
                $waterApplicationDtl->doc_status = 0;
                $waterApplicationDtl->save();
            }
            $reqs = [
                'remarks'           => $req->docRemarks,
                'verify_status'     => $status,
                'action_taken_by'   => $userId
            ];
            $mWfDocument->docVerifyReject($wfDocId, $reqs);
            $ifFullDocVerifiedV1 = $this->ifFullDocVerified($applicationId);
            if ($ifFullDocVerifiedV1 == 1) {                                        // If The Document Fully Verified Update Verify Status
                $mWaterApplication->updateAppliVerifyStatus($applicationId);
            }
            DB::commit();
            return responseMsgs(true, $req->docStatus . " Successfully", "", "010204", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "010204", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Check if the Document is Fully Verified or Not (0.1) | up
     * | @param
     * | @var 
     * | @return
        | Use
     */
    public function ifFullDocVerified($applicationId)
    {
        $mWaterApplication = new WaterApplication();
        $mWfActiveDocument = new WfActiveDocument();
        $refapplication = $mWaterApplication->getApplicationById($applicationId)
            ->firstOrFail();

        $refReq = [
            'activeId'      => $applicationId,
            'workflowId'    => $refapplication['workflow_id'],
            'moduleId'      => Config::get('module-constants.WATER_MODULE_ID')
        ];

        $req = new Request($refReq);
        $refDocList = $mWfActiveDocument->getDocsByActiveId($req);
        $ifPropDocUnverified = $refDocList->contains('verify_status', 0);
        if ($ifPropDocUnverified == true)
            return 0;
        else
            return 1;
    }


    /**
     * | Admin view : Get Application Details of viewind
     * | @param 
     * | @var 
     * | @return 
        | Serial No : 
        | Used Only for new Connection or New Regulization
     */
    public function getApplicationDetailById(Request $request)
    {
        $request->validate([
            'applicationId' => 'required|integer',
        ]);
        try {
            $mWaterConnectionCharge  = new WaterConnectionCharge();
            $mWaterApplication = new WaterApplication();
            $mWaterPenaltyInstallment = new WaterPenaltyInstallment();
            $mWaterTran = new WaterTran();

            # Application Details
            $applicationDetails['applicationDetails'] = $mWaterApplication->fullWaterDetails($request)->first();

            # Payment Details 
            $refAppDetails = collect($applicationDetails)->first();
            $waterTransaction = $mWaterTran->getTransNo($refAppDetails->id, $refAppDetails->connection_type)->get();
            $waterTransDetail['waterTransDetail'] = $waterTransaction;

            # calculation details
            $charges = $mWaterConnectionCharge->getWaterchargesById($refAppDetails['id'])
                ->firstOrFail();

            if ($charges['paid_status'] == 0) {
                $calculation['calculation'] = [
                    'connectionFee'     => $charges['conn_fee'],
                    'penalty'           => $charges['penalty'],
                    'totalAmount'       => $charges['amount'],
                    'chargeCatagory'    => $charges['charge_category'],
                    'paidStatus'        => $charges['paid_status']
                ];
                $waterTransDetail = array_merge($calculation, $waterTransDetail);
            } else {
                $penalty['penaltyInstallments'] = $mWaterPenaltyInstallment->getPenaltyByApplicationId($request->applicationId)
                    ->where('paid_status', 0)
                    ->get();
                $refPenalty = collect($penalty['penaltyInstallments'])->first();
                if ($refPenalty) {
                    $penaltyAmount = collect($penalty['penaltyInstallments'])->map(function ($value) {
                        return $value['balance_amount'];
                    })->sum();

                    $calculation['calculation'] = [
                        'connectionFee'     => 0.00,           # Static
                        'penalty'           => $penaltyAmount,
                        'totalAmount'       => $penaltyAmount,
                        'chargeCatagory'    => $charges['charge_category'],
                        'paidStatus'        => $charges['paid_status']
                    ];
                    $waterTransDetail = array_merge($calculation, $waterTransDetail);
                }
            }

            # penalty Data 
            if ($charges['penalty'] > 0) {
                $ids = null;
                $penalty['penaltyInstallments'] = $mWaterPenaltyInstallment->getPenaltyByApplicationId($request->applicationId)
                    ->where('paid_status', 0)
                    ->get();
                foreach ($penalty['penaltyInstallments'] as $key => $val) {
                    $ids = trim(($ids . "," . $val["id"]), ",");
                    $penalty['penaltyInstallments'][$key]["ids"] = $ids;
                }
                $waterTransDetail = array_merge($penalty, $waterTransDetail);
            }
            $returnData = array_merge($applicationDetails, $waterTransDetail);
            return responseMsgs(true, "Application Data!", remove_null($returnData), "", "", "", "Post", "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }


    /**
     * | List application applied according to its user type
     * | Serch Application btw Dates
     * | @param request
     * | @var 
     * | @return 
        | Serial No:
     */
    public function listApplicationBydate(Request $request)
    {
        $request->validate([
            'fromDate' => 'required|date_format:Y-m-d',
            'toDate'   => 'required|date_format:Y-m-d',
        ]);
        try {
            $mWaterConnectionCharge  = new WaterConnectionCharge();
            $mWaterPenaltyInstallment = new WaterPenaltyInstallment();
            $mWaterApplication = new WaterApplication();
            $refTimeDate = [
                "refStartTime" => date($request->fromDate),
                "refEndTime" => date($request->toDate)
            ];
            #application Details according to date
            $refApplications = $mWaterApplication->getapplicationByDate($refTimeDate)
                ->where('water_applications.user_id', authUser()->id)
                ->get();
            # Final Data to return
            $returnValue = collect($refApplications)->map(function ($value, $key)
            use ($mWaterConnectionCharge, $mWaterPenaltyInstallment) {

                # calculation details
                $penaltyList = $mWaterPenaltyInstallment->getPenaltyByApplicationId($value['id'])->get();
                $charges = $mWaterConnectionCharge->getWaterchargesById($value['id'])->get();

                $value['all_payment_status'] = $this->getAllPaymentStatus($charges, $penaltyList);
                $value['calculation'] = collect($charges)->map(function ($values) {
                    return  [
                        'connectionFee'     => $values['conn_fee'],
                        'penalty'           => $values['penalty'],
                        'totalAmount'       => $values['amount'],
                        'chargeCatagory'    => $values['charge_category'],
                        'paidStatus'        => $values['paid_status']
                    ];
                });
                return $value;
            });
            return responseMsgs(true, "listed Application!", remove_null($returnValue), "", "01", "ms", "POST", "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), $e->getFile(), "", "01", "ms", "POST", "");
        }
    }

    /**
     * | Get all the payment list and payment Status
     * | Checking the payment Satatus
     * | @param 
     * | @param
        | Serial No :
     */
    public function getAllPaymentStatus($charges, $penalties)
    {
        # Connection Charges
        $chargePaymentList = collect($charges)->map(function ($value1) {
            if ($value1['paid_status'] == 0) {
                return false;
            }
            return true;
        });
        if ($chargePaymentList->contains(false)) {
            return false;
        }

        # Penaty listing 
        $penaltyPaymentList = collect($penalties)->map(function ($value2) {
            if ($value2['paid_status'] == 0) {
                return false;
            }
            return true;
        });
        if ($penaltyPaymentList->contains(false)) {
            return false;
        }
        return true;
    }



    #----------------------------------------- Site Inspection ----------------------------------------|
    /**
     * | Site Comparision Screen 
     * | Je comparision data
        | Recheck 
     * | @param request
     */
    public function listComparision(Request $request)
    {
        $request->validate([
            'applicationId' => 'required'
        ]);
        try {
            # Site inspection Details
            $mWaterSiteInspection = new WaterSiteInspection();
            $mWaterApplication = new WaterApplication();
            $applicationDetails = $mWaterApplication->getApplicationById($request->applicationId)->firstOrFail();
            $siteInspectiondetails = $mWaterSiteInspection->getInspectionById($request->applicationId)->first();
            $returnData = [
                "applicationDetails" => $applicationDetails,
                "siteInspectiondetails" => $siteInspectiondetails
            ];
            return responseMsgs(true, "Comparative data!", remove_null($returnData), "", "01", "ms", "POST", "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), $e->getFile(), "", "01", "ms", "POST", "");
        }
    }

    /**
     * | Search Application for Site Inspection
     * | @param request
     * | @var 
        | Recheck
     */
    public function searchApplicationByParameter(Request $request)
    {
        $mWaterApplicant = new WaterApplication();
        $mWaterSiteInspectionsScheduling = new WaterSiteInspectionsScheduling();
        $mWaterSiteInspection = new WaterSiteInspection();
        $filterBy = Config::get('waterConstaint.FILTER_BY');
        $roleId = Config::get('waterConstaint.ROLE-LABEL.JE');
        $request->validate([
            'filterBy'  => 'required',
            'parameter' => $request->filterBy == $filterBy['APPLICATION'] ? 'required' : 'nullable',
            'fromDate'  => $request->filterBy == $filterBy['DATE'] ? 'required|date_format:Y-m-d' : 'nullable',
            'toDate'    => $request->filterBy == $filterBy['DATE'] ? 'required|date_format:Y-m-d' : 'nullable',
        ]);
        try {
            $key = $request->filterBy;
            switch ($key) {
                case ("byApplication"):
                    $refSiteDetails['SiteInspectionDate'] = null;
                    $refApplication = $mWaterApplicant->getApplicationByNo($request->parameter, $roleId)->get();
                    $returnData = collect($refApplication)->map(function ($value) use ($mWaterSiteInspectionsScheduling) {
                        $refViewSiteDetails['viewSiteDetails'] = false;
                        $refSiteDetails['SiteInspectionDate'] = $mWaterSiteInspectionsScheduling->getInspectionById($value['id'])->first();
                        if (isset($refSiteDetails['SiteInspectionDate'])) {
                            $refViewSiteDetails['viewSiteDetails'] = $this->canViewSiteDetails($refSiteDetails['SiteInspectionDate']);
                            return  collect($value)->merge(collect($refSiteDetails))->merge(collect($refViewSiteDetails));
                        }
                        $refSiteDetails['SiteInspectionDate'] = $mWaterSiteInspectionsScheduling->getInspectionData($value['id'])->first();
                        return  collect($value)->merge(collect($refSiteDetails))->merge(collect($refViewSiteDetails));
                    });

                    break;
                case ("byDate"):
                    $refTimeDate = [
                        "refStartTime" => Carbon::parse($request->fromDate)->format('Y-m-d'),
                        "refEndTime" => Carbon::parse($request->toDate)->format('Y-m-d')
                    ];
                    $refData = $mWaterApplicant->getapplicationByDate($refTimeDate)->get();
                    $returnData = collect($refData)->map(function ($value) use ($roleId, $mWaterSiteInspectionsScheduling) {
                        if ($value['current_role'] == $roleId) {
                            $refViewSiteDetails['viewSiteDetails'] = false;
                            $refSiteDetails['SiteInspectionDate'] = $mWaterSiteInspectionsScheduling->getInspectionById($value['id'])->first();
                            if (isset($refSiteDetails['SiteInspectionDate'])) {
                                $refViewSiteDetails['viewSiteDetails'] = $this->canViewSiteDetails($refSiteDetails['SiteInspectionDate']);
                                return  collect($value)->merge(collect($refSiteDetails))->merge(collect($refViewSiteDetails));
                            }
                            $refSiteDetails['SiteInspectionDate'] = $mWaterSiteInspectionsScheduling->getInspectionData($value['id'])->first();
                            return  collect($value)->merge(collect($refSiteDetails))->merge(collect($refViewSiteDetails));
                            return $value;
                        }
                    })->filter()->values();
                    break;
            }
            return responseMsgs(true, "Searched Data!", remove_null($returnData), "", "01", "ms", "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), $e->getFile(), "", "01", "ms", "POST", "");
        }
    }


    /**
     * | Can View Site Details 
     * | Check if the provided date is matchin to the current date
     * | @param sitDetails  
        | Recheck
     */
    public function canViewSiteDetails($sitDetails)
    {
        if ($sitDetails['inspection_date'] == Carbon::now()->format('Y-m-d')) {
            return true;
        }
        return false;
    }

    /**
     * | Cancel Site inspection 
     * | In case of date missmatch or changes
     * | @param request
     * | @var
     * | @return  
        | Make Route
        | Working
     */
    public function cancelSiteInspection(Request $request)
    {
        try {
            $request->validate([
                'applicationId' => 'required',
            ]);
            $this->checkForSaveDateTime($request);
            $mWaterSiteInspectionsScheduling = new WaterSiteInspectionsScheduling();
            $mWaterConnectionCharge = new WaterConnectionCharge();
            $mWaterPenaltyInstallment = new WaterPenaltyInstallment();
            $mWaterApplication = new WaterApplication();
            $refSiteInspection = Config::get("waterConstaint.CHARGE_CATAGORY.SITE_INSPECTON");
            $refApplicationId = $request->applicationId;

            DB::beginTransaction();
            $mWaterSiteInspectionsScheduling->cancelInspectionDateTime($refApplicationId);
            $mWaterConnectionCharge->deactivateSiteCharges($refApplicationId, $refSiteInspection);
            $mWaterPenaltyInstallment->deactivateSitePenalty($refApplicationId, $refSiteInspection);
            $mWaterApplication->updateOnlyPaymentstatus($refApplicationId);
            DB::commit();
            return responseMsgs(true, "Scheduled Date is Cancelled!", "", "", "01", ".ms", "POST", $request->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), $e->getFile(), "", "01", ".ms", "POST", "");
        }
    }

    /**
     * | Save the site Inspection Date and Time 
     * | Create record behalf of the date and time with respective to application no
     * | @param request
     * | @var 
     * | @return 
        | Working
     */
    public function saveInspectionDateTime(Request $request)
    {
        try {
            $request->validate([
                'applicationId' => 'required',
                'inspectionDate' => 'required|date|date_format:Y-m-d',
                'inspectionTime' => 'required|date_format:H:i'
            ]);
            $mWaterSiteInspectionsScheduling = new WaterSiteInspectionsScheduling();
            $refDate = Carbon::now()->format('Y-m-d');
            $this->checkForSaveDateTime($request);
            $mWaterSiteInspectionsScheduling->saveSiteDateTime($request);
            if ($request->inspectionDate == $refDate) {
                $canView['canView'] = true;
            } else {
                $canView['canView'] = false;
            }
            return responseMsgs(true, "Date for the Site Inspection is Saved!", $canView, "", "01", ".ms", "POST", "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), $e->getFile(), "", "01", ".ms", "POST", "");
        }
    }

    /**
     * | Check the validation for saving the site inspection 
     * | @param request
        | Working
        | Add more Validation 
     */
    public function checkForSaveDateTime($request)
    {
        $mWfRoleUser = new WfRoleusermap();
        $refApplication = WaterApplication::findOrFail($request->applicationId);
        $WaterRoles = Config::get('waterConstaint.ROLE-LABEL');
        $workflowId = Config::get('workflow-constants.WATER_WORKFLOW_ID');
        $metaReqs =  new Request([
            'userId'        => authUser()->id,
            'workflowId'    => $workflowId
        ]);
        $readRoles = $mWfRoleUser->getRoleByUserWfId($metaReqs);                      // Model to () get Role By User Id

        if ($refApplication['current_role'] != $WaterRoles['JE']) {
            throw new Exception("Application is not Under the JE!");
        }
        if ($readRoles->wf_role_id != $WaterRoles['JE']) {
            throw new Exception("you Are Not Autherised for the process!");
        }
    }


    /**
     * | Get the Date/Time alog with site details 
     * | Site Details  
        | Working
        | Recheck
     */
    public function getSiteInspectionDetails(Request $request)
    {
        try {
            $request->validate([
                'applicationId' => 'required',
            ]);
            $mWaterSiteInspectionsScheduling = new WaterSiteInspectionsScheduling();
            $siteInspection = $mWaterSiteInspectionsScheduling->getInspectionById($request->applicationId)->first();
            if (isset($siteInspection)) {
                $canInspect = $this->checkCanInspect($siteInspection);
                $returnData = [
                    "inspectionDate" => $siteInspection->inspection_date,
                    "inspectionTime" => $siteInspection->inspection_time,
                    "canInspect"     => $canInspect
                ];
                return responseMsgs(true, "Site InspectionDetails!", $returnData, "", "01", ".ms", "POST", "");
            }
            throw new Exception("Invalid data!");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "", "01", ".ms", "POST", "");
        }
    }


    /**
     * | Check if the current Application will be Inspected
     * | Checking the sheduled Date for inspection
     * | @param
     * | @var 
        | Working
     */
    public function checkCanInspect($siteInspection)
    {
        $refDate = Carbon::now()->format('Y-m-d');
        if ($siteInspection->inspection_date == $refDate) {
            $canInspect = true;
        } else {
            $canInspect = false;
        }
        return $canInspect;
    }


    /**
     * | Online site Inspection 
     * | Assistent Enginer site detail Entry
     * | @param request
     * | @var 
     * | @return 
        | Not Working
        | Make the concept clear
        | opration shoul be adding new record
     */
    public function onlineSiteInspection(Request $request)
    {
        try {
            $request->validate([
                'applicationId' => 'required',
                'waterLockArng' => 'required',
                'gateValve'     => 'required',
                'pipelineSize'  => 'required',
                'pipeSize'      => 'required|in:15,20,25',
                'ferruleType'   => 'required|in:6,10,12,16'
            ]);
            $user = authUser();
            $current = Carbon::now();
            $currentDate = $current->format('Y-m-d');
            $currentTime = $current->format('H:i:s');
            $mWaterSiteInspection = new WaterSiteInspection();
            $refDetails = $this->onlineSitePreConditionCheck($request);
            $request->request->add([
                'wardId'            => $refDetails['refApplication']->ward_id,
                'userId'            => $user->id,
                'applicationId'     => $refDetails['refApplication']->id,
                'roleId'            => $refDetails['roleDetails']->wf_role_id,
                'inspectionDate'    => $currentDate,
                'inspectionTime'    => $currentTime
            ]);
            $mWaterSiteInspection->saveOnlineSiteDetails($request);
            return responseMsgs(true, "Technical Inspection Completed!", "", "", "01", ".ms", "POST", $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), $e->getFile(), "", "01", ".ms", "POST", "");
        }
    }

    /**
     * | Check the Pre Site inspection Details 
     * | pre conditional Check for the AE online Site inspection
     * | @param
     * | @var mWfRoleUser
        | Working 
     */
    public function onlineSitePreConditionCheck($request)
    {
        $mWfRoleUser = new WfRoleusermap();
        $refApplication = WaterApplication::findOrFail($request->applicationId);
        $WaterRoles = Config::get('waterConstaint.ROLE-LABEL');
        $workflowId = $refApplication->workflow_id;
        $metaReqs =  new Request([
            'userId'        => authUser()->id,
            'workflowId'    => $workflowId
        ]);
        $readRoles = $mWfRoleUser->getRoleByUserWfId($metaReqs);                      // Model to () get Role By User Id

        # Condition checking
        if ($refApplication['current_role'] != $WaterRoles['AE']) {
            throw new Exception("Application is not under Assistent Engineer!");
        }
        if ($readRoles->wf_role_id != $WaterRoles['AE']) {
            throw new Exception("You are not autherised for the process!");
        }
        if ($refApplication['is_field_verified'] == false) {
            throw new Exception("Site verification by Junier Engineer is not done!");
        }
        return [
            'refApplication' => $refApplication,
            'roleDetails' => $readRoles
        ];
    }


    /**
     * | Get Site Inspection Details done by Je
     * | Details Filled by JE
     * | @param request
     * | @var 
     * | @return 
        | Working
     */
    public function getJeSiteDetails(Request $request)
    {
        try {
            $request->validate([
                'applicationId' => 'required',
            ]);
            # variable defining
            $returnData['final_verify'] = false;
            $mWaterSiteInspection = new WaterSiteInspection();
            $mWaterSiteInspectionsScheduling = new WaterSiteInspectionsScheduling();
            $refJe = Config::get("waterConstaint.ROLE-LABEL.JE");
            # level logic
            $sheduleDate = $mWaterSiteInspectionsScheduling->getInspectionData($request->applicationId)->first();
            if (!is_null($sheduleDate) && $sheduleDate->site_verify_status == true) {
                $returnData = $mWaterSiteInspection->getSiteDetails($request->applicationId)
                    ->where('order_officer', $refJe)
                    ->first();
                $returnData['final_verify'] = true;
                return responseMsgs(true, "JE Inspection details!", remove_null($returnData), "", "01", ".ms", "POST", $request->deviceId);
            }
            return responseMsgs(true, "Data not Found!", remove_null($returnData), "", "01", ".ms", "POST", $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), $e->getFile(), "", "01", ".ms", "POST", $request->deviceId);
        }
    }

    /**
     * | Get AE technical Inspection
     * | Pick the first details for the respective application 
     * | @param request
     * | @var 
     * | @return 
        | Working
     */
    public function getTechnicalInsDetails(Request $request)
    {
        try {
            $request->validate([
                'applicationId' => 'required',
            ]);
            # variable defining
            $mWaterSiteInspection = new WaterSiteInspection();
            $refRole = Config::get("waterConstaint.ROLE-LABEL");
            # level logic
            $returnData['aeData'] = $mWaterSiteInspection->getSiteDetails($request->applicationId)
                ->where('order_officer', $refRole['AE'])
                ->first();
            $jeData = $this->jeSiteInspectDetails($request, $refRole);
            $returnData['jeData'] = $jeData;
            return responseMsgs(true, "AE Inspection details!", remove_null($returnData), "", "01", ".ms", "POST", $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), $e->getFile(), "", "01", ".ms", "POST", $request->deviceId);
        }
    }

    /**
     * | Check and get the je site inspection details
     * | @param request
        | Working
     */
    public function jeSiteInspectDetails($request, $refRole)
    {
        $mWaterApplication = new WaterApplication();
        $mWaterSiteInspection = new WaterSiteInspection();
        $applicationId = $request->applicationId;

        $applicationDetails = $mWaterApplication->getApplicationById($applicationId)
            ->where('is_field_verified', true)
            ->first();
        if (!$applicationDetails) {
            throw new Exception("Application not found!");
        }
        $jeData = $mWaterSiteInspection->getSiteDetails($applicationId)
            ->where('order_officer', $refRole['JE'])
            ->first();
        if (!$jeData) {
            throw new Exception("JE site inspection data not found!");
        }
        $returnData = [
            'pipeline_size' => $jeData->pipeline_size,
            'pipe_size'     => $jeData->pipe_size,
            'ferrule_type'  => $jeData->ferrule_type
        ];
        return $returnData;
    }
}
