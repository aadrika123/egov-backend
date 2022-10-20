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
use App\Models\Trade\TradeLevelPending;
use App\Models\Trade\TradeParamApplicationType;
use App\Models\Trade\TradeParamCategoryType;
use App\Models\Trade\TradeParamFirmType;
use App\Models\Trade\TradeParamItemType;
use App\Models\Trade\TradeParamLicenceRate;
use App\Models\Trade\TradeParamOwnershipType;
use App\Models\Trade\TradeTransaction;
use App\Models\UlbMaster;
use App\Models\UlbWardMaster;
use App\Models\UlbWorkflowMaster;
use App\Models\WfWorkflow;
use App\Models\WorkflowTrack;
use App\Repository\Common\CommonFunction;
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
    protected $parent;

    public function __construct()
    { 
        $this->ModelWard = new ModelWard();
        $this->parent = new CommonFunction();
    }
    public function applyApplication(Request $request)
    {           
        $denialAmount = 0; 
        $user = Auth()->user();
        $this->user_id = $user->id;
        $this->ulb_id = $user->ulb_id;
        
        $this->redis = new Redis;
        $this->user_data = json_decode($this->redis::get('user:' . $this->user_id), true);
        $apply_from = $this->applyFrom();  dd( $apply_from);      
        try
        {
            $this->application_type_id = Config::get("TradeConstant.APPLICATION-TYPE.".$request->applicationType);            
            if(!$this->application_type_id)
            {
                throw new Exception("Invalide Application Type");
            }
            $workflow_id = Config::get('workflow-constants.TRADE_WORKFLOW_ID');
            $workflows = $this->parent->iniatorFinisher($this->user_id,$this->ulb_id,$workflow_id);   
            $this->roll_id =  $this->user_data['role_id']??($this->parent->getUserRoll($this->user_id, $this->ulb_id,$workflow_id)->role_id??-1);        
            if (!$workflows) 
            {
                return responseMsg(false, "Workflow Not Available", $request->all());
            }
            elseif(!$workflows['initiator'])
            {
                return responseMsg(false, "Initiator Not Available", $request->all()); 
            }
            elseif(!$workflows['finisher'])
            {
                return responseMsg(false, "Finisher Not Available", $request->all()); 
            }
           
            $data = array() ;
            $rules = [];
            $message = [];
            if (in_array($this->application_type_id, ["2", "3","4"])) 
            {
                $rules["licenceId"] = "required";
                $message["licenceId.required"] = "Old Licence Id Requird";
            }
            $validator = Validator::make($request->all(), $rules, $message);
            if ($validator->fails()) {
                return responseMsg(false, $validator->errors(),$request->all());
            }
            if(strtoupper($apply_from)=="ONLINE")
            {
                $data['wardList'] = $this->ModelWard->getAllWard($this->ulb_id)->map(function($val){
                    $val->ward_no = $val->ward_name;
                    return $val;
                });
                $data['wardList'] = adjToArray($data['wardList']);
            }
            else
            {
                $data['wardList'] = $this->parent->WardPermission($this->user_id);
            }
            if($request->getMethod()=='GET')
            {
                $data['apply_from'] =$apply_from;
                $data["firmTypeList"] = $this->getFirmTypeList();
                $data["ownershipTypeList"] = $this->getownershipTypeList();
                $data["categoryTypeList"] = $this->getCotegoryList();
                $data["natureOfBusiness"] = $this->gettradeitemsList(true);
                if(isset($request->licenceId) && $request->licenceId  && $this->application_type_id !=1)
                {
                    $oldLicece = $this->getLicenceById($request->licenceId);
                    if(!$oldLicece)
                    {
                        throw new Exception("No Priviuse Licence Found");
                    }
                    $oldOwneres =$this->getOwnereDtlByLId($request->licenceId);
                    $data["licenceDtl"] =  $oldLicece;
                    $data["ownerDtl"] = $oldOwneres;
                }
                return responseMsg(true,"",remove_null($data));
            }
            elseif($request->getMethod()=="POST")
            { 
                $nowdate = Carbon::now()->format('Y-m-d'); 
                $timstamp = Carbon::now()->format('Y-m-d H:i:s');                
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
                if(strtoupper($apply_from)=="ONLINE")
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
                if(isset($request->firmDetails["tocStatus"]) && $request->firmDetails["tocStatus"])
                {
                    $rules["licenseDetails.licenseFor"]="required|int|max:1";
                }
                if(in_array(strtoupper($apply_from),["JSK","UTC","TC","SUPER ADMIN","TL"]))
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
                
                $ward_no = array_filter($data['wardList'], function ($val) use($wardId ){
                    return $val['id'] == $wardId ;
                });
                $ward_no = array_values($ward_no)[0]['ward_no'];
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
                    if(!$oldLicence)
                    {
                        throw new Exception("Old Licence Not Found");
                    }
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
                    // $licence->current_user_id     = null;
                    $licence->initiator_id        = $workflows['initiator']['id'];
                    $licence->finisher_id         = $workflows['finisher']['id'];
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
                    // $licence->current_user_id     = null;
                    $licence->initiator_id        = $workflows['initiator']['id'];
                    $licence->finisher_id         = $workflows['finisher']['id'];
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
                $notice_date = null;
                $noticeDetails = null;
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
    
                    }
                }              
                if(in_array(strtoupper($apply_from),["JSK","UTC","TC","SUPER ADMIN","TL"]) && $this->application_type_id!=4)
                {
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
                    if($noticeDetails)
                    {
                        $this->updateStatusFine($denialId, $rate_data['notice_amount'], $licenceId); //update status and fineAmount
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
                    if($payment_status==1)
                    {
                        $licence->current_user_id = $workflows['initiator']['id'];
                    }
                    $ulbDtl = UlbMaster::find($this->ulb_id);
                    $ulb_name = explode(' ',$ulbDtl->ulb_name);
                    $short_ulb_name = "";
                    foreach($ulb_name as $val)
                    {
                        $short_ulb_name.=$val[0];
                    }
                    
                    $prov_no = $short_ulb_name . $ward_no . date('mdy') . $licenceId;
                    $licence->provisional_license_no = $prov_no;
                    $licence->payment_status = $payment_status;
                    $licence->save();
                    $res['transactionId'] = $transaction_id;
                    $res['paymentRecipt']= config('app.url')."/api/trade/paymentRecipt/".$licenceId."/".$transaction_id;
                    
                    
                }
                elseif($noticeDetails)
                {
                    $this->updateStatusFine($denialId, 0, $licenceId,1); //update status and fineAmount                     
                }
                if($this->application_type_id==4)
                {
                    $prov_no = $short_ulb_name . $ward_no . date('mdy') . $licenceId;
                    $licence->provisional_license_no = $prov_no;
                    $licence->payment_status = 1;
                    $licence->save();
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
    public function procidToPaymentCounter(Request $request)
    {
        $user = Auth()->user();
        $user_id = $user->id;
        $ulb_id = $user->ulb_id; 
        $workflow_id = Config::get('workflow-constants.TRADE_WORKFLOW_ID');  
        $workflow_id = Config::get('workflow-constants.TRADE_WORKFLOW_ID');
        $workflows = $this->parent->iniatorFinisher($user_id,$ulb_id,$workflow_id);    
        $user_data = $this->parent->getUserRoll($user_id, $ulb_id,$workflow_id);
        $apply_from = $this->applyFrom();
        $regex = '/^[a-zA-Z1-9][a-zA-Z1-9\.\s]+$/';
        $nowdate = Carbon::now()->format('Y-m-d'); 
        $timstamp = Carbon::now()->format('Y-m-d H:i:s');
        try{
            DB::beginTransaction();   
            if(in_array(strtoupper($apply_from),["JSK","UTC","TC","SUPER ADMIN","TL","BO"]))
            {
                $rules["paymentMode"]="required|alpha"; 
                $rules["licenceId"]="required|int"; 
                $rules["licenseFor"]="required|int";                
                if(isset($request->paymentMode) && $request->paymentMode!="CASH")
                {
                    $rules["chaqueNo"] ="required";
                    $rules["chequeDate"] ="required|date|date_format:Y-m-d|after_or_equal:$nowdate";
                    $rules["bankName"] ="required|regex:$regex";
                    $rules["branchName"] ="required|regex:$regex";
                } 
                $validator = Validator::make($request->all(), $rules);
                if ($validator->fails()) 
                {
                    return responseMsg(false, $validator->errors(),$request->all());
                }
                $lecence_data = ActiveLicence::find($request->licenceId);
                $licenceId = $request->licenceId;
                if(!$lecence_data)
                {
                    throw new Exception("Licence Data Not Found !!!!!");
                }
                elseif($lecence_data->application_type_id==4)
                {
                    throw new Exception("Surender Application Not Pay Anny Amount");
                }
                elseif($lecence_data->payment_status==1)
                {
                    throw new Exception("Payment Already Done Of This Application");
                }
                if($lecence_data->tobacco_status==1 && $request->licenseFor >1)
                {
                    throw new Exception("Tobaco Application Not Take Licence More Than One Year");
                }
                $notice_date = null;
                $noticeDetails=null;
                if($lecence_data->applyWith==1)
                { 
                    $noticeDetails = $this->getNotisDtl($lecence_data->id);
                    if ($noticeDetails) 
                    {   
                        $denialId = $noticeDetails->id;
                        $now = strtotime(date('Y-m-d H:i:s')); // todays date
                        $notice_date = strtotime($noticeDetails['created_on']); //notice date 
                    }
                }
                # Calculating rate
                {
                    
                    $args['areaSqft']            = (float)$lecence_data->area_in_sqft;
                    $args['application_type_id'] = $lecence_data->application_type_id;
                    if($lecence_data->application_type_id==1)
                    {
                        $args['firmEstdDate'] = $lecence_data->establishment_date;
                    }
                    else
                    {
                        $args['firmEstdDate'] = !empty(trim($lecence_data->valid_from))?$lecence_data->valid_from:$lecence_data->apply_date;
                    }
                    $args['tobacco_status']      = $lecence_data->tobacco_status;
                    $args['licenseFor']          =  $request->licenseFor ;
                    $args['nature_of_business']  = $lecence_data->nature_of_bussiness;
                    $args['noticeDate']          = $notice_date;
                    $rate_data = $this->getcharge($args);
                }
                $transaction_type = Config::get('TradeConstant.APPLICATION-TYPE-BY-ID.'.$lecence_data->application_type_id);  
                
                $totalCharge = $rate_data['total_charge'] ;
                $denialAmount = $rate_data['notice_amount'];
                $Tradetransaction = new TradeTransaction ;
                $Tradetransaction->related_id = $licenceId;
                $Tradetransaction->ward_mstr_id = $lecence_data->ward_mstr_id;
                $Tradetransaction->transaction_type = $transaction_type;
                $Tradetransaction->transaction_date = $nowdate;
                $Tradetransaction->payment_mode = $request->paymentMode;
                $Tradetransaction->paid_amount = $totalCharge;

                $Tradetransaction->penalty = $rate_data['penalty'] + $denialAmount + $rate_data['arear_amount'];
                if ($request->paymentMode != 'CASH') 
                {
                    $Tradetransaction->status = 2;
                }
                $Tradetransaction->emp_details_id = $user_id;
                $Tradetransaction->created_on = $timstamp;
                $Tradetransaction->ip_address = '';
                $Tradetransaction->ulb_id = $ulb_id;
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
                if ($request->paymentMode != 'CASH') 
                {
                    $tradeChq = new TradeChequeDtl;
                    $tradeChq->transaction_id = $transaction_id;
                    $tradeChq->cheque_no = $request->chaqueNo;
                    $tradeChq->cheque_date = $request->chequeDate;
                    $tradeChq->bank_name = $request->bankName;
                    $tradeChq->branch_name = $request->branchName;
                    $tradeChq->emp_details_id = $user_id;
                    $tradeChq->created_on = $timstamp;
                    $payment_status = 2;
                    $tradeChq->save();
                } 
                if($payment_status==1 && $lecence_data->document_upload_status =1 && $lecence_data->pending_status=0)
                {
                    $lecence_data->current_user_id = $workflows['initiator']['id'];
                }
                $ulbDtl = UlbMaster::find($ulb_id);
                $ulb_name = explode(' ',$ulbDtl->ulb_name);
                $short_ulb_name = "";
                foreach($ulb_name as $val)
                {
                    $short_ulb_name.=$val[0];
                }
                $ward_no = UlbWardMaster::select("ward_name")
                            ->where("id",$lecence_data->ward_mstr_id)
                            ->first();
                $ward_no = $ward_no['ward_name'];
                $prov_no = $short_ulb_name . $ward_no . date('mdy') . $licenceId;
                $lecence_data->provisional_license_no = $prov_no;
                $lecence_data->payment_status = $payment_status;
                $lecence_data->save();
                $res['transactionId'] = $transaction_id;
                // $res['paymentRecipt']= config('app.url')."/api/trade/paymentRecipt/".$licenceId."/".$transaction_id;
                $res['paymentRecipt']= "http://192.168.0.16:8000"."/api/trade/paymentRecipt/".$licenceId."/".$transaction_id;
                if($noticeDetails)
                {
                    $this->updateStatusFine($denialId, $rate_data['notice_amount'], $licenceId,1); //update status and fineAmount                     
                }
                DB::commit();
                return responseMsg(true,"",$res);
            }
            else
            {
                DB::rollBack();
                throw new Exception("You Are Not Authorized For Payment Cut");
            }
        }
        catch(Exception $e)
        {
            DB::rollBack();
            return responseMsg(false,$e->getMessage(),$request->all());
        }
    }
    public function paymentRecipt($id, $transectionId)
    { 
        try{
            $application = ActiveLicence::select("application_no","provisional_license_no","license_no",
                                                "firm_name","holding_no","address",
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
                                        "firm_name","holding_no","address",
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
            $transaction = TradeTransaction::select("transaction_no","transaction_type","transaction_date",
                                        "payment_mode","paid_amount","penalty",
                                        "trade_cheque_dtls.cheque_no","trade_cheque_dtls.cheque_date",
                                        "trade_cheque_dtls.bank_name","trade_cheque_dtls.branch_name"
                                    )
                            ->leftjoin("trade_cheque_dtls","trade_cheque_dtls.transaction_id","trade_transactions.id")
                            ->where("trade_transactions.id",$transectionId)
                            ->whereIn("trade_transactions.status",[1,2])
                            ->first();
            if(!$transaction)
            {
                throw New Exception("Transaction Not Faound");
            }
            $penalty = TradeFineRebetDetail::select("head_name","amount")
                        ->where('transaction_id',$transectionId)
                        ->where("status",1)
                        ->orderBy("id")
                        ->get();
            $pen=0;
            foreach($penalty as $val )
            {
                $pen+=$val->amount;
                
            }
            $transaction->rate = $transaction->paid_amount - $pen;
            $data = ["application"=>$application,
                     "transaction"=>$transaction,
                     "penalty"    =>$penalty
            ];
            $data['paymentRecipt']= config('app.url')."/api/trade/paymentRecipt/".$id."/".$transectionId;
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
    public function getNotisDtl($licence_id)
    {
        try{
            $data = TradeDenialNotice::select("*",
                    DB::raw("trade_denial_notices.created_on::date AS noticedate")
                    )
                    ->where("apply_id",$licence_id)
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
    public function searchLicenceByNo(Request $request)// reniwal/surrend/amendment
    {
        try{
            $user = Auth()->user();
            $user_id = $user->id;
            $ulb_id = $user->ulb_id;
            $nextMonth = Carbon::now()->addMonths(1)->format('Y-m-d');            
            $rules["licenceNo"] = "required";
            $message["licenceNo.required"] = "Licence No Requird";
            
            $validator = Validator::make($request->all(), $rules, $message);
            if ($validator->fails()) {
                return responseMsg(false, $validator->errors(),$request->all());
            }
            $licence_no = $request->licenceNo;
            $data = ActiveLicence::select("active_licences.*","owner.*",
                                    DB::raw("ulb_ward_masters.ward_name as ward_no")
                                    )
                    ->join("ulb_ward_masters","ulb_ward_masters.id","=","active_licences.ward_mstr_id")
                    ->leftjoin(
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
                    ->where('active_licences.status',1)
                    ->where('active_licences.license_no',$licence_no)
                    ->where("active_licences.ulb_id",$ulb_id)
                    ->where('active_licences.pending_status',5)
                    ->first();
           if(!$data)
           {
            throw new Exception("Data Not Faund");
           }
           elseif($data->valid_upto > $nextMonth)
           {
            throw new Exception("Licence Valice Upto ".$data->valid_upto);
           }            
           return responseMsg(true,"",remove_null($data));
        }
        catch(Exception $e)
        {
            return responseMsg(false,$e->getMessage(),$request->all());
        }
        
    }
    public function inbox(Request $request)
    {
        try {
            $user = Auth()->user();
            $user_id = $user->id;
            $ulb_id = $user->ulb_id;
            $refWorkflowId = Config::get('workflow-constants.TRADE_WORKFLOW_ID');
            $workflowId = WfWorkflow::where('wf_master_id', $refWorkflowId)
                ->where('ulb_id', $ulb_id)
                ->first();
            if (!$workflowId) 
            {
                throw new Exception("Workflow Not Available");
            }
            $role = $this->parent->getUserRoll($user_id,$ulb_id,$workflowId->wf_master_id); 
            if (!$role) 
            {
                throw new Exception("You Are Not Authorized");
            } 
            if($role->is_initiator)
            {
                $joins = "leftjoin";
            }
            else
            {
                $joins = "join";
            }  
            $role_id = $role->id;         
            $ward_permission = $this->parent->WardPermission($user_id);
            $ward_ids = array_map(function ($val) {
                return $val['ulb_ward_id'];
            }, $ward_permission);
            $inputs = $request->all();  
            // DB::enableQueryLog();          
            $licence = ActiveLicence::select("active_licences.id",
                                            "active_licences.application_no",
                                            "active_licences.provisional_license_no",
                                            "active_licences.license_no",
                                            "active_licences.apply_date",
                                            "active_licences.apply_from",
                                            "owner.owner_name",
                                            "owner.guardian_name",
                                            "owner.mobile_no",
                                            "owner.email_id",
                                            DB::raw("trade_level_pendings.id AS level_id")
                                            )
                        ->$joins("trade_level_pendings",function($join) use($role_id){
                            $join->on("trade_level_pendings.licence_id","active_licences.id")
                            ->where("trade_level_pendings.receiver_user_type_id",$role_id)
                            ->where("trade_level_pendings.status",1)
                            ->where("trade_level_pendings.status",0);
                        })
                        ->join(DB::raw("(select STRING_AGG(owner_name,',') AS owner_name,
                                            STRING_AGG(guardian_name,',') AS guardian_name,
                                            STRING_AGG(mobile::TEXT,',') AS mobile_no,
                                            STRING_AGG(emailid,',') AS email_id,
                                            licence_id
                                        FROM active_licence_owners 
                                        WHERE status =1
                                        GROUP BY licence_id
                                        )owner"),function($join){
                                            $join->on("owner.licence_id","active_licences.id");
                                        })
                        ->where("active_licences.status",1)                        
                        ->where("active_licences.ulb_id",$ulb_id);
            if(isset($inputs['key']) && trim($inputs['key']))
            {
                $key = trim($inputs['key']);
                $licence = $licence->where(function ($query) use ($key) {
                    $query->orwhere('active_licences.holding_no', 'ILIKE', '%' . $key . '%')
                        ->orwhere('active_licences.application_no', 'ILIKE', '%' . $key . '%')
                        ->orwhere("active_licences.license_no", 'ILIKE', '%' . $key . '%')
                        ->orwhere("active_licences.provisional_license_no", 'ILIKE', '%' . $key . '%')                                            
                        ->orwhere('owner.owner_name', 'ILIKE', '%' . $key . '%')
                        ->orwhere('owner.guardian_name', 'ILIKE', '%' . $key . '%')
                        ->orwhere('owner.mobile_no', 'ILIKE', '%' . $key . '%');
                });
            }
            if(isset($inputs['wardNo']) && trim($inputs['wardNo']) && $inputs['wardNo']!="ALL")
            {
                $ward_ids =$inputs['wardNo']; 
            }
            if(isset($inputs['formDate']) && isset($inputs['toDate']) && trim($inputs['formDate']) && $inputs['toDate'])
            {
                $licence = $licence
                            ->whereBetween('licence_level_pendings.created_at::date',[$inputs['formDate'],$inputs['formDate']]); 
            }
            if($role->is_initiator)
            {
                $licence = $licence->whereIn('active_licences.pending_status',[0,3]);
            }
            else
            {
                $licence = $licence->whereIn('active_licences.pending_status',[2]);
            }            
            $licence = $licence
                    ->whereIn('active_licences.ward_mstr_id', $ward_ids)
                    ->get();
            // dd(DB::getQueryLog());
            // $worckflowCondidate = $this->parent->getAllRoles($user_id,$ulb_id,$refWorkflowId ,$role->role_id,true);
            // $getForwordBackwordRoll = $this->parent->getForwordBackwordRoll($user_id,$ulb_id,$refWorkflowId ,$role->role_id,false);           
            return responseMsg(true, "", $licence);
            
        } 
        catch (Exception $e) 
        {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }
    public function outbox(Request $request)
    {
        try {
            $user = Auth()->user();
            $user_id = $user->id;
            $ulb_id = $user->ulb_id;
            $refWorkflowId = Config::get('workflow-constants.TRADE_WORKFLOW_ID');
            $workflowId = WfWorkflow::where('wf_master_id', $refWorkflowId)
                ->where('ulb_id', $ulb_id)
                ->first();
            if (!$workflowId) 
            {
                throw new Exception("Workflow Not Available");
            }
            $role = $this->parent->getUserRoll($user_id,$ulb_id,$workflowId->wf_master_id);           
            if (!$role) 
            {
                throw new Exception("You Are Not Authorized");
            }
            if($role->is_initiator)
            {
                $joins = "leftjoin";
            }
            else
            {
                $joins = "join";
            }
            $role_id = $role->id;
            $ward_permission = $this->parent->WardPermission($user_id);
            $ward_ids = array_map(function ($val) {
                return $val['ulb_ward_id'];
            }, $ward_permission);
            $inputs = $request->all();
            $licence = ActiveLicence::select("active_licences.id",
                                            "active_licences.application_no",
                                            "active_licences.provisional_license_no",
                                            "active_licences.license_no",
                                            "active_licences.apply_date",
                                            "active_licences.apply_from",
                                            "owner.owner_name",
                                            "owner.guardian_name",
                                            "owner.mobile_no",
                                            "owner.email_id"
                                            )
                        ->$joins("trade_level_pendings",function($join) use($role_id){
                            $join->on("trade_level_pendings.licence_id","active_licences.id")
                            ->where("trade_level_pendings.receiver_user_type_id","<>",$role_id)
                            ->where("trade_level_pendings.status",1)
                            ->where("trade_level_pendings.status",0);
                        })
                        ->join(DB::raw("(select STRING_AGG(owner_name,',') AS owner_name,
                                            STRING_AGG(guardian_name,',') AS guardian_name,
                                            STRING_AGG(mobile::TEXT,',') AS mobile_no,
                                            STRING_AGG(emailid,',') AS email_id,
                                            licence_id
                                        FROM active_licence_owners 
                                        WHERE status =1
                                        GROUP BY licence_id
                                        )owner"),function($join){
                                            $join->on("owner.licence_id","active_licences.id");
                                        })
                        ->where("active_licences.status",1)
                        ->where("active_licences.ulb_id",$ulb_id);
            
            if(isset($inputs['key']) && trim($inputs['key']))
            {
                $key = trim($inputs['key']);
                $licence = $licence->where(function ($query) use ($key) {
                    $query->orwhere('active_licences.holding_no', 'ILIKE', '%' . $key . '%')
                        ->orwhere('active_licences.application_no', 'ILIKE', '%' . $key . '%')
                        ->orwhere("active_licences.license_no", 'ILIKE', '%' . $key . '%')
                        ->orwhere("active_licences.provisional_license_no", 'ILIKE', '%' . $key . '%')
                        ->orwhere('owner.owner_name', 'ILIKE', '%' . $key . '%')
                        ->orwhere('owner.guardian_name', 'ILIKE', '%' . $key . '%')
                        ->orwhere('owner.mobile_no', 'ILIKE', '%' . $key . '%');
                });
            }
            
            if(isset($inputs['wardNo']) && trim($inputs['wardNo']) && $inputs['wardNo']!="ALL")
            {
                $ward_ids =$inputs['wardNo']; 
            }
            if(isset($inputs['formDate']) && isset($inputs['toDate']) && trim($inputs['formDate']) && $inputs['toDate'])
            {
                $licence = $licence
                            ->whereBetween('licence_level_pendings.created_at::date',[$inputs['formDate'],$inputs['formDate']]); 
            }
            if($role->is_initiator)
            {
                $licence = $licence->whereIn('active_licences.pending_status',[2]);
            }
            else
            {
                $licence = $licence->whereIn('active_licences.pending_status',[0,3]);
            }
            $licence = $licence
                        ->whereIn('active_licences.ward_mstr_id', $ward_ids)
                        ->get();
            return responseMsg(true, "", $licence);
            
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }
    public function postNextLevel(Request $request)
    {
        try{
            $receiver_user_type_id="";
            $sms = "";
            $licence_pending=2;
            $regex = '/^[a-zA-Z1-9][a-zA-Z1-9\\s]+$/';
            $user = Auth()->user();
            $user_id = $user->id;
            $ulb_id = $user->ulb_id;
            $refWorkflowId = Config::get('workflow-constants.TRADE_WORKFLOW_ID');
            $workflowId = WfWorkflow::where('wf_master_id', $refWorkflowId)
                ->where('ulb_id', $ulb_id)
                ->first();
            if (!$workflowId) 
            {
                throw new Exception("Workflow Not Available");
            }
            $role = $this->parent->getUserRoll($user_id,$ulb_id,$workflowId->wf_master_id);
            if (!$role) 
            {
                throw new Exception("You Are Not Authorized");
            }
            $role_id = $role->role_id;
            $rules = [
                // "receiverId" => "required|int",
                "btn" => "required|in:btc,forward,backword",
                "licenceId" => "required|int",
                "comment" => "required|min:10|regex:$regex",
            ];
            $message = [
                // "receiverId.int" => "Receiver User Id Must Be Integer",
                "btn.in"=>"button Value May be In btc,forward,backword",
                "comment.required" => "Comment Is Required",
                "comment.min" => "Comment Length At Least 10 Charecters",
            ];
            $validator = Validator::make($request->all(), $rules, $message);
            if ($validator->fails()) {
                return responseMsg(false, $validator->errors(), $request->all());
            }
            if($role->is_initiator && in_array($request->btn,['btc','backword']))
            {
               throw new Exception("Initator Can Not Back The Application");
            }
            $licenc_data = ActiveLicence::find($request->licenceId);            
            $level_data = $this->getLevelData($request->licenceId);
            if(!$licenc_data)
            {
                throw new Exception("Data Not Found");
            }
            elseif($licenc_data->pending_status==5)
            {
                throw new Exception("Licence Is Already Approved");
            }
            elseif(!$role->is_initiator && isset($level_data->receiver_user_type_id) && $level_data->receiver_user_type_id != $role->role_id)
            {
                throw new Exception("You Have Not Pending Application");
            }
            elseif(!$role->is_initiator && ! $level_data)
            {
                throw new Exception("Please Contact To Admin!!!...");
            }  
            elseif(isset($level_data->receiver_user_type_id) && $level_data->receiver_user_type_id != $role->role_id)
            {
                throw new Exception("You Are Already Taken The Action On This Application");
            }
            $init_finish = $this->parent->iniatorFinisher($user_id,$ulb_id,$refWorkflowId); 
            if(!$init_finish)
            {
                throw new Exception("Full Work Flow Not Desigen Proper Please Contact To Admin !!!...");
            }
            elseif(!$init_finish["initiator"])
            {
                throw new Exception("Initiar Not Avelable Please Contact To Admin !!!...");
            }
            elseif(!$init_finish["finisher"])
            {
                throw new Exception("Finisher Not Avelable Please Contact To Admin !!!...");
            }
            
            // dd($role);
            if($request->btn=="forward" && !$role->is_finisher && !$role->is_initiator)
            {
                $sms ="Application Forword To ".$role->forword_name;
                $receiver_user_type_id = $role->forward_role_id;
            }
            elseif($request->btn=="backword" && !$role->is_initiator)
            {
                $sms ="Application Forword To ".$role->backword_name;
                $receiver_user_type_id = $role->backward_role_id;
            }
            elseif($request->btn=="btc" && !$role->is_initiator)
            {
                $licence_pending = 3;
                $sms ="Application Forword To ".$init_finish["initiator"]['role_name'];
                $receiver_user_type_id = $init_finish["initiator"]['id'];
            } 
            elseif($request->btn=="forward" && !$role->is_initiator && $level_data)
            {
                $sms ="Application Forword ";
                $receiver_user_type_id = $level_data->sender_user_type_id;
            }
            elseif($request->btn=="forward" && $role->is_initiator && !$level_data)
            {
                $licence_pending = 2;
                $sms ="Application Forword To ".$role->forword_name;
                $receiver_user_type_id = $role->forward_role_id;
            }

            if(!$role->is_finisher && !$receiver_user_type_id)  
            {
                throw new Exception("Next Roll Not Found !!!....");
            }
            
            // dd($role_id);

            DB::beginTransaction();
            if($level_data)
            {
                
                $level_data->verification_status = 1;
                $level_data->receiver_user_id =$user_id;
                $level_data->remarks =$request->comment;
                $level_data->forward_date =Carbon::now("Y-m-d");
                $level_data->forward_time =Carbon::now("H:s:i");
                $level_data->save();
            }
            if(!$role->is_finisher)
            {                
                $level_insert = new TradeLevelPending;
                $level_insert->licence_id = $licenc_data->id;
                $level_insert->sender_user_type_id = $role_id;
                $level_insert->receiver_user_type_id = $receiver_user_type_id;
                $level_insert->sender_user_id = $user_id;
                $level_insert->save();
            }
            if($role->is_finisher && $request->btn=="forward")
            {
                
                    $licence_pending = 5;
                    $sms ="Application Approved By ".$role->forword_name;
                    $ulbDtl = UlbMaster::find($ulb_id);
                    $ulb_name = explode(' ',$ulbDtl->ulb_name);
                    $short_ulb_name = "";
                    foreach($ulb_name as $val)
                    {
                        $short_ulb_name.=$val[0];
                    }
                    $ward_no = UlbWorkflowMaster::select("ward_name")
                            ->where("id",$licenc_data->ward_mstr_id)
                            ->first();
                    $ward_no = $ward_no['ward_name'];
                    $license_no = $short_ulb_name.$ward_no.date("mdY").$licenc_data->id;
                    $licence_for_years = $licenc_data->licence_for_years;
                    # 1	NEW LICENSE
                    if($licenc_data->application_type_id == 1)
                    {
                        // update license validity
                        $valid_upto =date("Y-m-d", strtotime("+$licence_for_years years", strtotime($licenc_data->apply_date)));
                        

                    }

                    # 2 RENEWAL
                    if($licenc_data->application_type_id == 2)
                    {
                        $prive_licence = ExpireLicence::find($licenc_data->update_status);
                        if(!empty($prive_licence))
                        {                                    
                            $prive_licence_id = $prive_licence->id;
                            $license_no = $prive_licence->license_no;
                            $valid_from = $prive_licence->valid_upto;                        
                            {
                                $datef = date('Y-m-d', strtotime($valid_from));
                                $datefrom = date_create($datef);
                                $datea = date('Y-m-d', strtotime($licenc_data->apply_date));
                                $dateapply = date_create($datea);
                                $year_diff = date_diff($datefrom, $dateapply);
                                $year_diff =  $year_diff->format('%y');

                                $priv_m_d = date('m-d', strtotime($valid_from));
                                $date = date('Y',strtotime($valid_from)) . '-' . $priv_m_d;
                                $licence_for_years2 = $licence_for_years + $year_diff; 
                                $valid_upto = date('Y-m-d', strtotime($date . "+" . $licence_for_years2 . " years"));
                                $data['valid_upto'] = $valid_upto; 
                                
                            }
                            
                            
                        }
                        else
                        {
                            throw new Exception('licence','Some Error Occurred Please Contact to Admin!!!');
                        
                        }
                    }

                    # 3	AMENDMENT
                    if($licenc_data->application_type_id == 3)
                    {
                        $prive_licence = ExpireLicence::find($licenc_data->update_status);
                        $license_no = $prive_licence->license_no;
                        $oneYear_validity = date("Y-m-d", strtotime("+1 years", strtotime('now')));
                        $previous_validity = $prive_licence->valid_upto;
                        if($previous_validity > $oneYear_validity)
                            $valid_upto = $previous_validity;
                        else
                            $valid_upto = $oneYear_validity;                   
                        $licenc_data->valid_from = date('Y-m-d');
                    }
                    
                    # 4 SURRENDER
                    if($licenc_data->application_type_id==4)
                    {
                        // Incase of surrender valid upto is previous license validity
                        $prive_licence = ExpireLicence::find($licenc_data->update_status);
                        $license_no = $prive_licence->license_no;
                        $valid_upto = $prive_licence->valid_upto;
                    }
                    $licenc_data->license_no = $license_no;
                    $sms.=" Licence No ".$license_no;
            }
            $licenc_data->pending_status = $licence_pending;            
            $licenc_data->save();            
            DB::commit();
            return responseMsg(false, $sms, "");

        }
        catch(Exception $e)
        {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }
    public function provisionalCertificate($id)
    {
        try{

            $data = (array)null;
            $data['provisionalCertificate']= config('app.url')."/api/trade/provisionalCertificate/".$id;           
            $application = ActiveLicence::select("application_no","provisional_license_no","license_no",
                                            "firm_name","holding_no","address","payment_status",
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
                                    "firm_name","holding_no","address","payment_status",
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
            if($application->payment_status==0)
            {
                throw new Exception("Please Payment Of This Application");
            }
            $vUpto = $application->apply_date;
            $application->valid_upto = date('Y-m-d', strtotime(date("$vUpto", mktime(time())) . " + 20 day"));
            $transaction = TradeTransaction::select("trade_transactions.id","transaction_no","transaction_type","transaction_date",
                            "payment_mode","paid_amount","penalty",
                            "trade_cheque_dtls.cheque_no","trade_cheque_dtls.cheque_date",
                            "trade_cheque_dtls.bank_name","trade_cheque_dtls.branch_name"
                        )
                        ->leftjoin("trade_cheque_dtls","trade_cheque_dtls.transaction_id","trade_transactions.id")
                        ->where("trade_transactions.related_id",$id)
                        ->whereIn("trade_transactions.status",[1,2])
                        ->first();
            if(!$transaction)
            {
                throw New Exception("Transaction Not Faound");
            }
            $penalty = TradeFineRebetDetail::select("head_name","amount")
                        ->where('transaction_id',$transaction->id)
                        ->where("status",1)
                        ->orderBy("id")
                        ->get();
            $pen=0;
            foreach($penalty as $val )
            {
                $pen+=$val->amount;

            }
            $transaction->rate = $transaction->paid_amount - $pen;
            $data["application"]=$application;
            $data["transaction"]=$transaction;
            $data["penalty"]    =$penalty;
            
            $data = remove_null($data);
            return  responseMsg(true,"",$data);
        }
        catch(Exception $e)
        {
            return responseMsg(false, $e->getMessage(), $id);
        }

    }
    public function licenceCertificate($id)
    {
        try{

            $data = (array)null;
            $data['licenceCertificate']= config('app.url')."/api/trade/licenceCertificate/".$id;           
            $application = ActiveLicence::select("application_no","provisional_license_no","license_no",
                                            "firm_name","holding_no","address","apply_date","license_date",
                                            "valid_from","valid_upto","licence_for_years","establishment_date",
                                            "nature_of_bussiness","pending_status",
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
                                    "firm_name","holding_no","address","payment_status",
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
            if($application->pending_status!=5)
            {
                throw new Exception("Application Not Approved");
            }
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
            $data ["application"]=$application;
            $data = remove_null($data);
            return  responseMsg(true,"",$data);
        }
        catch(Exception $e)
        {
            return responseMsg(false, $e->getMessage(), $id);
        }

    }
    public function applyDenail(Request $request)
    {
        $user = Auth()->user();
        $user_id = $user->id;
        $ulb_id = $user->ulb_id;
        $nowdate = Carbon::now()->format('Y-m-d'); 
        $timstamp = Carbon::now()->format('Y-m-d H:i:s');                
        $regex = '/^[a-zA-Z1-9][a-zA-Z1-9\.\s]+$/';
        $alphaNumCommaSlash='/^[a-zA-Z0-9- ]+$/i';
        $alphaSpace ='/^[a-zA-Z ]+$/i';
        $alphaNumhyphen ='/^[a-zA-Z0-9- ]+$/i';
        $numDot = '/^\d+(?:\.\d+)+$/i';
        $dateFormatYYYMMDD ='/^([12]\d{3}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01]))+$/i';
        $dateFormatYYYMM='/^([12]\d{3}-(0[1-9]|1[0-2]))+$/i';
        try{
            $data = array();
            if($request->getMethod()=='GET')
            {
                $data['wardList'] = $this->parent->WardPermission($user_id);
                return  responseMsg(true,"",$data);
            }
            if($request->getMethod()=='POST')
            {
                $rules = [];
                $message = [];
                $rules["firmName"]="required|regex:$regex";
                $rules["ownerName"]="required|regex:$regex";
                $rules["wardNo"]="required|int";
                $rules["holdingNo"]="required";
                $rules["address"]="required|regex:$regex";
                $rules["landmark"]="required|regex:$regex";
                $rules["city"]="required|regex:$regex";
                $rules["pincode"]="required|digits:6";
                $rules["mobileNo"]="digits:10";
                $rules["comment"]="required|regex:$regex|min:10";
                $rules["document"]="required|mimes:pdf,jpg,jpeg,png|max:2048";
                $validator = Validator::make($request->all(), $rules, $message);
                if ($validator->fails()) {
                    return responseMsg(false, $validator->errors(),$request->all());
                }
                $file=$request->file("document");
                $data["File Name"]=$file->getClientOriginalName();
                $data["exten"] = $file->getClientOriginalExtension();
                $fileName = time().'_'.$file->getClientOriginalName();
                $filePath = $this->uplodeFile($file,$fileName);//$file->storeAs('uploads/Trade/', $fileName, 'public');
                $data["filePath"] =  $filePath;
                $data["file_url"]=config('file.url');
                $data["upload_url"] = storage_path('app/public/' . $filePath);
                return  responseMsg(true,"",$data);
            }
            
        }
        catch(Exception $e)
        {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }
    

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
    public function gettradeitemsList($all=false)
    {
        try{
            if($all)
            {
                return TradeParamItemType::select("id","trade_item","trade_code")
                    ->where("status",1)
                    ->where("id","<>",187)
                    ->get();
            }
            else
            {
                return TradeParamItemType::select("id","trade_item","trade_code")
                    ->where("status",1)
                    ->get();

            }
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
                $licence->forceDelete();
                ActiveLicenceOwner::where('licence_id',$licenceId)->forceDelete();
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
        $refWorkflowId = Config::get('workflow-constants.TRADE_WORKFLOW_ID');
        $user_data = $this->parent->getUserRoll($user_id, $ulb_id,$refWorkflowId); 
        $roll_id =  $user_data->role_id??-1;       
        if($roll_id != -1)
        {
            $user_type_sort = Config::get('TradeConstant.USER-TYPE-SHORT-NAME.'.strtoupper($user_data->role_name));
            if(!$user_type_sort)
            {
                return "Online";
            }
            return $user_type_sort;
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
    public function updateStatusFine($denial_id,$denialAmount,$applyid,$status = 2)
    {
        $tradeNotice = TradeDenialNotice::where("id",$denial_id)->find();
        $tradeNotice->apply_id =  $applyid;
        $tradeNotice->fine_amount =  $denialAmount;
        $tradeNotice->status =  $status;
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
                            ->where("status",1)
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
    public function searchLicence(string $licence_no,$ulb_id)
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
                    ->where("ulb_id",$ulb_id)
                    ->where('license_no',$licence_no)
                    ->first();
            return responseMsg(true,"",remove_null($data));
        }
        catch(Exception $e)
        {
            return responseMsg(false,$e->getMessage(),$licence_no);
        }
        
    }

    public function getLevelData(int $licenceId)
    {
        try{
            $data = TradeLevelPending::select("*")
                    ->where("licence_id",$licenceId)
                    ->where("status",1)
                    ->where("verification_status",0)
                    ->orderBy("id","DESC")
                    ->first();
            return $data;
        }
        catch(Exception $e)
        {
            echo $e->getMessage();
        }
    }
    public function getTransactionDtlByLicenceId($licenceId)
    {
        try{
            $data = TradeTransaction::select("*")
                    ->where("related_id",$licenceId)
                    ->whereIn('status', [1, 2])
                    ->first();
           
            return $data;
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
    public function uplodeFile($file,$custumFileName)
    {
        $filePath = $file->storeAs('uploads/Trade/', $custumFileName, 'public');
        return  $filePath;
    }
   
    #-------------------- End core function of core function --------------
    
}