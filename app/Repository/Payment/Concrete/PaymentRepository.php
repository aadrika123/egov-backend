<?php

namespace App\Repository\Payment\Concrete;

use App\Models\Payment;
use App\Models\Payment\DepartmentMaster;
use App\Models\Payment\PaymentGatewayDetail;
use App\Models\Payment\PaymentGatewayMaster;
use App\Models\Payment\WebhookPaymentData;
use App\Models\PaymentMaster; //<----------- model(CAUTION)
use Illuminate\Http\Request;
use App\Repository\Payment\Interfaces\iPayment;
use App\Repository\Property\Concrete\SafRepository;
use Illuminate\Support\Facades\Validator;
use App\Traits\Payment\Razorpay;


use Exception;

/**
 * | Created On-14-11-2022 
 * | Created By- sam kerketta
 * | Payment Regarding Crud Operations
 */
class PaymentRepository implements iPayment
{
    use Razorpay; //<-------------- here (TRAIT)

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
     * | @param req request from the frontend
     * | @var mReadDepartment collecting data from the table DepartmentMaster
     * | 
     */
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
     * | Get Payment gateway details by provided requests
     * | @param req request from the frontend
     * | @param error collecting the operation error
     * | @var mReadPg collecting data from the table PaymentGatewayMaster
     * | 
     */
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

        try {
            $mReadPg = PaymentGatewayMaster::select(
                'payment_gateway_masters.id',
                'payment_gateway_masters.pg_full_name AS paymentGatewayName'
            )
                ->join('department_pg_maps', 'department_pg_maps.pg_id', '=', 'payment_gateway_masters.id')
                ->join('ulb_department_maps', 'ulb_department_maps.department_id', '=', 'department_pg_maps.department_id')
                ->where('ulb_department_maps.department_id', $req->departmentId)
                ->where('ulb_department_maps.ulb_id', $req->ulbId)
                ->get();

            if (!empty($mReadPg['0'])) {
                return responseMsg(true, "Data of PaymentGateway", $mReadPg);
            }
            return responseMsg(false, "Data not found", "");
        } catch (Exception $error) {
            return responseMsg(false, "error", $error);
        }
    }


    /**
     * | Get Payment gateway details by required gateway
     * | @param req request from the frontend
     * | @param error collecting the operation error
     * | @var mReadRazorpay collecting data from the table RazorpayPgMaster
     * | 
     */
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
        try {
            $mReadRazorpay = PaymentGatewayDetail::select(
                'payment_gateway_details.pg_name AS paymentGatewayName',
                'payment_gateway_details.pg_details AS details'
            )
                ->join('payment_gateway_masters', 'payment_gateway_masters.id', '=', 'payment_gateway_details.id')
                ->join('department_pg_maps', 'department_pg_maps.pg_id', '=', 'payment_gateway_masters.id')
                ->join('ulb_department_maps', 'ulb_department_maps.department_id', '=', 'department_pg_maps.department_id')

                ->where('ulb_department_maps.department_id', $req->departmentId)
                ->where('ulb_department_maps.ulb_id', $req->ulbId)
                ->where('payment_gateway_masters.id', $req->paymentGatewayId)
                ->get();
            if (!empty($mReadRazorpay['0'])) {
                return responseMsg(true, "Razorpay Data!", $mReadRazorpay);
            }

            return responseMsg(false, "Data Not found", "");
        } catch (Exception $error) {
            return responseMsg(false, "error", $error->getMessage());
        }
    }


    /**
     * | Get Payment details by readind the webhook table
     * | @param req request from the frontend
     * | @param error collecting the operation error
     * | @var mReadPayment collecting data from the table WebhookPaymentData
     * | 
     */
    public function getWebhookDetails()
    {
        try {
            $mReadPayment =  WebhookPaymentData::select(array(
                'event',
                'payment_amount AS amount',
                'payment_order_id AS orderId',
                'payment_contact AS contact',
                'payment_method AS method',
                'payment_email AS email',
                'payment_status AS status'
            ))->get();

            return responseMsg(true, "Data fetched!", $mReadPayment);
        } catch (Exception $error) {
            return responseMsg(false, "Error listed below!", $error->getMessage());
        }
    }


    /**
     * | calling trait for the generation of order id
     * | @param request request from the frontend
     * | @param 
     * | @var 
     * | 
     */
    public function getTraitOrderId(Request $request)  //<------------------ here (INVALID)
    {
        try {
            $safRepo = new SafRepository();
            $calculateSafById = $safRepo->calculateSafBySafId($request);
            $mTotalAmount = $calculateSafById->original['data']['demand']['payableAmount'];
            // return $calculateSafById;
            if ($request->amount == $mTotalAmount) {
                $mOrderDetails = $this->saveGenerateOrderid($request);
                return responseMsg(true, "OrderId Generated!", $mOrderDetails);
            }
            return responseMsg(false, "Operation Amount not matched!", $request->amount);
        } catch (Exception $error) {
            return $error;
        }
    }


    /**
     * | verifiying the payment success and the signature key
     * | @param requet request from the frontend
     * | @param error collecting the operation error
     * | @var 
     * | 
     */
    public function verifyPaymentStatus(Request $request)
    {
        # validation 
        $validated = Validator::make(
            $request->all(),
            [
                'razorpayOrderId' => 'required',
                'razorpayPaymentId' => 'required',
                // 'razorpaySignature' => 'required'
            ]
        );
        if ($validated->fails()) {
            return responseMsg(false, "validation error", $validated->errors(), 401);
        }
        try {
            $mAttributes = null;
            if (!empty($request->razorpaySignature)) {
                $mVerification = $this->paymentVerify($request, $mAttributes);
                return responseMsg(true, "Operation Success!", $mVerification);
            }
            return ("error");
        } catch (Exception $error) {
            return responseMsg(false, "Error listed Below!", $error->getMessage());
        }
    }
}
