<?php

namespace App\Repository\Payment\Concrete;

use App\Models\Payment\DepartmentMaster;
use App\Models\Payment\PaymentGatewayDetail;
use App\Models\Payment\PaymentGatewayMaster;
use App\Models\Payment\PaymentReconciliation;
use App\Models\Payment\WebhookPaymentData;
use Illuminate\Http\Request;
use App\Repository\Payment\Interfaces\iPayment;
use App\Repository\Property\Concrete\SafRepository;;

use App\Traits\Payment\Razorpay;
use Illuminate\Support\Facades\Storage;

use Exception;


/**
 * |--------------------------------------------------------------------------------------------------------|
 * | Created On-14-11-2022 
 * | Created By- Sam kerketta
 * | Payment Regarding Crud Operations
 * |--------------------------------------------------------------------------------------------------------|
 */


class PaymentRepository implements iPayment
{
    # traits
    use Razorpay;

    /**
     * | Get Department By Ulb
     * | @param req request from the frontend
     * | @var mReadDepartment collecting data from the table DepartmentMaster
     * | 
     * | Rating : 2
     * | Time :
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
     * | Get Payment gateway details by provided requests
     * | @param req request from the frontend
     * | @param error collecting the operation error
     * | @var mReadPg collecting data from the table PaymentGatewayMaster
     * | 
     * | Rating : 
     * | Time :
     */
    public function getPaymentgatewayByrequests(Request $req)
    {
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
     * | Rating :
     * | Time :
     */
    public function getPgDetails(Request $req)
    {
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
     * | @var mReadPayment : collect webhook payment details
     * | @return mCollection
     * | 
     * | Rating :
     * | Time :
        | Working
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
            )
                ->orderByDesc('id')
                ->get();

            $mCollection = collect($mReadPayment)->map(function ($value, $key) {
                $decode = WebhookPaymentData::select('payment_notes AS userDetails')
                    ->where('payment_id', $value['paymentId'])
                    ->where('payment_order_id', $value['orderId'])
                    ->where('payment_status', $value['status'])
                    ->get();
                $details = json_decode(collect($decode)->first()->userDetails);
                $value['userDetails'] = (object)$details;
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
     * | Rating : 
     * | Time :
        | flag : red
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
     * |
     * | Rating :
     * | Time :
        | USE
     */
    public function verifyPaymentStatus(Request $request)
    {
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
     * |
     * | Rating :
     * | Time :
        | Working
     */
    public function gettingWebhookDetails(Request $request)
    {
        try {
            # creating json of webhook data
            $paymentId = $request->payload['payment']['entity']['id'];
            Storage::disk('public')->put($paymentId . '.json', json_encode($request->all()));

            if (!empty($request)) {
                $mWebhookDetails = $this->collectWebhookDetails($request);
                return $mWebhookDetails;
            }
            return responseMsg(false, "WEBHOOK DATA NOT ACCUIRED!", "");
        } catch (Exception $error) {
            return responseMsg(false, "OPERATIONAL ERROR!", $error->getMessage());
        }
    }

    /**
     * | ------------- geting details of the transaction according to the orderId, paymentId and payment status --------------|
     * | @param requet request from the frontend
     * | @param error collecting the operation error
     * | @var mReadTransactions
     * | @var mCollection
     * |
     * | Rating :
     * | Time:
        | Working
     */
    public function getTransactionNoDetails(Request $request)
    {
        try {
            $objWebhookData = new WebhookPaymentData();
            $mReadTransactions = $objWebhookData->webhookByTransaction($request)
                ->get();

            $mCollection = collect($mReadTransactions)->map(function ($value, $key) {
                $decode = WebhookPaymentData::select('payment_notes AS userDetails')
                    ->where('payment_id', $value['paymentId'])
                    ->where('payment_order_id', $value['orderId'])
                    ->where('payment_status', $value['status'])
                    ->get();
                $details = json_decode(collect($decode)->first()->userDetails);
                $value['userDetails'] = $details;
                return $value;
            });
            if (empty(collect($mCollection)->first())) {
                return responseMsg(false, "data not found!", "");
            }
            return responseMsgs(true, "Data fetched!", remove_null(collect($mCollection)->first()), "", "02", "618.ms", "POST", $request->deviceId);
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
     * | 
     * | Rating :
     * | Time : 
        | Working
     */
    public function getReconcillationDetails()
    {
        try {
            $reconciliation = new PaymentReconciliation();
            $reconciliation = $reconciliation->allReconciliationDetails()
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
     * |
     * | Rating : 
     * | Time :
        | Working
     */
    public function searchReconciliationDetails($request)
    {
        if (empty($request->fromDate) && empty($request->toDate) && null == ($request->chequeDdNo)) {
            return $this->getReconcillationDetails();
        }

        switch ($request) {
            case (null == ($request->chequeDdNo) && !null == ($request->verificationType) && null == ($request->paymentMode)): {
                    $reconciliationTypeWise = $this->reconciliationTypeWise($request);
                    return $reconciliationTypeWise;
                }
                break;
            case (null == ($request->chequeDdNo) && null == ($request->verificationType) && !null == ($request->paymentMode)): {
                    $reconciliationModeWise = $this->reconciliationModeWise($request);
                    return $reconciliationModeWise;
                }
                break;
            case (null == ($request->chequeDdNo) && null == ($request->verificationType) && null == ($request->paymentMode)): {
                    $reconciliationDateWise = $this->reconciliationDateWise($request);
                    return $reconciliationDateWise;
                }
                break;
            case (!null == ($request->chequeDdNo) && null == ($request->verificationType) && null == ($request->paymentMode)): {
                    $reconciliationOnlyTranWise = $this->reconciliationOnlyTranWise($request);
                    return $reconciliationOnlyTranWise;
                }
                break;
            case (!null == ($request->chequeDdNo) && !null == ($request->verificationType) && !null == ($request->paymentMode) && !null == ($request->fromDate)): {
                    $reconciliationWithAll = $this->reconciliationWithAll($request);
                    return $reconciliationWithAll;
                }
                break;
            case (null == ($request->chequeDdNo) && !null == ($request->verificationType) && !null == ($request->paymentMode)): {
                    $reconciliationModeType = $this->reconciliationModeType($request);
                    return $reconciliationModeType;
                }
                break;
            default:
                return ("Some Error try again !");
        }
    }


    /**
     * | --------------------------- UPDATING Payment Reconciliation details ------------------------------- |
     * | @param request
     * | @param error
     * | @var reconciliation
     * | Operation :  Payment Reconciliation / updating the data of the payment Recou..
     * | 
     * | Rating :
     * | Time :
        | Flag move to model
     */
    public function updateReconciliationDetails($request)
    {
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

    #____________________________________(Search Reconciliation - START)___________________________________________#

    /**
     * |--------- reconciliationDateWise 1.1----------
     * |@param request
     * |@var reconciliationDetails
     */
    public function reconciliationDateWise($request)
    {
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

            if (!empty(collect($reconciliationDetails)->first())) {
                return responseMsg(true, "Data Acording to request!", $reconciliationDetails);
            }
            return responseMsg(false, "data not found!", "");
    }

    /**
     * |--------- reconciliationModeWise 1.2----------
     * |@param request
     * |@var reconciliationDetails
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

            if (!empty(collect($reconciliationDetails)->first())) {
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
     * |@var reconciliationDetails
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

            if (!empty(collect($reconciliationDetails)->first())) {
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
     * |@var reconciliationDetails
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

            if (!empty(collect($reconciliationDetails)->first())) {
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
     * |@var reconciliationDetails
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

            if (!empty(collect($reconciliationDetails)->first())) {
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
     * |@var reconciliationDetails
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

            if (!empty(collect($reconciliationDetails)->first())) {
                return responseMsg(true, "Data Acording to request!", $reconciliationDetails);
            }
            return responseMsg(false, "data not found!", "");
        } catch (Exception $error) {
            return responseMsg(false, "ERROR!", $error->getMessage());
        }
    }

    #________________________________________(END)_________________________________________#

    /**
     * |--------- all the transaction details regardless of module ----------
     * |@var object webhookModel
     * |@var transaction
     * |@var userId
     */
    public function allModuleTransaction()
    {
        try {
            $userId = auth()->user()->id;
            $transaction = WebhookPaymentData::select(
                    'webhook_payment_data.payment_transaction_id AS transactionNo',
                    'webhook_payment_data.created_at AS dateOfTransaction',
                    'webhook_payment_data.payment_method AS paymentMethod',
                    'webhook_payment_data.payment_amount AS amount',
                    'webhook_payment_data.payment_status AS paymentStatus',
                    'department_masters.department_name AS modueName'
                )
                ->join('department_masters', 'department_masters.id', '=', 'webhook_payment_data.department_id')
                ->where('user_id', $userId)
                ->get();
            if (!empty(collect($transaction)->first())) {
                return responseMsgs(true, "All transaction for the respective id", $transaction);
            }
            return responseMsg(false, "No Data!", "");
        } catch (Exception $error) {
            return responseMsg(false, "", $error->getMessage());
        }
    }
}
