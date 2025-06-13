<?php

namespace App\Repository\Payment\Concrete;

use App\Http\Controllers\Property\ActiveSafController;
use App\Http\Controllers\Property\HoldingTaxController;
use App\Http\Controllers\Trade\TradeCitizenController;
use App\Http\Controllers\Water\WaterPaymentController;
use App\Http\Requests\Property\ReqPayment;
use App\MicroServices\IdGenerator\PrefixIdGenerator;
use App\Models\ApiMaster;
use App\Models\Payment\CardDetail;
use App\Models\Payment\DepartmentMaster;
use App\Models\Payment\PaymentGatewayDetail;
use App\Models\Payment\PaymentGatewayMaster;
use App\Models\Payment\PaymentReconciliation;
use App\Models\Payment\PaymentReject;
use App\Models\Payment\PaymentRequest;
use App\Models\Payment\PaymentSuccess;
use App\Models\Payment\WebhookPaymentData;
use App\Models\Property\PropAdjustment;
use App\Models\Property\PropDemand;
use App\Models\Property\PropOwner;
use App\Models\Property\PropPenaltyrebate;
use App\Models\Property\PropProperty;
use App\Models\Property\PropRazorpayPenalrebate;
use App\Models\Property\PropRazorpayRequest;
use App\Models\Property\PropRazorpayResponse;
use App\Models\Property\PropTranDtl;
use App\Models\Property\PropTransaction;
use App\Models\Water\WaterRazorPayRequest;
use Illuminate\Http\Request;
use App\Repository\Payment\Interfaces\iPayment;
use App\Repository\Property\Concrete\SafRepository;
use App\Repository\Trade\Trade;
use App\Repository\Water\Concrete\WaterNewConnection;
use App\Repository\Property\Interfaces\iSafRepository;
use App\Repository\Trade\TradeCitizen;;

use App\Traits\Payment\Razorpay;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

use function PHPUnit\Framework\isEmpty;

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
    protected $_safRepo;
    protected $_carbon;
    public function __construct(iSafRepository $safRepo)
    {
        $this->_safRepo = $safRepo;
        $this->_carbon = Carbon::now();
    }

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
     * | verifiying the payment success and the signature key
     * | @param requet request from the frontend
     * | @param error collecting the operation error
     * | @var mAttributes
     * | @var mVerification
        | Use for payment verificaton
        | $mVerification = $this->paymentVerify($request, $mAttributes); (Calling the trait function)
     */
    public function verifyPaymentStatus(Request $request)
    {

        # test code 
        $attributes     = null;
        $success        = false;
        $refRazorpayId  = Config::get('razorpay.RAZORPAY_ID');
        $refRazorpayKey = Config::get('razorpay.RAZORPAY_KEY');

        $refPaymentId = $request->razorpayPaymentId;
        $api = new Api($refRazorpayKey, $refRazorpayId);
        $paymentId = $_POST['razorpayPaymentId'];
        $payment = $api->payment->fetch($paymentId);
        if ($payment->status === 'captured') {
            return  $payment;
        } else {
            return $payment;
        }

        return false;


        # variable and model declaration 
        $attributes     = null;
        $success        = false;
        $refRazorpayId  = Config::get('razorpay.RAZORPAY_ID');
        $refRazorpayKey = Config::get('razorpay.RAZORPAY_KEY');

        # verify the existence of the razerpay Id
        try {
            $api = new Api($refRazorpayId, $refRazorpayKey);
            $attributes = [
                'razorpay_order_id'     => $request->razorpayOrderId,
                'razorpay_payment_id'   => $request->razorpayPaymentId,
                'razorpay_signature'    => $request->razorpaySignature
            ];
            $api->utility->verifyPaymentSignature($attributes);
            $success = true;
        } catch (SignatureVerificationError $exception) {
            $success = false;
            $messsage = $exception->getMessage();
        }

        try {
            if ($success === true) {
                # Check the webhook transaction data
                $messsage = "Payment Successfully done!";
                return responseMsgs(true, $messsage, [], "", "01", "", "POST", $request->deviceId);
            } else {
                # Update database with error data
                return responseMsgs(false, $messsage, [], "", "01", "", "POST", $request->deviceId);
            }
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "04", ".ms", "POST", $request->deviceId);
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
            // $paymentId = $request->payload['payment']['entity']['id'];
            // Storage::disk('public')->put($paymentId . '.json', json_encode($request->all()));

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
    public function gettingWebhookDetailsv1(Request $request)
    {
        try {
            # creating json of webhook data
            // $paymentId = $request->payload['payment']['entity']['id'];
            // Storage::disk('public')->put($paymentId . '.json', json_encode($request->all()));

            if (!empty($request)) {
                $mWebhookDetails = $this->collectWebhookDetailsv1($request);
                return $mWebhookDetails;
            }
            return responseMsg(false, "WEBHOOK DATA NOT ACCUIRED!", "");
        } catch (Exception $error) {
            return responseMsg(false, "OPERATIONAL ERROR!", $error->getMessage());
        }
    }

    public function collectWebhookDetailsv1($webhookData)
    {
        $request = $webhookData->toArray();
        try {
            # Variable Defining Section
            $webhookEntity  = "";
            $contains       = "";
            $notes          = "";
            $depatmentId    = $request['departmentId'];  // ModuleId
            // $status         = $request['status'];
            $captured       = $request['captured'];
            // $aCard          = $request['card_id'];
            $amount         = $request['amount'];
            $mPropRazorpayRequest = new PropRazorpayRequest();
            $mWaterRazorpayRequest = new WaterRazorPayRequest();

            $actulaAmount = $amount;
            // $firstKey = "";
            $actualTransactionNo = $this->generatingTransactionId($request['ulb_id']);

            // # Save card details 
            // if (!is_null($aCard)) {
            //     $webhookCardDetails = $webhookEntity['card'];
            //     $objcard = new CardDetail();
            //     $objcard->saveCardDetails($webhookCardDetails);
            // }

            # Data to be stored in webhook table
            // $webhookData = new WebhookPaymentData();
            // $refWebhookDetails = $webhookData->getWebhookRecord($request, $captured, $webhookEntity, $status)->first();
            // if (is_null($refWebhookDetails)) {
            //     $webhookData = $webhookData->saveWebhookData($request, $captured, $actulaAmount, $status, $notes, $firstKey, $contains, $actualTransactionNo, $webhookEntity);
            // }
            # data transfer to the respective module's database 
            $transfer = [
                'paymentMode'   => $webhookData->payment_method,
                'id'            => $request['applicationId'],
                'amount'        => $actulaAmount,
                'workflowId'    => $webhookData->workflow_id,
                'transactionNo' => $actualTransactionNo,
                'userId'        => $webhookData->user_id,
                'ulbId'         => $request['ulb_id'],
                'departmentId'  => $depatmentId,                        //ModuleId
                'orderId'       => $webhookData->payment_order_id,
                'paymentId'     => $webhookData->payment_id,
                'tranDate'      => $request['created_at'],
                'gatewayType'   => 1,                                   // Razorpay Id
                'citizenId'     => $request['citizenId']  ?? ""
            ];
            // property Razorpay Request

            // Property Razorpay Request
            // Property Razorpay Request
            $checkRequest = $mPropRazorpayRequest->getRazorPayRequestsv1($transfer);
            $checkWaterRequest = $mWaterRazorpayRequest->getRazorPayRequestsv1($transfer);
            if ($checkRequest) {
                foreach ($checkRequest as $req) {
                    // Convert object to an array before passing it to ReqPayment
                    $paymentRequest = new ReqPayment($req->toArray());

                    // Call the paymentHolding method
                    $this->paymentHolding($paymentRequest, $transfer);
                }
            }
            if ($checkWaterRequest) {
                foreach ($checkWaterRequest as $req) {
                    // Convert object to an array before passing it to ReqPaymen
                    $data = $this->razorPayResponse($req, $transfer);
                }
            }
            return responseMsg(true, "Webhook Data Collected!", $actualTransactionNo);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), $e->getLine());
        }
    }


    /**
     * | Payment Holding (Case for Online Payment)
     */
    public function paymentHolding(ReqPayment $req, $transfer)
    {
        try {
            $userId = $transfer['userId'];
            $tranBy = 'ONLINE';
            $mPropDemand = new PropDemand();
            $mPropTrans = new PropTransaction();
            $propId = $req['prop_id'];
            $mPropAdjustment = new PropAdjustment();
            $mPropOwner = new PropOwner();
            $mPropRazorPayRequest = new PropRazorpayRequest();
            $mPropRazorpayPenalRebates = new PropRazorpayPenalrebate();
            $mPropPenaltyRebates = new PropPenaltyrebate();
            $mPropRazorpayResponse = new PropRazorpayResponse();

            $propDetails = PropProperty::findOrFail($propId);
            $ownerDetails = $mPropOwner->getfirstOwner($propId);
            $orderId = $req['order_id'];
            $paymentId = $transfer['paymentId'];
            $razorPayReqs = new Request([
                'orderId' => $orderId,
                'key' => 'prop_id',
                'keyId' => $propId
            ]);
            $propRazorPayRequest = $mPropRazorPayRequest->getRazorPayRequests($razorPayReqs);
            if (collect($propRazorPayRequest)->isEmpty())
                throw new Exception("No Order Request Found");

            if (!$userId)
                $userId = 0;                                                        // For Ghost User in case of online payment

            $tranNo = $transfer['transactionNo'];

            $demands = json_decode($propRazorPayRequest->demand_list, true);
            $amount = $propRazorPayRequest['amount'];
            $advanceAmt = $propRazorPayRequest['advance_amount'];
            if (collect($demands)->isEmpty())
                throw new Exception("No Dues For this Property");

            DB::beginTransaction();
            // Replication of Prop Transactions
            $tranReqs = [
                'property_id' => $req['prop_id'],
                'tran_date' => $this->_carbon->format('Y-m-d'),
                'tran_no' => $tranNo,
                'payment_mode' => 'ONLINE',
                'amount' => $amount,
                'tran_date' => $this->_carbon->format('Y-m-d'),
                'verify_date' => $this->_carbon->format('Y-m-d'),
                'citizen_id' => $userId,
                'is_citizen' => true,
                'from_fyear' => $propRazorPayRequest->from_fyear,
                'to_fyear' => $propRazorPayRequest->to_fyear,
                'from_qtr' => $propRazorPayRequest->from_qtr,
                'to_qtr' => $propRazorPayRequest->to_qtr,
                'demand_amt' => $propRazorPayRequest->demand_amt,
                'ulb_id' => $propRazorPayRequest->ulb_id,
                'tran_type' => 'Property',
                'direct_payment' => true,

            ];

            $storedTransaction = $mPropTrans->storeTransv1($tranReqs);
            $tranId = $storedTransaction['id'];

            $razorpayPenalRebates = $mPropRazorpayPenalRebates->getPenalRebatesByReqId($propRazorPayRequest->id);
            // Replication of Razorpay Penalty Rebates to Prop Penal Rebates
            foreach ($razorpayPenalRebates as $item) {
                $propPenaltyRebateReqs = [
                    'tran_id' => $tranId,
                    'head_name' => $item['head_name'],
                    'amount' => $item['amount'],
                    'is_rebate' => $item['is_rebate'],
                    'tran_date' => $this->_carbon->format('Y-m-d'),
                    'prop_id' => $req['id'],
                ];
                $mPropPenaltyRebates->postRebatePenalty($propPenaltyRebateReqs);
            }

            // Updation of Prop Razor pay Request
            $propRazorPayRequest->status = 1;
            $propRazorPayRequest->payment_id = $paymentId;
            $propRazorPayRequest->save();

            // Update Prop Razorpay Response
            $razorpayResponseReq = [
                'razorpay_request_id' => $propRazorPayRequest->id,
                'order_id' => $orderId,
                'payment_id' => $paymentId,
                'prop_id' => $req['prop_id'],
                'from_fyear' => $propRazorPayRequest->from_fyear,
                'from_qtr' => $propRazorPayRequest->from_qtr,
                'to_fyear' => $propRazorPayRequest->to_fyear,
                'to_qtr' => $propRazorPayRequest->to_qtr,
                'demand_amt' => $propRazorPayRequest->demand_amt,
                'ulb_id' => $propDetails->ulb_id,
                'ip_address' => getClientIpAddress(),
            ];
            $mPropRazorpayResponse->store($razorpayResponseReq);

            // Reflect on Prop Tran Details
            foreach ($demands as $demand) {
                $propDemand = $mPropDemand->getDemandById($demand['id']);
                $propDemand->balance = 0;
                $propDemand->paid_status = 1;           // <-------- Update Demand Paid Status 
                $propDemand->save();

                $propTranDtl = new PropTranDtl();
                $propTranDtl->tran_id = $tranId;
                $propTranDtl->prop_demand_id = $demand['id'];
                $propTranDtl->total_demand = $demand['amount'];
                $propTranDtl->ulb_id = $propDetails->ulb_id;
                $propTranDtl->save();
            }
            // Advance Adjustment 
            if ($advanceAmt > 0) {
                $adjustReq = [
                    'prop_id' => $propId,
                    'tran_id' => $tranId,
                    'amount' => $advanceAmt
                ];
                if ($tranBy == 'Citizen')
                    $adjustReq = array_merge($adjustReq, ['citizen_id' => $userId ?? 0]);
                else
                    $adjustReq = array_merge($adjustReq, ['user_id' => $userId ?? 0]);

                $mPropAdjustment->store($adjustReq);
            }
            DB::commit();

            $ownerName   = $ownerDetails->applicant_name;
            $ownerMobile = $ownerDetails->mobile_no;
            $amount      = $req->amount;
            $holdingNo   = $propDetails->new_holding_no ?? $propDetails->holding_no;
            $url         = "https://jharkhandegovernance.com/citizen/paymentReceipt/direct/" . $tranNo . "/holding";

            #_Whatsaap Message
            if (strlen($ownerMobile) == 10) {

                $whatsapp2 = (Whatsapp_Send(
                    $ownerMobile,
                    "holding_payment_receipt",
                    [
                        "content_type" => "text",
                        [
                            $ownerName,
                            $amount,
                            $holdingNo,
                            $url
                        ]
                    ]
                ));
            }

            return responseMsgs(true, "Payment Successfully Done", ['TransactionNo' => $tranNo], "011509", "1.0", "", "POST", $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "011509", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Differenciate the water module payment 
     */
    public function razorPayResponse($args, $transfer)
    {
        try {
            # validation 
            $RazorPayRequest = WaterRazorPayRequest::select("*")
                ->where("order_id", $args["order_id"])
                ->where("related_id", $args["related_id"])
                ->where("status", 2)
                ->first();
            if (!$RazorPayRequest) {
                throw new Exception("Data Not Found");
            }
            switch ($RazorPayRequest->payment_from) {
                case ("Demand Collection"):
                    $mWaterPaymentController = new WaterPaymentController();
                    $response = $mWaterPaymentController->endOnlineDemandPaymentv1($args, $RazorPayRequest, $transfer);
                    break;
                default:
                    throw new Exception("Invalid Transaction");
            }
            return $response;
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), $args);
        }
    }

    /**
     * | Collecting the webhook details and storing in the database
       | collectWebhookDetails:1
     */
    public function collectWebhookDetails($webhookData)
    {
        $request = $webhookData->toArray();
        try {
            # Variable Defining Section
            $webhookEntity  = "";
            $contains       = "";
            $notes          = "";
            $depatmentId    = $request['departmentId'];  // ModuleId
            $status         = $request['status'];
            $captured       = $request['captured'];
            $aCard          = $request['card_id'];
            $amount         = $request['amount'];

            $actulaAmount = $amount;
            $firstKey = "";
            $actualTransactionNo = $this->generatingTransactionId($request['ulb_id']);

            # Save card details 
            if (!is_null($aCard)) {
                $webhookCardDetails = $webhookEntity['card'];
                $objcard = new CardDetail();
                $objcard->saveCardDetails($webhookCardDetails);
            }

            # Data to be stored in webhook table
            // $webhookData = new WebhookPaymentData();
            // $refWebhookDetails = $webhookData->getWebhookRecord($request, $captured, $webhookEntity, $status)->first();
            // if (is_null($refWebhookDetails)) {
            //     $webhookData = $webhookData->saveWebhookData($request, $captured, $actulaAmount, $status, $notes, $firstKey, $contains, $actualTransactionNo, $webhookEntity);
            // }
            # data transfer to the respective module's database 
            $transfer = [
                'paymentMode'   => $webhookData->payment_method,
                'id'            => $request['applicationId'],
                'amount'        => $actulaAmount,
                'workflowId'    => $webhookData->workflow_id,
                'transactionNo' => $actualTransactionNo,
                'userId'        => $webhookData->user_id,
                'ulbId'         => $request['ulb_id'],
                'departmentId'  => $depatmentId,                        //ModuleId
                'orderId'       => $webhookData->payment_order_id,
                'paymentId'     => $webhookData->payment_id,
                'tranDate'      => $request['created_at'],
                'gatewayType'   => 1,                                   // Razorpay Id
                'citizenId'     => $request['citizenId']  ?? ""
            ];

            # conditionaly upadting the request data
            if ($captured == 1) {
                PaymentRequest::where('razorpay_order_id', $webhookData->payment_order_id)
                    ->update(['payment_status' => 1]);

                # calling function for the modules                  
                switch ($depatmentId) {
                    case ('1'):
                        $refpropertyType = $webhookData->workflow_id;
                        if ($refpropertyType == 0) {
                            $objHoldingTaxController = new HoldingTaxController($this->_safRepo);
                            $transfer = new ReqPayment($transfer);
                            $objHoldingTaxController->paymentHolding($transfer);
                        } else {                                            //<------------------ (SAF PAYMENT)
                            $obj = new ActiveSafController($this->_safRepo);
                            $transfer = new ReqPayment($transfer);
                            $obj->paymentSaf($transfer);
                        }
                        break;
                    case ('2'):                                             //<------------------ (Water)
                        $objWater = new WaterNewConnection();
                        $data = $objWater->razorPayResponse($transfer);
                        $data;
                        break;
                    case ('3'):                                             //<------------------ (TRADE)
                        $objTrade = new TradeCitizen();
                        $objTrade->razorPayResponse($transfer);
                        break;
                    case ('5'):                                             //<------------------ (Advertisment) 
                        $adv = 76;                                                          // Static
                        $mApiMaster = new ApiMaster();
                        $api = $mApiMaster->getApiEndpoint($adv);
                        Http::withHeaders([])
                            ->post("$api->end_point", $transfer);
                        break;
                    case ('9'):                                              //<-------------------(Pet)
                        $pet = 80;
                        $mApiMaster = new ApiMaster();
                        $petApi = $mApiMaster->getApiEndpoint($pet);
                        $petRequest = Http::withHeaders([])
                            ->post("$petApi->end_point", $transfer);
                        break;
                    case ('11'):                                            //<-------------------(Water Tanker)
                        $waterTanker = 11;
                        $mApiMaster = new ApiMaster();
                        $waterTankerApi = $mApiMaster->getApiEndpoint($waterTanker);
                        Http::withHeaders([])
                            ->post("$waterTankerApi->end_point", $transfer);
                        break;
                    case ('12'):
                        $waterTanker = 78;
                        $mApiMaster = new ApiMaster();
                        $petApi = $mApiMaster->getApiEndpoint($waterTanker);
                        Http::withHeaders([])
                            ->post("$petApi->end_point", $transfer);
                        break;
                    case ('10'):                                            //<-------------------(Marriage)
                        $marriage = 29;
                        $mApiMaster = new ApiMaster();
                        $marriageApi = $mApiMaster->getApiEndpoint($marriage);
                        $details = Http::withHeaders([])
                            ->post("$marriageApi->end_point", $transfer);
                        $details;
                        break;
                    case ('15'):                                            //<-------------------(Rig Machine)
                        $rig = 15;
                        $mApiMaster = new ApiMaster();
                        $rigApi     = $mApiMaster->getApiEndpoint($rig);
                        $details    = Http::withHeaders([])
                            ->post("$rigApi->end_point", $transfer);
                        $details;
                        break;
                    case ('16'):                                            //<-------------------(Septic Tanker)
                        $septicTanker = 11;
                        $mApiMaster = new ApiMaster();
                        $septicTankerApi = $mApiMaster->getApiEndpoint($septicTanker);
                        Http::withHeaders([])
                            ->post("$septicTankerApi->end_point", $transfer);
                        break;
                }
                return responseMsg(true, "Webhook Data Collected!", $actualTransactionNo);
            }
            return responseMsg(true, "Webhook Data Collected!", "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), $e->getLine());
        }
    }

    /**
     * | -------------------------------- integration of the webhook ------------------------------- |
     * | @param request
     * | 
     * | @return 
     * | Operation : this function url is hited by the webhook and the detail of the payment is collected in request 
     *               thie the storage -> generating pdf -> generating json ->save -> hitting url for watsapp message.
     * | Rating : 4
     * | this -> naming
     * | here -> variable
        | Serial No : 03
        | Flag : department Id will be replaced / switch case / the checking of the payment is success (keys:amount,orderid,departmentid,status) / razorpay verification 
        | Commented beacuse online payyment was not working
     */
    // public function collectWebhookDetails($request)
    // {
    //     try {
    //         # Variable Defining Section
    //         $webhookEntity  = $request->payload['payment']['entity'];
    //         $contains       = json_encode($request->contains);
    //         $notes          = json_encode($webhookEntity['notes']);
    //         $depatmentId    = $webhookEntity['notes']['departmentId'];  // ModuleId
    //         $status         = $webhookEntity['status'];
    //         $captured       = $webhookEntity['captured'];
    //         $aCard          = $webhookEntity['card_id'];
    //         $amount         = $webhookEntity['amount'];
    //         $arrayInAquirer = $webhookEntity['acquirer_data'];

    //         $actulaAmount = $amount / 100;
    //         $firstKey = array_key_first($arrayInAquirer);
    //         $actualTransactionNo = $this->generatingTransactionId($webhookEntity['notes']['ulbId']);

    //         # Save card details 
    //         if (!is_null($aCard)) {
    //             $webhookCardDetails = $webhookEntity['card'];
    //             $objcard = new CardDetail();
    //             $objcard->saveCardDetails($webhookCardDetails);
    //         }

    //         # Data to be stored in webhook table
    //         $webhookData = new WebhookPaymentData();
    //         $refWebhookDetails = $webhookData->getWebhookRecord($request, $captured, $webhookEntity, $status)->first();
    //         if (is_null($refWebhookDetails)) {
    //             $webhookData = $webhookData->saveWebhookData($request, $captured, $actulaAmount, $status, $notes, $firstKey, $contains, $actualTransactionNo, $webhookEntity);
    //         }
    //         # data transfer to the respective module's database 
    //         $transfer = [
    //             'paymentMode'   => $webhookData->payment_method,
    //             'id'            => $webhookEntity['notes']['applicationId'],
    //             'amount'        => $actulaAmount,
    //             'workflowId'    => $webhookData->workflow_id,
    //             'transactionNo' => $actualTransactionNo,
    //             'userId'        => $webhookData->user_id,
    //             'ulbId'         => $webhookData->ulb_id,
    //             'departmentId'  => $webhookData->department_id,         //ModuleId
    //             'orderId'       => $webhookData->payment_order_id,
    //             'paymentId'     => $webhookData->payment_id,
    //             'tranDate'      => $request->created_at,
    //             'gatewayType'   => 1,                                   // Razorpay Id
    //         ];

    //         # conditionaly upadting the request data
    //         if ($status == 'captured' && $captured == 1) {
    //             PaymentRequest::where('razorpay_order_id', $webhookEntity['order_id'])
    //                 ->update(['payment_status' => 1]);

    //             # calling function for the modules                  
    //             switch ($depatmentId) {
    //                 case ('1'):
    //                     $refpropertyType = $webhookEntity['notes']['workflowId'];
    //                     if ($refpropertyType == 0) {
    //                         $objHoldingTaxController = new HoldingTaxController($this->_safRepo);
    //                         $transfer = new ReqPayment($transfer);
    //                         $objHoldingTaxController->paymentHolding($transfer);
    //                     } else {                                            //<------------------ (SAF PAYMENT)
    //                         $obj = new ActiveSafController($this->_safRepo);
    //                         $transfer = new ReqPayment($transfer);
    //                         $obj->paymentSaf($transfer);
    //                     }
    //                     break;
    //                 case ('2'):                                             //<------------------ (Water)
    //                     $objWater = new WaterNewConnection();
    //                     $data = $objWater->razorPayResponse($transfer);
    //                     $data;
    //                     break;
    //                 case ('3'):                                             //<------------------ (TRADE)
    //                     $objTrade = new TradeCitizen();
    //                     $objTrade->razorPayResponse($transfer);
    //                     break;
    //                 case ('5'):                                             //<------------------ (Advertisment) 
    //                     $adv = 76;                                                          // Static
    //                     $mApiMaster = new ApiMaster();
    //                     $api = $mApiMaster->getApiEndpoint($adv);
    //                     Http::withHeaders([])
    //                         ->post("$api->end_point", $transfer);
    //                     break;
    //                 case ('9'):
    //                     $pet = 80;
    //                     $mApiMaster = new ApiMaster();
    //                     $petApi = $mApiMaster->getApiEndpoint($pet);
    //                     $petRequest = Http::withHeaders([])
    //                         ->post("$petApi->end_point", $transfer);
    //                     $petRequest;
    //                     break;
    //                 case ('11'):
    //                     $waterTanker = 77;
    //                     $mApiMaster = new ApiMaster();
    //                     $petApi = $mApiMaster->getApiEndpoint($waterTanker);
    //                     Http::withHeaders([])
    //                         ->post("$petApi->end_point", $transfer);
    //                     break;
    //                 case ('12'):
    //                     $waterTanker = 78;
    //                     $mApiMaster = new ApiMaster();
    //                     $petApi = $mApiMaster->getApiEndpoint($waterTanker);
    //                     Http::withHeaders([])
    //                         ->post("$petApi->end_point", $transfer);
    //                     break;
    //                 case ('10'):                                            //<-------------------(Marriage)
    //                     $marriage = 29;
    //                     $mApiMaster = new ApiMaster();
    //                     $marriageApi = $mApiMaster->getApiEndpoint($marriage);
    //                     $details = Http::withHeaders([])
    //                         ->post("$marriageApi->end_point", $transfer);
    //                     $details;
    //                     break;
    //             }
    //         }
    //         return responseMsg(true, "Webhook Data Collected!", $request->event);
    //     } catch (Exception $e) {
    //         return responseMsg(false, $e->getMessage(), $e->getLine());
    //     }
    // }

    /**
     * | ------------------------ generating Application ID ------------------------------- |
     * | @param request
     * | @var id
     * | @return idDetails
     * | Operation : this function generate a random and unique transactionID
     * | Rating : 1
        | Serial No : 03.1
     */
    public function generatingTransactionId($ulbId)
    {
        $tranParamId = Config::get("waterConstaint.PARAM_IDS");
        $idGeneration = new PrefixIdGenerator($tranParamId['TRN'], $ulbId);
        $transactionNo = $idGeneration->generate();
        $transactionNo = str_replace('/', '-', $transactionNo);
        return $transactionNo;
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
     * |--------- all the transaction details regardless of module ----------|
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

    /**
        | function to be used for the payment reconcilation
     */
    public function searchTransaction($request)
    {
        // $typeWise = new Model();
        // switch ($request) {
        //     case (null == ($request->chequeDdNo) && !null == ($request->verificationType) && null == ($request->paymentMode)): {
        //             $reconciliationTypeWise = $typeWise->transactionTypeWise($request);
        //             return $reconciliationTypeWise;
        //         }
        //         break;
        //     case (null == ($request->chequeDdNo) && null == ($request->verificationType) && !null == ($request->paymentMode)): {
        //             $reconciliationModeWise = $typeWise->transactionModeWise($request);
        //             return $reconciliationModeWise;
        //         }
        //         break;
        //     case (null == ($request->chequeDdNo) && null == ($request->verificationType) && null == ($request->paymentMode)): {
        //             $reconciliationDateWise = $typeWise->transactionDateWise($request);
        //             return $reconciliationDateWise;
        //         }
        //         break;
        //     case (!null == ($request->chequeDdNo) && null == ($request->verificationType) && null == ($request->paymentMode)): {
        //             $reconciliationOnlyTranWise = $typeWise->transactionOnlyTranWise($request);
        //             return $reconciliationOnlyTranWise;
        //         }
        //         break;
        //     case (!null == ($request->chequeDdNo) && !null == ($request->verificationType) && !null == ($request->paymentMode)): {
        //             $reconciliationWithAll = $typeWise->transactionWithAll($request);
        //             return $reconciliationWithAll;
        //         }
        //         break;
        //     case (null == ($request->chequeDdNo) && !null == ($request->verificationType) && !null == ($request->paymentMode)): {
        //             $reconciliationModeType = $typeWise->transactionModeType($request);
        //             return $reconciliationModeType;
        //         }
        //         break;
        //     default:
        //         return ("Some Error try again !");
        // }
    }
}


# Conversion of epoch time in human readable time
// $epoch = 1673593047;
// $dt = new DateTime("@$epoch");  
// return  $dt->format('Y-m-d H:i:s');