<?php

namespace App\Repository\Payment\Concrete;

use App\Models\Payment\DepartmentMaster; //<----------- model(CAUTION)
use App\Models\Payment\UlbDepartmentMap; //<----------- model(CAUTION)
use App\Models\PaymentMaster; //<----------- model(CAUTION)
use Illuminate\Http\Request;
use App\Repository\Payment\Interfaces\iPayment;
use Exception;

/**
 * | Created On-14-11-2022 
 * | Created By- sam kerketta
 * | Payment Regarding Crud Operations
 */
class PaymentRepository implements iPayment
{

    /**
     * | Function for Store Payment
     * | @param Request
     * | @param Request $request
     * | @return response using laravel collections
     */
    // public function storePayment(Request $request)
    // {
    //     try {
    //         $payment = new PaymentMaster;
    //         $payment->payment_id = $request->paymentID;
    //         $payment->order_id = $request->orderID;
    //         $payment->amount = $request->amount;
    //         $payment->payment_method = $request->paymentMethod;
    //         $payment->payment_date = $request->paymentDate;
    //         $payment->name = $request->name;
    //         $payment->email = $request->email;
    //         $payment->phone = $request->phone;
    //         $payment->module = $request->module;
    //         $payment->payment_status = $request->paymentStatus;
    //         $payment->save();
    //         $message = ["status" => true, "message" => "Payment Successfully Done", "data" => ""];
    //         return response($message, 200);
    //     } catch (Exception $e) {
    //         return response()->json($e, 400);
    //     }
    // }

    /**
     * | Function for Get Payment by Payment-ID
     * | @param payment-id $id
     * | @return reponse
     */
    // public function getPaymentByID($id)
    // {
    //     $payment = PaymentMaster::find($id);
    //     if ($payment) {
    //         $message = ["status" => true, "message" => "Data Fetched", "data" => remove_null($payment)];
    //         return response()->json($message, 200);
    //     } else {
    //         $message = ["status" => false, "message" => "Data Not Fetched", "data" => ''];
    //         return response()->json($message, 200);
    //     }
    // }

    /**
     * | Get All Payments 
     * | @return response $message with laravel collection filterations
     */
    // public function getAllPayments()
    // {
    //     $payment = PaymentMaster::orderByDesc('id')->get();
    //     $message = ["status" => true, "message" => "Data Fetched", "data" => remove_null($payment)];
    //     return response()->json($message, 200);
    // }

    /**
     * | Get Department By Ulb
     * | @return req request from the frontend
     * | @var mReadDepartment collecting data from the table DepartmentMaster
     * | 
     */
    public function getDepartmentByulb(Request $req)
    {
        try {
            $mReadDepartment = DepartmentMaster::select(
                'department_masters.id',
                'department_masters.department_name AS departmentName'
            )
                ->join('ulb_department_maps', 'ulb_department_maps.department_id', '=', 'department_masters.id')
                ->where('ulb_department_maps.ulb_id', $req->ulbId)
                ->get();

            if (!empty($mReadDepartment['0'])) {
                return responseMsg(true, "Data according to ulbid", $mReadDepartment);
            }
            return responseMsg(false, "Data not exist", "");
        } catch (Exception $error) {
            return responseMsg(false, "Error", $error->getMessage());
        }
    }


    /**
     * | Get Department By Ulb
     * | @return req request from the frontend
     * | @var mReadDepartment collecting data from the table DepartmentMaster
     * | 
     */
    // public function getPg(Request $req)
    // {

    // }
}
