<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Payment\WebhookPaymentData;
use Illuminate\Http\Request;
use App\Repository\Payment\Interfaces\iPayment;
use App\Traits\Payment\Razorpay;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;


/**
 * |-------------------------------- Razorpay Payments ------------------------------------------|
 * | used for testing Razorpay Paymentgateway
    | FLAG : only use for testing purpuse
 */

class RazorpayPaymentController extends Controller
{
    //  Construct Function
    # traits
    use Razorpay;
    private iPayment $Prepository;
    public function __construct(iPayment $Prepository)
    {
        $this->Prepository = $Prepository;
    }

    //get department by ulbid
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
        return $this->Prepository->getDepartmentByulb($req);
    }

    //get PaymentGateway by request
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
        return $this->Prepository->getPaymentgatewayByrequests($req);
    }

    //get specific PaymentGateway Details according request
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
        return $this->Prepository->getPgDetails($req);
    }

    //get finla payment details of the webhook
    public function getWebhookDetails()
    {
        return $this->Prepository->getWebhookDetails();
    }


    /**
     * | Verify the payment status 
     * | Use to check the actual paymetn from the server 
        | Testing
        | This
     */
    public function verifyPaymentStatus(Request $req)
    {
        $req->validate([
            // 'razorpayOrderId' => 'required',
            'razorpayPaymentId' => 'required',
        ]);
        try {
            return responseMsgs(true, "payment On process!", [], "", "01", "", "POST", $req->deviceId);
            return $this->Prepository->verifyPaymentStatus($req);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "01", "", "POST", $req->deviceId);
        }
    }

    //verify the payment status
    /**
        | This
     */
    public function gettingWebhookDetails(Request $req)
    {
        return $this->Prepository->gettingWebhookDetails($req);
    }
    //verify the payment status
    /**
        | This
     */
    public function gettingWebhookDetailsv1(Request $req)
    {
        return $this->Prepository->gettingWebhookDetailsv1($req);
    }

    //get the details of webhook according to transactionNo
    public function getTransactionNoDetails(Request $req)
    {
        # validation 
        $validated = Validator::make(
            $req->all(),
            [
                'transactionNo' => 'required|integer',
            ]
        );
        if ($validated->fails()) {
            return responseMsg(false, "validation error", $validated->errors(), 401);
        }
        return $this->Prepository->getTransactionNoDetails($req);
    }

    // saveGenerateOrderid
    /**
        | This
     */
    public function generateOrderid(Request $req)
    {
        // return $req;
        try {
            $Returndata =  $this->saveGenerateOrderid($req);
            return responseMsgs(true, "OrderId Generated!", $Returndata, "", "04", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
        |-------------------------------------- Payment Reconcillation -----------------------------------------| 
     */

    //get all the details of Payment Reconciliation 
    public function getReconcillationDetails()
    {
        try {
            return $this->Prepository->getReconcillationDetails();
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    // serch the specific details according to the request
    public function searchReconciliationDetails(Request $request)
    {
        return $this->Prepository->searchReconciliationDetails($request);
    }

    // serch the specific details according to the request
    public function updateReconciliationDetails(Request $request)
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
        return $this->Prepository->updateReconciliationDetails($request);
    }

    // get all details of the payments of all modules
    public function allModuleTransaction()
    {
        return $this->Prepository->allModuleTransaction();
    }

    // Serch the tranasaction details
    /**
     | Flag / Route
     */
    public function searchTransaction(Request $request)
    {
        try {
            $request->validate([
                'fromDate' => 'required',
                'toDate' => 'required',
            ]);
            return $this->Prepository->searchTransaction($request);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Get Transaction dtls by orderid and paymentid
     */
    public function getTranByOrderId(Request $req)
    {
        $req->validate([
            // 'orderId' => 'required',
            'paymentId' => 'required'
        ]);
        try {
            $mWebhook = new WebhookPaymentData();
            $webhookData = $mWebhook = $mWebhook->getTranByOrderPayId($req);
            return responseMsgs(true, "Transaction No", remove_null($webhookData), "15", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * | Get Property Transactions
     * | @param req requested parameters
     * | @var userId authenticated user id
     * | @var propTrans Property Transaction details of the Logged In User
     * | @return responseMsg
     * | Status-Closed
     * | Run time Complexity-346ms
     * | Rating - 3
     */
    public function getDirectTransactionsOnline(Request $req)
    {
        try {
            $auth = authUser($req);
            $userId = $auth->id;
            if ($auth->user_type == 'Citizen')
                $propTrans = $this->getPropTransByCitizenUserId($userId, 'citizen_id');
            // else
            //     $propTrans = $this->getPropTransByCitizenUserId($userId, 'user_id');

            return responseMsgs(true, "Transactions History", remove_null($propTrans), "010118", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "010118", "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    /**
     * | Get Property and Saf Transaction
     */
    public function getPropTransByCitizenUserId($userId, $userType)
    {
        // Fetch property transactions
        $propertyQuery = "
            SELECT 
                prop_transactions.tran_no,
                SUM(prop_transactions.amount) AS total_amount,
                MIN(prop_transactions.tran_date) AS tran_date,
                TO_CHAR(MIN(prop_transactions.tran_date), 'dd-mm-YYYY') AS formatted_tran_date,
                STRING_AGG(DISTINCT p.holding_no, ', ') AS holding_no,
                CASE 
                    WHEN BOOL_OR(prop_transactions.saf_id IS NOT NULL) THEN 'SAF' 
                    ELSE 'PROPERTY' 
                END AS application_type
            FROM 
                prop_transactions 
            LEFT JOIN prop_properties AS p ON p.id = prop_transactions.property_id
            WHERE 
                prop_transactions.$userType = :userId
                AND prop_transactions.status <> 0
                AND prop_transactions.direct_payment = true
            GROUP BY 
                prop_transactions.tran_no
            ORDER BY 
                MIN(prop_transactions.id) DESC
        ";

        $propertyTransactions = DB::select($propertyQuery, ['userId' => $userId]);

        // Fetch water transactions
        $waterQuery = "
            SELECT 
                water_trans.tran_no,
                SUM(water_trans.amount) AS total_amount,
                MIN(water_trans.tran_date) AS tran_date,
                TO_CHAR(MIN(water_trans.tran_date), 'dd-mm-YYYY') AS formatted_tran_date,
                NULL AS holding_no,
                'WATER' AS application_type
            FROM 
                water_trans
            LEFT JOIN water_consumers AS wc ON wc.id = water_trans.related_id
            WHERE 
                water_trans.$userType = :userId
                AND water_trans.status <> 0
                AND water_trans.direct_payment = true
            GROUP BY 
                water_trans.tran_no
            ORDER BY 
                MIN(water_trans.id) DESC
        ";

        $waterTransactions = DB::connection('pgsql_water')->select($waterQuery, ['userId' => $userId]);

        // Merge and aggregate transactions in PHP
        $transactions = [];

        foreach (array_merge($propertyTransactions, $waterTransactions) as $transaction) {
            $tranNo = $transaction->tran_no;

            if (!isset($transactions[$tranNo])) {
                // Initialize transaction entry
                $transactions[$tranNo] = [
                    'tran_no' => $tranNo,
                    'total_amount' => 0,
                    'tran_date' => $transaction->tran_date,
                    'formatted_tran_date' => $transaction->formatted_tran_date,
                    'holding_no' => $transaction->holding_no ?? '',
                    'application_type' => []
                ];
            }

            // Sum total amount
            $transactions[$tranNo]['total_amount'] += $transaction->total_amount;

            // Keep the earliest transaction date
            if ($transaction->tran_date < $transactions[$tranNo]['tran_date']) {
                $transactions[$tranNo]['tran_date'] = $transaction->tran_date;
                $transactions[$tranNo]['formatted_tran_date'] = $transaction->formatted_tran_date;
            }

            // Merge holding numbers (avoid duplicates)
            if (!empty($transaction->holding_no)) {
                $transactions[$tranNo]['holding_no'] = implode(', ', array_unique(array_filter(explode(', ', $transactions[$tranNo]['holding_no'] . ', ' . $transaction->holding_no))));
            }

            // Merge application types (avoid duplicates)
            if (!in_array($transaction->application_type, $transactions[$tranNo]['application_type'])) {
                $transactions[$tranNo]['application_type'][] = $transaction->application_type;
            }
        }

        // Convert application types to string
        foreach ($transactions as &$transaction) {
            $transaction['application_type'] = implode(', ', $transaction['application_type']);
        }

        return array_values($transactions); // Return re-indexed array
    }
}
