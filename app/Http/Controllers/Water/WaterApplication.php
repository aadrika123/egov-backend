<?php

namespace App\Http\Controllers\water;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Water\NewConnectionController;
use App\MicroServices\IdGeneration;
use App\Models\Payment\TempTransaction;
use App\Models\Water\WaterApplicant;
use App\Models\Water\WaterApplication as WaterWaterApplication;
use App\Models\Water\WaterChequeDtl;
use App\Models\Water\WaterConsumer;
use App\Models\Water\WaterConsumerDemand;
use App\Models\Water\WaterPenaltyInstallment;
use App\Models\Water\WaterTran;
use App\Models\Water\WaterTranDetail;
use App\Models\Workflows\WfWorkflowrolemap;
use App\Models\WorkflowTrack;
use App\Repository\Water\Interfaces\iNewConnection;
use App\Repository\Water\Interfaces\IWaterNewConnection;
use App\Traits\Workflow\Workflow;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class WaterApplication extends Controller
{
    use Workflow;
    private $Repository;
    private $_NewConnectionController;
    public function __construct(IWaterNewConnection $Repository, iNewConnection $newConnection)
    {
        $this->Repository = $Repository;
        $this->_NewConnectionController = new NewConnectionController($newConnection);
    }

    public function applyApplication(Request $request)
    {
        return $this->Repository->applyApplication($request);
    }
    public function getCitizenApplication(Request $request)
    {
        try {
            $returnValue = $this->Repository->getCitizenApplication($request);
            return responseMsg(true, "", remove_null($returnValue));
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }
    public function handeRazorPay(Request $request)
    {
        return $this->Repository->handeRazorPay($request);
    }
    public function readTransectionAndApl(Request $request)
    {
        return $this->Repository->readTransectionAndApl($request);
    }
    public function paymentRecipt(Request $request)
    {
        $request->validate([
            'transectionNo' => 'required'
        ]);
        return $this->Repository->paymentRecipt($request->transectionNo);
    }
    public function documentUpload(Request $request)
    {
        return $this->Repository->documentUpload($request);
    }
    public function getUploadDocuments(Request $request)
    {
        return $this->Repository->getUploadDocuments($request);
    }
    public function calWaterConCharge(Request $request)
    {
        return $this->Repository->calWaterConCharge($request);
    }

    #--------------------------------------------------------Dashbording----------------------------------------------------------#

    /**
     * | Get All application Applied from jsk
     * | @param
     * | @var 
     * | @return 
        | Serial No : 
        | Not Working
     */
    public function getJskAppliedApplication(Request $request)
    {
        try {
            $mWaterApplication = new WaterWaterApplication();
            $mWaterTran = new WaterTran();
            $rawApplication = $mWaterApplication->getJskAppliedApplications();
            $refTransaction = $mWaterTran->tranDetailByDate();
            $applicationDetails = DB::select($rawApplication);
            $transactionDetails = DB::select($refTransaction);

            $returnData['applicationCount'] = collect($applicationDetails)->count();
            $returnData['totalCollection']  = collect($transactionDetails)->pluck('amount')->sum();
            $returnData['chequeCollection'] = collect($transactionDetails)->where('payment_mode', 'Cheque')->count();
            $returnData['onlineCollection'] = collect($transactionDetails)->where('payment_mode', 'Online')->count();
            $returnData['cashCollection']   = collect($transactionDetails)->where('payment_mode', 'Cash')->count();
            $returnData['ddCollection']     = collect($transactionDetails)->where('payment_mode', 'DD')->count();
            $returnData['neftCollection']   = collect($transactionDetails)->where('payment_mode', 'Neft')->count();
            return responseMsgs(true, "dashbord Data!", remove_null($returnData), "", "02", ".ms", "POST", "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), $e->getFile(), "", "01", ".ms", "POST", $request->deviceId);
        }
    }


    /**
     * | Workflow dasboarding details
     * | @param request 
     */
    public function workflowDashordDetails(Request $req)
    {
        try {
            $userId = authUser()->id;
            $ulbId = authUser()->ulb_id;
            $roleId = $this->getRoleIdByUserId($userId)->pluck('wf_role_id');
            $wfMstId = Config::get("workflow-constants.WATER_MASTER_ID");
            $moduleId = Config::get("module-constants.WATER_MODULE_ID");
            $WorkflowTrack = new WorkflowTrack();
            $mWaterWaterApplication = new WaterWaterApplication();
            $metaRequest = new request();
            $metaRequest->request->add([
                'workflowId'    => $wfMstId,
                'ulbId'         => $ulbId,
                'moduleId'      => $moduleId
            ]);
            if (!$roleId) {
                throw new Exception("role Not Defined!");
            }
            $dateWiseData = $WorkflowTrack->getWfDashbordData($metaRequest)->get();
            $applicationCount  = $mWaterWaterApplication->getApplicationByRole($roleId)->count();
            $returnData['todayForwardCount'] = collect($dateWiseData)->where('sender_role_id', $roleId)->count();
            $returnData['todayReceivedCount'] = collect($dateWiseData)->where('receiver_role_id', $roleId)->count();
            $returnData['pendingApplication'] = $applicationCount;
            return responseMsgs(true, "", remove_null($returnData), "", "01", ".ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            dd($e->getLine(), $e->getFile());
            return responseMsgs(false, $e->getMessage(), $e->getFile(), "", "01", ".ms", "POST", "");
        }
    }
}
