<?php

namespace App\Repository\Trade;

use App\EloquentModels\Common\ModelWard;
use App\Models\ActiveSafDetail;
use App\Models\ActiveSafOwnerDetail;
use App\Models\PropOwner;
use App\Models\PropPropertie;
use App\Models\Trade\ActiveLicence;
use App\Models\Trade\ActiveLicenceOwner;
use App\Models\Trade\ExpireLicence;
use App\Models\Trade\TradeBankRecancilation;
use App\Models\Trade\TradeDenialConsumerDtl;
use App\Models\Trade\TradeParamApplicationType;
use App\Models\Trade\TradeParamCategoryType;
use App\Models\Trade\TradeParamFirmType;
use App\Models\Trade\TradeParamItemType;
use App\Models\Trade\TradeParamLicenceRate;
use App\Models\Trade\TradeParamOwnershipType;
use App\Models\UlbWorkflowMaster;
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
        // $d = $this->getrate(['application_type_id'=>1,"area_in_sqft"=>500,"curdate"=>"2022-10-08","tobacco_status"=>0]); 
        // $a = $this->getChequeBouncePenalty(1);        
        //dd($a);   
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
                $apply_from = $this->applyFrom();
                $regex = '/^[a-zA-Z1-9][a-zA-Z1-9\\s]+$/';
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
                    $rules["initialBusinessDetails.otherfirmtype"]="required|regex:$regex";
                }
                $rules["initialBusinessDetails.ownershipType"]="required|int";
                if( isset($request->initialBusinessDetails['applyWith']) && $request->initialBusinessDetails['applyWith']==1)
                {
                    $rules["initialBusinessDetails.noticeNO"]="required|regex:$regex";
                    $rules["initialBusinessDetails.noticeDate"]="required|date";  
                }
                $rules["licenseDetails.licenseFor"]="required|int";

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
                        $proprty_id = $property['propery']['id'];
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
                    $licence->apply_date          = Carbon::now()->format('Y-m-d');
    
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
                    $licence->otherfirmtype       = $request->initialBusinessDetails['otherfirmtype']??null;
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
                    $licence->apply_date          = Carbon::now()->format('Y-m-d');
    
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
                
                DB::commit();
                return responseMsg(true,$appNo,'');
            }
            
        }
        catch (Exception $e) {
            DB::rollBack();
            echo($e->getLine());            
            return responseMsg(false,$e->getMessage(),$request->all());
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
            $rules["firmDetails.area_in_sqft"] = "required|numeric";
            $message["firmDetails.area_in_sqft.required"] = "Area is Required";
            $rules["firmDetails.tocStatus"] = "required|bool";
            $message["firmDetails.tocStatus.required"] = "TocStatus is Required";
            $validator = Validator::make($request->all(), $rules, $message);
            if ($validator->fails()) {
                return responseMsg(false, $validator->errors(),$request->all());
            }
            $data['application_type_id'] = Config::get("TradeConstant.APPLICATION-TYPE.".$request->applicationType);
            if(!$data['application_type_id'])
            {
                throw new Exception("Invalide Application Type");
            }
            $data["area_in_sqft"] = $request->firmDetails['areaSqft'];
            $data['curdate'] =Carbon::now()->format('Y-m-d');
            $data["tobacco_status"] =  $request->firmDetails['tocStatus'];
            $data = $this->getrate($data);
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
                    ->join("trade_denial_notices.denial_id","=","trade_denial_consumer_dtls.id")
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
            $data['area_in_sqft'] = (float)$inputs['areasqft'];
            $data['application_type_id'] = $inputs['applytypeid'];
            $data['firm_date'] = $inputs['estdate'];
            $data['firm_date'] = date('Y-m-d', strtotime($data['firm_date']));

            $data['tobacco_status'] = $inputs['tobacco_status'];
            $data['timeforlicense'] = $inputs['licensefor'];
            $data['curdate'] = $inputs['curdate']??date("Y-m-d");
            $denial_amount_month = 0;
            $count = $this->getrate($data);
            $rate = $count->rate * $data['timeforlicense'];

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
            $total_denial_amount = $denial_amount_month + $rate + $pre_app_amount;

            # Check If Any cheque bounce charges
            if (isset($inputs['apply_licence_id'], $inputs['apply_licence_id'])) 
            {
                $penalty = $this->getChequeBouncePenalty($inputs['apply_licence_id']);
                $denial_amount_month += $penalty;
                $total_denial_amount += $penalty;
            }

            if ($count) 
            {
                $response = ['response' => true, 'rate' => $rate, 'penalty' => $denial_amount_month, 'total_charge' => $total_denial_amount, 'rate_id' => $count['id'], 'arear_amount' => $pre_app_amount];
            } 
            else 
            {
                $response = ['response' => false];
            }
            return $response;
        }
        catch(Exception $e)
        {
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
                $response = ['response' => true,$propdet];

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
            return responseMsg(true,"",$data);
        }
        catch(Exception $e)
        {
            return responseMsg(false,$e->getMessage(),$licence_no);
        }
        
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
                            licence_id,owner_name,guardian_name,address,mobile,city,district,state,
                            document_id,id_no,emailid,emp_details_id,created_on,status,updated_at,
                            created_at,deleted_at
                        )
                        select  $expireLicenceId,owner_name,guardian_name,address,mobile,city,district,state,
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
                        ->where("status",1)
                        ->where("new_holding_no","<>","")
                        ->where("new_holding_no",$holdingNo)
                        ->where("ulb_id",$ulb_id)
                        ->first();
        if($property->id)
        {
            $owneres = PropOwner::select("*")
                        ->where("property_id",$property->id)
                        ->where('status',1)
                        ->get();
            return ["status"=>true,'property'=>adjToArray($property),'owneres'=>adjToArray($owneres)];

        }
        return ["status"=>false,'property'=>'','owneres'=>''];
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
    #-------------------- End core function of core function --------------
    
}