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
    // Get All Payments
    public function getDepartmentByulb(Request $req)
    {
        return $this->Prepository->getDepartmentByulb($req);
    }
}
