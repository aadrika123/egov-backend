<?php

namespace App\Repository\Payment;

use App\Repository\Payment\PaymentRepository;
use Illuminate\Http\Request;
use App\Models\PaymentMaster;
use Exception;

/**
 * | Created On-17-08-2022 
 * | Created By- Anshu Kumar
 * | Payment Regarding Crud Operations
 */
class EloquentPaymentRepository implements PaymentRepository
{

    /**
     * | Function for Store Payment
     * | @param Request
     * | @param Request $request
     * | @return response using laravel collections
     */
    public function storePayment(Request $request)
    {
        try {
            $payment = new PaymentMaster;
            $payment->payment_id = $request->paymentID;
            $payment->order_id = $request->orderID;
            $payment->amount = $request->amount;
            $payment->payment_method = $request->paymentMethod;
            $payment->payment_date = $request->paymentDate;
            $payment->name = $request->name;
            $payment->email = $request->email;
            $payment->phone = $request->phone;
            $payment->module = $request->module;
            $payment->payment_status = $request->paymentStatus;
            $payment->save();
            $message = ["status" => true, "message" => "Payment Successfully Done", "data" => ""];
            return response($message, 200);
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    /**
     * | Function for Get Payment by Payment-ID
     * | @param payment-id $id
     * | @return reponse
     */
    public function getPaymentByID($id)
    {
        $payment = PaymentMaster::find($id);
        if ($payment) {
            $message = ["status" => true, "message" => "Data Fetched", "data" => remove_null($payment)];
            return response()->json($message, 200);
        } else {
            $message = ["status" => false, "message" => "Data Not Fetched", "data" => ''];
            return response()->json($message, 200);
        }
    }

    /**
     * | Get All Payments 
     * | @return response $message with laravel collection filterations
     */
    public function getAllPayments()
    {
        $payment = PaymentMaster::orderByDesc('id')->get();
        $message = ["status" => true, "message" => "Data Fetched", "data" => remove_null($payment)];
        return response()->json($message, 200);
    }
}
