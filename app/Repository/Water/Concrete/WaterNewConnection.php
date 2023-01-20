<?php

namespace App\Repository\Water\Concrete;

use App\EloquentModels\Common\ModelWard;
use App\Http\Controllers\Water\NewConnectionController;
use App\Models\Payment\WebhookPaymentData;
use App\Models\UlbMaster;
use App\Models\Water\WaterApplicant;
use App\Models\Water\WaterApplicantDoc;
use App\Models\Water\WaterApplication;
use App\Models\Water\WaterConnectionCharge;
use App\Models\Water\WaterParamConnFee;
use App\Models\Water\WaterParamConnFeeOld;
use App\Models\Water\WaterParamDocumentType;
use App\Models\Water\WaterRazorPayRequest;
use App\Models\Water\WaterRazorPayResponse;
use App\Models\Water\WaterTran;
use App\Models\Water\WaterTranDetail;
use App\Models\Workflows\WfActiveDocument;
use App\Models\Workflows\WfWorkflow;
use App\Models\WorkflowTrack;
use App\Repository\Common\CommonFunction;
use App\Repository\Water\Interfaces\IWaterNewConnection;
use App\Traits\Auth;
use App\Traits\Payment\Razorpay;
use App\Traits\Property\WardPermission;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;

class WaterNewConnection implements IWaterNewConnection
{
    use Auth;               // Trait Used added by sandeep bara date 17-09-2022
    use WardPermission;
    use Razorpay;

    /**
     * | Created On-01-12-2022
     * | Created By- Sandeep Bara
     * -----------------------------------------------------------------------------------------
     * | WATER Module
     */

    protected $_modelWard;
    protected $_parent;
    protected $_shortUlbName;
    private $_dealingAssistent;

    public function __construct()
    {
        $this->_modelWard = new ModelWard();
        $this->_parent = new CommonFunction();
        $this->_dealingAssistent = Config::get('workflow-constants.DEALING_ASSISTENT_WF_ID');
    }
    /**
     * | Search the Citizen Related Water Application
       query cost (2.30)
     * ---------------------------------------------------------------------
     * | @var refUser            = Auth()->user()
     * | @var refUserId          = refUser->id      | loging user Id
     * | @var refUlbId           = refUser->ulb_id  | loging user ulb Id
     * | @var connection         = query data  [Model use (WaterApplication , WaterConnectionCharge)]
     * 
     */
    public function getCitizenApplication(Request $request)
    {
        try {
            $refUser            = Auth()->user();
            $refUserId          = $refUser->id;
            $mWebhookPaymentData = new WebhookPaymentData();
            $departmnetId       = Config::get('waterConstaint.WATER_DEPAPRTMENT_ID');
            // $refUlbId           = $request->ulbId;
            // AND water_applications.ulb_id = $refUlbId (btw line 95 96)
            $connection         = WaterApplication::select(
                "water_applications.id",
                "water_applications.application_no",
                "water_applications.address",
                "water_applications.payment_status",
                "water_applications.doc_status",
                "water_applications.ward_id",
                "water_applications.workflow_id",
                "ulb_ward_masters.ward_name",
                "charges.amount",
                DB::raw("'connection' AS type,
                                        water_applications.apply_date::date AS apply_date")
            )
                ->join(
                    DB::raw("( 
                                        SELECT DISTINCT(water_applications.id) AS application_id , SUM(COALESCE(amount,0)) AS amount
                                        FROM water_applications 
                                        LEFT JOIN water_connection_charges 
                                            ON water_applications.id = water_connection_charges.application_id 
                                            AND ( 
                                                water_connection_charges.paid_status ISNULL  
                                                OR water_connection_charges.paid_status=FALSE 
                                            )  
                                            AND( 
                                                    water_connection_charges.status = TRUE
                                                    OR water_connection_charges.status ISNULL  
                                                )
                                        WHERE water_applications.user_id = $refUserId
                                        GROUP BY water_applications.id
                                        ) AS charges
                                    "),
                    function ($join) {
                        $join->on("charges.application_id", "water_applications.id");
                    }
                )
                // ->whereNotIn("status",[0,6,7])
                ->join('ulb_ward_masters', 'ulb_ward_masters.id', '=', 'water_applications.ward_id')
                ->where("water_applications.user_id", $refUserId)
                // ->where("water_applications.ulb_id", $refUlbId)
                ->orderbydesc('id')
                ->get();

            $TransData = $mWebhookPaymentData->getTransactionDetails($departmnetId, $refUser);
            $returnValue = collect($connection)->map(function ($value) use ($TransData) {
                $id = $value['id'];
                $transactionIdDetail = collect($TransData)->map(function ($secondVal) use ($id) {
                    if ($secondVal['applicationId'] == $id) {
                        return $secondVal;
                    }
                });
                $filtered = collect($transactionIdDetail)->filter(function ($nonEmpty,) {
                    if ($nonEmpty != null) {
                        return $nonEmpty;
                    }
                });
                $value['transDetails'] = $filtered->values();
                return $value;
            });

            return responseMsg(true, "", remove_null($returnValue));
        } catch (Exception $e) {

            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }

    /**
     *  Genrate the RazorPay OrderId 
       Query const(3.30)
     * ---------------------------------------------------------------------------
     * | @var refUser            = Auth()->user()
     * | @var refUserId          = refUser->id      | loging user Id
     * | @var refUlbId           = refUser->ulb_id  | loging user ulb Id
     */
    public function handeRazorPay(Request $request)
    {
        try {
            $refUser            = Auth()->user();
            $refUserId          = $refUser->id;
            $refUlbId           = $refUser->ulb_id;
            $rules = [
                'id'                => 'required|digits_between:1,9223372036854775807',
                'applycationType'  => 'required|string|in:connection,consumer',
            ];
            $validator = Validator::make($request->all(), $rules,);
            if ($validator->fails()) {
                return responseMsg(false, $validator->errors(), $request->all());
            }
            #------------ new connection --------------------
            DB::beginTransaction();
            if ($request->applycationType == "connection") {
                $application = WaterApplication::find($request->id);
                if (!$application) {
                    throw new Exception("Data Not Found!......");
                }
                $cahges = $this->getWaterConnectionChages($application->id);
                if (!$cahges) {
                    throw new Exception("No Anny Due Amount!......");
                }
                $myRequest = new \Illuminate\Http\Request();
                $myRequest->setMethod('POST');
                $myRequest->request->add(['amount' => $cahges->amount]);
                $myRequest->request->add(['workflowId' => $application->workflow_id]);
                $myRequest->request->add(['id' => $application->id]);
                $myRequest->request->add(['departmentId' => 2]);
                $temp = $this->saveGenerateOrderid($myRequest);
                $RazorPayRequest = new WaterRazorPayRequest;
                $RazorPayRequest->related_id   = $application->id;
                $RazorPayRequest->payment_from = "New Connection";
                $RazorPayRequest->amount       = $cahges->amount;
                $RazorPayRequest->demand_from_upto = $cahges->ids;
                $RazorPayRequest->ip_address   = $request->ip();
                $RazorPayRequest->order_id        = $temp["orderId"];
                $RazorPayRequest->department_id = $temp["departmentId"];
                $RazorPayRequest->save();
            }
            #--------------------water Consumer----------------------
            else {
            }
            DB::commit();
            $temp['name']       = $refUser->user_name;
            $temp['mobile']     = $refUser->mobile;
            $temp['email']      = $refUser->email;
            $temp['userId']     = $refUser->id;
            $temp['ulbId']      = $refUser->ulb_id;
            $temp["applycationType"] = $request->applycationType;
            return responseMsg(true, "", $temp);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }
    public function razorPayResponse($args)
    {
        try {
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id ?? $args["userId"];
            $refUlbId       = $refUser->ulb_id ?? $args["ulbId"];
            $mNowDate       = Carbon::now()->format('Y-m-d');
            $mTimstamp      = Carbon::now()->format('Y-m-d H:i:s');
            $cahges         = null;
            $chargeData     = (array)null;
            $application    = null;
            $mDemands       = (array)null;

            #-----------valication------------------- 
            $RazorPayRequest = WaterRazorPayRequest::select("*")
                ->where("order_id", $args["orderId"])
                ->where("related_id", $args["id"])
                ->where("status", 2)
                ->first();
            if (!$RazorPayRequest) {
                throw new Exception("Data Not Found");
            }
            if ($RazorPayRequest->payment_from == "New Connection") {
                $application = WaterApplication::find($args["id"]);
                $cahges = 0;
                $id = explode(",", $RazorPayRequest->demand_from_upto);
                if ($id) {
                    $mDemands = WaterConnectionCharge::select("*")
                        ->whereIn("id", $id)
                        ->get();
                    $cahges = ($mDemands->sum("amount"));
                }
                $chargeData["total_charge"] = $cahges;
            } elseif ($RazorPayRequest->payment_from == "Demand Collection") {
                $application = null;
            }
            if (!$application) {
                throw new Exception("Application Not Found!......");
            }
            $applicationId = $args["id"];
            #-----------End valication----------------------------

            #-------------Calculation----------------------------- 
            if (!$chargeData || round($args['amount']) != round($chargeData['total_charge'])) {
                throw new Exception("Payble Amount Missmatch!!!");
            }

            $transactionType = $RazorPayRequest->payment_from;

            $totalCharge = $chargeData['total_charge'];
            #-------------End Calculation-----------------------------
            #-------- Transection -------------------
            DB::beginTransaction();

            $RazorPayResponse = new WaterRazorPayResponse;
            $RazorPayResponse->related_id   = $RazorPayRequest->related_id;
            $RazorPayResponse->request_id   = $RazorPayRequest->id;
            $RazorPayResponse->amount       = $args['amount'];
            $RazorPayResponse->merchant_id  = $args['merchantId'] ?? null;
            $RazorPayResponse->order_id     = $args["orderId"];
            $RazorPayResponse->payment_id   = $args["paymentId"];
            $RazorPayResponse->save();

            $RazorPayRequest->status = 1;
            $RazorPayRequest->update();

            $Tradetransaction = new WaterTran;
            $Tradetransaction->related_id       = $applicationId;
            $Tradetransaction->ward_id          = $application->ward_id;
            $Tradetransaction->tran_type        = $transactionType;
            $Tradetransaction->tran_date        = $mNowDate;
            $Tradetransaction->payment_mode     = "Online";
            $Tradetransaction->amount           = $totalCharge;
            $Tradetransaction->emp_dtl_id       = $refUserId;
            $Tradetransaction->created_at       = $mTimstamp;
            $Tradetransaction->ip_address       = '';
            $Tradetransaction->ulb_id           = $refUlbId;
            $Tradetransaction->save();
            $transaction_id                     = $Tradetransaction->id;
            $Tradetransaction->tran_no          = $args["transactionNo"];
            $Tradetransaction->update();

            foreach ($mDemands as $val) {
                $TradeDtl = new WaterTranDetail;
                $TradeDtl->tran_id        = $transaction_id;
                $TradeDtl->demand_id      = $val->id;
                $TradeDtl->total_demand   = $val->amount;
                $TradeDtl->application_id   = $val->application_id;
                $TradeDtl->created_at     = $mTimstamp;
                $TradeDtl->save();

                $val->paid_status = true;
                $val->update();
            }

            /**
             *  get the document details by (upload-document) api function 
             *  run foreach for (documentsList) then check the (document Mandatory is 1 and  collect the uplode doc then it should be contain data)
             *  then only the curent role will be farwarded to the current role  ie. below code 
             */
            // $req = [
            //     'applicationId' => $application->id,
            //     'userId' => $application->user_id,
            //     'ulbId' => $application->ulb_id
            // ];
            // $refrequest = new Request($req);
            // $details = $this->documentUpload($refrequest);
            // $verified = collect($details)->map(function ($value, $key) use ($applicationId) {
            //     if ($value['isMadatory'] == 1 && $value['uploadDoc'] != null) {
            //         return true;
            //     }
            //     return false;
            // })->reject(function ($value) {
            //     return $value === false;
            // });
            // if ($verified == true) {
            //     WaterApplication::where('id', $applicationId)
            //         ->update([
            //             'current_role' => $this->_dealingAssistent
            //         ]);
            // }

            WaterApplication::where('id', $applicationId)
                ->update([
                    'current_role' => $this->_dealingAssistent
                ]);

            $application->payment_status = true;
            $application->update();
            DB::commit();
            #----------End transaction------------------------
            #----------Response------------------------------
            $res['transactionId'] = $transaction_id;
            $res['paymentRecipt'] = config('app.url') . "/api/water/paymentRecipt/" . $applicationId . "/" . $transaction_id;
            return responseMsg(true, "", $res);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), $args);
        }
    }

    public function readTransectionAndApl(Request $request)
    {
        try {
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id;
            $refUlbId       = $refUser->ulb_id;
            $mDemands       = null;
            $application = null;
            $transection = null;
            $path = "/api/water/paymentRecipt/";
            $rules = [
                'orderId'    => 'required|string',
                'paymentId'  => 'required|string',
            ];
            $validator = Validator::make($request->all(), $rules,);
            if ($validator->fails()) {
                return responseMsg(false, $validator->errors(), $request->all());
            }
            $WaterRazorPayResponse = WaterRazorPayResponse::select("water_razor_pay_requests.*")
                ->join("water_razor_pay_requests", "water_razor_pay_requests.id", "water_razor_pay_responses.request_id")
                ->where("water_razor_pay_responses.order_id", $request->orderId)
                ->where("water_razor_pay_responses.payment_id", $request->paymentId)
                ->where("water_razor_pay_requests.status", 1)
                ->first();
            if (!$WaterRazorPayResponse) {
                throw new Exception("Not Transection Found...");
            }
            if ($WaterRazorPayResponse->payment_from == "New Connection") {
                $application = WaterApplication::find($WaterRazorPayResponse->related_id);
                $transection = WaterTran::select("*")
                    ->where("related_id", $WaterRazorPayResponse->related_id)
                    ->where("tran_type", $WaterRazorPayResponse->payment_from)
                    ->first();
            }
            if (!$application) {
                throw new Exception("Application Not Found....");
            }
            if (!$transection) {
                throw new Exception("Not Transection Data Found....");
            }
            $data["amount"]            = $WaterRazorPayResponse->amount;
            $data["applicationId"]     = $WaterRazorPayResponse->related_id;
            $data["applicationNo"]     = $application->application_no;
            $data["tranType"]          = $WaterRazorPayResponse->payment_from;
            $data["transectionId"]     = $transection->id;
            $data["transectionNo"]     = $transection->tran_no;
            $data["transectionDate"]   = $transection->tran_date;
            $data['paymentRecipt']     = config('app.url') . $path . $WaterRazorPayResponse->related_id . "/" . $transection->id;
            return responseMsg(true, "", $data);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }

    /**
     * get And Uploade Water Requied Documents
       Query cost(3.00)
     * ------------------------------------------------------------------ 
     * | @var refUser            = Auth()->user()
     * | @var refUserId          = refUser->id      | loging user Id
     * | @var refUlbId           = refUser->ulb_id  | loging user ulb Id
     * | @var refApplication     = WaterApplication(Model);
     * | @var refOwneres         = WaterApplicant(Model);
     * | @var requiedDocType     = $this->getDocumentTypeList()         |
     * | @var requiedDocs        = Application Related Required Documents
     * | @var ownersDoc          = Owners Related Required Documents
     * ----------------fucntion use---------------------------------------
     * | @var requiedDocType = $this->getDocumentTypeList(refApplication);
     * | @var refOwneres     = $this->getOwnereDtlByLId(refApplication->id);
     * | $this->getDocumentList($val->doc_for) |get All Related Document List
     * | $this->check_doc_exist(connectionId,$val->doc_for); |  Check Document is Uploaded Of That Type
     * | $this->readDocumentPath($doc['uploadDoc']["document_path"]); |  Create The Relative Path For Document Read
     * | $this->check_doc_exist_owner(refApplication->id,$val->id); # check Owners  Documents
     */
    public function documentUpload(Request $request)
    {
        try {
            $refUser            = Auth()->user();
            $refUserId          = $refUser->id ?? $request->userId;
            $refUlbId           = $refUser->ulb_id ?? $request->ulbId;
            $refApplication     = (array)null;
            $refOwneres         = (array)null;
            $mUploadDocument    = (array)null;
            $mDocumentsList     = (array)null;
            $requiedDocs        = (array)null;
            $ownersDoc          = (array)null;
            $testOwnersDoc      = (array)null;
            $data               = (array)null;
            $sms                = "";
            $refWaterWorkflowId = Config::get('workflow-constants.WATER_WORKFLOW_ID');
            $refWaterModuleId   = Config::get('module-constants.WATER_MODULE_ID');

            $rules = [
                'applicationId'     => 'required|digits_between:1,9223372036854775807',
            ];
            $validator = Validator::make($request->all(), $rules,);
            if ($validator->fails()) {
                return responseMsg(false, $validator->errors(), $request->all());
            }
            $connectionId = $request->applicationId;
            $refApplication = WaterApplication::where("status", 1)->find($connectionId);
            if (!$refApplication) {
                throw new Exception("Application Not Found.....");
            }
            // elseif($refApplication->doc_verify_status)
            // {
            //     throw new Exception("Documernt Already Verifed.....");
            // }
            $requiedDocType = $this->getDocumentTypeList($refApplication);  # get All Related Document Type List
            $refOwneres = $this->getOwnereDtlByLId($refApplication->id);    # get Owneres List
            foreach ($requiedDocType as $val) {
                $doc = (array) null;
                $doc['docName'] = $val->doc_for;
                $doc['isMadatory'] = $val->is_mandatory;
                $doc['docVal'] = $this->getDocumentList($val->doc_for);  # get All Related Document List
                $doc['uploadDoc'] = $this->check_doc_exist($connectionId, $val->doc_for); # Check Document is Uploaded Of That Type
                if (isset($doc['uploadDoc']["document_path"])) {
                    $path = $this->readDocumentPath($doc['uploadDoc']["document_path"]); # Create The Relative Path For Document Read
                    $doc['uploadDoc']["document_path"] = !empty(trim($doc['uploadDoc']["document_path"])) ? $path : null;
                }
                array_push($requiedDocs, $doc);
            }
            foreach ($refOwneres as $key => $val) {
                $doc = (array) null;
                $testOwnersDoc[$key] = (array) null;
                $doc["ownerId"] = $val->id;
                $doc["ownerName"] = $val->applicant_name;
                $doc["docName"]   = "ID Proof";
                $doc['isMadatory'] = 1;
                $doc['docVal'] = $this->getDocumentList("ID Proof");
                $refOwneres[$key]["ID Proof"] = $this->check_doc_exist_owner($refApplication->id, $val->id); # check Owners ID Proof Documents             
                $doc['uploadDoc'] = $refOwneres[$key]["ID Proof"];
                if (isset($refOwneres[$key]["ID Proof"]["document_path"])) {
                    $path = $this->readDocumentPath($refOwneres[$key]["ID Proof"]["document_path"]);
                    $refOwneres[$key]["ID Proof"]["document_path"] = !empty(trim($refOwneres[$key]["ID Proof"]["document_path"])) ? $path : null;
                    $doc['uploadDoc']["document_path"] = $path;
                }
                // array_push($ownersDoc, $doc);
                // array_push($testOwnersDoc[$key], $doc);
                # use of doc2
                $doc2 = (array) null;
                $doc2["ownerId"] = $val->id;
                $doc2["ownerName"] = $val->owner_name;
                $doc2["docName"]   = "image";
                $doc2['isMadatory'] = 0;
                $doc2['docVal'][] = ["id" => 0, "doc_name" => "Photo"];
                $refOwneres[$key]["image"] = $this->check_doc_exist_owner($refApplication->id, $val->id, 0);
                $doc2['uploadDoc'] = $refOwneres[$key]["image"];
                if (isset($refOwneres[$key]["image"]["document_path"])) {
                    $path = $this->readDocumentPath($refOwneres[$key]["image"]["document_path"]);
                    $refOwneres[$key]["image"]["document_path"] = !empty(trim($refOwneres[$key]["image"]["document_path"])) ? storage_path('app/public/' . $refOwneres[$key]["image"]["document_path"]) : null;
                    $refOwneres[$key]["image"]["document_path"] = !empty(trim($refOwneres[$key]["image"]["document_path"])) ? $path : null;
                    $doc2['uploadDoc']["document_path"] = $path;
                }
                array_push($ownersDoc, $doc);
                array_push($testOwnersDoc[$key], $doc);
            }

            #---------- upload the documents--------------
            // if (isset($request->docFor)) {
            //     #connection Doc
            //     if (in_array($request->docFor, objToArray($requiedDocType->pluck("doc_for")))) {
            //         $rules = [
            //             'docPath'        => 'required|max:30720|mimes:pdf,jpg,jpeg,png',
            //             'docMstrId'      => 'required|digits_between:1,9223372036854775807',
            //             'docFor'         => "required|string",
            //         ];
            //         $validator = Validator::make($request->all(), $rules,);
            //         if ($validator->fails()) {
            //             return responseMsg(false, $validator->errors(), $request->all());
            //         }
            //         $file = $request->file('docPath');
            //         $doc_for = "docFor";
            //         $doc_mstr_id = "docMstrId";
            //         $ids = objToArray(collect($this->getDocumentList($request->$doc_for))->pluck("id"));
            //         if ($file->IsValid() && in_array($request->$doc_mstr_id, $ids)) {
            //             if ($app_doc_dtl_id = $this->check_doc_exist($connectionId, $request->$doc_for)) {
            //                 if ($app_doc_dtl_id->verify_status == 0) {
            //                     $delete_path = storage_path('app/public/' . $app_doc_dtl_id['document_path']);
            //                     if (file_exists($delete_path)) {
            //                         unlink($delete_path);
            //                     }
            //                     $newFileName = $app_doc_dtl_id['id'];

            //                     $file_ext = $data["exten"] = $file->getClientOriginalExtension();
            //                     $fileName = "water_conn_doc/$newFileName.$file_ext";
            //                     $filePath = $this->uplodeFile($file, $fileName);
            //                     $app_doc_dtl_id->doc_name       =  $filePath;
            //                     $app_doc_dtl_id->document_id    =  $request->$doc_mstr_id;
            //                     $app_doc_dtl_id->update();
            //                 } else {
            //                     $app_doc_dtl_id->status = 0;
            //                     $app_doc_dtl_id->update();

            //                     $waterDoc = new WaterApplicantDoc;
            //                     $waterDoc->application_id = $connectionId;
            //                     $waterDoc->doc_for    = $request->$doc_for;
            //                     $waterDoc->document_id = $request->$doc_mstr_id;
            //                     $waterDoc->emp_details_id = $refUserId;

            //                     // $waterDoc = new WfActiveDocument();
            //                     // $waterDoc->active_id = $refApplication->application_no;
            //                     // $waterDoc->workflow_id = $refWaterWorkflowId;
            //                     // $waterDoc->ulb_id = $refUlbId;
            //                     // $waterDoc->module_id = $refWaterModuleId;
            //                     // $waterDoc->relative_path =
            //                     // $waterDoc->image =
            //                     // $waterDoc->uploaded_by =$refUserId

            //                     $waterDoc->save();
            //                     $newFileName = $waterDoc->id;

            //                     $file_ext = $data["exten"] = $file->getClientOriginalExtension();
            //                     $fileName = "water_conn_doc/$newFileName.$file_ext";
            //                     $filePath = $this->uplodeFile($file, $fileName);
            //                     $waterDoc->doc_name =  $filePath;
            //                     $waterDoc->update();
            //                 }
            //                 $sms = $app_doc_dtl_id->doc_for . " Update Successfully";
            //             } else {
            //                 $waterDoc = new WaterApplicantDoc;
            //                 $waterDoc->application_id = $connectionId;
            //                 $waterDoc->doc_for    = $request->$doc_for;
            //                 $waterDoc->document_id = $request->$doc_mstr_id;
            //                 $waterDoc->emp_details_id = $refUserId;

            //                 $waterDoc->save();
            //                 $newFileName = $waterDoc->id;

            //                 $file_ext = $data["exten"] = $file->getClientOriginalExtension();
            //                 $fileName = "water_conn_doc/$newFileName.$file_ext";
            //                 $filePath = $this->uplodeFile($file, $fileName);
            //                 $waterDoc->doc_name =  $filePath;
            //                 $waterDoc->update();
            //                 $sms = $waterDoc->doc_for . " Upload Successfully";
            //             }
            //         } else {
            //             return responseMsg(false, "something errors in Document Uploades", $request->all());
            //         }
            //     }
            //     #owners Doc
            //     elseif (in_array($request->docFor, objToArray(collect($ownersDoc)->pluck("docName")))) {
            //         $rules = [
            //             'docPath'        => 'required|max:30720|mimes:pdf,jpg,jpeg,png',
            //             'docMstrId'      => 'required|digits_between:1,9223372036854775807',
            //             'docFor'         => "required|string",
            //             'ownerId'        => "required|digits_between:1,9223372036854775807",
            //         ];
            //         $validator = Validator::make($request->all(), $rules,);
            //         if ($validator->fails()) {
            //             return responseMsg(false, $validator->errors(), $request->all());
            //         }
            //         $file = $request->file('docPath');
            //         $doc_for = "docFor";
            //         $doc_mstr_id = "docMstrId";
            //         if ($request->$doc_for == "image") {
            //             $ids = [0];
            //         } else {

            //             $ids = objToArray(collect($this->getDocumentList($request->$doc_for))->pluck("id"));
            //         }
            //         if (!in_array($request->ownerId, objToArray(collect($ownersDoc)->pluck("ownerId")))) {
            //             throw new Exception("Invalid Owner Id supply.....");
            //         }
            //         if ($file->IsValid() && in_array($request->$doc_mstr_id, $ids)) {
            //             if ($app_doc_dtl_id = $this->check_doc_exist_owner($connectionId, $request->ownerId, $request->docMstrId)) {
            //                 if ($app_doc_dtl_id->verify_status == 0) {
            //                     $delete_path = storage_path('app/public/' . $app_doc_dtl_id['document_path']);
            //                     if (file_exists($delete_path)) {
            //                         unlink($delete_path);
            //                     }
            //                     $newFileName = $app_doc_dtl_id['id'];

            //                     $file_ext = $data["exten"] = $file->getClientOriginalExtension();
            //                     $fileName = "water_conn_doc/$newFileName.$file_ext";
            //                     $filePath = $this->uplodeFile($file, $fileName);
            //                     $app_doc_dtl_id->doc_name       =  $filePath;
            //                     $app_doc_dtl_id->document_id    =  $request->$doc_mstr_id;
            //                     $app_doc_dtl_id->update();
            //                 } else {
            //                     $app_doc_dtl_id->status    =  0;
            //                     $app_doc_dtl_id->update();

            //                     $waterDoc                = new WaterApplicantDoc;
            //                     $waterDoc->application_id    = $connectionId;
            //                     $waterDoc->doc_for       = $request->docFor;
            //                     $waterDoc->document_id   = $request->docMstrId;
            //                     $waterDoc->applicant_id  = $request->ownerId;
            //                     $waterDoc->emp_details_id = $refUserId;

            //                     $waterDoc->save();
            //                     $newFileName = $waterDoc->id;

            //                     $file_ext = $data["exten"] = $file->getClientOriginalExtension();
            //                     $fileName = "water_conn_doc/$newFileName.$file_ext";
            //                     $filePath = $this->uplodeFile($file, $fileName);
            //                     $waterDoc->doc_name =  $filePath;
            //                     $waterDoc->update();
            //                 }
            //                 $sms = $app_doc_dtl_id->doc_for . " Update Successfully";
            //             } else {
            //                 $waterDoc                = new WaterApplicantDoc;
            //                 $waterDoc->application_id    = $connectionId;
            //                 $waterDoc->doc_for       = $request->docFor;
            //                 $waterDoc->document_id   = $request->docMstrId;
            //                 $waterDoc->applicant_id  = $request->ownerId;
            //                 $waterDoc->emp_details_id = $refUserId;

            //                 $waterDoc->save();
            //                 $newFileName = $waterDoc->id;

            //                 $file_ext = $data["exten"] = $file->getClientOriginalExtension();
            //                 $fileName = "water_conn_doc/$newFileName.$file_ext";
            //                 $filePath = $this->uplodeFile($file, $fileName);
            //                 $waterDoc->doc_name =  $filePath;
            //                 $waterDoc->update();
            //                 $sms = $waterDoc->doc_for . " Upload Successfully";
            //             }
            //         } else {
            //             return responseMsg(false, "something errors in Document Uploades", $request->all());
            //         }
            //     } else {
            //         throw new Exception("Invalid Document type Passe");
            //     }
            //     return responseMsg(true, $sms, "");
            // }
            $data["documentsList"]  = $requiedDocs;
            $data["ownersDocList"]  = collect($testOwnersDoc)->first();
            return responseMsg(true, $sms, $data);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }

    /**
     * Get Uploade Document Of Water Application
        Query Cost(2.30)
     * | --------------------------------------------------
     * | @var applicationId     = request->applicationId
     * | @var refApplication    = WaterApplication(Model);
     * | @var mUploadDocument   = $this->getWaterDocuments(applicationId)
     * ----------------------function use----------------------------------------
     * | @var mUploadDocument   = $this->getWaterDocuments(applicationId)
     * | $this->readDocumentPath( $val["document_path"])
     */
    public function getUploadDocuments(Request $request)
    {
        try {
            $rules = [
                'applicationId'     => 'required|digits_between:1,9223372036854775807',
            ];
            $validator = Validator::make($request->all(), $rules,);
            if ($validator->fails()) {
                return responseMsg(false, $validator->errors(), $request->all());
            }
            $applicationId = $request->applicationId;
            if (!$applicationId) {
                throw new Exception("Applicatin Id Required");
            }
            $refApplication = WaterApplication::where("status", 1)->find($applicationId);;
            if (!$refApplication) {
                throw new Exception("Data Not Found");
            }
            $mUploadDocument = $this->getWaterDocuments($applicationId)->map(function ($val) {
                if (isset($val["document_path"])) {
                    $path = $this->readDocumentPath($val["document_path"]);
                    $val["document_path"] = !empty(trim($val["document_path"])) ? $path : null;
                }
                return $val;
            });
            $data["uploadDocument"] = $mUploadDocument;
            return responseMsg(true, "", $data);
        } catch (Exception $e) {

            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }

    /**
     *  Get The Payment Reciept Data Or Water Module
        Query Cost(2.00)
     */
    public function paymentRecipt($transectionNo)
    {
        try {
            $application = (array)null;
            $transection = WaterTran::select("*")
                ->where("tran_no", $transectionNo)
                ->whereIn("status", [1, 2])
                ->first();

            if (!$transection) {
                throw new Exception("Transection Data Not Found....");
            }
            if ($transection->tran_type != "Demand-collection") {
                $application = WaterApplication::select(
                    "water_applications.application_no",
                    "address",
                    "owner.owner_name",
                    "owner.guardian_name",
                    "owner.mobile",
                    DB::raw("ulb_ward_masters.ward_name AS ward_no, 
                                            ulb_masters.id as ulb_id, ulb_masters.ulb_name,ulb_masters.ulb_type
                                            ")
                )
                    ->join("ulb_masters", "ulb_masters.id", "water_applications.ulb_id")
                    ->join("ulb_ward_masters", function ($join) {
                        $join->on("ulb_ward_masters.id", "=", "water_applications.ward_id");
                    })
                    ->leftjoin(DB::raw("(SELECT STRING_AGG(applicant_name,',') as owner_name,
                                                            STRING_AGG(guardian_name,',') as guardian_name,
                                                            STRING_AGG(mobile_no::text,',') as mobile,
                                                            application_id
                                                        FROM water_applicants 
                                                        WHERE application_id = $transection->related_id
                                                            AND status != FALSE
                                                        GROUP BY application_id
                                                        ) owner"), function ($join) {
                        $join->on("owner.application_id", "=", "water_applications.id");
                    })
                    ->where('water_applications.id', $transection->related_id)
                    ->first();
            }
            $data["transaction"] = $transection;
            $data["application"] = $application;
            return responseMsg(true, "datFech", $data);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), '');
        }
    }

    public function calWaterConCharge(Request $request)
    {
        try {
            if (($request->applyDate && $request->applyDate < "2021-04-01") || $request->pipelineTypeId == "1") {
                $res = $this->conRuleSet1($request);
            } else {
                $res = $this->conRuleSet2($request);
            }
            return collect($res);
        } catch (Exception $e) {
            $response["status"] = false;
            $response["errors"] = $e->getMessage();
            return collect($response);
        }
    }
    public function conRuleSet2(Request $request)
    {
        $response = (array)null;
        $response["status"] = false;
        $response["ruleSete"] = "RuleSet2";
        try {
            $response["water_fee_mstr_id"] = 0;
            $response["water_fee_mstr"] = [];
            $response["installment_amount"] = [];
            $conneFee  = 0;
            $mPenalty  = 0;
            $mNowDate  = Carbon::now()->format("Y-m-d");
            $mEffectiveFrom  = Carbon::parse("2021-01-01")->format('Y-m-d');
            $mSixMonthsAfter = Carbon::parse("2021-01-01")->addMonth(6)->format('Y-m-d');
            if ($request->category != "BPL") {
                $waterConFee = WaterParamConnFee::select("*")
                    ->where("property_type_id", $request->propertyTypeId)
                    ->where("effective_date", "<=", $mNowDate);
                if (in_array($request->propertyTypeId, [1, 7])) {
                    $waterConFee = $waterConFee->where(function ($where) use ($request) {
                        $where->where("area_from_sqft", "<=", ceil($request->areaSqft))
                            ->where("area_upto_sqft", ">=", ceil($request->areaSqft));
                    });
                }

                $waterConFee = $waterConFee->first();
                $response["water_fee_mstr"] = collect($waterConFee);
                $response["water_fee_mstr_id"]   =   $waterConFee->id;
                if ($waterConFee->calculation_type == 'Fixed') {
                    $conneFee   = $waterConFee->conn_fee;
                } else {
                    $conneFee   = $waterConFee->conn_fee * $request->areaSqft;
                }
            }

            $conn_fee_charge = array();
            $conn_fee_charge['charge_for'] = 'New Connection';
            $conn_fee_charge['conn_fee']   = (float)$conneFee;

            // Regularization
            # penalty 4000 for residential 10000 for commercial in regularization effective from 
            # 01-01-2021 and half the amount is applied for connection who applied under 6 months from 01-01-2021 
            if ($request->connectionTypeId == 2) {
                $mPenalty = 10000;
                if ($request->propertyTypeId == 1) {
                    $mPenalty = 4000;
                }
                if ($mNowDate < $mSixMonthsAfter) {
                    $mPenalty = $mPenalty / 2;
                }

                $inltment40Per = ($mPenalty * 40) / 100;
                $inltment30Per = ($mPenalty * 30) / 100;
                for ($j = 1; $j <= 3; $j++) {
                    if ($j == 1) {
                        $installment_amount = $inltment40Per;
                    } else {
                        $installment_amount = $inltment30Per;
                    }
                    $penalty_installment = array();
                    $penalty_installment['penalty_head'] = "$j" . " Installment";
                    $penalty_installment['installment_amount'] = $installment_amount;
                    $penalty_installment['balance_amount'] = $installment_amount;
                    array_push($response["installment_amount"], $penalty_installment);
                }
            }
            $conn_fee_charge['penalty'] = $mPenalty;
            $conn_fee_charge['amount']  = $mPenalty + $conneFee;
            $response["conn_fee_charge"] =  $conn_fee_charge;
            $response["status"] = true;
            return collect($response);
        } catch (Exception $e) {
            $response["errors"] = $e->getMessage();
            return collect($response);
        }
    }
    public function conRuleSet1(Request $request)
    {
        $response = (array)null;
        $response["status"] = false;
        $response["ruleSete"] = "RuleSet1";
        try {
            $response["water_fee_mstr_id"] = 0;
            $response["water_fee_mstr"] = [];
            $response["installment_amount"] = [];
            $conneFee  = 0;
            $mPenalty  = 0;
            $connection_through = (isset($request->connection_through) && $request->connection_through == 3 ? 2 : $request->connection_through) ?? 1;
            $mNowDate  = $request->applyDate ? Carbon::parse($request->applyDate)->format('Y-m-d') : Carbon::now()->format("Y-m-d");
            DB::enableQueryLog();
            $waterConFee = WaterParamConnFeeOld::select("*", DB::raw("'Fixed' AS calculation_type"))
                ->where("property_type_id", $request->propertyTypeId)
                ->where("pipeline_type_id", $request->pipelineTypeId ?? 2)
                ->where("connection_type_id", $request->connectionTypeId ?? 2)
                ->where("connection_through_id", $connection_through ?? 1)
                ->where("category", $request->category ?? "APL")
                ->where("effect_date", "<=", $mNowDate)
                ->where("status", 1)
                ->orderBy("effect_date", "DESC")
                ->orderBy("id", "ASC")
                ->first();
            // dd(DB::getyQueryLog());

            $response["water_fee_mstr_id"] = $waterConFee->id;
            $response["water_fee_mstr"] = $waterConFee;
            $conneFee   = $waterConFee->reg_fee + $waterConFee->proc_fee + $waterConFee->app_fee + $waterConFee->sec_fee + $waterConFee->conn_fee;

            $conn_fee_charge = array();
            $conn_fee_charge['charge_for'] = 'New Connection';
            $conn_fee_charge['conn_fee']   = (float)$conneFee;

            // Regularization
            # penalty 4000 for residential 10000 for commercial in regularization effective from 
            # 01-01-2021 and half the amount is applied for connection who applied under 6 months from 01-01-2021 

            $conn_fee_charge['penalty'] = $mPenalty;
            $conn_fee_charge['amount']  = $mPenalty + $conneFee;
            $response["conn_fee_charge"] =  $conn_fee_charge;
            $response["status"] = true;
            return collect($response);
        } catch (Exception $e) {
            $response["errors"] = $e->getMessage();
            return collect($response);
        }
    }

    #---------- core function --------------------------------------------------

    public function getWaterConnectionChages($applicationId)
    {
        try {
            $cahges = WaterConnectionCharge::select(DB::raw("SUM(COALESCE(amount,0)) AS amount, STRING_AGG(id::TEXT,',') AS ids"))
                ->where("application_id", $applicationId)
                ->Where(function ($where) {
                    $where->orWhere("paid_status", FALSE)
                        ->orWhereNull("paid_status");
                })
                ->Where(function ($where) {
                    $where->orWhere("status", TRUE)
                        ->orWhereNull("status");
                })
                ->groupBy("application_id")
                ->first();
            return $cahges;
        } catch (Exception $e) {
            return [];
        }
    }
    public function getDocumentTypeList(WaterApplication $application)
    {
        $refUser            = Auth()->user();
        $refUserId          = $refUser->id;
        $refUlbId           = $refUser->ulb_id;
        $WfWorkflow         = WfWorkflow::where('id', $application->workflow_id)->first();
        $mUserType          = $this->_parent->userType($WfWorkflow->wf_master_id);
        $return = (array)null;
        $type   = ["METER BILL", "ELECTRICITY_NEW", "ELECTRICITY_NEW", "Address Proof", "Others"];
        if (in_array($application->connection_through_id, [1, 5]))    // Holding No, SAF No
        {
            $type[] = "HOLDING PROOF";
        }
        if (strtoupper($application->category) == "BPL")    // FOR BPL APPLICATION
        {
            $type[] = "BPL";
        }
        if ($application->property_type_id == 2)    // FOR COMERCIAL APPLICATION
        {
            $type[] = "Commercial";
        }

        if (strtoupper($mUserType) == "ONLINE") // Online
        {
            $type[]  = "Form(Scan Copy)";
        }
        $doc = WaterParamDocumentType::select(
            "doc_for",
            DB::raw("CASE WHEN doc_for ='Others' THEN 0 
                                                ELSE 1 END AS is_mandatory")
        )
            ->whereIn("doc_for", $type)
            ->where("status", 1)
            ->groupBy("doc_for")
            ->get();
        return $doc;
    }
    public function getDocumentList($doc_for)
    {
        try {
            $data = WaterParamDocumentType::select("id", "document_name as doc_name")
                ->where("status", 1)
                ->where("doc_for", $doc_for)
                ->get();
            return $data;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
    public function check_doc_exist($applicationId, $doc_for, $doc_mstr_id = null, $woner_id = null)
    {
        try {

            $doc = WaterApplicantDoc::select(
                "id",
                "doc_for",
                "verify_status",
                "water_applicant_docs.remarks",
                DB::raw("doc_name AS document_path"),
                "document_id"
            )
                ->where('application_id', $applicationId)
                ->where('doc_for', $doc_for);
            if ($doc_mstr_id) {
                $doc = $doc->where('document_id', $doc_mstr_id);
            }
            if ($woner_id) {
                $doc = $doc->where('applicant_id', $woner_id);
            }
            $doc = $doc->where('status', 1)
                ->orderBy('id', 'DESC')
                ->first();
            return $doc;
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    public function check_doc_exist_owner($applicationId, $owner_id, $document_id = null)
    {
        try {
            // DB::enableQueryLog();
            $doc = WaterApplicantDoc::select(
                "id",
                "doc_for",
                "verify_status",
                "water_applicant_docs.remarks",
                DB::raw("doc_name AS document_path"),
                "document_id"
            )
                ->where('application_id', $applicationId)
                ->where('applicant_id', $owner_id);
            if ($document_id !== null) {
                $document_id = (int)$document_id;
                $doc = $doc->where('document_id', $document_id);
            } else {
                $doc = $doc->where("document_id", "<>", 0);
            }
            $doc = $doc->where('status', 1)
                ->orderBy('id', 'DESC')
                ->first();
            //    print_var(DB::getQueryLog());                    
            return $doc;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
    public function getOwnereDtlByLId($applicationId)
    {
        try {
            $ownerDtl   = WaterApplicant::select("*")
                ->where("application_id", $applicationId)
                ->where("status", 1)
                ->get();
            return $ownerDtl;
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
    public function uplodeFile($file, $custumFileName)
    {
        $filePath = $file->storeAs('uploads/Water', $custumFileName, 'public');
        return  $filePath;
    }
    public function readDocumentPath($path)
    {
        $path = (config('app.url')."/". $path);
        return $path;
    }
    public function getWaterDocuments($id)
    {
        try {
            $doc =  WaterApplicantDoc::select(
                "water_applicant_docs.id",
                "water_applicant_docs.remarks",
                "water_applicant_docs.verify_status",
                // "trade_licence_documents.doc_for",
                DB::raw("
                            CASE WHEN water_applicants.id NOTNULL THEN CONCAT(water_applicants.applicant_name,'( ',water_applicant_docs.doc_for,' )') 
                            ELSE water_applicant_docs.doc_for 
                            END doc_for,
                            water_applicant_docs.doc_name AS document_path
                            ")
            )
                ->leftjoin("water_applicants", function ($join) {
                    $join->on("water_applicants.id", "water_applicant_docs.applicant_id");
                })
                ->where('water_applicant_docs.application_id', $id)
                ->where('water_applicant_docs.status', 1)
                ->orderBy('water_applicant_docs.id', 'desc')
                ->get();
            return $doc;
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    #-----------------incomplite Code------------------------------

    public function applyApplication(Request $request)
    {
        try {
            #------------------------ Declaration-----------------------           
            $refUser            = Auth()->user();
            $refUserId          = $refUser->id;
            $refUlbId           = $refUser->ulb_id;
            $refUlbDtl          = UlbMaster::find($refUlbId);
            $refUlbName         = explode(' ', $refUlbDtl->ulb_name);
            $refNoticeDetails   = null;
            $refWorkflowId      = Config::get('workflow-constants.TRADE_WORKFLOW_ID');
            $refWorkflows       = $this->_parent->iniatorFinisher($refUserId, $refUlbId, $refWorkflowId);

            $redis              = new Redis;
            $mDenialAmount      = 0;
            $mUserData          = json_decode($redis::get('user:' . $refUserId), true);
            $mUserType          = $this->_parent->userType($refWorkflowId);
            $mShortUlbName      = "";
            $mNowdate           = Carbon::now()->format('Y-m-d');
            $mTimstamp          = Carbon::now()->format('Y-m-d H:i:s');
            $mNoticeDate        = null;
            $mProprtyId         = null;
            $mnaturOfBusiness   = null;

            $rollId             =  $mUserData['role_id'] ?? ($this->_parent->getUserRoll($refUserId, $refUlbId, $refWorkflowId)->role_id ?? -1);
            $data               = array();
            #------------------------End Declaration-----------------------
            #---------------validation-------------------------------------
            if (!in_array(strtoupper($mUserType), ["ONLINE", "JSK", "UTC", "TC", "SUPER ADMIN", "TL"])) {
                throw new Exception("You Are Not Authorized For This Action !");
            }
            if (!$refWorkflows) {
                return responseMsg(false, "Workflow Not Available", $request->all());
            } elseif (!$refWorkflows['initiator']) {
                return responseMsg(false, "Initiator Not Available", $request->all());
            } elseif (!$refWorkflows['finisher']) {
                return responseMsg(false, "Finisher Not Available", $request->all());
            }
            #---------------End validation-------------------------
            if (in_array(strtoupper($mUserType), ["ONLINE", "JSK", "SUPER ADMIN", "TL"])) {
                $data['wardList'] = $this->_modelWard->getAllWard($refUlbId)->map(function ($val) {
                    $val->ward_no = $val->ward_name;
                    return $val;
                });
                $data['wardList'] = objToArray($data['wardList']);
            } else {
                $data['wardList'] = $this->_parent->WardPermission($refUserId);
            }

            if ($request->getMethod() == 'GET') {

                $data['userType']           = $mUserType;
                $data["propertyType"]       = $this->getPropertyTypeList();
                $data["ownershipTypeList"]  = $this->getOwnershipTypeList();
                return responseMsg(true, "", remove_null($data));
            } elseif ($request->getMethod() == "POST") {
                return responseMsg(true, "", $data);
            }
        } catch (Exception $e) {

            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }
    public function getPropertyTypeList()
    {
        // try {
        //     $data = WaterPropertyTypeMstr::select('water_connection_type_mstrs.id', 'water_connection_type_mstrs.connection_type')
        //         ->where('status', 1)
        //         ->get();
        //     return $data;
        // } catch (Exception $e) 
        // {
        //     return [];
        // }
    }
    public function getOwnershipTypeList()
    {
    }
}
