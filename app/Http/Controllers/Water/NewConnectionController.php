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

    // Water Inbox
    public function waterInbox()
    {
        try {
            return $this->newConnection->waterInbox();
        } catch (Exception $error) {
            return responseMsg(false, $error->getMessage(), "");
        }
    }

    // Water Outbox
    public function waterOutbox()
    {
        try {
            return $this->newConnection->waterOutbox();
        } catch (Exception $error) {
            return responseMsg(false, $error->getMessage(), "");
        }
    }

    // Back to citizen Inbox
    public function btcInbox(Request $req)
    {
        try {
            $mWfWardUser = new WfWardUser();

            $userId = authUser()->id;
            $ulbId = authUser()->ulb_id;
            $mDeviceId = $req->deviceId ?? "";

            $workflowRoles = $this->getRoleIdByUserId($userId);
            $roleId = $workflowRoles->map(function ($value, $key) {                         // Get user Workflow Roles
                return $value->wf_role_id;
            });

            $refWard = $mWfWardUser->getWardsByUserId($userId);
            $wardId = $refWard->map(function ($value, $key) {
                return $value->ward_id;
            });

            $waterList = $this->getWaterApplicatioList($ulbId)
                ->whereIn('water_applications.current_role', $roleId)
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
                'applicationId' => 'required',
                'senderRoleId' => 'required',
                'receiverRoleId' => 'required',
                'action' => 'required|In:forward,backward',
                'comment' => $request->senderRoleId == $wfLevels['BO'] ? 'nullable' : 'required',
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
                "status" => "required"
            ]);
            $waterDetails = WaterApplication::find($request->applicationId);
            $mWfRoleUsermap = new WfRoleusermap();
            $waterRoles = $this->_waterRoles;

            # check the login user is Eo or not
            $userId = authUser()->id;
            $workflowId = $waterDetails->workflow_id;
            $getRoleReq = new Request([                                                 // make request to get role id of the user
                'userId' => $userId,
                'workflowId' => $workflowId
            ]);
            $readRoleDtls = $mWfRoleUsermap->getRoleByUserWfId($getRoleReq);
            $roleId = $readRoleDtls->wf_role_id;
            if ($roleId != $waterRoles['EO']) {
                throw new Exception("You are not Executive Officer!");
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
            return $this->newConnection->commentIndependent($request);
        } catch (Exception $e) {
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
                            $connectionName = $refConnectionName['1'];
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
                }
                $consumerDetails = $consumerDetails->merge($documentDetails)->merge($consumerDemand);
                return responseMsgs(true, "Consumer Details!", remove_null($consumerDetails), "", "01", ".ms", "POST", $request->deviceId);
            }

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

    // Field Verification of water Applications // Recheck
    /**
        | Recheck
     */
    public function fieldVerification(reqSiteVerification $request)
    {
        try {
            $juniorEngRoleId = Config::get('waterConstaint.ROLE-LABEL.JE');
            $mWaterApplication = new WaterApplication();
            // $verification = new WaterSiteInspection();
            $verificationStatus = $request->verificationStatus;                                             // Verification Status true or false

            switch ($request->currentRoleId) {
                case $juniorEngRoleId;                                                                  // In Case of Agency TAX Collector
                    if ($verificationStatus == 1) {
                        $msg = "Site Successfully Verified";
                    }
                    if ($verificationStatus == 0) {
                        $msg = "Site Successfully Rebuted";
                    }
                    $mWaterApplication->markSiteVerification($request->id);
                    break;
                default:
                    return responseMsg(false, "Forbidden Access", "");
            }
            // DB::beginTransaction();
            // // Verification Store
            // $verification->store($request);                                                                          // Model function to store verification and get the id
            // DB::commit();
            return responseMsgs(true, $msg, "", "010118", "1.0", "310ms", "POST", $request->deviceId);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    // Back to Citizen  // Recheck
    public function backToCitizen(Request $req)
    {
        $req->validate([
            'applicationId' => 'required|integer',
            'workflowId' => 'required|integer',
            'currentRoleId' => 'required|integer',
            'comment' => 'required|string'
        ]);

        try {
            $mWaterApplication = WaterApplication::find($req->applicationId);
            $WorkflowTrack = new WorkflowTrack();

            DB::beginTransaction();

            $initiatorRoleId = $mWaterApplication->initiator_role_id;
            $mWaterApplication->current_role = $initiatorRoleId;
            $mWaterApplication->parked = true;                        //<------  Pending Status true
            $mWaterApplication->save();

            $metaReqs['moduleId'] = Config::get('module-constants.WATER_MODULE_ID');
            $metaReqs['workflowId'] = $mWaterApplication->workflow_id;
            $metaReqs['refTableDotId'] = 'water_applications.id';
            $metaReqs['refTableIdValue'] = $req->applicationId;
            $metaReqs['senderRoleId'] = $req->currentRoleId;
            $req->request->add($metaReqs);
            $WorkflowTrack->saveTrack($req);

            DB::commit();
            return responseMsgs(true, "Successfully Done", "", "", "1.0", "350ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
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
            if ($applicantDetals->payment_status == true) {
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
                if ($refApplication->payment_status == true) {
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
                ->where('paid_status', false)
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
                'moduleId' => $refmoduleId,
                'activeId' => $getWaterDetails->id,
                'workflowId' => $getWaterDetails->workflow_id,
                'ulbId' => $getWaterDetails->ulb_id,
                'relativePath' => $relativePath,
                'document' => $imageName,
                'docCode' => $req->docCode,
                'ownerDtlId' => $req->ownerId,
            ];

            $ifDocExist = $mWfActiveDocument->ifDocExists($getWaterDetails->id, $getWaterDetails->workflow_id, $refmoduleId, $req->docCode, $req->ownerId);   // Checking if the document is already existing or not
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
     */
    public function updateWaterStatus($req, $application)
    {
        $mWaterApplication = new WaterApplication();
        $waterRoles = $this->_waterRoles;
        $mWaterApplication->activateUploadStatus($req->applicationId);
        if ($application->payment_status == true) {
            $mWaterApplication->updateCurrentRoleForDa($$req->applicationId, $waterRoles);
        }
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
            return responseMsgs(true, "Uploaded Documents", remove_null($documents), "010102", "1.0", "", "POST", $req->deviceId ?? "");
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
                $doc = (array) null;
                $testOwnersDoc[] = (array) null;
                $doc["ownerId"] = $val->id;
                $doc["ownerName"] = $val->applicant_name;
                $doc["docName"]   = "ID Proof";
                $doc['isMadatory'] = 1;
                $ref['docValue'] = $refWaterNewConnection->getDocumentList(["ID_PROOF"]);   #"CONSUMER_PHOTO"
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
            $key = $request->connectionThrough;
            $refTenanted = Config::get('PropertyConstaint.OCCUPANCY-TYPE.TENANTED');
            switch ($key) {
                case ("1"):
                    $mPropProperty = new PropProperty();
                    $mPropOwner = new PropOwner();
                    $mPropFloor = new PropFloor();
                    $application = collect($mPropProperty->getPropByHolding($request->id, $request->ulbId));
                    $checkExist = collect($application)->first();
                    if ($checkExist) {
                        $areaInSqft['areaInSqFt'] = decimalToSqFt($application['total_area_in_desimal']);
                        $propUsageType = $this->getPropUsageType($request, $application['id']);
                        $occupancyOwnerType = collect($mPropFloor->getOccupancyType($application['id'], $refTenanted));
                        $owners['owners'] = collect($mPropOwner->getOwnerByPropId($application['id']));
                        $details = $application->merge($areaInSqft)->merge($owners)->merge($occupancyOwnerType)->merge($propUsageType);
                        return responseMsgs(true, "related Details!", $details, "", "", "", "POST", "");
                    }
                    throw new Exception("Data According to Holding Not Found!");
                    break;

                case ("2"):
                    $mPropActiveSafOwners = new PropActiveSafsOwner();
                    $mPropActiveSafsFloor = new PropActiveSafsFloor();
                    $mPropActiveSaf = new PropActiveSaf();
                    $application = collect($mPropActiveSaf->getSafDtlBySafUlbNo($request->id, $request->ulbId));
                    $checkExist = collect($application)->first();
                    if ($checkExist) {
                        $areaInSqft['areaInSqFt'] = decimalToSqFt($application['total_area_in_desimal']);
                        $safUsageType = $this->getPropUsageType($request, $application['id']);
                        $occupancyOwnerType = collect($mPropActiveSafsFloor->getOccupancyType($application['id'], $refTenanted));
                        $owners['owners'] = collect($mPropActiveSafOwners->getOwnerDtlsBySafId($application['id']));
                        $details = $application->merge($areaInSqft)->merge($owners)->merge($occupancyOwnerType)->merge($safUsageType);
                        return responseMsgs(true, "related Details!", $details, "", "", "", "POST", "");
                    }
                    throw new Exception("Data According to SAF Not Found!");
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
                // $porpDetails = $this->checkPropInfo($request, $id);
                // if ($porpDetails == false) {
                $mPropFloor = new PropFloor();
                $usageCatagory = $mPropFloor->getPropUsageCatagory($id);
                // }
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
     * | Get the 
     */



    // final submition of the Water Application
    /**
        | dont check the application payment status 
        | call the payment ineciate function
        | Change / not used 
     */
    public function finalSubmitionApplication(Request $request)
    {
        try {
            $request->validate([
                'applicationId' => 'required|int',
            ]);

            $mWaterApplication = new WaterApplication();
            $refApplicationList = $mWaterApplication->getWaterApplicationsDetails($request->applicationId);
            $checkExist = collect($refApplicationList)->first();
            if (!$checkExist) {
                throw new Exception("Application Data Not found!");
            }

            $documentList = $this->getDocToUpload($request);
            $refDoc = collect($documentList)['original']['data']['documentsList'];
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

            if ($checkDocument->contains(false)) {
                throw new Exception("Please Upload Req Documents before Final Submition!");
            }
            if ($refApplicationList->payment_status == false) {
                throw new Exception("Payment Not done!");
            }
            if ($refApplicationList->apply_from != 'Online') {
                throw new Exception("Respective Application is Not Applied Online by Citizen!");
            }
            if (isset($refApplicationList->current_role)) {
                throw new Exception("Application is already In workflow!");
            }
            switch ($refApplicationList->user_type) {
                case ('Citizen'):
                    // call the payment function
                    WaterApplication::where('id', $request->applicationId)
                        ->update([
                            'current_role' => $this->_dealingAssistent
                        ]);
                    return responseMsgs(true, "Application Submited to Workflow!", $request, "", "01", "", "POST", "");
                    break;
                default:
                    throw new Exception("Citizen Application Not found!");
                    break;
            }
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
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
            return $waterOwnerDocs['ownerDocs'] = collect($refWaterApplicant)->map(function ($owner) use ($refWaterApplication) {
                return $this->getOwnerDocLists($owner, $refWaterApplication);
            });

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
                    $response = [
                        "uploadedDocId" => $uploadedDoc->id ?? "",
                        "documentCode" => $item,
                        "ownerId" => $uploadedDoc->owner_dtl_id ?? "",
                        "docPath" => $uploadedDoc->doc_path ?? "",
                        "verifyStatus" => $uploadedDoc->verify_status ?? "",
                        "remarks" => $uploadedDoc->remarks ?? "",
                    ];
                    $documents->push($response);
                }
            });
            $reqDoc['docType'] = $key;
            $reqDoc['uploadedDoc'] = $documents->first();
            $reqDoc['docName'] = substr($label, 1, -1);

            $reqDoc['masters'] = collect($document)->map(function ($doc) use ($uploadedDocs) {
                $uploadedDoc = $uploadedDocs->where('doc_code', $doc)->first();
                $strLower = strtolower($doc);
                $strReplace = str_replace('_', ' ', $strLower);
                $arr = [
                    "documentCode" => $doc,
                    "docVal" => ucwords($strReplace),
                    "uploadedDoc" => $uploadedDoc->doc_path ?? "",
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
        if (in_array($application->connection_through_id, [1, 2]))      // Holding No, SAF No
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
            $ownerDocList['ownerDetails'] = [
                'ownerId' => $refOwners['id'],
                'name' => $refOwners['owner_name'],
                'mobile' => $refOwners['mobile_no'],
                'guardian' => $refOwners['guardian_name'],
                'uploadedDoc' => $ownerPhoto->doc_path ?? "",
                'verifyStatus' => $ownerPhoto->verify_status ?? ""
            ];
            return $ownerDocList;
        }
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
            $key = $request->filterBy;
            $paramenter = $request->parameter;
            switch ($key) {
                case ("consumerNo"):
                    $mWaterConsumer = new WaterConsumer();
                    $string = preg_replace("/([A-Z])/", "_$1", $key);
                    $refstring = strtolower($string);
                    $waterReturnDetails = $mWaterConsumer->getDetailByConsumerNo($refstring, $paramenter);
                    $checkVal = collect($waterReturnDetails)->first();
                    if (!$checkVal)
                        throw new Exception("Data Not Found!");
                    break;
                case ("holdingNo"):
                    $mWaterConsumer = new WaterConsumer();
                    $string = preg_replace("/([A-Z])/", "_$1", $key);
                    $refstring = strtolower($string);
                    $waterReturnDetails = $mWaterConsumer->getDetailByConsumerNo($refstring, $paramenter);
                    $checkVal = collect($waterReturnDetails)->first();
                    if (!$checkVal)
                        throw new Exception("Data Not Found!");
                    break;
                case ("safNo"):
                    $mWaterConsumer = new WaterConsumer();
                    $string = preg_replace("/([A-Z])/", "_$1", $key);
                    $refstring = strtolower($string);
                    $waterReturnDetails = $mWaterConsumer->getDetailByConsumerNo($refstring, $paramenter);
                    $checkVal = collect($waterReturnDetails)->first();
                    if (!$checkVal)
                        throw new Exception("Data Not Found!");
                    break;
                case ("applicantName"):
                    $mWaterConsumer = new WaterConsumer();
                    $string = preg_replace("/([A-Z])/", "_$1", $key);
                    $refstring = strtolower($string);
                    $waterReturnDetails = $mWaterConsumer->getDetailByOwnerDetails($refstring, $paramenter);
                    $checkVal = collect($waterReturnDetails)->first();
                    if (!$checkVal)
                        throw new Exception("Data Not Found!");
                    break;
                case ('mobileNo'):
                    $mWaterConsumer = new WaterConsumer();
                    $string = preg_replace("/([A-Z])/", "_$1", $key);
                    $refstring = strtolower($string);
                    $waterReturnDetails = $mWaterConsumer->getDetailByOwnerDetails($refstring, $paramenter);
                    $checkVal = collect($waterReturnDetails)->first();
                    if (!$checkVal)
                        throw new Exception("Data Not Found!");
                    break;
            }
            return responseMsgs(true, "Water Consumer Data According To Parameter!", $waterReturnDetails, "", "01", "652 ms", "POST", "");
        } catch (Exception $e) {
            return responseMsg(true, $e->getMessage(), "");
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
            'id' => 'required|digits_between:1,9223372036854775807',
            'applicationId' => 'required|digits_between:1,9223372036854775807',
            'docRemarks' =>  $req->docStatus == "Rejected" ? 'required|regex:/^[a-zA-Z1-9][a-zA-Z1-9\. \s]+$/' : "nullable",
            'docStatus' => 'required|in:Verified,Rejected'
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
            # Derivative Assigments
            $waterApplicationDtl = $mWaterApplication->getApplicationById($applicationId)
                ->firstOrFail();

            if (!$waterApplicationDtl || collect($waterApplicationDtl)->isEmpty())
                throw new Exception("Application Details Not Found");

            $waterReq = new Request([
                'userId' => $userId,
                'workflowId' => $waterApplicationDtl['workflow_id']
            ]);
            $senderRoleDtls = $mWfRoleusermap->getRoleByUserWfId($waterReq);
            if (!$senderRoleDtls || collect($senderRoleDtls)->isEmpty())
                throw new Exception("Role Not Available");

            $senderRoleId = $senderRoleDtls->wf_role_id;

            if ($senderRoleId != $wfLevel['DA'])                                // Authorization for Dealing Assistant Only
                throw new Exception("You are not Authorized");

            $ifFullDocVerified = $this->ifFullDocVerified($applicationId);       // (Current Object Derivative Function 0.1)

            if ($ifFullDocVerified == 1)
                throw new Exception("Document Fully Verified");

            DB::beginTransaction();
            if ($req->docStatus == "Verified") {
                $status = 1;
            }
            if ($req->docStatus == "Rejected") {
                $status = 2;
                # For Rejection Doc Upload Status and Verify Status will disabled
                $waterApplicationDtl->doc_upload_status = 0;
                $waterApplicationDtl->doc_status = 0;
                $waterApplicationDtl->save();
            }

            $reqs = [
                'remarks' => $req->docRemarks,
                'verify_status' => $status,
                'action_taken_by' => $userId
            ];
            $mWfDocument->docVerifyReject($wfDocId, $reqs);
            $ifFullDocVerifiedV1 = $this->ifFullDocVerified($applicationId);

            if ($ifFullDocVerifiedV1 == 1) {                                     // If The Document Fully Verified Update Verify Status
                $waterApplicationDtl->doc_status = 1;
                $waterApplicationDtl->save();
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
            'activeId' => $applicationId,
            'workflowId' => $refapplication['workflow_id'],
            'moduleId' => Config::get('module-constants.WATER_MODULE_ID')
        ];

        $req = new Request($refReq);
        $refDocList = $mWfActiveDocument->getDocsByActiveId($req);
        // Water List Documents
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

            if ($charges['paid_status'] == false) {
                $calculation['calculation'] = [
                    'connectionFee' => $charges['conn_fee'],
                    'penalty' => $charges['penalty'],
                    'totalAmount' => $charges['amount'],
                    'chargeCatagory' => $charges['charge_category'],
                    'paidStatus' => $charges['paid_status']
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
                        'connectionFee' => 0.00,           # Static
                        'penalty' => $penaltyAmount,
                        'totalAmount' => $penaltyAmount,
                        'chargeCatagory' => $charges['charge_category'],
                        'paidStatus' => $charges['paid_status']
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
            if ($value1['paid_status'] == false) {
                return false;
            }
            return true;
        });
        if ($chargePaymentList->contains(false)) {
            return false;
        }

        # Penaty listing 
        $penaltyPaymentList = collect($penalties)->map(function ($value2) {
            if ($value2['paid_status'] == false) {
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
            $siteInspectiondetails = $mWaterSiteInspection->getInspectionById($request->applicationId)->get();
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
        $filterBy = Config::get('waterConstaint.FILTER_BY');
        $roleId = Config::get('waterConstaint.ROLE-LABEL.JE');
        $request->validate([
            'filterBy'  => 'required',
            'parameter' => $request->filterBy == $filterBy['APPLICATION'] ? 'required' : 'nullable',
            'fromDate'  => $request->filterBy == $filterBy['DATE'] ? 'required|date_format:d-m-Y' : 'nullable',
            'toDate'    => $request->filterBy == $filterBy['DATE'] ? 'required|date_format:d-m-Y' : 'nullable',
        ]);
        try {
            $key = $request->filterBy;
            switch ($key) {
                case ("byApplication"):
                    $refSiteDetails['SiteInspectionDate'] = null;
                    $mWaterApplicant = new WaterApplication();
                    $mWaterSiteInspectionsScheduling = new WaterSiteInspectionsScheduling();
                    $refApplication = $mWaterApplicant->getApplicationByNo($request->parameter, $roleId)->get();
                    $returnData = collect($refApplication)->map(function ($value) use ($mWaterSiteInspectionsScheduling) {
                        $refViewSiteDetails['viewSiteDetails'] = false;
                        $refSiteDetails['SiteInspectionDate'] = $mWaterSiteInspectionsScheduling->getInspectionById($value['id'])->first();
                        if (isset($refSiteDetails['SiteInspectionDate'])) {
                            $refViewSiteDetails['viewSiteDetails'] = $this->canViewSiteDetails($refSiteDetails['SiteInspectionDate']);
                            return  collect($value)->merge(collect($refSiteDetails))->merge(collect($refViewSiteDetails));
                        }
                        return  collect($value)->merge(collect($refSiteDetails))->merge(collect($refViewSiteDetails));
                    });

                    break;
                case ("byDate"):
                    $mWaterApplicant = new WaterApplication();
                    $mWaterSiteInspectionsScheduling = new WaterSiteInspectionsScheduling();
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
        | Recheck
     */
    public function cancelSiteInspection(Request $request)
    {
        try {
            $request->validate([
                'applicationId' => 'required',
            ]);
            $this->checkForSaveDateTime($request);
            $mWaterSiteInspectionsScheduling = new WaterSiteInspectionsScheduling();
            $mWaterSiteInspectionsScheduling->cancelInspectionDateTime($request->applicationId);
            return responseMsgs(true, "Scheduled Date is Cancelled!", "", "", "01", ".ms", "POST", $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), $e->getFile(), "", "01", ".ms", "POST", "");
        }
    }

    /**
     * | Save the site Inspection Date and Time 
     * | Create record behalf of the date and time with respective to application no
     * | @param request
     * | @var 
     * | @return 
        | Recheck
     */
    public function saveInspectionDateTime(Request $request)
    {
        try {
            $request->validate([
                'applicationId' => 'required',
                'inspectionDate' => 'required|date|date_format:d-m-Y',
                'inspectionTime' => 'required|date_format:H:i'
            ]);
            $mWaterSiteInspectionsScheduling = new WaterSiteInspectionsScheduling();
            $refDate = Carbon::now();
            $TodaysDate = date('d-m-Y', strtotime($refDate));
            $this->checkForSaveDateTime($request);
            $mWaterSiteInspectionsScheduling->saveSiteDateTime($request);
            if ($request->inspectionDate == $TodaysDate) {
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
                $returnData = [
                    "inspectionDate" => $siteInspection->inspection_date,
                    "inspectionTime" => $siteInspection->inspection_time
                ];
                return responseMsgs(true, "Site InspectionDetails!", $returnData, "", "01", ".ms", "POST", "");
            }
            throw new Exception("Invalid data!");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "", "01", ".ms", "POST", "");
        }
    }
}
