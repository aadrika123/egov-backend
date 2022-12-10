<?php

namespace App\Repository\Water\Concrete;

use App\EloquentModels\Common\ModelWard;
use App\Models\UlbMaster;
use App\Models\Water\WaterApplication;
use App\Models\Water\WaterConnectionCharge;
use App\Models\Water\WaterRazorPayRequest;
use App\Models\Water\WaterRazorPayResponse;
use App\Models\Water\WaterTran;
use App\Models\Water\WaterTranDetail;
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

    protected $_modelWard;
    protected $_parent;
    protected $_wardNo;
    protected $_licenceId;
    protected $_shortUlbName;

    public function __construct()
    { 
        $this->_modelWard = new ModelWard();
        $this->_parent = new CommonFunction();
    }
    public function getCitizenApplication(Request $request)
    {
        try{
            $refUser            = Auth()->user();
            $refUserId          = $refUser->id;
            $refUlbId           = $refUser->ulb_id;
            $connection         = WaterApplication::select("water_applications.id",
                                        "water_applications.application_no",
                                        "water_applications.address",
                                        "water_applications.payment_status",
                                        "water_applications.doc_status",
                                        "charges.amount",
                                        DB::raw("'connection' AS type,water_applications.apply_date::date AS apply_date")
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
                                                    AND water_applications.ulb_id = $refUlbId
                                                GROUP BY water_applications.id
                                                ) AS charges
                                            "),
                                        function($join){
                                            $join->on("charges.application_id","water_applications.id");
                                        })
                                // ->whereNotIn("status",[0,6,7])
                                ->where("water_applications.user_id",$refUserId)
                                ->where("water_applications.ulb_id",$refUlbId)
                                ->get();
            return responseMsg(true,"",$connection);
        }
        catch(Exception $e)
        {

            return responseMsg(false,$e->getMessage(),$request->all());
        }
    }
    public function handeRazorPay(Request $request)
    {
        try{
            $refUser            = Auth()->user();
            $refUserId          = $refUser->id;
            $refUlbId           = $refUser->ulb_id;
            $rules = [
                'id'                =>'required|digits_between:1,9223372036854775807',
                'applycationType'  =>'required|string|in:connection,consumer',
            ];                         
            $validator = Validator::make($request->all(), $rules,);                    
            if ($validator->fails()) {                        
                return responseMsg(false, $validator->errors(),$request->all());
            }
            #------------ new connection --------------------
            DB::beginTransaction();
            if($request->applycationType=="connection")
            {
                $application = WaterApplication::find($request->id);
                if(!$application)
                {
                    throw new Exception("Data Not Found!......");
                }                
                $cahges = $this->getWaterConnectionChages($application->id);
                if(!$cahges)
                {
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
                $RazorPayRequest->payment_from = "New Connection" ;
                $RazorPayRequest->amount       = $cahges->amount;
                $RazorPayRequest->demand_from_upto = $cahges->ids;
                $RazorPayRequest->ip_address   = $request->ip() ;
                $RazorPayRequest->order_id	    = $temp["orderId"];
                $RazorPayRequest->department_id = $temp["departmentId"];
                $RazorPayRequest->save(); 
                
            }
            #--------------------water Consumer----------------------
            else
            {

            }
            DB::commit(); 
            $temp['name']       = $refUser->user_name;
            $temp['mobile']     = $refUser->mobile;
            $temp['email']      = $refUser->email;
            $temp['userId']     = $refUser->id;
            $temp['ulbId']      = $refUser->ulb_id;             
            $temp["applycationType"] = $request->applycationType;
            return responseMsg(true,"",$temp);
        }
        catch(Exception $e)
        { 
            DB::rollBack();
            return responseMsg(false,$e->getMessage(),$request->all());
        }
    }
    public function razorPayResponse($args)
    {
        try{
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id??$args["userId"];
            $refUlbId       = $refUser->ulb_id??$args["ulbId"];         
            $mNowDate       = Carbon::now()->format('Y-m-d'); 
            $mTimstamp      = Carbon::now()->format('Y-m-d H:i:s');
            $cahges         = null; 
            $chargeData     = (array)null; 
            $application    = null;
            $mDemands       = (array)null; 
            
            #-----------valication------------------- 
            $RazorPayRequest = WaterRazorPayRequest::select("*")
                                    ->where("order_id",$args["orderId"])
                                    ->where("related_id",$args["id"])
                                    ->where("status",2)
                                    ->first();
            if(!$RazorPayRequest)
            {
                throw new Exception("Data Not Found");
            }
            if($RazorPayRequest->payment_from=="New Connection")
            {
                $application = WaterApplication::find($args["id"]);
                $cahges = 0 ;
                $id = explode(",",$RazorPayRequest->demand_from_upto); 
                if($id) 
                {
                    $mDemands = WaterConnectionCharge::select("*")
                                ->whereIn("id",$id)
                                ->get();
                    $cahges = ($mDemands->sum("amount"));

                }              
                $chargeData["total_charge"]= $cahges;
            }
            elseif($RazorPayRequest->payment_from=="Demand Collection")
            {
                $application = null;
            }
            if(!$application)
            {
                throw new Exception("Application Not Found!......");
            }
            $applicationId = $args["id"];
            #-----------End valication----------------------------

            #-------------Calculation----------------------------- 
            if(!$chargeData|| round($args['amount'])!= round($chargeData['total_charge']))
            {
                throw new Exception("Payble Amount Missmatch!!!");
            }
            
            $transactionType = $RazorPayRequest->payment_from;  
            
            $totalCharge = $chargeData['total_charge'] ;
            #-------------End Calculation-----------------------------
            #-------- Transection -------------------
            DB::beginTransaction();

            $RazorPayResponse = new WaterRazorPayResponse;
            $RazorPayResponse->related_id   = $RazorPayRequest->related_id;
            $RazorPayResponse->request_id   = $RazorPayRequest->id;
            $RazorPayResponse->amount       = $args['amount'];
            $RazorPayResponse->merchant_id  =  $args['merchantId']??null;
            $RazorPayResponse->order_id     = $args["orderId"];
            $RazorPayResponse->payment_id   = $args["paymentId"];
            $RazorPayResponse->save();

            $RazorPayRequest->status=1;
            $RazorPayRequest->update();

            $Tradetransaction = new WaterTran;
            $Tradetransaction->related_id       = $applicationId;
            $Tradetransaction->ward_id          = $application->ward_id;
            $Tradetransaction->tran_type = $transactionType;
            $Tradetransaction->tran_date = $mNowDate;
            $Tradetransaction->payment_mode     = "Online";
            $Tradetransaction->amount           = $totalCharge;
            $Tradetransaction->emp_dtl_id       = $refUserId;
            $Tradetransaction->created_at       = $mTimstamp;
            $Tradetransaction->ip_address       = '';
            $Tradetransaction->ulb_id           = $refUlbId;
            $Tradetransaction->save();
            $transaction_id                     = $Tradetransaction->id;
            $Tradetransaction->tran_no   = "WTRAN/".date("m")."/".date("Y")."/".($transaction_id);//"WTRAN/date('m')/date('Y')/$transaction_id;
            $Tradetransaction->update();
            
            foreach($mDemands as $val)
            {
                $TradeDtl = new WaterTranDetail;
                $TradeDtl->tran_id        = $transaction_id;
                $TradeDtl->demand_id      = $val->id;
                $TradeDtl->total_demand   = $val->amount;
                $TradeDtl->application_id   = $val->application_id;
                $TradeDtl->created_on     = $mTimstamp;
                $TradeDtl->save();

                $val->paid_status = true ;
                $val->update();
            }
            $application->payment_status = true;
            DB::commit();
            #----------End transaction------------------------
            #----------Response------------------------------
            $res['transactionId'] = $transaction_id;
            $res['paymentRecipt']= config('app.url')."/api/water/paymentRecipt/".$applicationId."/".$transaction_id;
            return responseMsg(true,"",$res); 
        }
        catch(Exception $e)
        {
            DB::rollBack();
            return responseMsg(false,$e->getMessage(),$args);
        }
    }

    public function readPaymentRecipt($id, $transectionId)
    {
        try{
            return responseMsg(true,"",'');
        }
        catch(Exception $e)
        {
            return responseMsg(false,$e->getMessage(),'');
        }
    }

    #---------- core function --------------------------------------------------
     
    public function getWaterConnectionChages($applicationId)
    {
        try{
            $cahges = WaterConnectionCharge::select(DB::raw("SUM(COALESCE(amount,0)) AS amount, STRING_AGG(id::TEXT,',') AS ids"))
                          ->where("application_id",$applicationId)
                          ->Where(function($where){
                            $where->orWhere("paid_status",FALSE)
                            ->orWhereNull("paid_status");
                          })
                          ->Where(function($where){
                            $where->orWhere("status",TRUE)
                            ->orWhereNull("status");
                          })
                          ->groupBy("application_id")
                          ->first();
            return $cahges;
        }
        catch(Exception $e)
        { 
            return [];
        }
    }  
    
    #-----------------incomplite Code------------------------------
   
    public function applyApplication(Request $request)
    {
        try{
            #------------------------ Declaration-----------------------           
            $refUser            = Auth()->user();
            $refUserId          = $refUser->id;
            $refUlbId           = $refUser->ulb_id;
            $refUlbDtl          = UlbMaster::find($refUlbId);
            $refUlbName         = explode(' ',$refUlbDtl->ulb_name);
            $refNoticeDetails   = null;
            $refWorkflowId      = Config::get('workflow-constants.TRADE_WORKFLOW_ID');
            $refWorkflows       = $this->_parent->iniatorFinisher($refUserId,$refUlbId,$refWorkflowId);

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

            $rollId             =  $mUserData['role_id']??($this->_parent->getUserRoll($refUserId, $refUlbId,$refWorkflowId)->role_id??-1);
            $data               = array() ;
            #------------------------End Declaration-----------------------
            #---------------validation-------------------------------------
            if(!in_array(strtoupper($mUserType),["ONLINE","JSK","UTC","TC","SUPER ADMIN","TL"]))
            {
                throw new Exception("You Are Not Authorized For This Action !");
            }       
            if (!$refWorkflows) 
            {
                return responseMsg(false, "Workflow Not Available", $request->all());
            }
            elseif(!$refWorkflows['initiator'])
            {
                return responseMsg(false, "Initiator Not Available", $request->all()); 
            }
            elseif(!$refWorkflows['finisher'])
            {
                return responseMsg(false, "Finisher Not Available", $request->all()); 
            }
            #---------------End validation-------------------------
            if(in_array(strtoupper($mUserType),["ONLINE","JSK","SUPER ADMIN","TL"]))
            {
                $data['wardList'] = $this->_modelWard->getAllWard($refUlbId)->map(function($val){
                    $val->ward_no = $val->ward_name;
                    return $val;
                });
                $data['wardList'] = objToArray($data['wardList']);
            }
            else
            {                
                $data['wardList'] = $this->_parent->WardPermission($refUserId);
            }

            if($request->getMethod()=='GET')
            {

                $data['userType']           = $mUserType;
                $data["propertyType"]       = $this->getPropertyTypeList();
                $data["ownershipTypeList"]  = $this->getOwnershipTypeList();
                return responseMsg(true,"",remove_null($data));
            }
            elseif($request->getMethod()=="POST")
            {
                return responseMsg(true,"",$data);
            }
        }
        catch(Exception $e)
        {

            return responseMsg(false,$e->getMessage(),$request->all());
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