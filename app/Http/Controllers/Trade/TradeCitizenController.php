<?php

namespace App\Http\Controllers\Trade;

use App\EloquentModels\Common\ModelWard;
use App\Http\Controllers\Controller;
use App\Http\Requests\Trade\ReqCitizenAddRecorde;
use App\Models\Trade\ActiveLicence;
use App\Models\Trade\ExpireLicence;
use App\Models\Trade\TradeFineRebetDetail;
use App\Models\Trade\TradeRazorPayRequest;
use App\Models\Trade\TradeRazorPayResponse;
use App\Models\Trade\TradeTransaction;
use App\Models\UlbMaster;
use App\Models\UlbWardMaster;
use App\Repository\Common\CommonFunction;
use App\Repository\Trade\ITradeCitizen;
use App\Repository\Trade\Trade;
use App\Traits\Auth;
use App\Traits\Payment\Razorpay;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TradeCitizenController extends Controller
{
    use Auth;               // Trait Used added by sandeep bara date 17-09-2022    
    use Razorpay;
    /**
     * | Created On-22-12-2022 
     * | Created By-Sandeep Bara
     * --------------------------------------------------------------------------------------
     * | Controller regarding with Trade Module
     */

    // Initializing function for Repository
    private $Repository;
    private $_modelWard;
    private $_parent;
    protected $_metaData;
    protected $_queryRunTime;
    private $_counter;
    public function __construct(ITradeCitizen $TradeRepository)
    {
        $this->Repository = $TradeRepository ;
        $this->_modelWard = new ModelWard();
        $this->_parent = new CommonFunction();
        $this->_counter = new Trade;
        $this->_queryRunTime=0.00;
        $this->_metaData = [
            "apiId" =>1.1,
            "version"=>1.1,            
            'queryRunTime' => $this->_queryRunTime,            
        ];
    }

    public function getWardList(Request $request)
    {
        $this->_metaData["apiId"] = "c1";
        $this->_metaData["queryRunTime"] = 2.48;
        $this->_metaData["action"]    = $request->getMethod();
        $this->_metaData["deviceId"] = $request->ip();
        try{
            $rules["ulbId"]="required|digits_between:1,9223372036854775807";
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) 
            {
               return responseMsgs(false,$validator->errors(),$request->all(),
                        $this->_metaData["apiId"],$this->_metaData["version"],$this->_metaData["queryRunTime"],
                        $this->_metaData["action"],$this->_metaData["deviceId"]
                    );
            }
            $mWardList = $this->_modelWard->getAllWard($request->ulbId)->map(function($val){
                $val["ward_no"] = $val["ward_name"];
                return $val;
            });
            $mWardList = remove_null($mWardList);
            return responseMsgs(true,"",$mWardList,
                $this->_metaData["apiId"],$this->_metaData["version"],$this->_metaData["queryRunTime"],
                $this->_metaData["action"],$this->_metaData["deviceId"]
            );
        }
        catch(Exception $e)
        {
            return responseMsgs(false,$e->getMessage(),$request->all(),
            $this->_metaData["apiId"],$this->_metaData["version"],$this->_metaData["queryRunTime"],
            $this->_metaData["action"],$this->_metaData["deviceId"]);
        }
    }

    public function applyApplication(ReqCitizenAddRecorde $request)
    {
        $this->_metaData["apiId"] = "c2";
        $this->_metaData["queryRunTime"] = 2.48;
        $this->_metaData["action"]    = $request->getMethod();
        $this->_metaData["deviceId"] = $request->ip();
        try{
            $refUser            = Auth()->user(); 
            $refUserId          = $refUser->id;
            $refUlbId           = $request->ulbId;
            $wardId = $request->firmDetails["wardNo"];            
            $wardId = $this->_modelWard->getAllWard($request->ulbId)->filter(function($item) use($wardId){
                            if($item->id== $wardId )
                            {
                                return $item;
                            }
                    });
            $refWorkflowId      = Config::get('workflow-constants.TRADE_WORKFLOW_ID'); 
            $mUserType          = $this->_parent->userType($refWorkflowId);
            $refWorkflows       = $this->_parent->iniatorFinisher($refUserId,$refUlbId,$refWorkflowId);        
            $mApplicationTypeId = Config::get("TradeConstant.APPLICATION-TYPE.".$request->applicationType);            
            if(sizeOf($wardId)<1)
            {
                throw new Exception("Invalide Ward Id Pase");
            }
            if(!in_array(strtoupper($mUserType),["ONLINE"]))
            {
                throw new Exception("You Are Not Authorized For This Action. Please Apply From Counter");
            }            
            if(!$mApplicationTypeId)
            {
                throw new Exception("Invalide Application Type");
            }
            if (!$refWorkflows) 
            {
                throw new Exception("Workflow Not Available");
            } 
            if(!$refWorkflows['initiator'])
            {
                throw new Exception("Initiator Not Available"); 
            }
            if(!$refWorkflows['finisher'])
            {
                throw new Exception("Finisher Not Available"); 
            }
            if (in_array($mApplicationTypeId, ["2", "3","4"]) && (!$request->id || !is_numeric($request->id))) 
            {
                throw new Exception ("Old licence Id Requird");
            } 
            return $this->Repository->addRecord($request);
            
        }
        catch(Exception $e)
        {
            return responseMsgs(false,$e->getMessage(),$request->all(),
            $this->_metaData["apiId"],$this->_metaData["version"],$this->_metaData["queryRunTime"],
            $this->_metaData["action"],$this->_metaData["deviceId"]);
        }        
    }
    # Serial No : 03 
    /**
     * | Get Notice Data
     */
    public function getDenialDetails(Request $request)
    {
        $this->_metaData["apiId"] = "c3";
        $this->_metaData["queryRunTime"] = 2.48;
        $this->_metaData["action"]    = $request->getMethod();
        $this->_metaData["deviceId"] = $request->ip();

        $data = (array)null;
        $refUser = Auth()->user();
        $refUlbId = $request->ulbId;
        $mNoticeNo = null;
        $mNowDate = Carbon::now()->format('Y-m-d'); // todays date
        try 
        {
            $rules=[
                "noticeNo"=>"required|string",
                "ulbId"    =>"required|digits_between:1,92"
            ];
            $validator = Validator::make($request->all(), $rules, ); 
            if ($validator->fails()) {                        
                return responseMsg(false, $validator->errors(),$request->all());
            }
            $mNoticeNo = $request->noticeNo;

            $refDenialDetails =  $this->_counter->getDenialFirmDetails($refUlbId,strtoupper(trim($mNoticeNo)));
            if ($refDenialDetails) 
            {
                $notice_date = Carbon::parse($refDenialDetails->noticedate)->format('Y-m-d'); //notice date
                $denialAmount =  $this->_counter->getDenialAmountTrade($notice_date, $mNowDate);
                $data['denialDetails'] = $refDenialDetails;
                $data['denialAmount'] = $denialAmount;
                return responseMsgs(true,"",$data,
                    $this->_metaData["apiId"],$this->_metaData["version"],$this->_metaData["queryRunTime"],
                    $this->_metaData["action"],$this->_metaData["deviceId"]
                    );
            } 
            else 
            {
                $response = "no Data";
                return responseMsgs(false,$response,$request->all(),
                    $this->_metaData["apiId"],$this->_metaData["version"],$this->_metaData["queryRunTime"],
                    $this->_metaData["action"],$this->_metaData["deviceId"]
                    );
            }
        } 
        catch (Exception $e) 
        {
            return responseMsgs(false,$e->getMessage(),$request->all(),
                $this->_metaData["apiId"],$this->_metaData["version"],$this->_metaData["queryRunTime"],
                $this->_metaData["action"],$this->_metaData["deviceId"]
            );
        }
        
    }

    # Serial No : 04
    public function handeRazorPay(Request $request)
    {
        $this->_metaData["apiId"] = "c4";
        $this->_metaData["queryRunTime"] = 4.00;
        $this->_metaData["action"]    = $request->getMethod();
        $this->_metaData["deviceId"] = $request->ip();
        try{
            #------------------------ Declaration-----------------------
            $refUser            = Auth()->user();
            $refNoticeDetails   = null;
            $refWorkflowId      = Config::get('workflow-constants.TRADE_WORKFLOW_ID');
            $mNoticeDate        = null;
            #------------------------End Declaration-----------------------
            $refLecenceData = $this->_counter->getLicenceById($request->licenceId);
            if(!$refLecenceData)
            {
                throw new Exception("Licence Data Not Found !!!!!");
            }
            elseif($refLecenceData->application_type_id==4)
            {
                throw new Exception("Surender Application Not Pay Anny Amount");
            }
            elseif(in_array($refLecenceData->payment_status,[1,2]))
            {
                throw new Exception("Payment Already Done Of This Application");
            }
            if($refLecenceData->tobacco_status==1 && $request->licenseFor >1)
            {
                throw new Exception("Tobaco Application Not Take Licence More Than One Year");
            }
            if($refNoticeDetails = $this->_counter->readNotisDtl($refLecenceData->id))
            { 
                $mNoticeDate = date('Y-m-d',strtotime($refNoticeDetails['created_on'])); //notice date 
            }

            #-----------End valication-------------------
            #-------------Calculation-----------------------------                
            $args['areaSqft']            = (float)$refLecenceData->area_in_sqft;
            $args['application_type_id'] = $refLecenceData->application_type_id;                    
            $args['firmEstdDate'] = !empty(trim($refLecenceData->valid_from)) ? $refLecenceData->valid_from : $refLecenceData->apply_date;
            if($refLecenceData->application_type_id==1)
            {
                $args['firmEstdDate'] = $refLecenceData->establishment_date;
            }
            $args['tobacco_status']      = $refLecenceData->tobacco_status;
            $args['licenseFor']          = $refLecenceData->licence_for_years ;
            $args['nature_of_business']  = $refLecenceData->nature_of_bussiness;
            $args['noticeDate']          = $mNoticeDate;
            $chargeData = $this->_counter->cltCharge($args);
            if($chargeData['response']==false || $chargeData['total_charge']==0)
            {
                throw new Exception("Payble Amount Missmatch!!!");
            }
            
            $transactionType = Config::get('TradeConstant.APPLICATION-TYPE-BY-ID.'.$refLecenceData->application_type_id);  
            
            $totalCharge = $chargeData['total_charge'] ;

            $myRequest = new \Illuminate\Http\Request();
            $myRequest->setMethod('POST');
            $myRequest->request->add(['amount' => $totalCharge]);
            $myRequest->request->add(['workflowId' => $refWorkflowId]);
            $myRequest->request->add(['id' => $request->licenceId]);
            $myRequest->request->add(['departmentId' => 3]);
            $myRequest->request->add(['ulbId' => $refLecenceData->ulb_id]);
            $temp = $this->saveGenerateOrderid($myRequest);
            DB::beginTransaction();
            $TradeRazorPayRequest = new TradeRazorPayRequest();
            $TradeRazorPayRequest->licence_id   = $request->licenceId;
            $TradeRazorPayRequest->payment_from = $transactionType ;
            $TradeRazorPayRequest->amount       = $totalCharge; 
            $TradeRazorPayRequest->ip_address   = $request->ip() ;
            $TradeRazorPayRequest->order_id	    = $temp["orderId"];
            $TradeRazorPayRequest->department_id = $temp["departmentId"];
            $TradeRazorPayRequest->save();

            $temp["requestId"]  = $TradeRazorPayRequest->id; 
            $temp['name']       = $refUser->user_name;
            $temp['mobile']     = $refUser->mobile;
            $temp['email']      = $refUser->email;
            $temp['userId']     = $refUser->id;
            $temp['ulbId']      = $refLecenceData->ulb_id;
            $temp['firmName']   = $refLecenceData->firm_name;
            $temp['wardNo']     = $refLecenceData->ward_no;
            $temp['newWardNo']  = $refLecenceData->new_ward_no;
            $temp['applyDate']  = $refLecenceData->apply_date;
            $temp['licenceForYears']  = $refLecenceData->licence_for_years;
            $temp['applicationType']  = config::get("TradeConstant.APPLICATION-TYPE-BY-ID.".$refLecenceData->application_type_id);
            DB::commit();
            return responseMsgs(true,"",$temp,
                $this->_metaData["apiId"],$this->_metaData["version"],$this->_metaData["queryRunTime"],
                $this->_metaData["action"],$this->_metaData["deviceId"]
            );
        }
        catch(Exception $e)
        { 
            DB::rollBack();
            return responseMsgs(false,$e->getMessage(),$request->all(),
                $this->_metaData["apiId"],$this->_metaData["version"],$this->_metaData["queryRunTime"],
                $this->_metaData["action"],$this->_metaData["deviceId"]
            );
        }
    }
    public function conformRazorPayTran(Request $request)
    {
        $this->_metaData["apiId"] = "c5";
        $this->_metaData["queryRunTime"] = 4.00;
        $this->_metaData["action"]    = $request->getMethod();
        $this->_metaData["deviceId"] = $request->ip();
        try{
            $refUser        = Auth()->user();
            $application = null;
            $transection = null;
            $path = "/api/trade/paymentReceipt/";
            $rules = [
                'orderId'    => 'required|string',
                'paymentId'  => 'required|string',
            ];
            $validator = Validator::make($request->all(), $rules,);
            if ($validator->fails()) {
                return responseMsg(false, $validator->errors(), $request->all());
            }
            $TradeRazorPayResponse = TradeRazorPayResponse::select("trade_razor_pay_responses.*","trade_razor_pay_requests.payment_from")
                ->join("trade_razor_pay_requests", "trade_razor_pay_requests.id", "trade_razor_pay_responses.request_id")
                ->where("trade_razor_pay_responses.order_id", $request->orderId)
                ->where("trade_razor_pay_responses.payment_id", $request->paymentId)
                ->where("trade_razor_pay_requests.status", 1)
                ->first();
            if (!$TradeRazorPayResponse) 
            {
                throw new Exception("Not Transection Found...");
            }
            $application = ActiveLicence::find($TradeRazorPayResponse->licence_id);
            $transection = TradeTransaction::select("*")
                ->where("related_id", $TradeRazorPayResponse->licence_id)
                ->first();
            
            if (!$application) 
            {
                throw new Exception("Application Not Found....");
            }
            if (!$transection) 
            {
                throw new Exception("Not Transection Data Found....");
            }
            $data["amount"]            = $TradeRazorPayResponse->amount;
            $data["applicationId"]     = $TradeRazorPayResponse->licence_id;
            $data["applicationNo"]     = $application->application_no;
            $data["tranType"]          = $TradeRazorPayResponse->payment_from;
            $data["transectionId"]     = $transection->id;
            $data["transectionNo"]     = $transection->transaction_no;
            $data["transectionDate"]   = $transection->transaction_date;
            $data['paymentRecipt']     = config('app.url') . $path . $TradeRazorPayResponse->licence_id . "/" . $transection->id;
            return responseMsgs(true,"",$data,
                $this->_metaData["apiId"],$this->_metaData["version"],$this->_metaData["queryRunTime"],
                $this->_metaData["action"],$this->_metaData["deviceId"]
            );
        }
        catch(Exception $e)
        {
            return responseMsgs(false,$e->getMessage(),$request->all(),
                $this->_metaData["apiId"],$this->_metaData["version"],$this->_metaData["queryRunTime"],
                $this->_metaData["action"],$this->_metaData["deviceId"]
            ); 
        }
    }
    # Serial No : 27
    public function citizenApplication(Request $request)
    {
        return $this->Repository->citizenApplication($request);
    }
    # Serial No : 28
    public function readCitizenLicenceDtl($id)
    {
        return $this->Repository->readCitizenLicenceDtl($id);
    }
}