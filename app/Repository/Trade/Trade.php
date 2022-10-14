<?php

namespace App\Repository\Trade;

use App\EloquentModels\Common\ModelWard;
use App\Models\ActiveSafOwnerDetail;
use App\Models\Property\ActiveSafDetail ;
use App\Models\PropOwner;
use App\Models\PropPropertie;
use App\Models\Trade\ActiveLicence;
use App\Models\Trade\ActiveLicenceOwner;
use App\Models\Trade\ExpireLicence;
use App\Models\Trade\TradeApplicationDoc;
use App\Models\Trade\TradeBankRecancilation;
use App\Models\Trade\TradeChequeDtl;
use App\Models\Trade\TradeDenialConsumerDtl;
use App\Models\Trade\TradeDenialNotice;
use App\Models\Trade\TradeFineRebetDetail;
use App\Models\Trade\TradeParamApplicationType;
use App\Models\Trade\TradeParamCategoryType;
use App\Models\Trade\TradeParamFirmType;
use App\Models\Trade\TradeParamItemType;
use App\Models\Trade\TradeParamLicenceRate;
use App\Models\Trade\TradeParamOwnershipType;
use App\Models\Trade\TradeTransaction;
use App\Models\UlbWorkflowMaster;
use App\Models\WorkflowTrack;
use Illuminate\Http\Request;

use App\Traits\Auth;
use App\Traits\Property\WardPermission;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;

class Trade implements ITrade
{
    use Auth;
    use WardPermission;

    protected $user_id;
    protected $roll_id;
    protected $ulb_id;
    protected $redis;
    protected $user_data;
    protected $application_type_id;

    public function __construct()
    { 
        $this->ModelWard = new ModelWard();
    }
    public function applyApplication(Request $request)
    {           
        $denialAmount = 0; 
        $user = Auth()->user();
        $this->user_id = $user->id;
        $this->ulb_id = $user->ulb_id;
        $this->redis = new Redis;
        $this->user_data = json_decode($this->redis::get('user:' . $this->user_id), true);
        $this->roll_id =  $this->user_data['role_id']??($this->getUserRoll($this->user_id,'Trade','Trade')->role_id??-1);
        try
        {
            $this->application_type_id = Config::get("TradeConstant.APPLICATION-TYPE.".$request->applicationType);            
            if(!$this->application_type_id)
            {
                throw new Exception("Invalide Application Type");
            }
            $workflow_id = Config::get('workflow-constants.TRADE_WORKFLOW_ID');
            $workflows = UlbWorkflowMaster::select('initiator', 'finisher')
                ->where('ulb_id', $this->ulb_id)
                ->where('workflow_id', $workflow_id)
                ->first();
            if (!$workflows) {
                return responseMsg(false, "Workflow Not Available", $request->all());
            }
            $data = array() ;
            $rules = [];
            $message = [];
            if (in_array($this->application_type_id, ["2", "3","4"])) {
                $rules["licenceId"] = "required";
                $message["licenceId.required"] = "Old Licence Id Requird";
            }
            $validator = Validator::make($request->all(), $rules, $message);
            if ($validator->fails()) {
                return responseMsg(false, $validator->errors(),$request->all());
            }
            $data['wardList'] = $this->ModelWard->getAllWard($this->ulb_id)->map(function($val){
                $val->ward_no = $val->ward_name;
                return $val;
             });
            if($request->getMethod()=='GET')
            {
                $data["firmTypeList"] = $this->getFirmTypeList();
                $data["ownershipTypeList"] = $this->getownershipTypeList();
                $data["categoryTypeList"] = $this->getCotegoryList();
                $data["natureOfBusiness"] = $this->gettradeitemsList();
                if(isset($request->licenceId) && $request->licenceId  && $this->application_type_id !=1)
                {
        
                }
                return responseMsg(true,"",$data);
            }
            elseif($request->getMethod()=="POST")
            { 
                $nowdate = Carbon::now()->format('Y-m-d'); 
                $timstamp = Carbon::now()->format('Y-m-d H:i:s');
                $apply_from = $this->applyFrom();
                $regex = '/^[a-zA-Z1-9][a-zA-Z1-9\.\s]+$/';
                $alphaNumCommaSlash='/^[a-zA-Z0-9- ]+$/i';
                $alphaSpace ='/^[a-zA-Z ]+$/i';
                $alphaNumhyphen ='/^[a-zA-Z0-9- ]+$/i';
                $numDot = '/^\d+(?:\.\d+)+$/i';
                $dateFormatYYYMMDD ='/^([12]\d{3}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01]))+$/i';
                $dateFormatYYYMM='/^([12]\d{3}-(0[1-9]|1[0-2]))+$/i';
                $rules["firmDetails.areaSqft"]="required|numeric";
                $rules["firmDetails.businessAddress"]="required|regex:$regex";
                $rules["firmDetails.businessDescription"]="required|regex:$regex"; 
                $rules["firmDetails.firmEstdDate"]="required|date"; 
                $rules["firmDetails.firmName"]="required|regex:$regex";
                if (in_array($this->application_type_id, ["2"])) 
                {                    
                    $rules["firmDetails.holdingNo"]="required|regex:$regex";
                } 
                $rules["firmDetails.premisesOwner"]="required|regex:$regex";
                $rules["firmDetails.natureOfBusiness"]="required|array";
                $rules["firmDetails.natureOfBusiness.*.id"]="required|int";
                $rules["firmDetails.newWardNo"]="required|int";
                $rules["firmDetails.wardNo"]="required|int";
                $rules["firmDetails.tocStatus"] = "required|bool";
                $rules["firmDetails.landmark"]="regex:$regex";
                $rules["firmDetails.categoryTypeId"]="int";
                $rules["firmDetails.k_no"] = "digits|regex:/[0-9]{10}/";
                $rules["firmDetails.bind_book_no"] = "regex:$regex";
                $rules["firmDetails.account_no"] = "regex:$regex";
                if($apply_from=="Online")
                {
                    $rules["firmDetails.pincode"]="digits:6|regex:/[0-9]{6}/";                    
                }                

                $rules["initialBusinessDetails.applyWith"]="required|int";
                $rules["initialBusinessDetails.firmType"]="required|int";
                if(isset($request->initialBusinessDetails['firmType']) && $request->initialBusinessDetails['firmType']==5)
                {
                    $rules["initialBusinessDetails.otherFirmType"]="required|regex:$regex";
                }
                $rules["initialBusinessDetails.ownershipType"]="required|int";
                if( isset($request->initialBusinessDetails['applyWith']) && $request->initialBusinessDetails['applyWith']==1)
                {
                    $rules["initialBusinessDetails.noticeNo"]="required";
                    $rules["initialBusinessDetails.noticeDate"]="required|date";  
                }
                $rules["licenseDetails.licenseFor"]="required|int";
                if($apply_from =="Online")
                {
                    $rules["licenseDetails.paymentMode"]="required|alpha"; 
                    if(isset($request->licenseDetails['paymentMode']) && $request->licenseDetails['paymentMode']!="CASH")
                    {
                        $rules["licenseDetails.chaqueNo"] ="required";
                        $rules["licenseDetails.chequeDate"] ="required|date|date_format:Y-m-d|after_or_equal:$nowdate";
                        $rules["licenseDetails.bankName"] ="required|regex:$regex";
                        $rules["licenseDetails.branchName"] ="required|regex:$regex";
                    } 
                }

                $rules["ownerDetails"] = "required|array";
                $rules["ownerDetails.*.businessOwnerName"]="required|regex:/^([a-zA-Z]+)(\s[a-zA-Z0-9]+)*$/";
                $rules["ownerDetails.*.guardianName"]="regex:/^([a-zA-Z]+)(\s[a-zA-Z0-9]+)*$/";
                $rules["ownerDetails.*.mobileNo"]="required|digits:10|regex:/[0-9]{10}/";
                $rules["ownerDetails.*.email"]="email";
                
                if (in_array($this->application_type_id, ["2", "3","4"])) 
                {                    
                    $rules["licenceId"] = "required";
                    $message["licenceId.required"] = "Old Licence Id Requird";
                }
                $validator = Validator::make($request->all(), $rules, $message);
                if ($validator->fails()) 
                {
                    return responseMsg(false, $validator->errors(),$request->all());
                }
                
                $wardId = $request->firmDetails['wardNo'];
                $ward_no = array_filter(adjToArray($data['wardList']), function ($val) use($wardId ){
                    return $val['id'] == $wardId ;
                });
                $ward_no = array_values($ward_no)[0]['ward_name'];
                $proprty_id = null;
                if($request->firmDetails['holdingNo'])
                {
                    $property = $this->propertyDetailsfortradebyHoldingNo($request->firmDetails['holdingNo'],$this->ulb_id);
                    if($property['status'])
                        $proprty_id = $property['property']['id'];
                    else
                        throw new Exception("Property Details Not Found");
                }
                $natureOfBussiness = array_map(function($val){
                    return $val['id'];
                },$request->firmDetails['natureOfBusiness']);
                $natureOfBussiness = implode(',', $natureOfBussiness);
                
                DB::beginTransaction();                
                $licence = new ActiveLicence();
                if (in_array($this->application_type_id, ["2", "3","4"])) 
                {   
                    $oldLicence = ActiveLicence::find($request->licenceId);
                    $oldowners = ActiveLicenceOwner::where('licence_id',$request->licenceId)
                                ->get();
                    $licence->id                  = $oldLicence->id;
                    $licence->firm_type_id        = $oldLicence->firm_type_id;
                    $licence->otherfirmtype       = $oldLicence->otherfirmtype;
                    $licence->application_type_id = $this->application_type_id;
                    $licence->category_type_id    = $oldLicence->category_type_id;
                    $licence->ownership_type_id   = $oldLicence->ownership_type_id;
                    $licence->ward_mstr_id        = $oldLicence->ward_mstr_id;
                    $licence->new_ward_mstr_id    = $oldLicence->new_ward_mstr_id;
                    $licence->ulb_id              = $this->ulb_id;
    
                    $licence->prop_dtl_id         = $proprty_id;
                    $licence->holding_no          = $request->firmDetails['holdingNo'];
                    $licence->nature_of_bussiness = $oldLicence->nature_of_bussiness;
                    $licence->firm_name           = $oldLicence->firm_name;
                    $licence->premises_owner_name = $oldLicence->premises_owner_name;
                    $licence->brife_desp_firm     = $oldLicence->brife_desp_firm;
                    $licence->area_in_sqft        = $oldLicence->area_in_sqft;
    
                    $licence->k_no                = $oldLicence->k_no;
                    $licence->bind_book_no        = $oldLicence->bind_book_no;
                    $licence->account_no          = $oldLicence->account_no;
                    $licence->pan_no              = $oldLicence->pan_no;
                    $licence->tin_no              = $oldLicence->tin_no;
                    $licence->salestax_no         = $oldLicence->salestax_no;
                    $licence->emp_details_id      = $this->user_id;
                    $licence->establishment_date  = $oldLicence->establishment_date;
                    $licence->apply_date          =$nowdate;
    
                    $licence->licence_for_years   = $request->licenseDetails['licenseFor'];
                    $licence->address             = $oldLicence->address;
                    $licence->landmark            = $oldLicence->landmark;
                    $licence->pin_code            = $oldLicence->pin_code;
                    $licence->street_name         = $oldLicence->street_name;
                    $licence->property_type       = $oldLicence->property_type;
                    $licence->update_status       = $this->transfareExpire($request->licenceId);
                    $licence->tobacco_status      = $oldLicence->tobacco_status;
                    
                    $licence->apply_from          = $apply_from;
                    $licence->current_user_id     = $workflows->initiator;
                    $licence->initiator_id        = $workflows->initiator;
                    $licence->finisher_id         = $workflows->finisher;
                    $licence->workflow_id         = $workflow_id;
    
                    $licence->save();
                    $licenceId = $licence->id;                
                    $appNo = "APP".str_pad($ward_no, 2, '0', STR_PAD_LEFT).str_pad($licenceId, 7, '0', STR_PAD_LEFT);
                    $licence->application_no = $appNo;
                    $licence->save();

                    foreach($oldowners as $owners)
                    {
                        $owner = new ActiveLicenceOwner();
                        $owner->id = $owners->id;
                        $owner->licence_id = $licenceId;
                        $owner->owner_name = $owners->owner_name;
                        $owner->guardian_name = $owners->guardian_name;
                        $owner->address = $owners->address;
                        $owner->mobile = $owners->mobile;
                        $owner->city = $owners->city;
                        $owner->district = $owners->district;
                        $owner->state = $owners->state;
                        $owner->emailid = $owners->emailid;
                        $owner->emp_details_id = $this->user_id;
                        $owner->save();
    
                    }
                }
                elseif($this->application_type_id==1)
                {                    
                    $licence->firm_type_id        = $request->initialBusinessDetails['firmType'];
                    $licence->otherfirmtype       = $request->initialBusinessDetails['otherFirmType']??null;
                    $licence->application_type_id = $this->application_type_id;
                    $licence->category_type_id    = $request->firmDetails['categoryTypeId']??null;
                    $licence->ownership_type_id   = $request->initialBusinessDetails['ownershipType'];
                    $licence->ward_mstr_id        = $request->firmDetails['wardNo'];
                    $licence->new_ward_mstr_id    = $request->firmDetails['newWardNo'];
                    $licence->ulb_id              = $this->ulb_id;
    
                    $licence->prop_dtl_id         = $proprty_id;
                    $licence->holding_no          = $request->firmDetails['holdingNo'];
                    $licence->nature_of_bussiness = $natureOfBussiness;
                    $licence->firm_name           = $request->firmDetails['firmName'];
                    $licence->premises_owner_name = $request->firmDetails['premisesOwner']??null;
                    $licence->brife_desp_firm     = $request->firmDetails['businessDescription'];
                    $licence->area_in_sqft        = $request->firmDetails['areaSqft'];
    
                    $licence->k_no                = $request->firmDetails['kNo']??null;
                    $licence->bind_book_no        = $request->firmDetails['bindBookNo']??null;
                    $licence->account_no          = $request->firmDetails['accountNo']??null;
                    $licence->pan_no              = $request->firmDetails['panNo']??null;
                    $licence->tin_no              = $request->firmDetails['tinNo']??null;
                    $licence->salestax_no         = $request->firmDetails['salestaxNo']??null;
                    $licence->emp_details_id      = $this->user_id;
                    $licence->establishment_date  = $request->firmDetails['firmEstdDate'];
                    $licence->apply_date          = $nowdate;
    
                    $licence->licence_for_years   = $request->licenseDetails['licenseFor'];
                    $licence->address             = $request->firmDetails['businessAddress'];
                    $licence->landmark            = $request->firmDetails['landmark']??null;
                    $licence->pin_code            = $request->firmDetails['pincode']??null;
                    $licence->street_name         = $request->firmDetails['streetName']??null;
                    $licence->property_type       ="Property";
                    $licence->update_status       = in_array($this->application_type_id,[2,3,4])?$this->transfareExpire($request->licenceId):0;
                    $licence->tobacco_status      = $request->firmDetails['tocStatus'];
                    
                    $licence->apply_from          = $apply_from;
                    $licence->current_user_id     = $workflows->initiator;
                    $licence->initiator_id        = $workflows->initiator;
                    $licence->finisher_id         = $workflows->finisher;
                    $licence->workflow_id         = $workflow_id;
    
                    $licence->save();
                    $licenceId = $licence->id;                
                    $appNo = "APP".str_pad($ward_no, 2, '0', STR_PAD_LEFT).str_pad($licenceId, 7, '0', STR_PAD_LEFT);
                    $licence->application_no = $appNo;
                    $licence->save();
                    foreach($request->ownerDetails as $owners)
                    {
                        $owner = new ActiveLicenceOwner();
                        $owner->licence_id = $licenceId;
                        $owner->owner_name = $owners['businessOwnerName'];
                        $owner->guardian_name = $owners['guardianName']??null;
                        $owner->address = $owners['address']??null;
                        $owner->mobile = $owners['mobileNo'];
                        $owner->city = $owners['city']??null;
                        $owner->district = $owners['district']??null;
                        $owner->state = $owners['state']??null;
                        $owner->emailid = $owners['email']??null;
                        $owner->emp_details_id = $this->user_id;
                        $owner->save();
    
                    }
                }
                // dd($apply_from);
                if($apply_from =="Online")
                {
                    $notice_date = null;
                    if($request->initialBusinessDetails['applyWith']==1)
                    { 
                        $noticeNo = trim($request->initialBusinessDetails['noticeNo']);
                        $firm_date = $request->firmDetails['firmEstdDate'];
                        $noticeDetails = $this->getDenialFirmDetails(strtoupper(trim($noticeNo)), $firm_date);
                        if ($noticeDetails) 
                        {   
                            $denialId = $noticeDetails->id;
                            $now = strtotime(date('Y-m-d H:i:s')); // todays date
                            $notice_date = strtotime($noticeDetails['created_on']); //notice date 
                            $this->updateStatusFine($denialId, $denialAmount, $licenceId); //update status and fineAmount                           
        
                        }
                    }
                    # Calculating rate
                    {
                       
                        $args['areaSqft']            = (float)$licence->area_in_sqft;
                        $args['application_type_id'] = $this->application_type_id;
                        $args['firmEstdDate']        = $request->firmDetails['firmEstdDate'];
                        $args['tobacco_status']      = $licence->tobacco_status;
                        $args['licenseFor']          =  $licence->licence_for_years ;
                        $args['nature_of_business']   = $licence->nature_of_bussiness;
                        $args['noticeDate']            = $notice_date;
                        $rate_data = $this->getcharge($args);
                    }
    
                    //end
                    $totalCharge = $rate_data['total_charge'] ;
                    $denialAmount = $rate_data['notice_amount'];
                    $Tradetransaction = new TradeTransaction ;
                    $Tradetransaction->related_id = $licenceId;
                    $Tradetransaction->ward_mstr_id = $licence->ward_mstr_id;
                    $Tradetransaction->transaction_type = $this->application_type_id==1?"NEW LICENSE":$request->applicationType;
                    $Tradetransaction->transaction_date = $nowdate;
                    $Tradetransaction->payment_mode = $request->licenseDetails['paymentMode'];
                    $Tradetransaction->paid_amount = $totalCharge;
    
                    $Tradetransaction->penalty = $rate_data['penalty'] + $denialAmount + $rate_data['arear_amount'];
                    if ($request->licenseDetails['paymentMode'] != 'CASH') 
                    {
                        $Tradetransaction->status = 2;
                    }
                    $Tradetransaction->emp_details_id = $this->user_id;
                    $Tradetransaction->created_on = $timstamp;
                    $Tradetransaction->ip_address = '';
                    $Tradetransaction->ulb_id = $this->ulb_id;
                    $Tradetransaction->save();
                    $transaction_id = $Tradetransaction->id;
                    $Tradetransaction->transaction_no = "TRANML" . date('d') . $transaction_id . date('Y') . date('m') . date('s');
                    $Tradetransaction->update();
                    $TradeFineRebet = new TradeFineRebetDetail;
                    $TradeFineRebet->transaction_id = $transaction_id;
                    $TradeFineRebet->head_name = 'Delay Apply License';
                    $TradeFineRebet->amount = $rate_data['penalty'];
                    $TradeFineRebet->value_add_minus = 'Add';
                    $TradeFineRebet->created_on = $timstamp;
                    $TradeFineRebet->save();
                    $denialAmount = $denialAmount + $rate_data['arear_amount'];
                    if ($denialAmount > 0) 
                    {
                        $TradeFineRebet2 = new TradeFineRebetDetail;
                        $TradeFineRebet2->transaction_id = $transaction_id;
                        $TradeFineRebet2->head_name = 'Denial Apply';
                        $TradeFineRebet2->amount = $denialAmount;
                        $TradeFineRebet2->value_add_minus = 'Add';
                        $TradeFineRebet2->created_on = $timstamp;
                        $TradeFineRebet2->save();
                    }
    
    
                    $payment_status = 1;
                    if ($request->licenseDetails['paymentMode'] != 'CASH') 
                    {
                        $tradeChq = new TradeChequeDtl;
                        $tradeChq->transaction_id = $transaction_id;
                        $tradeChq->cheque_no = $request->licenseDetails['chaqueNo'];
                        $tradeChq->cheque_date = $request->licenseDetails['chequeDate'];
                        $tradeChq->bank_name = $request->licenseDetails['bankName'];
                        $tradeChq->branch_name = $request->licenseDetails['branchName'];
                        $tradeChq->emp_details_id = $this->user_id;
                        $tradeChq->created_on = $timstamp;
                        $payment_status = 2;
                        $tradeChq->save();
                    } 
                    $licence->payment_status = $payment_status;
                    $licence->save();
                    $res['paymentRecipt']= config('app.url')."/api/trade/paymentRecipt/".$licenceId."/".$transaction_id;
                }
                
                DB::commit();
                $res['applicationNo']=$appNo;
                $res['applyLicenseId'] = $licenceId;
                return responseMsg(true,$appNo,$res);
            }
            
        }
        catch (Exception $e) {
            DB::rollBack(); 
            echo $e->getFile(); 
            echo $e->getLine();          
            return responseMsg(false,$e->getMessage(),$request->all());
        }
    }
    public function paymentRecipt($id, $transectionId)
    { 
        try{
            $application = ActiveLicence::select("application_no","provisional_license_no","license_no",
                                            "owner.owner_name","owner.guardian_name","owner.mobile",
                                            DB::raw("ulb_ward_masters.ward_name AS ward_no, 
                                            ulb_masters.id as ulb_id, ulb_masters.ulb_name,ulb_masters.ulb_type
                                            ")
                            )
                            ->join("ulb_masters","ulb_masters.id","active_licences.ulb_id")
                            ->join("ulb_ward_masters",function($join){
                                $join->on("ulb_ward_masters.id","=","active_licences.ward_mstr_id");                                
                            })
                            ->join(DB::raw("(SELECT STRING_AGG(owner_name,',') as owner_name,
                                                STRING_AGG(guardian_name,',') as guardian_name,
                                                STRING_AGG(mobile::text,',') as mobile,
                                                licence_id
                                            FROM active_licence_owners 
                                            WHERE licence_id = $id
                                                AND status =1
                                            GROUP BY licence_id
                                            ) owner"),function($join){
                                                $join->on("owner.licence_id","=","active_licences.id");
                                            })
                            ->where('active_licences.id',$id)
                            ->first();
            if(!$application)
            {
                $application = ExpireLicence::select("application_no","provisional_license_no","license_no",
                                        "owner.owner_name","owner.guardian_name","owner.mobile",
                                        DB::raw("ulb_ward_masters.ward_name AS ward_no, 
                                        ulb_masters.id as ulb_id, ulb_masters.ulb_name,ulb_masters.ulb_type
                                        ")
                                    )
                            ->join("ulb_masters","ulb_masters.id","active_licences.ulb_id")
                            ->join("ulb_ward_masters",function($join){
                                $join->on("ulb_ward_masters.id","=","active_licences.ward_mstr_id");                                
                            })
                            ->join(DB::raw("(SELECT STRING_AGG(owner_name,',') as owner_name,
                                                STRING_AGG(guardian_name,',') as guardian_name,
                                                STRING_AGG(mobile,',') as mobile,
                                                licence_id
                                            FROM expire_licence_owners 
                                            WHERE licence_id = $id
                                                AND status =1
                                            GROUP BY licence_id
                                            ) owner"),function($join){
                                                $join->on("owner.licence_id","=","expire_licences.id");
                                            })
                            ->where('expire_licences.id',$id)
                            ->first();
                if(!$application)
                {
                    throw new Exception("Application Not Found");
                }
            }
            $transection = TradeTransaction::select("transaction_no","transaction_type","transaction_date",
                                        "payment_mode","paid_amount","penalty",
                                        "trade_cheque_dtls.cheque_no","trade_cheque_dtls.cheque_date",
                                        "trade_cheque_dtls.bank_name","trade_cheque_dtls.branch_name"
                                    )
                            ->leftjoin("trade_cheque_dtls","trade_cheque_dtls.transaction_id","trade_transactions.id")
                            ->where("trade_transactions.id",$transectionId)
                            ->whereIn("trade_transactions.status",[1,2])
                            ->first();
            if(!$transection)
            {
                throw New Exception("Transaction Not Faound");
            }
            $penalty = TradeFineRebetDetail::select("head_name","amount")
                        ->where('transaction_id',$transectionId)
                        ->where("status",1)
                        ->orderBy("id")
                        ->get();
            $data = ["application"=>$application,
                     "transection"=>$transection,
                     "penalty"    =>$penalty
            ];
            $data = remove_null($data);
            return responseMsg(true,"", $data);

        }
        catch(Exception $e)
        {
            return responseMsg(false,$e->getMessage(),'');
        }
    }
    public function updateBasicDtl(Request $request)
    {
        $user = Auth()->user();
        $this->user_id = $user->id;
        $this->ulb_id = $user->ulb_id;
        $this->redis = new Redis;
        $this->user_data = json_decode($this->redis::get('user:' . $this->user_id), true);
        $this->roll_id =  $this->user_data['role_id']??($this->getUserRoll($this->user_id,'Trade','Trade')->role_id??-1);
        $rules = [];
        $message = [];
        try{
            if($this->roll_id==-1)
            {
                throw new Exception("You Are Not Authorized");
            }
            $regex = '/^[a-zA-Z1-9][a-zA-Z1-9\.\s]+$/';
            $rules["licenceId"] = "required";
            $message["licenceId.required"] = "Licence Id Requird";
            $rules["wordNo"] = "int";
            $rules["newWordNo"] = "int";
            $rules["businessAddress"] = "regex:$regex";
            $rules["businessDescription"] = "regex:$regex";
            $rules["premisesOwner"] = "regex:$regex";
            $rules["pincode"] = "digits:6|regex:/[0-9]{6}/";
            $rules["landmark"] = "regex:$regex";
            $rules["ownershipType"] = "int";
            $rules['ownerDetails.*.id']="required|digits";
            $rules['ownerDetails.*.businessOwnerName'] = "regex:$regex"; 
            $rules['ownerDetails.*.guardianName'] = "regex:$regex"; 
            $rules['ownerDetails.*.mobileNo'] = "digits:10|regex:/[0-9]{10}/"; 
            $rules['ownerDetails.*.email'] = "email"; 
            $validator = Validator::make($request->all(), $rules, $message);
            if ($validator->fails()) 
            {
                return responseMsg(false, $validator->errors(),$request->all());
            }

            return responseMsg(true,'',$request->all());
        }
        catch(Exception $e)
        {
            return responseMsg(false,$e->getMessage(),$request->all());
        }
    }
    public function getLicenceDtl($id)
    {
        try{
            
            $application = $this->getLicenceById($id);
            $item_name="";
            $cods = "";
            if($application->nature_of_bussiness)
            {
                $items = $this->getLicenceItemsById($application->nature_of_bussiness);                
                foreach($items as $val)
                {
                    $item_name .= $val->trade_item.",";
                    $cods .= $val->trade_code.",";                    
                }
                $item_name= trim($item_name,',');
                $cods= trim($cods,',');
            }
            $application->items = $item_name;
            $application->items_code = $cods;
            $owner_dtl = $this->getOwnereDtlByLId($id);
            // $time_line = $this->getTimelin($id);
            $documents = $this->getLicenceDocuments($id);
            $data['licenceDtl'] = $application;
            $data['owner_dtl'] = $owner_dtl;
            // $data['time_line'] = $time_line;
            $data['documents'] = $documents;
            $data = remove_null($data);
            return responseMsg(true,"",$data);
            dd($application);
            
        }
        catch(Exception $e)
        {
            return responseMsg(false,$e->getMessage(),'');
        }
    }
    public function getDenialDetails(Request $request)
    {
        $data = (array)null;
        if ($request->getMethod()== 'POST') 
        {
            try 
            {
                $noticeNo = $request->noticeNo;
                $firm_date = $request->firm_date; //firm establishment date

                $denialDetails = $this->getDenialFirmDetails(strtoupper(trim($noticeNo)), $firm_date);
                if ($denialDetails) 
                {

                    $now = Carbon::now()->format('Y-m-d'); // todays date
                    $notice_date = Carbon::parse($denialDetails->noticedate)->format('Y-m-d'); //notice date
                    $denialAmount = $this->getDenialAmountTrade($notice_date, $now);
                    $data['denialDetails'] = $denialDetails;
                    $data['denialAmount'] = $denialAmount;

                    return json_encode($data);
                } 
                else 
                {
                    $response = "noData";
                    return responseMsg(false,$response,$request->all());
                }
            } 
            catch (Exception $e) 
            {
                return responseMsg(false,$e->getMessage(),$request->all());
            }
        }
    }

    public function paybleAmount(Request $request)
    {
        try{
            $rules["applicationType"] = "required|string";
            $message["applicationType.required"] = "Application Type Required";

            $rules["areaSqft"] = "required|numeric";
            $message["areaSqft.required"] = "Area is Required";

            $rules["tocStatus"] = "required|bool";
            $message["tocStatus.required"] = "TocStatus is Required";

            $rules["firmEstdDate"] = "required|date";
            $message["firmEstdDate.required"] = "firmEstdDate is Required";

            $rules["licenseFor"] = "required|int";
            $message["licenseFor.required"] = "license For year is Required";

            $rules["natureOfBusiness"]="required|array";
            $rules["natureOfBusiness.*.id"]="required|int";

            $rules["noticeDate"] = "date";

            $validator = Validator::make($request->all(), $rules, $message);
            if ($validator->fails()) {
                return responseMsg(false, $validator->errors(),$request->all());
            }
            $data['application_type_id'] = Config::get("TradeConstant.APPLICATION-TYPE.".$request->applicationType);
            if(!$data['application_type_id'])
            {
                throw new Exception("Invalide Application Type");
            }
            $natureOfBussiness = array_map(function($val){
                return $val['id'];
            },$request->natureOfBusiness);
            $natureOfBussiness = implode(',', $natureOfBussiness);

            $data["areaSqft"] = $request->areaSqft;
            $data['curdate'] =Carbon::now()->format('Y-m-d');
            $data["firmEstdDate"] = $request->firmEstdDate;
            $data["tobacco_status"] =  $request->tocStatus;
            $data['noticeDate'] =  $request->noticeDate??null;
            $data["licenseFor"] = $request->licenseFor;
            $data["nature_of_business"] = $natureOfBussiness; 
            $data = $this->getcharge($data);
            if($data['response'])
                return responseMsg(true,"", $data);
            else
                throw new Exception("some Errors on Calculation");
        }
        catch(Exception $e)
        {
            return responseMsg(false,$e->getMessage(),$request->all());
        }        
    }
    public function getDenialFirmDetails($notice_no,$firm_date)
    {
        try{
            $data = TradeDenialConsumerDtl::select("trade_denial_notices.*",
                        DB::raw("trade_denial_notices.notice_no,
                                trade_denial_notices.created_on::date AS noticedate,
                                trade_denial_notices.id as dnialid")
                    )
                    ->join("trade_denial_notices","trade_denial_notices.denial_id","=","trade_denial_consumer_dtls.id")
                    ->where("trade_denial_notices.notice_no",$notice_no)
                    ->where("trade_denial_notices.created_on","<",$firm_date)
                    ->where("trade_denial_consumer_dtls.status","=", 5)
                    ->where("trade_denial_notices.status","=", 1)
                    ->first();
            return $data;
        }
        catch (Exception $e)
        {
            echo $e->getMessage();
        }
          
    }

    public function getcharge(array $args)
    {
        $response=['response' => false];
        try 
        {
            $data = array();
            $inputs = $args;
            $data['area_in_sqft'] = (float)$inputs['areaSqft'];
            $data['application_type_id'] = $inputs['application_type_id'];
            $data['firm_date'] = $inputs['firmEstdDate'];
            $data['firm_date'] = date('Y-m-d', strtotime($data['firm_date']));
           
            $data['tobacco_status'] = $inputs['tobacco_status'];
            $data['timeforlicense'] = $inputs['licenseFor'];
            $data['curdate'] = $inputs['curdate']??date("Y-m-d");
            
            $denial_amount_month = 0;
            $count = $this->getrate($data);
            $rate = $count->rate * $data['timeforlicense'];
            $notice_amount = 0;
            if(isset($inputs['noticeDate']) && $inputs['noticeDate'])
            {
                $notice_amount = $this->getDenialAmountTrade($inputs['noticeDate']);
            }
            $pre_app_amount = 0;
            if (isset($data['application_type_id']) && in_array($data['application_type_id'], [1, 2])) 
            {
                $nob = array();
                $data['nature_of_business'] = null;
                if (isset($inputs['nature_of_business']))
                    $nob = explode(',', $inputs['nature_of_business']);
                if (sizeof($nob) == 1) 
                {
                    $data['nature_of_business'] = $nob[0];
                }

                $temp = $data['firm_date'];
                $temp2 = $data['firm_date'];
                if ($data['nature_of_business'] == 198 && strtotime($temp) <= strtotime('2021-10-30')) 
                {
                    $temp = '2021-10-30';
                    $temp2 = $temp;
                } 
                elseif ($data['nature_of_business'] != 198 && strtotime($temp) <= strtotime('2020-01-01')) 
                {
                    $temp = '2020-01-01';
                }
                $data['firm_date'] = $temp;
                $diff_year = date_diff(date_create($temp2), date_create($data['curdate']))->format('%R%y');
                $pre_app_amount = ($diff_year > 0 ? $diff_year : 0) * $count->rate;
            }

            $vDiff = abs(strtotime($data['curdate']) - strtotime($data['firm_date'])); // here abs in case theres a mix in the dates
            $vMonths = ceil($vDiff / (30 * 60 * 60 * 24)); // number of seconds in a month of 30 days
            if(strtotime($data['firm_date']) >= strtotime($data['curdate']))
            { 
               $vMonths = round($vDiff / (30 * 60 * 60 * 24));
            }
            if ($vMonths > 0) 
            {
                $denial_amount_month = 100 + (($vMonths) * 20);
                # In case of ammendment no denial amount
                if ($data['application_type_id'] == 3)
                    $denial_amount_month = 0;
            }
            $total_denial_amount = $denial_amount_month + $rate + $pre_app_amount + $notice_amount ;

            # Check If Any cheque bounce charges
            if (isset($inputs['apply_licence_id'], $inputs['apply_licence_id'])) 
            {
                $penalty = $this->getChequeBouncePenalty($inputs['apply_licence_id']);
                $denial_amount_month += $penalty;
                $total_denial_amount += $penalty;
            }

            if ($count) 
            {
                $response = ['response' => true, 'rate' => $rate, 'penalty' => $denial_amount_month, 'total_charge' => $total_denial_amount, 'rate_id' => $count['id'], 'arear_amount' => $pre_app_amount,"notice_amount" =>$notice_amount];
            } 
            else 
            {
                $response = ['response' => false];
            }
            return $response;
        }
        catch(Exception $e)
        {
            echo $e->getLine();
            echo $e->getMessage();
            echo $e->getFile();
            return $response;
        }
    }
    public function validate_saf_no(Request $request)
    {
        $user = Auth()->user();
        $user_id = $user->id;
        $ulb_id = $user->ulb_id;
        if ($this->request->getMethod() == "post") 
        {
            $data = array();
            $inputs = $request->all();

            $saf_no = $inputs['saf_no']??null;

            $safdet = $this->getSafDtlBySafno($saf_no,$ulb_id);

            if($safdet['status'])
            {
                $response = ['response' => true,$safdet];

            }
            else 
            {
                $response = ['response' => false];
            }
        } 
        else 
        {
            $response = ['response' => false];
        }
        return json_encode($response);
    }

    public function validate_holding_no(Request $request)
    {
        $user = Auth()->user();
        $user_id = $user->id;
        $ulb_id = $user->ulb_id;
        if ($request->getMethod() == "POST") 
        {
            $data = array();
            $inputs = $request->all();

            $propdet = $this->propertyDetailsfortradebyHoldingNo($inputs['holdingNo'],$ulb_id);
            if($propdet['status'])
            {
                $response = ['status' => true,"data"=>["property"=>$propdet['property']],"message"=>""];

            }
            else
            {
                $response = ['status' => false,"data"=>'',"message"=>'No Property Found'];
            }
        } 
        else 
        {
            $response = ['status' => false,"data"=>'',"message"=>'Onlly Post Allowed'];
        }
        return responseMsg($response['status'],$response["message"],remove_null($response["data"]));
    }
    public function searchLicenceByNo(Request $request)
    {
        try{
            $rules["licenceNo"] = "required";
            $message["licenceNo.required"] = "Licence No Requird";
            
            $validator = Validator::make($request->all(), $rules, $message);
            if ($validator->fails()) {
                return responseMsg(false, $validator->errors(),$request->all());
            }
            $licence_no = $request->licenceNo;
            $data = ActiveLicence::select("*")
                    ->join(
                        DB::raw("(SELECT licence_id,
                                    string_agg(owner_name,',') as owner_name,
                                    string_agg(guardian_name,',') as guardian_name,
                                    string_agg(mobile,',') as mobile
                                    FROM active_licence_owners
                                    WHERE status =1
                                    GROUP BY licence_id
                                    ) owner
                                    "),
                                    function ($join) {
                                        $join->on("owner.licence_id","=",  "active_licences.id");
                                    }
                                    )
                    ->where('status',1)
                    ->where('license_no',$licence_no)
                    ->first();
            return responseMsg(true,"",remove_null($data));
        }
        catch(Exception $e)
        {
            return responseMsg(false,$e->getMessage(),$request->all());
        }
        
    }
    // public function inbox($key)
    // {
    //     try {

    //         $user_id = auth()->user()->id;
    //         $redis = Redis::connection();  // Redis Connection
    //         $redis_data = json_decode(Redis::get('user:' . $user_id), true);
    //         $ulb_id = $redis_data['ulb_id'] ?? auth()->user()->ulb_id;;
    //         $roll_id = $redis_data['role_id'] ?? ($this->getUserRoll($user_id)->role_id ?? -1);
    //         $workflow_id = Config::get('workflow-constants.SAF_WORKFLOW_ID');
    //         $work_flow_candidate = $this->work_flow_candidate($user_id, $ulb_id);
    //         if (!$work_flow_candidate || $roll_id == -1) 
    //         {
    //             throw new Exception("Your Are Not Authoried");
    //         }
    //         $work_flow_candidate = collect($work_flow_candidate);
    //         $ward_permission = $this->WardPermission($user_id);
    //         $ward_ids = array_map(function ($val) {
    //             return $val['ulb_ward_id'];
    //         }, $ward_permission);
    //         $data = ActiveSafDetail::select(
    //             DB::raw("owner_name,
    //                                guardian_name ,
    //                                mobile_no,
    //                                assessment_type as assessment_type,
    //                                property_type as property_type,
    //                                 ulb_ward_masters.ward_name as ward_no,
    //                                 active_saf_details.created_at::date as apply_date,
    //                                 active_saf_details.id"),

    //             "active_saf_details.saf_no"
    //         )
    //             ->join('ulb_ward_masters', function ($join) {
    //                 $join->on("ulb_ward_masters.id", "=", "active_saf_details.ward_mstr_id");
    //             })
    //             ->join('prop_param_property_types', function ($join) {
    //                 $join->on("prop_param_property_types.id", "=", "active_saf_details.prop_type_mstr_id")
    //                     ->where("prop_param_property_types.status", 1);
    //             })
    //             ->join('prop_param_ownership_types', function ($join) {
    //                 $join->on("prop_param_ownership_types.id", "=", "active_saf_details.ownership_type_mstr_id")
    //                     ->where("prop_param_ownership_types.status", 1);
    //             })
    //             ->leftJoin(
    //                 DB::raw("(SELECT active_saf_owner_details.saf_dtl_id,
    //                                                string_agg(active_saf_owner_details.owner_name,', ') as owner_name,
    //                                                string_agg(active_saf_owner_details.guardian_name,', ') as guardian_name,
    //                                                string_agg(active_saf_owner_details.mobile_no::text,', ') as mobile_no
    //                                           FROM active_saf_owner_details 
    //                                           WHERE active_saf_owner_details.status = 1
    //                                           GROUP BY active_saf_owner_details.saf_dtl_id
    //                                           )active_saf_owner_details
    //                                            "),
    //                 function ($join) {
    //                     $join->on("active_saf_owner_details.saf_dtl_id", "=", "active_saf_details.id");
    //                 }
    //             )
    //             ->where(
    //                 function ($query) use ($roll_id) {
    //                     return $query
    //                         ->where('active_saf_details.current_user', '<>', $roll_id)
    //                         ->orwhereNull('active_saf_details.current_user');
    //                 }
    //             )
    //             ->where("active_saf_details.status", 1)
    //             ->where("active_saf_details.ulb_id", $ulb_id)
    //             ->whereIn('active_saf_details.ward_mstr_id', $ward_ids);
    //         if ($key) {
    //             $data = $data->where(function ($query) use ($key) {
    //                 $query->orwhere('active_saf_details.holding_no', 'ILIKE', '%' . $key . '%')
    //                     ->orwhere('active_saf_details.saf_no', 'ILIKE', '%' . $key . '%')
    //                     ->orwhere('active_saf_owner_details.owner_name', 'ILIKE', '%' . $key . '%')
    //                     ->orwhere('active_saf_owner_details.guardian_name', 'ILIKE', '%' . $key . '%')
    //                     ->orwhere('active_saf_owner_details.mobile_no', 'ILIKE', '%' . $key . '%');
    //             });
    //         }
    //         $saf = $data->get();
    //         $data = remove_null([
    //             'ulb_id' => $ulb_id,
    //             'user_id' => $user_id,
    //             'roll_id' => $roll_id,
    //             'workflow_id' => $workflow_id,
    //             'work_flow_candidate_id' => $work_flow_candidate['id'],
    //             'module_id' => $work_flow_candidate['module_id'],
    //             "data_list" => $saf,
    //         ], true, ['ulb_id', 'user_id', 'roll_id', 'workflow_id', 'module_id', 'id']);

    //         return responseMsg(true, '', $data);
    //     } catch (Exception $e) {
    //         return responseMsg(false, $e->getMessage(), $key);
    //     }
    // }

    #---------- core function for trade Application--------

    
    function getDenialAmountTrade($notice_date=null,$current_date=null)
    {
        $notice_date=$notice_date?Carbon::createFromFormat("Y-m-d",$notice_date)->format("Y-m-d"):Carbon::now()->format('Y-m-d');
        $current_date=$current_date?Carbon::createFromFormat("Y-m-d",$current_date)->format("Y-m-d"):Carbon::now()->format('Y-m-d');
        
        $datediff = strtotime($current_date)-strtotime($notice_date); //days difference in second
        $totalDays =   abs(ceil($datediff / (60 * 60 * 24))); // total no. of days
        $denialAmount=100+(($totalDays)*10);
    
        return $denialAmount;
    }    
    public function getCotegoryList()
    {
        try{
            return TradeParamCategoryType::select("id","category_type")
                ->where("status",1)
                ->get();
        }
        catch (Exception $e)
        {
            echo $e->getMessage();
        }
    }
    public function getFirmTypeList()
    {
        try{
            return TradeParamFirmType::select("id","firm_type")
                ->where("status",1)
                ->get();
        }
        catch (Exception $e)
        {
            echo $e->getMessage();
        }
    }
    public function getownershipTypeList()
    {
        try{
            return TradeParamOwnershipType::select("id","ownership_type")
                ->where("status",1)
                ->get();
        }
        catch (Exception $e)
        {
            echo $e->getMessage();
        }
    }
    public function gettradeitemsList()
    {
        try{
            return TradeParamItemType::select("id","trade_item","trade_code")
                ->where("status",1)
                ->get();
        }
        catch (Exception $e)
        {
            echo $e->getMessage();
        }
    }
    public function getAllApplicationType()
    {
        try
        {
            $data = TradeParamApplicationType::select("id","application_type")
            ->where('status','1')
            ->get();
            return $data;

        }
        catch (Exception $e)
        {
            echo $e->getMessage();
        }
    }
    public function getrate(array $input):object //stdcl object array
    {
        try{
            $builder = TradeParamLicenceRate::select('id','rate')
                    ->where('application_type_id', $input['application_type_id'])
                    ->where('range_from','<=', $input['area_in_sqft'])
                    ->where('range_to','>=', $input['area_in_sqft'])      
                    ->where('effective_date','<', $input['curdate'])        
                    ->where('status', 1)
                    ->where('tobacco_status', $input['tobacco_status'])
                    ->orderBy('effective_date','Desc')
                    ->first();
                    // dd($builder);
            return $builder;
        }
        catch(Exception $e)
        {
            echo $e->getMessage();
        }

    }
    public function getChequeBouncePenalty(int $apply_licence_id):float
    {
        try{

            $result = TradeBankRecancilation::select(DB::raw("coalesce(sum(amount), 0) as penalty"))
                        ->where("related_id",$apply_licence_id)
                        ->where("status",3)
                        ->first();
            return $result->penalty;
        }
        catch(Exception $e)
        {
            echo $e->getMessage();
        }
    }
    public function transfareExpire(int $licenceId):int
    {
        try{
            $expireLicence = new ExpireLicence();
            $licence = ActiveLicence::find($licenceId);
            if($licence->id)
            {
                $expireLicence->id                      = $licence->id;
                $expireLicence->application_no          = $licence->application_no;
                $expireLicence->provisional_license_no  = $licence->provisional_license_no;
                $expireLicence->license_no              = $licence->license_no;
                $expireLicence->firm_type_id            = $licence->firm_type_id;
                $expireLicence->otherfirmtype           = $licence->otherfirmtype;
                $expireLicence->application_type_id     = $licence->application_type_id;
                $expireLicence->category_type_id        = $licence->category_type_id;
                $expireLicence->ownership_type_id       = $licence->ownership_type_id;
                $expireLicence->ward_mstr_id            = $licence->ward_mstr_id;
                $expireLicence->new_ward_mstr_id        = $licence->new_ward_mstr_id;
                $expireLicence->ulb_id                  = $licence->ulb_id;
                $expireLicence->prop_dtl_id             = $licence->prop_dtl_id;
                $expireLicence->holding_no              = $licence->holding_no;
                $expireLicence->nature_of_bussiness     = $licence->nature_of_bussiness;
                $expireLicence->firm_name               = $licence->firm_name;
                $expireLicence->premises_owner_name     = $licence->premises_owner_name;
                $expireLicence->brife_desp_firm         = $licence->brife_desp_firm;
                $expireLicence->area_in_sqft            = $licence->area_in_sqft;
                $expireLicence->k_no                    = $licence->k_no;
                $expireLicence->bind_book_no            = $licence->bind_book_no;
                $expireLicence->account_no              = $licence->account_no;
                $expireLicence->pan_no                  = $licence->pan_no;
                $expireLicence->tin_no                  = $licence->tin_no;
                $expireLicence->salestax_no             = $licence->salestax_no;
                $expireLicence->doc_verify_date         = $licence->doc_verify_date;
                $expireLicence->doc_verify_emp_details_id=$licence->doc_verify_emp_details_id;
                $expireLicence->emp_details_id          = $licence->emp_details_id;
                $expireLicence->establishment_date      = $licence->establishment_date;
                $expireLicence->apply_date              = $licence->apply_date;
                $expireLicence->fy_mstr_id              = $licence->fy_mstr_id;
                $expireLicence->created_on              = $licence->created_on;
                $expireLicence->license_date            = $licence->license_date;
                $expireLicence->valid_from              = $licence->valid_from;
                $expireLicence->valid_upto              = $licence->valid_upto;
                $expireLicence->licence_for_years       = $licence->licence_for_years;
                $expireLicence->payment_status          = $licence->payment_status;
                $expireLicence->document_upload_status  = $licence->document_upload_status;
                $expireLicence->pending_status          = $licence->pending_status;
                $expireLicence->status                  = $licence->status;
                $expireLicence->rate_id                 = $licence->rate_id;
                $expireLicence->address                 = $licence->address;
                $expireLicence->landmark                = $licence->landmark;
                $expireLicence->pin_code                = $licence->pin_code;
                $expireLicence->street_name             = $licence->street_name;
                $expireLicence->property_type           = $licence->property_type;
                $expireLicence->update_status           = $licence->update_status;
                $expireLicence->turnover                = $licence->turnover;
                $expireLicence->tobacco_status          = $licence->tobacco_status;
                $expireLicence->apply_from              = $licence->apply_from;
                $expireLicence->current_user_id         = $licence->current_user_id;
                $expireLicence->initiator_id            = $licence->initiator_id;
                $expireLicence->finisher_id             = $licence->finisher_id;
                $expireLicence->workflow_id             = $licence->workflow_id ;
                $expireLicence->updated_at              = $licence->updated_at ;
                $expireLicence->created_at              = $licence->created_at ;
                $expireLicence->deleted_at              = $licence->deleted_at ;

                $expireLicence->save();
                $expireLicenceId = $expireLicence->id;

                $sql = "Insert Into expire_licence_owners
                        (
                            id,licence_id,owner_name,guardian_name,address,mobile,city,district,state,
                            document_id,id_no,emailid,emp_details_id,created_on,status,updated_at,
                            created_at,deleted_at
                        )
                        select id, $expireLicenceId,owner_name,guardian_name,address,mobile,city,district,state,
                            document_id,id_no,emailid,emp_details_id,created_on,status,updated_at,
                            created_at,deleted_at 
                        from active_licence_owners where licence_id = $licenceId order by id ";
                DB::insert($sql);
                $licence->delet();
                ActiveLicenceOwner::where('licence_id',$licenceId)->delet();
               return $expireLicenceId; 
            }
        }
        catch(Exception $e)
        {
            echo $e->getMessage();
        }
    }
    public function applyFrom():string
    {
        $user = Auth()->user();
        $user_id = $user->id;
        $ulb_id = $user->ulb_id;
        $redis = new Redis;
        $user_data = json_decode($this->redis::get('user:' . $user_id), true);
        $roll_id =  $this->user_data['role_id']??($this->getUserRoll($user_id,'Trade','Trade')->role_id??-1);
        if($roll_id != -1)
        {
            return "Emp";
        }
        else
            return "Online";
    }    
    public function propertyDetailsfortradebyHoldingNo(string $holdingNo,int $ulb_id):array
    {
        $property = PropPropertie::select("*")
                    ->leftjoin(DB::raw("(SELECT STRING_AGG(owner_name,',') as owner_name ,property_id
                                        FROM Prop_OwnerS 
                                        WHERE status = 1
                                        GROUP BY property_id
                                        ) owners
                                        "),function($join)
                                        {
                                            $join->on("owners.property_id","=","prop_properties.id");
                                        }
                                        )
                        ->where("status",1)
                        ->where("new_holding_no","<>","")
                        ->where("new_holding_no",$holdingNo)
                        ->where("ulb_id",$ulb_id)
                        ->first();
        if($property)
        {
            return ["status"=>true,'property'=>adjToArray($property)];

        }
        return ["status"=>false,'property'=>''];
    }
    public function getSafDtlBySafno(string $safNo,int $ulb_id):array
    {
        $saf = ActiveSafDetail::select("*")
                ->where('status',1)
                ->where('saf_no',$safNo)
                ->where('ulb_id',$ulb_id)
                ->first();
        if($saf->id)
        {
            $owneres = ActiveSafOwnerDetail::select("*")
                        ->where("saf_dtl_id",$saf->id)
                        ->where('status',1)
                        ->get();
            return ["status"=>true,'saf'=>adjToArray($saf),'owneres'=>adjToArray($owneres)];

        }
        return ["status"=>false,'property'=>'','owneres'=>''];
    }
    public function updateStatusFine($denial_id,$denialAmount,$applyid)
    {
        $tradeNotice = TradeDenialNotice::where("denial_id",$denial_id)->find();
        $tradeNotice->apply_id =  $applyid;
        $tradeNotice->fine_amount =  $denialAmount;
        $tradeNotice->status =  2;
        $tradeNotice->update();
    }
    public function getLicenceById($id)
    {
        try{
            $application = ActiveLicence::select("active_licences.*","trade_param_application_types.application_type",
                            "trade_param_category_types.category_type","trade_param_firm_types.firm_type",
                    DB::raw("ulb_ward_masters.ward_name AS ward_no")
                    )
                ->join("ulb_ward_masters",function($join){
                    $join->on("ulb_ward_masters.id","=","active_licences.ward_mstr_id");                                
                })
                ->join("trade_param_application_types","trade_param_application_types.id","active_licences.application_type_id")
                ->leftjoin("trade_param_category_types","trade_param_category_types.id","active_licences.category_type_id")
                ->leftjoin("trade_param_firm_types","trade_param_firm_types.id","active_licences.firm_type_id")                
                ->where('active_licences.id',$id)   
                ->first();
            return $application;
        }
        catch(Exception $e)
        {
            echo $e->getMessage();
        }
        
    }
    public function getLicenceItemsById($id)
    {
        try{
            $id = explode(",",$id);
            $items = TradeParamItemType::select("*")
                ->whereIn("id",$id)
                ->get();
            return $items;
        }
        catch(Exception $e)
        {
            echo $e->getMessage();
        }        
    }
    public function getOwnereDtlByLId($id)
    {
        try{
            $ownerDtl   = ActiveLicenceOwner::select("*")
                            ->where("licence_id",$id)
                            ->get();
            return $ownerDtl;
        }
        catch(Exception $e)
        {
            echo $e->getMessage();
        }
        
    }
    public function getTimelin($id)
    {
        try{
           
            $time_line =  WorkflowTrack::select(
                        "workflow_tracks.message",
                        "role_masters.role_name",
                        DB::raw("workflow_tracks.track_date::date as track_date")
                    )
                    ->leftjoin('users', "users.id", "workflow_tracks.citizen_id")
                    ->leftjoin('role_users', 'role_users.user_id', 'users.id')
                    ->leftjoin('role_masters', 'role_masters.id', 'role_users.role_id')
                    ->where('ref_table_dot_id', 'TradeLicence')
                    ->where('ref_table_id_value', $id)                    
                    ->orderBy('track_date', 'desc')
                    ->get();
            return $time_line;
        }
        catch(Exception $e)
        {
            echo $e->getMessage();
        }
    }
    public function getLicenceDocuments($id)
    {
        try{
           
            $time_line =  TradeApplicationDoc::select(
                        "trade_application_docs.doc_for",
                        "trade_application_docs.document_path",
                        "trade_application_docs.remarks",
                        "trade_application_docs.verify_status"
                    )
                    ->where('trade_application_docs.licence_id', $id)
                    ->where('trade_application_docs.status', 1)                    
                    ->orderBy('id', 'desc')
                    ->get();
            return $time_line;
        }
        catch(Exception $e)
        {
            echo $e->getMessage();
        }
    }
    public function searchLicence(string $licence_no)
    {
        try{
            $data = ActiveLicence::select("*")
                    ->join(
                        DB::raw("(SELECT licence_id,
                                    string_agg(owner_name,',') as owner_name,
                                    string_agg(guardian_name,',') as guardian_name,
                                    string_agg(mobile,',') as mobile
                                    FROM active_licence_owners
                                    WHERE status =1
                                    GROUP BY licence_id
                                    ) owner
                                    "),
                                    function ($join) {
                                        $join->on("owner.licence_id","=",  "active_licences.id");
                                    }
                                    )
                    ->where('status',1)
                    ->where('license_no',$licence_no)
                    ->first();
            return responseMsg(true,"",remove_null($data));
        }
        catch(Exception $e)
        {
            return responseMsg(false,$e->getMessage(),$licence_no);
        }
        
    }
    #-------------------- End core function of core function --------------
    
}