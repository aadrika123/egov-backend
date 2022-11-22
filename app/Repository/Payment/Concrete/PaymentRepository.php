<?php

namespace App\Repository\Payment\Concrete;

use App\Models\Payment;
use App\Models\Payment\DepartmentMaster;
use App\Models\Payment\PaymentGatewayDetail;
use App\Models\Payment\PaymentGatewayMaster;
use App\Models\Payment\PaymentReconciliation;
use App\Models\Payment\PaymentReject;
use App\Models\Payment\PaymentSuccess;
use App\Models\Payment\WebhookPaymentData;
use App\Models\PaymentMaster; //<----------- model(CAUTION)
use Illuminate\Http\Request;
use App\Repository\Payment\Interfaces\iPayment;
use App\Repository\Property\Concrete\SafRepository;
use Illuminate\Support\Facades\Validator;
use App\Traits\Payment\Razorpay;
use Carbon\Carbon;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

use Exception;
use Illuminate\Support\Facades\Config;
use PhpParser\Node\Expr\Empty_;

/**
 * | Created On-14-11-2022 
 * | Created By- sam kerketta
 * | Payment Regarding Crud Operations
 */
class PaymentRepository implements iPayment
{
    use Razorpay; //<-------------- here (TRAIT)
    private $refRazorpayId = "rzp_test_3MPOKRI8WOd54p";
    private $refRazorpayKey = "k23OSfMevkBszuPY5ZtZwutU";

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
        # operation
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
            $mReadPayment =  WebhookPaymentData::select(
                'payment_transaction_id AS transactionNo',
                'payment_order_id AS orderId',
                'payment_id AS paymentId',
                'payment_amount AS amount',
                'payment_status AS status',
                'created_at AS date',
                // 'payment_notes AS notes'
            )->get();

            $mCollection = collect($mReadPayment)->map(function ($value, $key) {
                $decode = WebhookPaymentData::select('payment_notes AS userDetails')
                    ->where('payment_id', $value['paymentId'])
                    ->where('payment_order_id', $value['orderId'])
                    ->where('payment_status', $value['status'])
                    ->get();
                $details = json_decode($decode['0']->userDetails);
                $value['userDetails'] = (object)$details;
                // $date = $value['date'];
                // $value['date']=Str::limit($date, 10);
                return $value;
            });
            return responseMsg(true, "Data fetched!", $mCollection);
        } catch (Exception $error) {
            return responseMsg(false, "Error listed below!", $error->getMessage());
        }
    }


    /**
     * |--------------------------------------- Payment Gateway --------------------------------------
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
     * | @var mAttributes
     * | @var mVerification
     */
    public function verifyPaymentStatus(Request $request)
    {
        # validation 
        $validated = Validator::make(
            $request->all(),
            [
                'razorpayOrderId' => 'required',
                'razorpayPaymentId' => 'required'
            ]
        );
        if ($validated->fails()) {
            return responseMsg(false, "validation error", $validated->errors(), 401);
        }
        try {
            $mAttributes = null;
            $mVerification = $this->paymentVerify($request, $mAttributes);
            return responseMsg(true, "Operation Success!", $mVerification);
        } catch (Exception $error) {
            return responseMsg(false, "Error listed Below!", $error->getMessage());
        }
    }


    /**
     * | ----------------------------------- payment Gateway ENDS -------------------------------
     * | collecting the data provided by the webhook in database
     * | @param requet request from the frontend
     * | @param error collecting the operation error
     * | @var mAttributes
     * | @var mVerification
     */
    public function gettingWebhookDetails(Request $request)
    {
        try {
            # creating json of webhook data
            $paymentId = $request->payload['payment']['entity']['id'];
            Storage::disk('public')->put($paymentId . '.json', json_encode($request->all()));

            if (!empty($request)) {
                $mWebhookDetails = $this->collectWebhookDetails($request);
                // return responseMsg(true, "OPERATION SUCCESS", $mWebhookDetails);
                return $mWebhookDetails;
            }
            return responseMsg(false, "WEBHOOK DATA NOT ACCUIRED!", "");
        } catch (Exception $error) {
            return responseMsg(false, "OPERATIONAL ERROR!", $error->getMessage());
        }
    }

    /**
     * | geting details of the transaction according to the orderId, paymentId and payment status
     * | @param requet request from the frontend
     * | @param error collecting the operation error
     * | @var mReadTransactions
     * | @var mCollection
     */
    public function getTransactionNoDetails(Request $request)
    {
        # validation 
        $validated = Validator::make(
            $request->all(),
            [
                'transactionNo' => 'required|integer',
            ]
        );
        if ($validated->fails()) {
            return responseMsg(false, "validation error", $validated->errors(), 401);
        }
        try {
            $mReadTransactions =  WebhookPaymentData::select(
                'payment_order_id AS orderId',
                'payment_amount AS amount',
                'payment_status AS status',
                'payment_bank AS bank',
                'payment_contact AS contact',
                'payment_method AS method',
                'payment_id AS paymentId',
                'payment_transaction_id AS transactionNo',
                'payment_acquirer_data_value AS paymentAcquirerDataValue',
                'payment_acquirer_data_type AS paymentAcquirerDataType',
                'payment_error_reason AS paymentErrorReason',
                'payment_error_source AS paymentErrorSource',
                'payment_error_description AS paymentErrorDescription',
                'payment_error_code AS paymentErrorCode',
                'payment_email AS emails',
                'payment_vpa AS  paymentVpa',
                'payment_wallet AS paymentWallet',
                'payment_card_id AS paymentCardId'
            )
                ->where('payment_transaction_id', $request->transactionNo)
                ->get();

            $mCollection = collect($mReadTransactions)->map(function ($value, $key) {
                $decode = WebhookPaymentData::select('payment_notes AS userDetails')
                    ->where('payment_id', $value['paymentId'])
                    ->where('payment_order_id', $value['orderId'])
                    ->where('payment_status', $value['status'])
                    ->get();
                $details = json_decode($decode['0']->userDetails);
                $value['userDetails'] =  $details;
                return $value;
            });
            if (!empty($mCollection['0']) && $mCollection['0'] == !null) {
                return responseMsg(true, "Data fetched!", $mCollection['0']);
            }
            return responseMsg(false, "data not found", "");
        } catch (Exception $error) {
            return responseMsg(false, "Error listed below!", $error->getMessage());
        }
    }


    /**
     * | --------------------------- Payment Reconciliation (/1.6) ------------------------------- |
     * | @param request
     * | @param error
     * | @var reconciliation
     * | Operation :  Payment Reconciliation / viewing all data
     * | this -> naming
     * | here -> variable
     * | (/) -> the api also calls this function
     */
    public function getReconcillationDetails()
    {
        try {
            $reconciliation = PaymentReconciliation::select(
                'ulb_id AS ulbId',
                'department_id AS dpartmentId',
                'transaction_no AS transactionNo',
                'payment_mode AS paymentMode',
                'date AS transactionDate',
                'status',
                'cheque_no AS chequeNo',
                'cheque_date AS chequeDate',
                'branch_name AS branchName',
                'bank_name AS bankName',
                'transaction_amount AS amount',
                'clearance_date AS clearanceDate'
            )
                ->get();
            return responseMsg(true, "Payment Reconciliation data!", $reconciliation);
        } catch (Exception $error) {
            return responseMsg(false, "ERROR!", $error->getMessage());
        }
    }



    /**
     * | -------------------- Payment Reconciliation details acoording to request details (1)------------------------------- |
     * | @param request
     * | @param error
     * | @var reconciliationTypeWise
     * | @var reconciliationModeWise
     * | @var reconciliationDateWise
     * | @var reconciliationOnlyTranWise
     * | @var reconciliationWithAll
     * | Operation :  Payment Reconciliation / searching for the specific data
     * | this -> naming
     * | here -> variable
     */
    public function searchReconciliationDetails($request)
    {
        if (empty($request->fromDate) && empty($request->toDate) && null == ($request->chequeDdNo)) {
            return $this->getReconcillationDetails();
        }
// return $request;
        switch ($request) {
            case (null == ($request->chequeDdNo) && !null == ($request->verificationType) && null == ($request->paymentMode)): {
                    $reconciliationTypeWise = $this->reconciliationTypeWise($request);
                    return $reconciliationTypeWise;
                }
            case (null == ($request->chequeDdNo) && null == ($request->verificationType) && !null == ($request->paymentMode)): {
                    $reconciliationModeWise = $this->reconciliationModeWise($request);
                    return $reconciliationModeWise;
                }
            case (null == ($request->chequeDdNo) && null == ($request->verificationType) && null == ($request->paymentMode)): {
                    $reconciliationDateWise = $this->reconciliationDateWise($request);
                    return $reconciliationDateWise;
                }
            case (!null == ($request->chequeDdNo) && null == ($request->verificationType) && null == ($request->paymentMode)): {
                    $reconciliationOnlyTranWise = $this->reconciliationOnlyTranWise($request);
                    return $reconciliationOnlyTranWise;
                }
            case (!null == ($request->chequeDdNo) && !null == ($request->verificationType) && !null == ($request->paymentMode) && !null == ($request->fromDate)): {
                    $reconciliationWithAll = $this->reconciliationWithAll($request);
                    return $reconciliationWithAll;
                }
            case (null == ($request->chequeDdNo) && !null == ($request->verificationType) && !null == ($request->paymentMode)): {
                $reconciliationModeType = $this->reconciliationModeType($request);    
                return $reconciliationModeType;
                }
            default:
                return ("some error renter the details!");
        }
    }


    /**
     * | --------------------------- UPDATING Payment Reconciliation details ------------------------------- |
     * | @param request
     * | @param error
     * | @var reconciliation
     * | Operation :  Payment Reconciliation / updating the data of the payment Recou..
     * | this -> naming
     * | here -> variable
     */
    public function updateReconciliationDetails($request)
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
        // return $request;
        try {
            PaymentReconciliation::where('transaction_no', $request->transactionNo)
                ->update([
                    'status' => $request->status,
                    'date' => $request->date,
                    'remark' => $request->reason,
                    'cancellation_charges' => $request->cancellationCharges
                ]);
            return responseMsg(true, "Data Saved!", "");
        } catch (Exception $error) {
            return responseMsg(false, "ERROR!", $error->getMessage());
        }
    }













    /**
     * |--------- reconciliationDateWise 1.1----------
     * |@param request
     */
    public function reconciliationDateWise($request)
    {
        try {
            $reconciliationDetails = PaymentReconciliation::select(
                'ulb_id AS ulbId',
                'department_id AS dpartmentId',
                'transaction_no AS transactionNo',
                'payment_mode AS paymentMode',
                'date AS transactionDate',
                'status',
                'cheque_no AS chequeNo',
                'cheque_date AS chequeDate',
                'branch_name AS branchName',
                'bank_name AS bankName',
                'transaction_amount AS amount',
                'clearance_date AS clearanceDate'
            )
                ->whereBetween('date', [$request->fromDate, $request->toDate])
                ->get();

            if (!empty($reconciliationDetails['0'])) {
                return responseMsg(true, "Data Acording to request!", $reconciliationDetails);
            }
            return responseMsg(false, "data not found!", "");
        } catch (Exception $error) {
            return responseMsg(false, "ERROR!", $error->getMessage());
        }
    }

    /**
     * |--------- reconciliationModeWise 1.2----------
     * |@param request
     */
    public function reconciliationModeWise($request)
    {
        try {
            $reconciliationDetails = PaymentReconciliation::select(
                'ulb_id AS ulbId',
                'department_id AS dpartmentId',
                'transaction_no AS transactionNo',
                'payment_mode AS paymentMode',
                'date AS transactionDate',
                'status',
                'cheque_no AS chequeNo',
                'cheque_date AS chequeDate',
                'branch_name AS branchName',
                'bank_name AS bankName',
                'transaction_amount AS amount',
                'clearance_date AS clearanceDate'
            )
                ->whereBetween('date', [$request->fromDate, $request->toDate])
                ->where('payment_mode', $request->paymentMode)
                ->get();

            if (!empty($reconciliationDetails['0'])) {
                return responseMsg(true, "Data Acording to request!", $reconciliationDetails);
            }
            return responseMsg(false, "data not found!", "");
        } catch (Exception $error) {
            return responseMsg(false, "ERROR!", $error->getMessage());
        }
    }

    /**
     * |--------- reconciliationTypeWise 1.3----------
     * |@param request
     */
    public function reconciliationTypeWise($request)
    {
        try {
            $reconciliationDetails = PaymentReconciliation::select(
                'ulb_id AS ulbId',
                'department_id AS dpartmentId',
                'transaction_no AS transactionNo',
                'payment_mode AS paymentMode',
                'date AS transactionDate',
                'status',
                'cheque_no AS chequeNo',
                'cheque_date AS chequeDate',
                'branch_name AS branchName',
                'bank_name AS bankName',
                'transaction_amount AS amount',
                'clearance_date AS clearanceDate'
            )
                ->whereBetween('date', [$request->fromDate, $request->toDate])
                ->where('status', $request->verificationType)
                ->get();

            if (!empty($reconciliationDetails['0'])) {
                return responseMsg(true, "Data Acording to request!", $reconciliationDetails);
            }
            return responseMsg(false, "data not found!", "");
        } catch (Exception $error) {
            return responseMsg(false, "ERROR!", $error->getMessage());
        }
    }

    /**
     * |--------- reconciliationOnlyTranWise 1.4-------
     * |@param request
     */
    public function reconciliationOnlyTranWise($request)
    {
        try {
            $reconciliationDetails = PaymentReconciliation::select(
                'ulb_id AS ulbId',
                'department_id AS dpartmentId',
                'transaction_no AS transactionNo',
                'payment_mode AS paymentMode',
                'date AS transactionDate',
                'status',
                'cheque_no AS chequeNo',
                'cheque_date AS chequeDate',
                'branch_name AS branchName',
                'bank_name AS bankName',
                'transaction_amount AS amount',
                'clearance_date AS clearanceDate'
            )
                ->where('cheque_no', $request->chequeDdNo)
                ->get();

            if (!empty($reconciliationDetails['0'])) {
                return responseMsg(true, "Data Acording to request!", $reconciliationDetails);
            }
            return responseMsg(false, "data not found!", "");
        } catch (Exception $error) {
            return responseMsg(false, "ERROR!", $error->getMessage());
        }
    }

    /**
     * |--------- reconciliationOnlyTranWise 1.5--------
     * |@param request
     */
    public function reconciliationWithAll($request)
    {
        try {
            $reconciliationDetails = PaymentReconciliation::select(
                'ulb_id AS ulbId',
                'department_id AS dpartmentId',
                'transaction_no AS transactionNo',
                'payment_mode AS paymentMode',
                'date AS transactionDate',
                'status',
                'cheque_no AS chequeNo',
                'cheque_date AS chequeDate',
                'branch_name AS branchName',
                'bank_name AS bankName',
                'transaction_amount AS amount',
                'clearance_date AS clearanceDate'
            )
                ->whereBetween('date', [$request->fromDate, $request->toDate])
                ->where('payment_mode', $request->paymentMode)
                ->where('status', $request->verificationType)
                ->where('cheque_no', $request->chequeDdNo)
                ->get();

            if (!empty($reconciliationDetails['0'])) {
                return responseMsg(true, "Data Acording to request!", $reconciliationDetails);
            }
            return responseMsg(false, "data not found!", "");
        } catch (Exception $error) {
            return responseMsg(false, "ERROR!", $error->getMessage());
        }
    }

       /**
     * |--------- reconciliationDateWise 1.1----------
     * |@param request
     */
    public function reconciliationModeType($request)
    {
        try {
            $reconciliationDetails = PaymentReconciliation::select(
                'ulb_id AS ulbId',
                'department_id AS dpartmentId',
                'transaction_no AS transactionNo',
                'payment_mode AS paymentMode',
                'date AS transactionDate',
                'status',
                'cheque_no AS chequeNo',
                'cheque_date AS chequeDate',
                'branch_name AS branchName',
                'bank_name AS bankName',
                'transaction_amount AS amount',
                'clearance_date AS clearanceDate'
            )
                ->whereBetween('date', [$request->fromDate, $request->toDate])
                ->where('payment_mode', $request->paymentMode)
                ->where('status', $request->verificationType)
                ->get();

            if (!empty($reconciliationDetails['0'])) {
                return responseMsg(true, "Data Acording to request!", $reconciliationDetails);
            }
            return responseMsg(false, "data not found!", "");
        } catch (Exception $error) {
            return responseMsg(false, "ERROR!", $error->getMessage());
        }
    }
}
