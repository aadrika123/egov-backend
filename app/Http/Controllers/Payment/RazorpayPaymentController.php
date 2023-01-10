<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repository\Payment\Interfaces\iPayment;
use Exception;
use Illuminate\Support\Facades\Validator;

/**
 * |-------------------------------- Razorpay Payments ------------------------------------------|
 * | used for testing Razorpay Paymentgateway
    | FLAG : only use for testing purpuse
 */

class RazorpayPaymentController extends Controller
{
    //  Construct Function
    private iPayment $Prepository;
    public function __construct(iPayment $Prepository)
    {
        $this->Prepository = $Prepository;
    }

    //get department by ulbid
    public function getDepartmentByulb(Request $req)
    {
        #   validation
        $validateUser = Validator::make(
            $req->all(),
            [
                'ulbId'   => 'required|integer',
            ]
        );

        if ($validateUser->fails()) {
            return responseMsg(false, 'validation error', $validateUser->errors(), 401);
        }
        return $this->Prepository->getDepartmentByulb($req);
    }

    //get PaymentGateway by request
    public function getPaymentgatewayByrequests(Request $req)
    {
        #   validation
        $validateUser = Validator::make(
            $req->all(),
            [
                'departmentId'   => 'required|integer',
                'ulbId'   => 'required|integer',
            ]
        );

        if ($validateUser->fails()) {
            return responseMsg(false, 'validation error', $validateUser->errors(), 401);
        }
        return $this->Prepository->getPaymentgatewayByrequests($req);
    }

    //get specific PaymentGateway Details according request
    public function getPgDetails(Request $req)
    {
        # validation
        $validateUser = Validator::make(
            $req->all(),
            [
                'departmentId'   => 'required|integer',
                'ulbId'   => 'required|integer',
                'paymentGatewayId'   => 'required|integer',
            ]
        );

        if ($validateUser->fails()) {
            return responseMsg(false, 'validation error', $validateUser->errors(), 401);
        }
        return $this->Prepository->getPgDetails($req);
    }

    //get finla payment details of the webhook
    public function getWebhookDetails()
    {
        return $this->Prepository->getWebhookDetails();
    }

    //verify the payment status
    public function verifyPaymentStatus(Request $req)
    {
        # validation 
        $validated = Validator::make(
            $req->all(),
            [
                'razorpayOrderId' => 'required',
                'razorpayPaymentId' => 'required'
            ]
        );
        if ($validated->fails()) {
            return responseMsg(false, "validation error", $validated->errors(), 401);
        }
        return $this->Prepository->verifyPaymentStatus($req);
    }

    //verify the payment status
    public function gettingWebhookDetails(Request $req)
    {
        return $this->Prepository->gettingWebhookDetails($req);
    }

    //get the details of webhook according to transactionNo
    public function getTransactionNoDetails(Request $req)
    {
        # validation 
        $validated = Validator::make(
            $req->all(),
            [
                'transactionNo' => 'required|integer',
            ]
        );
        if ($validated->fails()) {
            return responseMsg(false, "validation error", $validated->errors(), 401);
        }
        return $this->Prepository->getTransactionNoDetails($req);
    }

    /**
        |-------------------------------------- Payment Reconcillation -----------------------------------------| 
     */

    //get all the details of Payment Reconciliation 
    public function getReconcillationDetails()
    {
        try {
            return $this->Prepository->getReconcillationDetails();
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    // serch the specific details according to the request
    public function searchReconciliationDetails(Request $request)
    {
        return $this->Prepository->searchReconciliationDetails($request);
    }

    // serch the specific details according to the request
    public function updateReconciliationDetails(Request $request)
    {
        # validation 
        $validated = Validator::make(
            $request->all(),
            [
                'transactionNo' => 'required',
                'status' => 'required',
                'date' => 'required|date'
            ]
        );
        if ($validated->fails()) {
            return responseMsg(false, "validation error", $validated->errors(), 401);
        }
        return $this->Prepository->updateReconciliationDetails($request);
    }

    // get all details of the payments of all modules
    public function allModuleTransaction()
    {
        return $this->Prepository->allModuleTransaction();
    }

    // Serch the tranasaction details
    /**
     | Flag
     */
    public function searchTransaction(Request $request)
    {
        try{
            $request->validate([
                'fromDate' => 'required',
                'toDate' => 'required',
            ]);
            return $this->Prepository->searchTransaction($request);
        }catch(Exception $e)
        {
            return responseMsg(false,$e->getMessage(),"");
        }
    }
}
