<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repository\Payment\Interfaces\iPayment;

class RazorpayPaymentController extends Controller
{
    //  Construct Function
    private iPayment $Prepository;
    public function __construct(iPayment $Prepository)
    {
        $this->Prepository = $Prepository;
    }

    /*
    // Store Payment
    public function storePayment(Request $request)
    {
        return $this->Repository->storePayment($request);
    }

    // Get Payment by id
    public function getPaymentByID($id)
    {
        return $this->Repository->getPaymentByID($id);
    }

    // Get All Payments
    public function getAllPayments()
    {
        return $this->Repository->getAllPayments();
    }  
    */

    //get department by ulbid
    public function getDepartmentByulb(Request $req)
    {
        return $this->Prepository->getDepartmentByulb($req);
    }


    //get PaymentGateway by request
    public function getPaymentgatewayByrequests(Request $req)
    {
        return $this->Prepository->getPaymentgatewayByrequests($req);
    }

     //get specific PaymentGateway Details according request
     public function getPgDetails(Request $req)
     {
         return $this->Prepository->getPgDetails($req);
     }

     //get finla payment details of the webhook
     public function getWebhookDetails()
     {
         return $this->Prepository->getWebhookDetails();
     }

     //get order Id of the transaction
     public function getTraitOrderId(Request $req) //<------------------ here (INVALID)
     {
         return $this->Prepository->getTraitOrderId($req);
     }

     //verify the payment status
     public function verifyPaymentStatus(Request $req)
     {
         return $this->Prepository->verifyPaymentStatus($req);
     }
}
