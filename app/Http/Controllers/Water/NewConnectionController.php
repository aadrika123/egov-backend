<?php

namespace App\Http\Controllers\Water;

use App\Http\Controllers\Controller;
use App\Http\Requests\Water\newApplyRules;
use App\Http\Requests\Water\reqSiteVerification;
use App\MicroServices\DocUpload;
use App\Models\Masters\RefRequiredDocument;
use App\Models\Payment\WebhookPaymentData;
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
use App\Models\Water\WaterConnectionCharge;
use App\Models\Water\WaterConnectionThroughMstrs;
use App\Models\Water\WaterConnectionTypeMstr;
use App\Models\Water\WaterConsumer;
use App\Models\Water\WaterConsumerDemand;
use App\Models\Water\WaterConsumerOwner;
use App\Models\Water\WaterOwnerTypeMstr;
use App\Models\Water\WaterParamConnFee;
use App\Models\Water\WaterPenaltyInstallment;
use App\Models\Water\WaterPropertyTypeMstr;
use App\Models\Water\WaterSiteInspection;
use App\Models\Water\WaterTran;
use App\Models\Workflows\WfActiveDocument;
use App\Models\Workflows\WfRoleusermap;
use App\Models\Workflows\WfWardUser;
use App\Models\Workflows\WfWorkflow;
use App\Models\WorkflowTrack;
use App\Repository\Water\Concrete\WaterNewConnection;
use Illuminate\Http\Request;
use App\Repository\Water\Interfaces\iNewConnection;
use App\Traits\Ward;
use App\Traits\Water\WaterTrait;
use App\Traits\Workflow\Workflow;
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
    public function __construct(iNewConnection $newConnection)
    {
        $this->newConnection = $newConnection;
        $this->_dealingAssistent = Config::get('workflow-constants.DEALING_ASSISTENT_WF_ID');
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
     * |---------------------------------------- Citizen/ View Water Screen For Mobile -------------------------------------------|
     */

    // Get connection type / water
    public function getConnectionType(Request $request)
    {
        try {
            $objConnectionTypes = new WaterConnectionTypeMstr();
            $connectionTypes = $objConnectionTypes->getConnectionType();
            return responseMsgs(true, 'data of the connectionType', $connectionTypes, "", "02", "", "GET", "");
        } catch (Exception $error) {
            return responseMsg(false, $error->getMessage(), "");
        }
    }

    // Get connection through / water
    public function getConnectionThrough(Request $request)
    {
        try {
            $objConnectionThrough = new WaterConnectionThroughMstrs();
            $connectionThrough = $objConnectionThrough->getAllThrough();
            return responseMsgs(true, 'data of the connectionThrough', $connectionThrough, "", "02", "", "GET", "");
        } catch (Exception $error) {
            return responseMsg(false, $error->getMessage(), "");
        }
    }

    // Get property type / water
    public function getPropertyType(Request $request)
    {
        try {
            $objPropertyType = new WaterPropertyTypeMstr();
            $propertyType = $objPropertyType->getAllPropertyType();
            return responseMsgs(true, 'data of the propertyType', $propertyType, "", "02", "", "GET", "");
        } catch (Exception $error) {
            return responseMsg(false, $error->getMessage(), "");
        }
    }

    // Get owner type / water
    public function getOwnerType(Request $request)
    {
        try {
            $objOwnerType = new WaterOwnerTypeMstr();
            $ownerType = $objOwnerType->getallOwnwers();
            return responseMsgs(true, 'data of the ownerType', $ownerType, "", "02", "", "GET", "");
        } catch (Exception $error) {
            return responseMsg(false, $error->getMessage(), "");
        }
    }

    // Get ward no / water
    public function getWardNo(Request $request)
    {
        try {
            $ulbId = auth()->user()->ulb_id;
            $ward = $this->getAllWard($ulbId);
            return responseMsgs(true, "Ward List!", $ward, "", "02", "", "GET", "");
        } catch (Exception $error) {
            return responseMsg(false, $error->getMessage(), "");
        }
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
            $request->validate([
                'applicationId' => 'required',
                'senderRoleId' => 'required',
                'receiverRoleId' => 'required',
                'comment' => "required"
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

    // View Uploaded Documents   
    /**
        | Not used
     */
    public function getWaterDocDetails(Request $request)
    {
        try {
            $request->validate([
                "applicationId" => "required",
            ]);
            return $this->newConnection->getWaterDocDetails($request);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    // Verification/Rejection of Document 
    /**
        | Not used
     */
    public function waterDocStatus(Request $request)
    {
        try {
            $request->validate([
                "applicationId" => "required",
                "docStatus" => "required"
            ]);
            return $this->newConnection->waterDocStatus($request);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    // final Approval or Rejection of the Application
    public function approvalRejectionWater(Request $request)
    {
        try {
            $request->validate([
                "applicationId" => "required",
                "roleId" => "required",
                "status" => "required"
            ]);
            $waterDetails = WaterApplication::find($request->id);
            if ($waterDetails) {
                return $this->newConnection->approvalRejectionWater($request);
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

    // Get Approved Water Appliction   // RECHECK
    public function approvedWaterApplications(Request $request)
    {
        try {
            $consumerNo = collect($request)->first();
            if ($consumerNo) {
                return $this->newConnection->getApprovedWater($request);
            }

            $userId = auth()->user()->id;
            $obj = new WaterApprovalApplicationDetail();
            $chargesObj = new WaterConnectionCharge();
            $approvedWater = $obj->getApplicationRelatedDetails()
                ->select(
                    'water_approval_application_details.id',
                    'consumer_no',
                    'water_approval_application_details.address',
                    'ulb_masters.ulb_name',
                    'water_approval_application_details.ward_id',
                    'ulb_ward_masters.ward_name'
                )
                ->where('user_id', $userId)
                ->get();
            // return $approvedWater->first()->id;
            if ($approvedWater) {
                $connectionCharge = $chargesObj->getWaterchargesById($approvedWater->first()->id)->first();
                $returnWater = collect($approvedWater)->map(
                    function ($value, $key) {
                        $owner = WaterApplicant::select(
                            'applicant_name',
                            'guardian_name',
                            'mobile_no',
                            'email'
                        )
                            ->where('application_id', $value['id'])
                            ->get();
                        $owner = collect($owner)->first();
                        $user = collect($value);
                        return $user->merge($owner);
                    }
                );
                return responseMsgs(true, "List of Approved water Applications!", remove_null($returnWater), "", "02", ".ms", "POST", $request->deviceId);
            }
            throw new Exception("Data Not Found!");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    // Get the water payment details and track details  // RECHECK  
    /**
        | Not used
     */
    public function getIndependentComment(Request $request)
    {
        try {
            $request->validate([
                "id" => "required|int",
            ]);
            $userId = auth()->user()->id;
            $trackObj = new WorkflowTrack();
            $mWaterRef = 'water_applications.id';
            $responseData = $trackObj->getTracksByRefId($mWaterRef, $request->id);
            return responseMsgs(true, "payment Details!", remove_null($responseData), "01", "", ".ms", "POST", $request->deviceId);
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

    // Generate the payment Recipt  
    public function generatePaymentReceipt(Request $req)
    {
        $req->validate([
            'transactionNo' => 'required'
        ]);

        try {
            $mPaymentData = new WebhookPaymentData();
            $mWaterApplication = new WaterApplication();
            $mWaterTransaction = new WaterTran();

            $mTowards = Config::get('waterConstaint.TOWARDS');
            $mAccDescription = Config::get('waterConstaint.ACCOUNT_DESCRIPTION');
            $mDepartmentSection = Config::get('waterConstaint.DEPARTMENT_SECTION');

            $applicationDtls = $mPaymentData->getApplicationId($req->transactionNo);
            $applicationId = json_decode($applicationDtls)->applicationId;

            $applicationDetails = $mWaterApplication->getWaterApplicationsDetails($applicationId);
            $webhookData = $mPaymentData->getPaymentDetailsByPId($req->transactionNo);
            $webhookDetails = collect($webhookData)->last();

            $transactionDetails = $mWaterTransaction->getTransactionDetailsById($applicationId);
            $waterTrans = collect($transactionDetails)->last();

            $epoch = $webhookDetails->payment_created_at;
            $dateTime = new DateTime("@$epoch");
            $transactionTime = $dateTime->format('H:i:s');

            $responseData = [
                "departmentSection" => $mDepartmentSection,
                "accountDescription" => $mAccDescription,
                "transactionDate" => $waterTrans->tran_date,
                "transactionNo" => $waterTrans->tran_no,
                "transactionTime" => $transactionTime,
                "applicationNo" => $applicationDetails->application_no,
                "customerName" => $applicationDetails->applicant_name,
                "customerMobile" => $applicationDetails->mobile_no,
                "address" => $applicationDetails->address,
                "paidFrom" => "",
                "paidFromQtr" => "",
                "paidUpto" => "",
                "paidUptoQtr" => "",
                "paymentMode" => $waterTrans->payment_mode,
                "bankName" => $webhookDetails->payment_bank ?? null,
                "branchName" => "",
                "chequeNo" => "",
                "chequeDate" => "",
                "noOfFlats" => "",
                "monthlyRate" => "",
                "demandAmount" => "",  // if the trans is diff
                "taxDetails" => "",
                "ulbId" => $webhookDetails->ulb_id,
                "WardNo" => $applicationDetails->ward_id,
                "towards" => $mTowards,
                "description" => $waterTrans->tran_type,
                "totalPaidAmount" => $webhookDetails->payment_amount,
                "paidAmtInWords" => getIndianCurrency($webhookDetails->payment_amount),
            ];
            return responseMsgs(true, "Payment Receipt", remove_null($responseData), "", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "", "", "1.0", "", "POST", $req->deviceId ?? "");
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
            $mWaterApplication->parked = true;                        //<------ SAF Pending Status true
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
     */
    public function editWaterDetails(Request $req)
    {
        $req->validate([
            'applicatonId' => 'required|integer',
            // 'owner' => 'array',
            // 'owner.*.ownerId' => 'required|integer',
            // 'owner.*.ownerName' => 'required',
            // 'owner.*.guardianName' => 'required',
            // 'owner.*.mobileNo' => 'numeric|string|digits:10',
            // 'owner.*.aadhar' => 'numeric|string|digits:12|nullable',
            // 'owner.*.email' => 'email|nullable',
        ]);

        try {
            $mWaterApplication = new WaterApplication();
            $mWaterApplicant = new WaterApplicant();
            $refWaterApplications = $mWaterApplication->getWaterApplicationsDetails($req->applicatonId);
            $mOwners = $req->owner;

            if ($refWaterApplications->payment_status == true) {
                throw new Exception("Payment has been made!");
            }
            DB::beginTransaction();
            // $mWaterApplication->editWaterApplication($req, $refWaterApplications);                              // Updation water Basic Details
            // collect($mOwners)->map(function ($owner) use ($mWaterApplicant, $refWaterApplications) {            // Updation of Owner Basic Details
            //     $mWaterApplicant->editWaterOwners($owner, $refWaterApplications);
            // });
            DB::commit();
            return responseMsgs(true, "Successfully Updated the Data", "", 010124, 1.0, "308ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", 010124, 1.0, "308ms", "POST", $req->deviceId);
        }
    }

    // Citizen view : Get Application Details of viewind
    public function getApplicationDetails(Request $request)
    {
        $request->validate([
            'applicationId' => 'required|integer',
        ]);
        try {
            $mWaterConnectionCharge  = new WaterConnectionCharge();
            $mWaterParamConnFee = new WaterParamConnFee();
            $mWaterApplication = new WaterApplication();
            $mWaterApplicant = new WaterApplicant();
            $mWaterTran = new WaterTran();

            # Application Details
            $applicationDetails['applicationDetails'] = $mWaterApplication->fullWaterDetails($request)->first();
            $propertyId = $applicationDetails['applicationDetails']['property_type_id'];
            # Document Details
            $metaReqs = [
                'userId' => auth()->user()->id,
                'ulbId' => auth()->user()->ulb_id,
            ];
            $request->request->add($metaReqs);
            $document = $this->getDocToUpload($request);
            $documentDetails['documentDetails'] = collect($document)['original']['data'];

            # Payment Details 
            $refAppDetails = collect($applicationDetails)->first();
            $waterTransaction = $mWaterTran->getTransNo($refAppDetails->id, $refAppDetails->connection_type)->first();
            $waterTransDetail['waterTransDetail'] = $waterTransaction;

            # owner details
            $ownerDetails['ownerDetails'] = $mWaterApplicant->getOwnerList($request->applicationId)->get();

            # calculation details
            $charges = $mWaterConnectionCharge->getWaterchargesById($refAppDetails['id'])->first();
            $processCall = $mWaterParamConnFee->getCallParameter($propertyId)->first();  // <---------- here
            $calculation['calculation'] =
                [
                    'connectionFee' => $charges['conn_fee'],
                    'penalty' => $charges['penalty'],
                    'totalAmount' => $charges['amount']
                ];
            $callParamenter['callParamenter'] = $processCall;

            $returnData = array_merge($applicationDetails, $documentDetails, $waterTransDetail, $ownerDetails, $calculation, $callParamenter);
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

            $getWaterDetails = $mWaterApplication->getWaterApplicationsDetails($req->applicationId);
            $refImageName = $req->docRefName;
            $refImageName = $getWaterDetails->id . '-' . str_replace(' ', '_', $refImageName);
            $document = $req->document;
            $imageName = $docUpload->upload($refImageName, $document, $relativePath);

            $metaReqs = [
                'moduleId' => Config::get('module-constants.WATER_MODULE_ID'),
                'activeId' => $getWaterDetails->id,
                'workflowId' => $getWaterDetails->workflow_id,
                'ulbId' => $getWaterDetails->ulb_id,
                'relativePath' => $relativePath,
                'document' => $imageName,
                'docCode' => $req->docCode,
                'ownerDtlId' => $req->ownerId,
            ];

            $metaReqs = new Request($metaReqs);
            $mWfActiveDocument->postDocuments($metaReqs);
            return responseMsgs(true, "Document Uploadation Successful", "", "", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "", "1.0", "", "POST", $req->deviceId ?? "");
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
        | Working
     */
    public function getDocToUpload(Request $request)
    {
        $request->validate([
            'applicationId' => 'required|numeric'
        ]);
        try {
            $refApplication     = (array)null;
            $refOwneres         = (array)null;
            $requiedDocs        = (array)null;
            $ownersDoc          = (array)null;
            $testOwnersDoc      = (array)null;
            $data               = (array)null;
            $refWaterNewConnection = new WaterNewConnection();
            $refWfActiveDocument = new WfActiveDocument();
            $moduleId = Config::get('module-constants.WATER_MODULE_ID');

            $connectionId = $request->applicationId;
            $refApplication = WaterApplication::where("status", 1)->find($connectionId);
            if (!$refApplication) {
                throw new Exception("Application Not Found!");
            }

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
                $doc['docVal'] = $refWaterNewConnection->getDocumentList($val->doc_for);  # get All Related Document List
                $docFor = collect($doc['docVal'])->map(function ($value) {
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
                $doc['docVal'] = $refWaterNewConnection->getDocumentList(["ID_PROOF", "CONSUMER_PHOTO"]);
                $refdocForId = collect($doc['docVal'])->map(function ($value, $key) {
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
            return responseMsg(true, "Document Uploaded!", $data);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }

    // Serch the holding and the saf details 
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
                        $getCatagory['catagory'] = $this->checkCatagory($request, $areaInSqft, $propUsageType);
                        $occupancyOwnerType = collect($mPropFloor->getOccupancyType($application['id'], $refTenanted));
                        $owners['owners'] = collect($mPropOwner->getOwnerByPropId($application['id']));
                        $details = $application->merge($areaInSqft)->merge($owners)->merge($occupancyOwnerType)->merge($propUsageType)->merge($getCatagory);
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
                        $getCatagory['catagory'] = $this->checkCatagory($request, $areaInSqft, $safUsageType);
                        $occupancyOwnerType = collect($mPropActiveSafsFloor->getOccupancyType($application['id'], $refTenanted));
                        $owners['owners'] = collect($mPropActiveSafOwners->getOwnerDtlsBySafId($application['id']));
                        $details = $application->merge($areaInSqft)->merge($owners)->merge($occupancyOwnerType)->merge($safUsageType)->merge($getCatagory);
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
     * | check the catagory of the user 
        | Not Used
     */
    public function checkCatagory($request, $areaInSqFt, $propUsageType)
    {
        $refResidential = collect($propUsageType)->first()->first()['usageType'];
        switch ($request->connectionThrough) {
            case ('1'):
                if ($areaInSqFt < 350 && $refResidential == "Residential")   // Static
                {
                    return "BPL";
                }
                return "APL";
                break;
            case ('2'):
                if ($areaInSqFt < 350 && $refResidential == "Residential")   // Static
                {
                    return "BPL";
                }
                return "APL";
                break;
        }
    }

    /**
     * | Get Usage type according to holding
        | Calling function : for the search of the property usage type 
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

    // final submition of the Water Application
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
            $waterTypeDocs['listDocs'] = $this->getWaterDocLists($refWaterApplication);                // Current Object(Saf Docuement List)
            $waterOwnerDocs['ownerDocs'] = collect($refWaterApplicant)->map(function ($owner) use ($refWaterApplication) {
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


    // Filter Document(1.2)
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
            $reqDoc['docFor'] = $documentList->code;

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

    // List of the doc to upload
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
        $documentList = $mRefReqDocs->getCollectiveDocByCode($moduleId, $type);
        return collect($documentList)->map(function ($value, $key) use ($application) {
            return $filteredDocs = $this->filterDocument($value, $application)->first();
        });
    }

    // Get owner Doc list
    public function getOwnerDocLists($refOwners, $application)
    {
        $mRefReqDocs = new RefRequiredDocument();
        $mWfActiveDocument = new WfActiveDocument();
        $moduleId = Config::get('module-constants.WATER_MODULE_ID');
        $type   = ["ID_PROOF", "CONSUMER_PHOTO"];

        $documentList = $mRefReqDocs->getCollectiveDocByCode($moduleId, $type);
        $ownerDocList['documents'] = collect($documentList)->map(function ($value, $key) use ($application) {
            return $filteredDocs = $this->filterDocument($value, $application)->first();
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

    // Search Application
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
}











































// function for the process of the operatio
// public function try(Request $req)
// {
//     $metaData = $this->maps($req->all());
//     $myRequest = new \Illuminate\Http\Request();
//     $myRequest->setMethod($req->getMethod());
//     foreach ($metaData as  $key => $value) {
//         $myRequest->request->add([$key => $value]);
//     }
//     return $myRequest;
// }