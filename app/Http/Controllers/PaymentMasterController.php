<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repository\Payment\EloquentPaymentRepository;

class PaymentMasterController extends Controller
{
    /**
     * | Created On-17-08-2022 
     * | Created by-Anshu Kumar
     * | Created for the payments crud operations
     */

    //  Construct Function
    protected $eloquent_repository;
    public function __construct(EloquentPaymentRepository $eloquent_repository)
    {
        $this->Repository = $eloquent_repository;
    }

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
}
