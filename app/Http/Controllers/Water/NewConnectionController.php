<?php

namespace App\Http\Controllers\Water;

use App\Http\Controllers\Controller;
use App\Http\Requests\Water\reqSiteVerification;
use App\Models\Payment\WebhookPaymentData;
use App\Models\UlbWardMaster;
use App\Models\Water\WaterApplicant;
use App\Models\Water\WaterApplication;
use App\Models\Water\WaterApprovalApplicationDetail;
use App\Models\Water\WaterConnectionCharge;
use App\Models\Water\WaterConnectionThroughMstrs;
use App\Models\Water\WaterConnectionTypeMstr;
use App\Models\Water\WaterConsumerDemand;
use App\Models\Water\WaterOwnerTypeMstr;
use App\Models\Water\WaterPropertyTypeMstr;
use App\Models\Water\WaterSiteInspection;
use App\Models\Water\WaterTran;
use App\Models\Workflows\WfRoleusermap;
use App\Models\Workflows\WfWardUser;
use App\Models\WorkflowTrack;
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

class NewConnectionController extends Controller
{
    use Ward;
    use Workflow;
    use WaterTrait;

    private iNewConnection $newConnection;
    public function __construct(iNewConnection $newConnection)
    {
        $this->newConnection = $newConnection;
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
     */
    public function store(Request $request)
    {
        try {
            $validateUser = Validator::make(
                $request->all(),
                [
                    'connectionTypeId'   => 'required|integer',
                    'propertyTypeId'     => 'required|integer',
                    'ownerType'          => 'required',
                    'wardId'             => 'required|integer',
                    'areaSqft'           => 'required',
                    'landmark'           => 'required',
                    'pin'                => 'required|digits:6',
                    'elecKNo'            => 'required',
                    'elecBindBookNo'     => 'required',
                    'elecAccountNo'      => 'required',
                    'elecCategory'       => 'required',
                    'connection_through' => 'required|integer',
                    'owners'             => 'required',
                    'ulbId'              => 'required'
                ]
            );

            if ($validateUser->fails()) {
                return responseMsg(false, "Validation Error!", $validateUser->getMessageBag());
            }
            return $this->newConnection->store($request);
        } catch (Exception $error) {
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
                'appId' => 'required',
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
                'id' => 'required'
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
    public function getWaterDocDetails(Request $request)
    {
        try {
            $request->validate([
                "id" => "required",
            ]);
            return $this->newConnection->getWaterDocDetails($request);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    // Verification/Rejection of Document 
    public function waterDocStatus(Request $request)
    {
        try {
            $request->validate([
                "id" => "required",
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
                "id" => "required",
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
                'id' => 'required|integer'
            ]);
            return $this->newConnection->commentIndependent($request);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    // Get Approved Water Appliction
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

    // Get the water payment details and track details
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

    // Get the Field fieldVerifiedInbox
    public function fieldVerifiedInbox(Request $request)
    {
        try {
            return $this->newConnection->fieldVerifiedInbox($request);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    // Field Verification of water Applications
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

    // Back to Citizen 
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
    public function deleteWaterApplication(Request $req)
    {
        $req->validate([
            'applicationId' => 'required|integer'
        ]);
        try {
            $userId = auth()->user()->id;
            $mWaterApplication = new WaterApplication();
            $mWaterApplicant = new WaterApplicant();
            $applicantDetals = $mWaterApplication->getWaterApplicationsDetails($req->applicationId);

            if (!$applicantDetals) {
                throw new Exception("Data or Owner not found!");
            }
            if ($applicantDetals->payment_status == true) {
                throw new Exception("Your paymnet is done application Cannot be Deleted!");
            }
            if ($applicantDetals->user_id == $userId) {
                DB::beginTransaction();
                $mWaterApplication->deleteWaterApplication($req->applicationId);
                $mWaterApplicant->deleteWaterApplicant($req->applicationId);
                DB::commit();
                return responseMsgs(true, "Application Successfully Deleted", "", "", "1.0", "", "POST", $req->deviceId);
            }
            throw new Exception("You'r not the user of this form!");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }
}
