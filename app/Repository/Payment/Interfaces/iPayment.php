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
    public function getDepartmentByulb(Request $req);
}