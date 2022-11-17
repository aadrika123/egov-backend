<?php

namespace App\Repository\Payment\Interfaces;

use Illuminate\Http\Request;

/**
 * | Created On-14
 * | Created By-sam kerketta
 * | Interface for the Eloquent Repostory for PaymentRepository
 */

interface iPayment
{
    /* 
    public function storePayment(Request $request);
    public function getPaymentByID($id);
    public function getAllPayments();
    */

    # payment Gateway (RAZORPAY/Property)
    public function getDepartmentByulb(Request $req);
    public function getPaymentgatewayByrequests(Request $req);
    public function getPgDetails(Request $req);
    public function getWebhookDetails();
    public function getTraitOrderId(Request $request); //<--------------- here(INVALID) 
    public function verifyPaymentStatus(Request $request);

}
