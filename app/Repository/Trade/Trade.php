<?php

namespace App\Repository\Trade;

use Illuminate\Support\Facades\Storage;
use App\EloquentModels\Common\ModelWard;
use App\Models\Property\ActiveSafsOwnerDtl;
use App\Models\Property\ActiveSaf ;
use App\Models\Property\PropOwner;
use App\Models\Property\PropProperty;
use App\Models\Trade\ActiveLicence;
use App\Models\Trade\ActiveLicenceOwner;
use App\Models\Trade\ExpireLicence;
use App\Models\Trade\TradeBankRecancilation;
use App\Models\Trade\TradeChequeDtl;
use App\Models\Trade\TradeDenialConsumerDtl;
use App\Models\Trade\TradeDenialMailDtl;
use App\Models\Trade\TradeDenialNotice;
use App\Models\Trade\TradeFineRebetDetail;
use App\Models\Trade\TradeLevelPending;
use App\Models\Trade\TradeLicenceDocument;
use App\Models\Trade\TradeParamApplicationType;
use App\Models\Trade\TradeParamCategoryType;
use App\Models\Trade\TradeParamDocumentType;
use App\Models\Trade\TradeParamFirmType;
use App\Models\Trade\TradeParamItemType;
use App\Models\Trade\TradeParamLicenceRate;
use App\Models\Trade\TradeParamOwnershipType;
use App\Models\Trade\TradeTransaction;
use App\Models\UlbMaster;
use App\Models\UlbWardMaster;
use App\Models\UlbWorkflowMaster;
use App\Models\Workflows\WfWorkflow;
// use App\Models\WorkflowTrack;
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
    use Auth;               // Trait Used added by sandeep bara date 17-09-2022
    use WardPermission;

    /**
     * | Created On-01-10-2022 
     * | Created By-Sandeep Bara
     * | Status (open)
     * |
     * |----------------------
     * | Applying For Trade License
     * | Proper Validation will be applied 
     * | @param Illuminate\Http\Request
     * | @param Request $request
     * | @param response
     */
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
    /**
     * | Apply Of Application
     * | --------------------descriptin------------------------------------
     * | add records only ["ONLINE","JSK","UTC","TC","SUPER ADMIN","TL"]
     * |----------------------------------------------
     * | @var mDenialAmount      = 0
     * | @var refUser            = Auth()->user()
     * | @var refUserId          = refUser->id      | loging user Id
     * | @var refUlbId           = refUser->ulb_id  | loging user ulb Id
     * | @var refUlbDtl          = UlbMaster::find(refUlbId) | 
     * | @var refUlbName         = explode(' ',refUlbDtl->ulb_name)
     * | @var refNoticeDetails   = null
     * | @var refWorkflowId      = Config::get('workflow-constants.TRADE_WORKFLOW_ID')
     * | @var refWorkflows       = this->_parent->iniatorFinisher(refUserId,refUlbId,refWorkflowId)
     * | @var redis              = new Redis
     * | @var mUserData          = json_decode(redis::get('user:' . refUserId), true)
     * | @var mUserType          = this->_parent->userType()    | loging user Role Name
     * | @var mShortUlbName      = ""           | first charecter of each word
     * | @var mApplicationTypeId = null         | 1-> NEW LICENCE, 2-> RENEWAL, 3-> AMENDMENT, 4-> SURENDER
     * | @var mNowdate           = Carbon::now()->format('Y-m-d')   | curent date
     * | @var mTimstamp          = Carbon::now()->format('Y-m-d H:i:s') | curent timestamp
     * | @var mNoticeDate        = null
     * | @var mProprtyId         = null  | integer In Case of Holding No is Supply
     * | @var mnaturOfBusiness   = null  | Trade Item Ids
     * | @var mAppNo application number 12 charectes
     * | @var mProvNo provisinal license number  
     * |-------------------------------------------------------------------
     * | @var licence model object(active_licences)
     * | @var mOldLicenceId = request->id   |priviuse licence id only for applicationType(2,3,4) 
     * | @var nextMonth = Carbon::now()->addMonths(1)->format('Y-m-d')  | on month priviuse date
     * | @var refOldLicece = ActiveLicence::find(mOldLicenceId) | priviuse licence data only for applicationType(2,3,4) 
     * | @var mWardNo      ward no | ulb_ward_masters->ward_name 
     * | @var refOldowners  priviuse licence owner data only for applicationType(2,3,4) 
     * | @var owner model object(active_licence_owners)
     * | @var Tradetransaction model object(trade_transactions)
     * | @var TradeFineRebet model object(trade_fine_rebet_details)
     * | @var TradeFineRebet2 model object(trade_fine_rebet_details)
     * | @var tradeChq model object(trade_cheque_dtls)
     * |
     * |----------------function-------------------------------------------
     * |    this->getFirmTypeList()                               |   read FirmType
     * |    this->getOwnershipTypeList()                          |   read OwnerShipType
     * |    this->getCategoryList()                               |   read Category Type                   
     * |    this->geItemsList(true)                               |    read Trade Item List without tomacco item
     * |    this->getLicenceById(request->id)                           |    only for applicationType(2,3,4)  |  read Old Licence Dtl
     * |    this->getOwnereDtlByLId($request->id)                       |    only for applicationType(2,3,4)  |  read Old Licence Owner Dtl
     * |    this->getLicenceItemsById(refOldLicece->nature_of_bussiness)|    only for applicationType(2,3,4)  |  read Old Licence Trade Items
     * 
     * |    this->transferExpire(mOldLicenceId,licenceId)               |    only for applicationType(2,3,4)  | transfer Aclive Licence To Expire Licence
     * |    this->createApplicationNo(mWardNo,licenceId)                |    Create the Application no
     * |    this->createProvisinalNo(mShortUlbName,mWardNo,licenceId)   |    Create Provisinal Certification No (provisinal certificati valide 20 day from apply date)
     * |    this->getDenialFirmDetails($refUlbId,strtoupper(trim($noticeNo))) |  Get the Notice Data 
     * |    this->updateStatusFine(refDenialId, mChargeData['notice_amount'], licence) | update The Notice Ammount And Licence Id
     * |
     * |    * | add records only ["JSK","UTC","TC","SUPER ADMIN","TL"] Authorize User For Cute The Mement In Ofline Mode
     * |    this->cltCharge(args)       | Calculation Of Charges
     * |
     * |----------------Basic Logic-------------------------------------------
     * |    case 1) mApplicationTypeId !=1  -> Transfer Old Licece into Expire and create New Records According To Old Licence Dtl
     * |         2)  mApplicationTypeId ==1 ->  create New Records According To Old Licence Dtl
     * |    
     * |
     */
    public function addRecord(Request $request)
    {           
        try
        {
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
            $mUserType          = $this->_parent->userType(); 
            $mShortUlbName      = "";
            $mApplicationTypeId = null;
            $mNowdate           = Carbon::now()->format('Y-m-d'); 
            $mTimstamp          = Carbon::now()->format('Y-m-d H:i:s'); 
            $mNoticeDate        = null;
            $mProprtyId         = null;
            $mnaturOfBusiness   = null;

            $rollId             =  $mUserData['role_id']??($this->_parent->getUserRoll($refUserId, $refUlbId,$refWorkflowId)->role_id??-1);
            $data               = array() ;
            foreach($refUlbName as $mval)
            {
                $mShortUlbName.=$mval[0];
            }
            #------------------------End Declaration-----------------------
            #---------------validation-------------------------------------
            if(!in_array(strtoupper($mUserType),["ONLINE","JSK","UTC","TC","SUPER ADMIN","TL"]))
            {
                throw new Exception("You Are Not Authorized For Apply Appliocation");
            }
            $mApplicationTypeId = Config::get("TradeConstant.APPLICATION-TYPE.".$request->applicationType);            
            if(!$mApplicationTypeId)
            {
                throw new Exception("Invalide Application Type");
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
            
            if (in_array($mApplicationTypeId, ["2", "3","4"]) && !$request->id) 
            {
                throw new Exception ("Old licence Id Requird");
            }
            #---------------End validation-------------------------
            if(in_array(strtoupper($mUserType),["ONLINE","JSK","SUPER ADMIN","TL"]))
            {
                $data['wardList'] = $this->_modelWard->getAllWard($refUlbId)->map(function($val){
                    $val->ward_no = $val->ward_name;
                    return $val;
                });
                $data['wardList'] = adjToArray($data['wardList']);
            }
            else
            {                
                $data['wardList'] = $this->_parent->WardPermission($refUserId);
            }

            if($request->getMethod()=='GET')
            {

                $data['userType']          = $mUserType;
                $data["firmTypeList"]       = $this->getFirmTypeList();
                $data["ownershipTypeList"]  = $this->getOwnershipTypeList();
                $data["categoryTypeList"]   = $this->getCategoryList();
                $data["natureOfBusiness"]   = $this->geItemsList(true);
                if(isset($request->id) && $request->id  && $mApplicationTypeId !=1)
                {
                    $refOldLicece = $this->getLicenceById($request->id); // recieving olde lisense id from url
                    if(!$refOldLicece)
                    {
                        throw new Exception("No Priviuse Licence Found");
                    }
                    $refOldOwneres =$this->getOwnereDtlByLId($request->id);
                    $mnaturOfBusiness = $this->getLicenceItemsById($refOldLicece->nature_of_bussiness);
                    $natur = array();
                    foreach($mnaturOfBusiness as $val)
                    {
                        $natur[]=["id"=>$val->id,
                            "trade_item" =>"(". $val->trade_code.") ". $val->trade_item
                        ];
                    }
                    $refOldLicece->nature_of_bussiness = $natur;

                    $data["licenceDtl"]     =  $refOldLicece;
                    $data["ownerDtl"]       = $refOldOwneres;
                }
                return responseMsg(true,"",remove_null($data));
            }
            elseif($request->getMethod()=="POST")
            {                 
                if($request->firmDetails['holdingNo'])
                {
                    $property = $this->propertyDetailsfortradebyHoldingNo($request->firmDetails['holdingNo'],$refUlbId);
                    if($property['status'])
                        $mProprtyId = $property['property']['id'];
                    else
                        throw new Exception("Property Details Not Found");
                }
                if($mApplicationTypeId==1)
                {
                    $mnaturOfBusiness = array_map(function($val){
                        return $val['id'];
                    },$request->firmDetails['natureOfBusiness']);
                    $mnaturOfBusiness = implode(',', $mnaturOfBusiness);

                }
                
                DB::beginTransaction();                
                $licence = new ActiveLicence();
                
                #----------------Crate Application--------------------
                if (in_array($mApplicationTypeId, ["2","4"])) # code for Renewal,Amendment,Surender respectively
                {   
                    
                    $mOldLicenceId = $request->id; 
                    $nextMonth = Carbon::now()->addMonths(1)->format('Y-m-d');
                    $refOldLicece = ActiveLicence::find($mOldLicenceId);
                    if(!$refOldLicece)
                    {
                        throw new Exception("Old Licence Not Found");
                    }
                    elseif($refOldLicece->valid_upto > $nextMonth)
                    {
                            throw new Exception("Licence Valice Upto ".$refOldLicece->valid_upto);
                    } 
                    elseif($refOldLicece->pending_status!=5)
                    {
                            throw new Exception("Application Aready Apply Please Track  ".$refOldLicece->application_no);
                    }
                    $mnaturOfBusiness = $refOldLicece->nature_of_bussiness;
                    $wardId = $refOldLicece->ward_mstr_id;                
                    $mWardNo = array_filter($data['wardList'], function ($val) use($wardId ){
                        return $val['id'] == $wardId ;
                    });
                    $mWardNo = array_values($mWardNo)[0]['ward_no']??"";
                    $refOldowners = ActiveLicenceOwner::where('licence_id',$mOldLicenceId)
                                ->get();
                    
                    $licence->firm_type_id        = $refOldLicece->firm_type_id;
                    $licence->otherfirmtype       = $refOldLicece->otherfirmtype;
                    $licence->application_type_id = $mApplicationTypeId;                    
                    $licence->category_type_id    = $refOldLicece->category_type_id ;
                    $licence->ownership_type_id   = $refOldLicece->ownership_type_id;
                    $licence->ward_mstr_id        = $refOldLicece->ward_mstr_id;
                    $licence->new_ward_mstr_id    = $refOldLicece->new_ward_mstr_id;
                    $licence->ulb_id              = $refUlbId;
    
                    $licence->prop_dtl_id         = $mProprtyId;
                    $licence->holding_no          = $request->firmDetails['holdingNo'];
                    $licence->nature_of_bussiness = $refOldLicece->nature_of_bussiness;
                    $licence->firm_name           = $refOldLicece->firm_name;
                    $licence->premises_owner_name = $refOldLicece->premises_owner_name;
                    $licence->brife_desp_firm     = $refOldLicece->brife_desp_firm;
                    $licence->area_in_sqft        = $refOldLicece->area_in_sqft;
                    
                    $licence->k_no                = $refOldLicece->k_no;
                    $licence->bind_book_no        = $refOldLicece->bind_book_no;
                    $licence->account_no          = $refOldLicece->account_no;
                    $licence->pan_no              = $refOldLicece->pan_no;
                    $licence->tin_no              = $refOldLicece->tin_no;
                    $licence->salestax_no         = $refOldLicece->salestax_no;
                    $licence->emp_details_id      = $refUserId;
                    $licence->establishment_date  = $refOldLicece->establishment_date;
                    $licence->apply_date          = $mNowdate;
                    
                    $licence->licence_for_years   = $mApplicationTypeId==2 ? $request->licenseDetails['licenseFor']: $refOldLicece->licence_for_years ;
                    $licence->address             = $refOldLicece->address;
                    $licence->landmark            = $refOldLicece->landmark;
                    $licence->pin_code            = $refOldLicece->pin_code;
                    $licence->street_name         = $refOldLicece->street_name;
                    $licence->property_type       = $refOldLicece->property_type;
                    $licence->update_status       = 0;
                    $licence->valid_from          = $refOldLicece->valid_upto;
                    $licence->license_no          = $refOldLicece->license_no;
                    $licence->tobacco_status      = $refOldLicece->tobacco_status;
                    
                    $licence->apply_from          = $mUserType;
                    // $licence->current_user_id     = null;
                    $licence->initiator_id        = $refWorkflows['initiator']['id'];
                    $licence->finisher_id         = $refWorkflows['finisher']['id'];
                    $licence->workflow_id         = $refWorkflowId;
                    
                    $licence->save();
                    $licenceId = $licence->id;
                    
                    $mAppNo = $this->createApplicationNo($mWardNo,$licenceId);
                    $licence->application_no = $mAppNo;
                    $licence->save();
                    $this->transferExpire($mOldLicenceId,$licenceId);
                    foreach($refOldowners as $owners)
                    {
                        $owner = new ActiveLicenceOwner();
                        $owner->licence_id      = $licenceId;
                        $owner->owner_name      = $owners->owner_name;
                        $owner->guardian_name   = $owners->guardian_name;
                        $owner->address         = $owners->address;
                        $owner->mobile          = $owners->mobile;
                        $owner->city            = $owners->city;
                        $owner->district        = $owners->district;
                        $owner->state           = $owners->state;
                        $owner->emailid         = $owners->emailid;
                        $owner->emp_details_id  = $refUserId;
                        $owner->save();
    
                    }
                }
                elseif($mApplicationTypeId==3)
                {
                    $mOldLicenceId = $request->id; 
                    $nextMonth = Carbon::now()->addMonths(1)->format('Y-m-d');
                    $refOldLicece = ActiveLicence::find($mOldLicenceId);
                    if(!$refOldLicece)
                    {
                        throw new Exception("Old Licence Not Found");
                    }
                    elseif($refOldLicece->valid_upto > $nextMonth)
                    {
                            throw new Exception("Licence Valice Upto ".$refOldLicece->valid_upto);
                    } 
                    elseif($refOldLicece->pending_status!=5)
                    {
                            throw new Exception("Application Aready Apply Please Track  ".$refOldLicece->application_no);
                    }
                    $mnaturOfBusiness = $refOldLicece->nature_of_bussiness;
                    $wardId = $refOldLicece->ward_mstr_id;                
                    $mWardNo = array_filter($data['wardList'], function ($val) use($wardId ){
                        return $val['id'] == $wardId ;
                    });
                    $mWardNo = array_values($mWardNo)[0]['ward_no']??"";
                    $refOldowners = ActiveLicenceOwner::where('licence_id',$mOldLicenceId)
                                ->get();
                    
                    $licence->firm_type_id        = $request->initialBusinessDetails['firmType'];
                    $licence->otherfirmtype       = $request->initialBusinessDetails['otherFirmType']??null;
                    $licence->application_type_id = $mApplicationTypeId;                    
                    $licence->category_type_id    = $refOldLicece->category_type_id ;
                    $licence->ownership_type_id   = $request->initialBusinessDetails['ownershipType'];//$refOldLicece->ownership_type_id;
                    $licence->ward_mstr_id        = $refOldLicece->ward_mstr_id;
                    $licence->new_ward_mstr_id    = $refOldLicece->new_ward_mstr_id;
                    $licence->ulb_id              = $refUlbId;
    
                    $licence->prop_dtl_id         = $mProprtyId;
                    $licence->holding_no          = $request->firmDetails['holdingNo'];
                    $licence->nature_of_bussiness = $refOldLicece->nature_of_bussiness;
                    $licence->firm_name           = $refOldLicece->firm_name;
                    $licence->premises_owner_name = $refOldLicece->premises_owner_name;
                    $licence->brife_desp_firm     = $request->firmDetails['businessDescription'];//$refOldLicece->brife_desp_firm;
                    $licence->area_in_sqft        = $request->firmDetails['areaSqft'];//$refOldLicece->area_in_sqft;
                    
                    $licence->k_no                = $refOldLicece->k_no;
                    $licence->bind_book_no        = $refOldLicece->bind_book_no;
                    $licence->account_no          = $refOldLicece->account_no;
                    $licence->pan_no              = $refOldLicece->pan_no;
                    $licence->tin_no              = $refOldLicece->tin_no;
                    $licence->salestax_no         = $refOldLicece->salestax_no;
                    $licence->emp_details_id      = $refUserId;
                    $licence->establishment_date  = $refOldLicece->establishment_date;
                    $licence->apply_date          = $mNowdate;
                    
                    $licence->licence_for_years   = $request->licenseDetails['licenseFor'];
                    $licence->address             = $refOldLicece->address;
                    $licence->landmark            = $refOldLicece->landmark;
                    $licence->pin_code            = $refOldLicece->pin_code;
                    $licence->street_name         = $refOldLicece->street_name;
                    $licence->property_type       = $refOldLicece->property_type;
                    $licence->update_status       = 0;
                    $licence->valid_from          = $refOldLicece->valid_upto;
                    $licence->license_no          = $refOldLicece->license_no;
                    $licence->tobacco_status      = $refOldLicece->tobacco_status;
                    
                    $licence->apply_from          = $mUserType;
                    // $licence->current_user_id     = null;
                    $licence->initiator_id        = $refWorkflows['initiator']['id'];
                    $licence->finisher_id         = $refWorkflows['finisher']['id'];
                    $licence->workflow_id         = $refWorkflowId;
                    
                    $licence->save();
                    $licenceId = $licence->id;
                    
                    $mAppNo = $this->createApplicationNo($mWardNo,$licenceId);
                    $licence->application_no = $mAppNo;
                    $licence->save();
                    $this->transferExpire($mOldLicenceId,$licenceId);
                    foreach($refOldowners as $owners)
                    {
                        $owner = new ActiveLicenceOwner();
                        $owner->licence_id      = $licenceId;
                        $owner->owner_name      = $owners->owner_name;
                        $owner->guardian_name   = $owners->guardian_name;
                        $owner->address         = $owners->address;
                        $owner->mobile          = $owners->mobile;
                        $owner->city            = $owners->city;
                        $owner->district        = $owners->district;
                        $owner->state           = $owners->state;
                        $owner->emailid         = $owners->emailid;
                        $owner->emp_details_id  = $refUserId;
                        $owner->save();
    
                    }
                    foreach($request->ownerDetails as $owners)
                    {
                        $owner = new ActiveLicenceOwner();
                        $owner->licence_id      = $licenceId;
                        $owner->owner_name      = $owners['businessOwnerName'];
                        $owner->guardian_name   = $owners['guardianName']??null;
                        $owner->address         = $owners['address']??null;
                        $owner->mobile          = $owners['mobileNo'];
                        $owner->city            = $owners['city']??null;
                        $owner->district        = $owners['district']??null;
                        $owner->state           = $owners['state']??null;
                        $owner->emailid         = $owners['email']??null;
                        $owner->emp_details_id  = $refUserId;
                        $owner->save();
    
                    }
                }
                elseif($mApplicationTypeId==1)
                {   
                    $wardId = $request->firmDetails['wardNo'];                
                    $mWardNo = array_filter($data['wardList'], function ($val) use($wardId ){
                        return $val['id'] == $wardId ;
                    });
                    $mWardNo = array_values($mWardNo)[0]['ward_no']??"";

                    $licence->firm_type_id        = $request->initialBusinessDetails['firmType'];
                    $licence->otherfirmtype       = $request->initialBusinessDetails['otherFirmType']??null;
                    $licence->application_type_id = $mApplicationTypeId;
                    $licence->category_type_id    = $request->firmDetails['categoryTypeId']??null;
                    $licence->ownership_type_id   = $request->initialBusinessDetails['ownershipType'];
                    $licence->ward_mstr_id        = $request->firmDetails['wardNo'];
                    $licence->new_ward_mstr_id    = $request->firmDetails['newWardNo'];
                    $licence->ulb_id              = $refUlbId;
    
                    $licence->prop_dtl_id         = $mProprtyId;
                    $licence->holding_no          = $request->firmDetails['holdingNo'];
                    $licence->nature_of_bussiness = $mnaturOfBusiness;
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
                    $licence->emp_details_id      = $refUserId;
                    $licence->establishment_date  = $request->firmDetails['firmEstdDate'];
                    $licence->apply_date          = $mNowdate;
    
                    $licence->licence_for_years   = $request->licenseDetails['licenseFor'];
                    $licence->address             = $request->firmDetails['businessAddress'];
                    $licence->landmark            = $request->firmDetails['landmark']??null;
                    $licence->pin_code            = $request->firmDetails['pincode']??null;
                    $licence->street_name         = $request->firmDetails['streetName']??null;
                    $licence->property_type       ="Property";
                    $licence->update_status       = 0;
                    $licence->tobacco_status      = $request->firmDetails['tocStatus'];
                    
                    $licence->apply_from          = $mUserType;
                    // $licence->current_user_id     = null;
                    $licence->initiator_id        = $refWorkflows['initiator']['id'];
                    $licence->finisher_id         = $refWorkflows['finisher']['id'];
                    $licence->workflow_id         = $refWorkflowId;
    
                    $licence->save();
                    $licenceId = $licence->id;                
                    // $mAppNo = "APN".str_pad($ward_no, 2, '0', STR_PAD_LEFT).str_pad($licenceId, 7, '0', STR_PAD_LEFT);
                    $mAppNo = $this->createApplicationNo($mWardNo,$licenceId);
                    $licence->application_no = $mAppNo;
                    $licence->save();

                    foreach($request->ownerDetails as $owners)
                    {
                        $owner = new ActiveLicenceOwner();
                        $owner->licence_id      = $licenceId;
                        $owner->owner_name      = $owners['businessOwnerName'];
                        $owner->guardian_name   = $owners['guardianName']??null;
                        $owner->address         = $owners['address']??null;
                        $owner->mobile          = $owners['mobileNo'];
                        $owner->city            = $owners['city']??null;
                        $owner->district        = $owners['district']??null;
                        $owner->state           = $owners['state']??null;
                        $owner->emailid         = $owners['email']??null;
                        $owner->emp_details_id  = $refUserId;
                        $owner->save();
    
                    }
                }
                #----------------End Crate Application--------------------
                #---------------- transaction of payment-------------------------------
                if($mApplicationTypeId==1 && $request->initialBusinessDetails['applyWith']==1 )
                { 
                    $noticeNo = trim($request->initialBusinessDetails['noticeNo']);
                    $firm_date = $request->firmDetails['firmEstdDate'];
                    $refNoticeDetails = $this->getDenialFirmDetails($refUlbId,strtoupper(trim($noticeNo)));
                    if ($refNoticeDetails) 
                    {   
                        $refDenialId = $refNoticeDetails->dnialid;
                        $mNoticeDate = date("Y-m-d",strtotime($refNoticeDetails['created_on'])); //notice date  
                        if($firm_date > $mNoticeDate) 
                        {
                            throw new Exception("Firm Establishment Date Can Not Be Greater Than Notice Date ");
                        }                                                    
    
                    }
                }              
                if(in_array(strtoupper($mUserType),["JSK","UTC","TC","SUPER ADMIN","TL"]) && $mApplicationTypeId!=4)
                {
                    # Calculating rate
                    {
                       
                        $args['areaSqft']            = (float)$licence->area_in_sqft;
                        $args['application_type_id'] = $mApplicationTypeId;
                        $args['firmEstdDate']        = $request->firmDetails['firmEstdDate'];
                        $args['tobacco_status']      = $licence->tobacco_status;
                        $args['licenseFor']          = $licence->licence_for_years ;
                        $args['nature_of_business']  = $licence->nature_of_bussiness;
                        $args['noticeDate']          = $mNoticeDate;

                        $mChargeData = $this->cltCharge($args);
                    }
                    if($mChargeData['total_charge']!=$request->licenseDetails["totalCharge"])
                    {
                        throw new Exception("Payble Amount Missmatch!!!");
                    }
    
                    //end
                    $totalCharge = $mChargeData['total_charge'] ;
                    $mDenialAmount = $mChargeData['notice_amount'];

                    $Tradetransaction = new TradeTransaction ;                    
                    $Tradetransaction->related_id       = $licenceId;
                    $Tradetransaction->ward_mstr_id     = $licence->ward_mstr_id;
                    $Tradetransaction->transaction_type = $mApplicationTypeId==1?"NEW LICENSE":$request->applicationType;
                    $Tradetransaction->transaction_date = $mNowdate;
                    $Tradetransaction->payment_mode     = $request->licenseDetails['paymentMode'];
                    $Tradetransaction->paid_amount      = $totalCharge;
    
                    $Tradetransaction->penalty          = $mChargeData['penalty'] + $mDenialAmount + $mChargeData['arear_amount'];
                    if ($request->licenseDetails['paymentMode'] != 'CASH') 
                    {
                        $Tradetransaction->status = 2;
                    }
                    $Tradetransaction->emp_details_id   = $refUserId;
                    $Tradetransaction->created_on       = $mTimstamp;
                    $Tradetransaction->ip_address       = '';
                    $Tradetransaction->ulb_id           = $refUlbId;
                    $Tradetransaction->save();

                    $transaction_id = $Tradetransaction->id;
                    $Tradetransaction->transaction_no = "TRANML" . date('d') . $transaction_id . date('Y') . date('m') . date('s');
                    $Tradetransaction->update();

                    $TradeFineRebet = new TradeFineRebetDetail;
                    $TradeFineRebet->transaction_id  = $transaction_id;
                    $TradeFineRebet->head_name       = 'Delay Apply License';
                    $TradeFineRebet->amount          = $mChargeData['penalty'];
                    $TradeFineRebet->value_add_minus = 'Add';
                    $TradeFineRebet->created_on      = $mTimstamp;
                    $TradeFineRebet->save();
                    $mDenialAmount = $mDenialAmount + $mChargeData['arear_amount'];
                    if ($mDenialAmount > 0) 
                    {
                        $TradeFineRebet2 = new TradeFineRebetDetail;
                        $TradeFineRebet2->transaction_id  = $transaction_id;
                        $TradeFineRebet2->head_name       = 'Denial Apply';
                        $TradeFineRebet2->amount          = $mDenialAmount;
                        $TradeFineRebet2->value_add_minus = 'Add';
                        $TradeFineRebet2->created_on      = $mTimstamp;
                        $TradeFineRebet2->save();
                    }
                    if($refNoticeDetails)
                    {
                        $this->updateStatusFine($refDenialId, $mChargeData['notice_amount'], $licenceId); //update status and fineAmount
                    }
    
                    $paymentStatus = 1;
                    if ($request->licenseDetails['paymentMode'] != 'CASH') 
                    {
                        $tradeChq = new TradeChequeDtl;
                        $tradeChq->transaction_id        = $transaction_id;
                        $tradeChq->cheque_no             = $request->licenseDetails['chequeNo'];
                        $tradeChq->cheque_date           = $request->licenseDetails['chequeDate'];
                        $tradeChq->bank_name             = $request->licenseDetails['bankName'];
                        $tradeChq->branch_name           = $request->licenseDetails['branchName'];
                        $tradeChq->emp_details_id        = $refUserId;
                        $tradeChq->created_on            = $mTimstamp;
                        $paymentStatus                  = 2;
                        $tradeChq->save();
                    } 
                    if($paymentStatus==1)
                    {
                        $licence->current_user_id = $refWorkflows['initiator']['id'];
                    } 
                    $mProvno                         = $this->createProvisinalNo($mShortUlbName,$mWardNo,$licenceId);
                    $licence->provisional_license_no = $mProvno;
                    $licence->payment_status         = $paymentStatus;
                    $licence->save();
                    $res['transactionId']            = $transaction_id;
                    $res['paymentRecipt']            = config('app.url')."/api/trade/paymentRecipt/".$licenceId."/".$transaction_id;
                    
                    
                }
                elseif($refNoticeDetails)
                {
                    $this->updateStatusFine($refDenialId, 0 , $licenceId,1); //update status and fineAmount                     
                }
                #---------------- End transaction of payment----------------------------
                if($mApplicationTypeId==4)
                {
                    $mProvno                         = $this->createProvisinalNo($mShortUlbName,$mWardNo,$licenceId);
                    $licence->provisional_license_no = $mProvno;
                    $licence->payment_status         = 1;
                    $licence->save();
                }
                DB::commit();

                $res['applicationNo']=$mAppNo;
                $res['applyLicenseId'] = $licenceId;
                return responseMsg(true,$mAppNo,$res);
            }
            
        }
        catch (Exception $e) {            
            DB::rollBack();           
            return responseMsg(false,$e->getMessage(),$request->all());
        }
    }

    /**
     * | Trade Licence Charge Payment (offline Mode)
     * | --------------------descriptin------------------------------------
     * | make Payment only ["JSK","UTC","TC","SUPER ADMIN","TL"]
     * |-------------------------------------------------------------------
     * | @var refUser        = Auth()->user()
     * | @var refUserId      = refUser->id          | loging user Id
     * | @var refUlbId       = refUser->ulb_id      | loging user Ulb Id
     * | @var refWorkflowId  = Config::get('workflow-constants.TRADE_WORKFLOW_ID')
     * | @var refWorkflows   = this->_parent->iniatorFinisher(refUserId,refUlbId,refWorkflowId)
     * | @var refNoticeDetails= null
     * | @var refDenialId    = null
     * | @var refUlbDtl      = UlbMaster::find(refUlbId)
     * | @var refUlbName     = explode(' ',refUlbDtl->ulb_name)
     * | @var refLecenceData  = model object(active_licences)
     * | @var licenceId   = request->licenceId
     * | @var refLevelData = $this->getLevelData(licenceId)
     * |   
     * | @var mUserData      = this->_parent->getUserRoll(refUserId, refUlbId,refWorkflowId)
     * | @var mUserType      = this->_parent->userType()
     * | @var mNowDate       = Carbon::now()->format('Y-m-d')   | curent date
     * | @var mTimstamp      = Carbon::now()->format('Y-m-d H:i:s')     | curent timestamp
     * | @var mDenialAmount  = 0
     * | @var mPaymentStatus = 1
     * | @var mNoticeDate = null
     * | @var mShortUlbName = ""        | first charecter of each word
     * | @var mWardNo        = ""
     * |
     * |-------------------functions-------------------------------------
     * |
     * |  mUserData      = this->_parent->getUserRoll(refUserId, refUlbId,refWorkflowId)
     * |  mUserType      = $this->_parent->userType()
     * |  refLevelData   = $this->getLevelData(licenceId)
     * |  refNoticeDetails = this->readNotisDtl(refLecenceData->id)
     * |  chargeData    = this->cltCharge(args)
     * |  this->updateStatusFine(refDenialId, chargeData['notice_amount'], licenceId,1)
     * |
     * |------------------------------------------------------------------
     * | *********************validation**********************************
     * | case 1) refLecenceData==null                                           |(data Not Available)
     * |      2) refLecenceData->application_type_id==4                         |(surender license Not Pay any amount)
     * |      3) refLecenceData->payment_status in [1,2]                        |(1->CHASE, 2->CHEQUE Payment mode, 0->not payment)
     * |      4) refLecenceData->tobacco_status==1 && request->licenseFor >1    |(tobacco licence issue only one years)
     * |      5) chargeData['response']==false || chargeData['total_charge']!=request->totalCharge      | (Charge not Calculate Or Total Charge Missmatch)
     * |
     * |----------------------basic logic---------------------------------
     * | @var totalCharge = chargeData['total_charge'] 
     * | @var mDenialAmount = chargeData['notice_amount']
     * | @var transactionType = Config::get('TradeConstant.APPLICATION-TYPE-BY-ID.'.refLecenceData->application_type_id)    | NEW LICENSE, RENEWAL, AMENDMENT
     * | @var Tradetransaction model(Tradetransaction)
     * | Tradetransaction->transaction_type = transactionType
     * | Tradetransaction->transaction_date = mNowDate      |   curent date
     * | Tradetransaction->payment_mode     = request->paymentMode
     * | Tradetransaction->paid_amount      = totalCharge        |  total paid amount
     * | Tradetransaction->penalty          = chargeData['penalty'] + mDenialAmount + chargeData['arear_amount'] (total penalty)
     * | Tradetransaction->status =         |   when request->paymentMode != CASH then 2 else 1 (default)
     * |
     * |   **************************transaction on Fin-Rebet model(TradeFineRebetDetail)*******************
     * | ++++++ first row
     * | TradeFineRebet->transaction_id = transaction_id (Tradetransaction->id)
     * | TradeFineRebet->head_name      = 'Delay Apply License'
     * | TradeFineRebet->amount         = chargeData['penalty']  (leat penalty)
     * |
     * | ++++++ second row (not neccesary)
     * | mDenialAmount = mDenialAmount + $chargeData['arear_amount']       | update priviuse value (Other Penalty)
     * | ---TradeFineRebet2  model(TradeFineRebetDetail)
     * | TradeFineRebet2->transaction_id = transaction_id (Tradetransaction->id)
     * | TradeFineRebet2->head_name      = 'Denial Apply'
     * | TradeFineRebet2->amount         = mDenialAmount
     * 
     * |    *************************transaction on Cheque Details model(TradeChequeDtl) *****************************************
     * | ++++++ only on Case Of request->paymentMode != 'CASH'
     * | tradeChq->transaction_id = transaction_id (Tradetransaction->id)
     * | tradeChq->cheque_no      = request->chequeNo
     * | tradeChq->cheque_date    = request->chequeDate
     * | tradeChq->bank_name      = request->bankName
     * | tradeChq->branch_name    = request->branchName
     * | tradeChq->emp_details_id = refUserId
     * | tradeChq->created_on     = mTimstamp
     * | 
     * | mPaymentStatus           = 2
     * |
     * |    *********************** transaction on level model(TradeLevelPending)******************************
     * | ++++++ only on Case Of (mPaymentStatus==1 && refLecenceData->document_upload_status =1 && refLecenceData->pending_status=0 && !refLevelData)
     * | level_insert->licence_id            = licenceId
     * | level_insert->sender_user_type_id   = refWorkflows['initiator']['id']
     * | level_insert->receiver_user_type_id = refWorkflows['initiator']['forward_id']
     * | level_insert->sender_user_id        = refUserId
     * |
     * |    ***************finally**************************
     * | provNo = $this->createProvisinalNo($mShortUlbName,$mWardNo,$licenceId)
     * | refLecenceData->provisional_license_no = provNo
     * | refLecenceData->payment_status         = mPaymentStatus
     * |
     * |-----------------------------------------------------------------------------
     * |
     */
    public function paymentCounter(Request $request)
    {        
        try{
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id;
            $refUlbId       = $refUser->ulb_id; 
            $refWorkflowId  = Config::get('workflow-constants.TRADE_WORKFLOW_ID'); 
            $refWorkflows   = $this->_parent->iniatorFinisher($refUserId,$refUlbId,$refWorkflowId);  
            $refNoticeDetails= null;
            $refDenialId    = null;
            $refUlbDtl      = UlbMaster::find($refUlbId);
            $refUlbName     = explode(' ',$refUlbDtl->ulb_name);

            $mUserData      = $this->_parent->getUserRoll($refUserId, $refUlbId,$refWorkflowId);
            $mUserType      = $this->_parent->userType();
            $mNowDate       = Carbon::now()->format('Y-m-d'); 
            $mTimstamp      = Carbon::now()->format('Y-m-d H:i:s');
            $mDenialAmount  = 0;
            $mPaymentStatus = 1;            
            $mNoticeDate    = null;            
            $mShortUlbName  = "";
            $mWardNo        = "";
            foreach($refUlbName as $val)
            {
                $mShortUlbName.=$val[0];
            }

            #-----------valication-------------------                            
            if(!in_array($mUserType,["JSK","UTC","TC","SUPER ADMIN","TL"]))
            {
                DB::rollBack();
                throw new Exception("You Are Not Authorized For Payment Cut");
            }
            $refLecenceData = ActiveLicence::find($request->licenceId);
            $licenceId = $request->licenceId;
            $refLevelData = $this->getLevelData($licenceId);
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
            if($refLecenceData->applyWith==1 && $refNoticeDetails = $this->readNotisDtl($refLecenceData->id))
            { 
                $refDenialId = $refNoticeDetails->dnialid;
                $mNoticeDate = strtotime($refNoticeDetails['created_on']); //notice date 
            }

            $ward_no = UlbWardMaster::select("ward_name")
                        ->where("id",$refLecenceData->ward_mstr_id)
                        ->first();
            $mWardNo = $ward_no['ward_name']; 

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
            $args['licenseFor']          = $request->licenseFor ;
            $args['nature_of_business']  = $refLecenceData->nature_of_bussiness;
            $args['noticeDate']          = $mNoticeDate;
            $chargeData = $this->cltCharge($args);
            if($chargeData['response']==false || $chargeData['total_charge']!=$request->totalCharge)
            {
                throw new Exception("Payble Amount Missmatch!!!");
            }
            
            $transactionType = Config::get('TradeConstant.APPLICATION-TYPE-BY-ID.'.$refLecenceData->application_type_id);  
            
            $totalCharge = $chargeData['total_charge'] ;
            $mDenialAmount = $chargeData['notice_amount'];
            #-------------End Calculation-----------------------------
            #-------- Transection -------------------
            DB::beginTransaction();
            $Tradetransaction = new TradeTransaction ;
            $Tradetransaction->related_id       = $licenceId;
            $Tradetransaction->ward_mstr_id     = $refLecenceData->ward_mstr_id;
            $Tradetransaction->transaction_type = $transactionType;
            $Tradetransaction->transaction_date = $mNowDate;
            $Tradetransaction->payment_mode     = $request->paymentMode;
            $Tradetransaction->paid_amount      = $totalCharge;
            $Tradetransaction->penalty          = $chargeData['penalty'] + $mDenialAmount + $chargeData['arear_amount'];
            if ($request->paymentMode != 'CASH') 
            {
                $Tradetransaction->status = 2;
            }
            $Tradetransaction->emp_details_id   = $refUserId;
            $Tradetransaction->created_on       = $mTimstamp;
            $Tradetransaction->ip_address       = '';
            $Tradetransaction->ulb_id           = $refUlbId;
            $Tradetransaction->save();
            $transaction_id                     = $Tradetransaction->id;
            $Tradetransaction->transaction_no   = $this->createTransactionNo($transaction_id);//"TRANML" . date('d') . $transaction_id . date('Y') . date('m') . date('s');
            $Tradetransaction->update();

            $TradeFineRebet = new TradeFineRebetDetail;
            $TradeFineRebet->transaction_id = $transaction_id;
            $TradeFineRebet->head_name      = 'Delay Apply License';
            $TradeFineRebet->amount         = $chargeData['penalty'];
            $TradeFineRebet->value_add_minus = 'Add';
            $TradeFineRebet->created_on     = $mTimstamp;
            $TradeFineRebet->save();

            $mDenialAmount = $mDenialAmount + $chargeData['arear_amount'];
            if ($mDenialAmount > 0) 
            {
                $TradeFineRebet2 = new TradeFineRebetDetail;
                $TradeFineRebet2->transaction_id = $transaction_id;
                $TradeFineRebet2->head_name      = 'Denial Apply';
                $TradeFineRebet2->amount         = $mDenialAmount;
                $TradeFineRebet2->value_add_minus = 'Add';
                $TradeFineRebet2->created_on     = $mTimstamp;
                $TradeFineRebet2->save();
            }

            if ($request->paymentMode != 'CASH') 
            {
                $tradeChq = new TradeChequeDtl;
                $tradeChq->transaction_id = $transaction_id;
                $tradeChq->cheque_no      = $request->chequeNo;
                $tradeChq->cheque_date    = $request->chequeDate;
                $tradeChq->bank_name      = $request->bankName;
                $tradeChq->branch_name    = $request->branchName;
                $tradeChq->emp_details_id = $refUserId;
                $tradeChq->created_on     = $mTimstamp;
                $tradeChq->save();
                $mPaymentStatus = 2;
            } 
            if($mPaymentStatus==1 && $refLecenceData->document_upload_status =1 && $refLecenceData->pending_status=0 && !$refLevelData)
            {
                $refLecenceData->current_user_id = $refWorkflows['initiator']['id'];
                $level_insert = new TradeLevelPending;
                $level_insert->licence_id            = $licenceId;
                $level_insert->sender_user_type_id   = $refWorkflows['initiator']['id'];
                $level_insert->receiver_user_type_id = $refWorkflows['initiator']['forward_id'];
                $level_insert->sender_user_id        = $refUserId;
                $level_insert->save();
            }
            
            $provNo = $this->createProvisinalNo($mShortUlbName,$mWardNo,$licenceId);
            $refLecenceData->provisional_license_no = $provNo;
            $refLecenceData->payment_status         = $mPaymentStatus;
            $refLecenceData->save();
                            
            if($refNoticeDetails)
            {
                $this->updateStatusFine($refDenialId, $chargeData['notice_amount'], $licenceId,1); //update status and fineAmount                     
            }
            DB::commit();
            #----------End transaction------------------------
            #----------Response------------------------------
            $res['transactionId'] = $transaction_id;
            $res['paymentRecipt']= config('app.url')."/api/trade/paymentRecipt/".$licenceId."/".$transaction_id;
            return responseMsg(true,"",$res);            
            
        }
        catch(Exception $e)
        {
            DB::rollBack();
            return responseMsg(false,$e->getMessage(),$request->all());
        }
    }
   /**
    * 
    */
    public function readPaymentRecipt($id, $transectionId) # unauthorised  function
    { 
        try{
            // DB::enableQueryLog();
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
                            ->leftjoin(DB::raw("(SELECT STRING_AGG(owner_name,',') as owner_name,
                                                STRING_AGG(guardian_name,',') as guardian_name,
                                                STRING_AGG(mobile::text,',') as mobile,
                                                licence_id
                                            FROM active_licence_owners 
                                            WHERE licence_id = $id
                                                AND status = 1
                                            GROUP BY licence_id
                                            ) owner"),function($join){
                                                $join->on("owner.licence_id","=","active_licences.id");
                                            })
                            ->where('active_licences.id',$id)
                            ->first();
                            // dd(DB::getQueryLog());
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
                            ->leftjoin(DB::raw("(SELECT STRING_AGG(owner_name,',') as owner_name,
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
            $delay_fee=0;
            $denial_fee = 0; 
            foreach($penalty as $val )
            {
                if(strtoupper($val->head_name)==strtoupper("Delay Apply License"))
                {
                    $delay_fee = $val->amount;
                }
                elseif(strtoupper($val->head_name)==strtoupper("Denial Apply"))
                {
                    $denial_fee = $val->amount;
                }
                $pen+=$val->amount;
                
            }
            $transaction->rate = number_format(($transaction->paid_amount - $pen),2);
            $transaction->delay_fee = $delay_fee;
            $transaction->denial_fee = $denial_fee;
            $transaction->paid_amount_in_words = getIndianCurrency($transaction->paid_amount);
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
    
    public function documentUpload(Request $request)
    {
        $refUser = Auth()->user();
        $refUserId = $refUser->id;
        $refUlbId = $refUser->ulb_id;
        $refLicence = null;
        $refOwneres = (array)null;
        $mUploadDocument = (array)null;
        $mDocumentsList  = (array)null;
        $mItemName="";
        $mCods = "";
        try{
            $licenceId = $request->id;
            if(!$licenceId)
            {
                throw new Exception("Licence Id Required");
            }
            $refLicence = $this->getLicenceById($licenceId);
            if(!$refLicence)
            {
                throw new Exception("Data Not Found");
            }
            elseif($refLicence->doc_verify_date)
            {
                throw new Exception("Document Verified You Can Not Upload Documents");
            }
            if($refLicence->nature_of_bussiness)
            {
                $items = $this->getLicenceItemsById($refLicence->nature_of_bussiness);                
                foreach($items as $val)
                {
                    $mItemName .= $val->trade_item.",";
                    $mCods .= $val->trade_code.",";                    
                }
                $mItemName= trim($mItemName,',');
                $mCods= trim($mCods,',');
            }
            $refLicence->items = $mItemName;
            $refLicence->items_code = $mCods;
            $refOwneres = $this->getOwnereDtlByLId($licenceId);
            $mUploadDocument = $this->getLicenceDocuments($licenceId);
            
            $mDocumentsList = $this->getDocumentTypeList($refLicence);                 
            foreach($mDocumentsList as $val)
            {   
                $data["documentsList"][$val->doc_for] = $this->getDocumentList($val->doc_for,$refLicence->application_type_id,$val->show);
                $data["documentsList"][$val->doc_for]["is_mandatory"] = $val->is_mandatory;
            }
            if($refLicence->application_type_id==1)
            {                
                $data["documentsList"]["Identity Proof"] = $this->getDocumentList("Identity Proof",$refLicence->application_type_id,0);
                $data["documentsList"]["Identity Proof"]["is_mandatory"] = 1;
            }
            $doc = (array) null;
            foreach($data["documentsList"] as $key => $val)
            {
                if($key == "Identity Proof")
                {
                    continue;
                }
                $data["documentsList"][$key]["doc"] = $this->check_doc_exist($licenceId,$key);
                if(isset($data["documentsList"][$key]["doc"]["document_path"]))
                {
                    $data["documentsList"][$key]["doc"]["document_path"] = !empty(trim($data["documentsList"][$key]["doc"]["document_path"]))?storage_path('app/public/' . $data["documentsList"][$key]["doc"]["document_path"]):null;

                }
            } 
            if($refLicence->application_type_id==1)
            {
                foreach($refOwneres as $key=>$val)
                {
                   
                    $refOwneres[$key]["Identity Proof"] = $this->check_doc_exist_owner($licenceId,$val->id);
                    if(isset($refOwneres[$key]["Identity Proof"]["document_path"]))
                    {
                        $refOwneres[$key]["Identity Proof"]["document_path"] = !empty(trim($refOwneres[$key]["Identity Proof"]["document_path"]))?storage_path('app/public/' . $refOwneres[$key]["Identity Proof"]["document_path"]):null;
    
                    }
                    $refOwneres[$key]["image"] = $this->check_doc_exist_owner($licenceId,$val->id,0);
                    if(isset( $refOwneres[$key]["image"]["document_path"]))
                    {
                        $refOwneres[$key]["image"]["document_path"] = !empty(trim($refOwneres[$key]["image"]["document_path"]))?storage_path('app/public/' . $refOwneres[$key]["image"]["document_path"]):null;
    
                    }
                }         

            }
            $data["licence"] = $refLicence;
            $data["owneres"] = $refOwneres;
            $data["uploadDocument"] = $mUploadDocument;
            if($request->getMethod()=="GET")
            {
                return responseMsg(true,"",$data);

            }
            if($request->getMethod()=="POST")
            {
                DB::beginTransaction();
                $rules = [];
                $message = [];
                $sms = "";
                # Upload Document 
                if(isset($request->btn_doc_path))
                {              
                    $cnt=$request->btn_doc_path;
                    $rules = [
                            'doc_path'.$cnt=>'required|max:30720]|mimes:pdf,jpg,jpeg',
                            'doc_path_mstr_id'.$cnt.''=>'required|int',
                            'doc_path_for'.$cnt =>"required|string",
                        ];                         
                    $validator = Validator::make($request->all(), $rules, $message);                    
                    if ($validator->fails()) {                        
                        return responseMsg(false, $validator->errors(),$request->all());
                    }                
                    $file = $request->file('doc_path'.$cnt);
                    $doc_for = "doc_path_for$cnt";
                    $doc_mstr_id = "doc_path_mstr_id$cnt";
                    if ($file->IsValid())
                    { 
                        if ($app_doc_dtl_id = $this->check_doc_exist($licenceId,$request->$doc_for))
                        {                                                          
                            $delete_path = storage_path('app/public/'.$app_doc_dtl_id['document_path']);
                            if (file_exists($delete_path)) 
                            {   
                                unlink($delete_path);
                            }
                            $newFileName = $app_doc_dtl_id['id'];

                            $file_ext = $data["exten"] = $file->getClientOriginalExtension();
                            $fileName = "licence_doc/$newFileName.$file_ext";
                            $filePath = $this->uplodeFile($file,$fileName);
                            $app_doc_dtl_id->document_path =  $filePath;
                            $app_doc_dtl_id->document_id =  $request->$doc_mstr_id;
                            $app_doc_dtl_id->save();
                            $sms .= "\n".$app_doc_dtl_id->doc_for." Update Successfully";

                        }
                        else
                        {
                            $licencedocs = new TradeLicenceDocument;
                            $licencedocs->licence_id = $licenceId;
                            $licencedocs->doc_for    = $request->$doc_for;
                            $licencedocs->document_id = $request->$doc_mstr_id;
                            $licencedocs->emp_details_id = $refUserId;
                            
                            $licencedocs->save();
                            $newFileName = $licencedocs->id;
                            
                            $file_ext = $data["exten"] = $file->getClientOriginalExtension();
                            $fileName = "licence_doc/$newFileName.$file_ext";
                            $filePath = $this->uplodeFile($file,$fileName);
                            $licencedocs->document_path =  $filePath;
                            $licencedocs->save();
                            $sms .= "\n". $licencedocs->doc_for." Upload Successfully";

                        }                         

                    }
                    else
                    {
                        return responseMsg(false, "something errors in Document Uploades",$request->all());
                    }
                    
                }
                
                $owners = adjtoArray($refOwneres);
                # Upload Owner Document Id Proof
                if(isset($request->btn_doc_path_owner))
                { 
                    $cnt_owner=$request->btn_doc_path_owner;                    
                    $rules = [
                            'id_doc_path_owner'.$cnt_owner =>'required|max:30720|mimes:pdf',
                            // 'idproof'.$cnt_owner.''=>'required',
                            'id_doc_mstr_id'.$cnt_owner =>"required|int",
                            "id_owner_id"=>"required|int",
                            "id_doc_for".$cnt_owner =>"required|string",
                        ];
                        
                    $validator = Validator::make($request->all(), $rules, $message);                    
                    if ($validator->fails()) {
                        return responseMsg(false, $validator->errors(),$request->all());
                    }
                    $req_owner_id = $request->id_owner_id;
                    $woner_id = array_filter($owners,function($val)use($req_owner_id){
                            return $val['id']==$req_owner_id;
                    }); 
                    $woner_id = array_values($woner_id)[0]??[];
                    if(!$woner_id)
                    {
                        throw new Exception("Invalide Owner Id given!!!");
                    }                    
                    $file = $request->file('id_doc_path_owner'.$cnt_owner);
                    // $idproof = "idproof$cnt_owner";
                    $doc_mstr_id = "id_doc_mstr_id$cnt_owner";
                    $doc_for = "id_doc_for$cnt_owner";                    
                    if ($file->IsValid() )
                    {
                        if ($app_doc_dtl_id = $this->check_doc_exist_owner($licenceId,$request->id_owner_id))
                        {                                
                            $delete_path = storage_path('app/public/'.$app_doc_dtl_id['document_path']);
                            if (file_exists($delete_path)) 
                            { 
                                unlink($delete_path);
                            }

                            $newFileName = $app_doc_dtl_id['id'];

                            $file_ext = $data["exten"] = $file->getClientOriginalExtension();
                            $fileName = "licence_doc/$newFileName.$file_ext";
                            $filePath = $this->uplodeFile($file,$fileName);
                            $app_doc_dtl_id->document_path =  $filePath;
                            $app_doc_dtl_id->document_id =  $request->$doc_mstr_id;
                            $app_doc_dtl_id->save();
                            $sms .= "\n". $app_doc_dtl_id->doc_for." Update Successfully";
                        }                            
                        else 
                        {
                            $licencedocs = new TradeLicenceDocument;
                            $licencedocs->licence_id = $licenceId;
                            $licencedocs->doc_for    = $request->$doc_for;
                            $licencedocs->licence_owner_dtl_id =$request->id_owner_id;
                            $licencedocs->document_id = $request->$doc_mstr_id;
                            $licencedocs->emp_details_id = $refUserId;
                            
                            $licencedocs->save();
                            $newFileName = $licencedocs->id;

                            $file_ext = $data["exten"] = $file->getClientOriginalExtension();
                            $fileName = "licence_doc/$newFileName.$file_ext";
                            $filePath = $this->uplodeFile($file,$fileName);
                            $licencedocs->document_path =  $filePath;
                            $licencedocs->save();
                            $sms .= "\n". $licencedocs->doc_for." Upload Successfully";
                            
                        }
                    } 
                    else 
                    {
                        return responseMsg(false, "something errors in Document Uploades",$request->all());
                    }
                     
                    
                } 
                # owner image upload hear 
                if(isset($request->btn_doc_path_owner_img))
                {                    
                    $cnt_owner = $request->btn_doc_path_owner_img;                    
                    $rules = [
                            "photo_doc_path_owner$cnt_owner"=>'required|max:30720|mimes:pdf,png,jpg,jpeg',                            
                            'photo_doc_for'.$cnt_owner.''=>'required',
                            "photo_owner_id"=>"required|int",
                        ];
                    $validator = Validator::make($request->all(), $rules, $message);                    
                    if ($validator->fails()) {
                        return responseMsg(false, $validator->errors(),$request->all());
                    } 
                    $req_owner_id = $request->photo_owner_id;
                    $woner_id = array_filter($owners,function($val)use($req_owner_id){
                            return $val['id']==$req_owner_id;
                    }); 
                    $woner_id = array_values($woner_id)[0]??[];
                    if(!$woner_id)
                    {
                        throw new Exception("Invalide Owner Id given!!!");
                    }
                    $file = $request->file('photo_doc_path_owner'.$cnt_owner);
                    $doc_for = "photo_doc_for$cnt_owner";
                    if ($file->IsValid())
                    {  
                        if ($app_doc_dtl_id = $this->check_doc_exist_owner($licenceId,$request->photo_owner_id,0))
                        {
                            $delete_path = storage_path('app/public/'.$app_doc_dtl_id['document_path']);
                            if (file_exists($delete_path)) 
                            { 
                                unlink($delete_path);
                            }

                            $newFileName = $app_doc_dtl_id['id'];
                            $file_ext = $data["exten"] = $file->getClientOriginalExtension();
                            $fileName = "licence_doc/$newFileName.$file_ext";
                            $filePath = $this->uplodeFile($file,$fileName);
                            $app_doc_dtl_id->document_path =  $filePath;
                            $app_doc_dtl_id->document_id =  0;
                            $app_doc_dtl_id->save();
                            $sms .= "\n". $app_doc_dtl_id->doc_for." Update Successfully";
                        }
                        
                        else
                        {
                            $licencedocs = new TradeLicenceDocument;
                            $licencedocs->licence_id = $licenceId;
                            $licencedocs->doc_for    = $request->$doc_for;
                            $licencedocs->licence_owner_dtl_id =$request->photo_owner_id;
                            $licencedocs->document_id =0;
                            $licencedocs->emp_details_id = $refUserId;
                            
                            $licencedocs->save();
                            $newFileName = $licencedocs->id;

                            $file_ext = $data["exten"] = $file->getClientOriginalExtension();
                            $fileName = "licence_doc/$newFileName.$file_ext";
                            $filePath = $this->uplodeFile($file,$fileName);
                            $licencedocs->document_path =  $filePath;
                            $licencedocs->save();
                            $sms .= "\n". $licencedocs->doc_for." Upload Successfully";
                        }                                

                    } 
                    else 
                    {
                        return responseMsg(false, "something errors in Document Uploades",$request->all());
                    }              
                }                 
                DB::commit();
                return responseMsg(true, $sms,"");
            }
        }
        catch(Exception $e)
        {
            return responseMsg(false,$e->getMessage(),$request->all());
        }
    }
    public function documentVirify(Request $request)
    {
        $user = Auth()->user();
        $user_id = $user->id;
        $ulb_id = $user->ulb_id;
        $redis = new Redis;
        $user_data = json_decode($redis::get('user:' . $user_id), true);
        $workflow_id = Config::get('workflow-constants.TRADE_WORKFLOW_ID');
        $workflows = $this->_parent->iniatorFinisher($user_id,$ulb_id,$workflow_id);   
        $roll_id =  $this->_parent->getUserRoll($user_id, $ulb_id,$workflow_id)->role_id??null;
        $apply_from = $this->_parent->userType();  
        $licenceId = $request->id;
        $rules = [];
        $message = [];
        try{
            if(!$roll_id || strtoupper($apply_from)!="DA")
            {
                throw new Exception("You are Not Authorized For Document Verify");
            }
            if(!$licenceId)
            {
                throw new Exception("Data Not Found");
            }
            $licence = $this->getLicenceById($licenceId);
            if(!$licence)
            {
                throw new Exception("Data Not Found");
            }
            $item_name="";
            $cods = "";
            if($licence->nature_of_bussiness)
            {
                $items = $this->getLicenceItemsById($licence->nature_of_bussiness);                
                foreach($items as $val)
                {
                    $item_name .= $val->trade_item.",";
                    $cods .= $val->trade_code.",";                    
                }
                $item_name= trim($item_name,',');
                $cods= trim($cods,',');
            }
            $licence->items = $item_name;
            $licence->items_code = $cods;
            $owneres = $this->getOwnereDtlByLId($licenceId);
            // $time_line = $this->getTimelin($licenceId);
            $documents = $this->getLicenceDocuments($licenceId);
            $documentsList = $this->getDocumentTypeList($licence);                 
            foreach($documentsList as $val)
            {   
                $data["documentsList"][$val->doc_for] = $this->getDocumentList($val->doc_for,$licence->application_type_id,$val->show);
                $data["documentsList"][$val->doc_for]["is_mandatory"] = $val->is_mandatory;
            }
            if($licence->application_type_id==1)
            {                
                $data["documentsList"]["Identity Proof"] = $this->getDocumentList("Identity Proof",$licence->application_type_id,0);
                $data["documentsList"]["Identity Proof"]["is_mandatory"] = 1;
            }
            foreach($data["documentsList"] as $key =>$val)
            {
                if($key == "Identity Proof")
                {
                    continue;
                }     
                $data["documentsList"][$key]["doc"] = $this->check_doc_exist($licenceId,$key);
                if(isset($data["documentsList"][$key]["doc"]["document_path"]))
                {
                    $data["documentsList"][$key]["doc"]["document_path"] = !empty(trim($data["documentsList"][$key]["doc"]["document_path"]))?storage_path('app/public/' . $data["documentsList"][$key]["doc"]["document_path"]):null;

                }
            }
            if($licence->application_type_id==1)
            {
                foreach($owneres as $key=>$val)
                {                   
                    $data["documentsList"]["Identity Proof"]["doc"] =  $this->check_doc_exist_owner($licenceId,$val->id);
                    if(isset($data["documentsList"]["Identity Proof"]["doc"]["document_path"]))
                    {
                        $data["documentsList"]["Identity Proof"]["doc"]["document_path"] = !empty(trim($data["documentsList"]["Identity Proof"]["doc"]["document_path"]))?storage_path('app/public/' . $data["documentsList"]["Identity Proof"]["doc"]["document_path"]):null;
    
                    }
                    $data["documentsList"]["image"]["doc"] = $this->check_doc_exist_owner($licenceId,$val->id,0);
                    if(isset($data["documentsList"]["image"]["doc"]["document_path"]))
                    {
                        $data["documentsList"]["image"]["doc"]["document_path"] = !empty(trim($data["documentsList"]["image"]["doc"]["document_path"]))?storage_path('app/public/' . $data["documentsList"]["image"]["doc"]["document_path"]):null;
    
                    }
                }         

            }
            $data["licence"] = $licence;
            $data["owneres"] = $owneres;
            if($request->getMethod()=="GET")
            {
                return responseMsg(true,"",remove_null($data));
            }
            elseif($request->getMethod()=="POST")
            {
                $rules = [];
                $message = [];
                $nowdate = Carbon::now()->format('Y-m-d'); 
                $timstamp = Carbon::now()->format('Y-m-d H:i:s');                
                $regex = '/^[a-zA-Z1-9][a-zA-Z1-9\.\s]+$/';                
                $rules = [
                    'btn'=>'required|in:virify,reject',
                    'id'=>'required',
                ];
                $status = 1;
                if($request->btn=="reject")
                {
                    $status =2;
                    $rules["comment"]="required|regex:$regex|min:10";
                }
                $validator = Validator::make($request->all(), $rules, $message);                    
                if ($validator->fails()) {
                    return responseMsg(false, $validator->errors(),$request->all());
                }
                $level_data = $this->getLevelData($licenceId);
                if(!$level_data || $level_data->receiver_user_type_id != $roll_id)
                {
                    throw new Exception("You Are Not Authorized For This Action");
                }
                DB::beginTransaction();                
                $tradeDoc = TradeLicenceDocument::find($request->id);
                $tradeDoc->verify_status = $status;
                $tradeDoc->remarks = ($status==2?$request->comment:null);
                $tradeDoc->verified_by_emp_id = $user_id;
                $tradeDoc->lvl_pending_id = $level_data->id;
                DB::commit();
                $sms = $tradeDoc->doc_for." ".strtoupper($request->btn);
                return responseMsg(true,$sms,"");
            }           
           
        }
        catch(Exception $e)
        {
            return responseMsg(false,$e->getMessage(),$request->all());
        }
    }    
    public function readLicenceDtl($id)
    {
        try{
            $mUserType      = $this->_parent->userType();
            $refApplication = $this->getLicenceById($id);
            $mItemName      ="";
            $mCods          = "";
            if($refApplication->nature_of_bussiness)
            {
                $items = $this->getLicenceItemsById($refApplication->nature_of_bussiness);                
                foreach($items as $val)
                {
                    $mItemName  .= $val->trade_item.",";
                    $mCods      .= $val->trade_code.",";                    
                }
                $mItemName= trim($mItemName,',');
                $mCods= trim($mCods,',');
            }
            $refApplication->items      = $mItemName;
            $refApplication->items_code = $mCods;
            $refOwnerDtl                = $this->getOwnereDtlByLId($id);
            $refTransactionDtl          = $this->readTranDtl($id);
            $refTimeLine                = $this->getTimelin($id);
            $refUploadDocuments         = $this->getLicenceDocuments($id)->map(function($val){
                                                $val->document_pat = !empty(trim($val->document_path))? Storage::url("1.pdf"):"";
                                                return $val;
                                            });

            $data['licenceDtl']     = $refApplication;
            $data['ownerDtl']       = $refOwnerDtl;
            $data['transactionDtl'] = $refTransactionDtl;
            $data['remarks']        = $refTimeLine;
            $data['documents']      = $refUploadDocuments;            
            $data["userType"]       = $mUserType;
            $data = remove_null($data);
            return responseMsg(true,"",$data);
            
        }
        catch(Exception $e)
        {
            return responseMsg(false,$e->getMessage(),'');
        }
    }
    /**
     * | Get Notice Data
     */
    public function readDenialdtlbyNoticno(Request $request)
    {
        $data = (array)null;
        $refUser = Auth()->user();
        $refUserId = $refUser->id;
        $refUlbId = $refUser->ulb_id;
        $mNoticeNo = null;
        $mNowDate = Carbon::now()->format('Y-m-d'); // todays date
        try 
        {
            $rules=[
                "noticeNo"=>"required|string",
            ];
            $validator = Validator::make($request->all(), $rules, ); 
            if ($validator->fails()) {                        
                return responseMsg(false, $validator->errors(),$request->all());
            }
            $mNoticeNo = $request->noticeNo;

            $refDenialDetails = $this->getDenialFirmDetails($refUlbId,strtoupper(trim($mNoticeNo)));
            if ($refDenialDetails) 
            {
                $notice_date = Carbon::parse($refDenialDetails->noticedate)->format('Y-m-d'); //notice date
                $denialAmount = $this->getDenialAmountTrade($notice_date, $mNowDate);
                $data['denialDetails'] = $refDenialDetails;
                $data['denialAmount'] = $denialAmount;
                return responseMsg(true,"",$data);
            } 
            else 
            {
                $response = "no Data";
                return responseMsg(false,$response,$request->all());
            }
        } 
        catch (Exception $e) 
        {
            return responseMsg(false,$e->getMessage(),$request->all());
        }
        
    }

    public function getPaybleAmount(Request $request)
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
            if(isset($request->noticeDate) && $request->noticeDate)
            {
                $rules["noticeDate"] = "date";
            }

            $validator = Validator::make($request->all(), $rules, $message);
            if ($validator->fails()) {
                return responseMsg(false, $validator->errors(),$request->all());
            }
            $data['application_type_id'] = Config::get("TradeConstant.APPLICATION-TYPE.".$request->applicationType);
            if(!$data['application_type_id'])
            {
                throw new Exception("Invalide Application Type");
            }
            $mNatureOfBussiness = array_map(function($val){
                return $val['id'];
            },$request->natureOfBusiness);

            $mNatureOfBussiness = implode(',', $mNatureOfBussiness);

            $data["areaSqft"] = $request->areaSqft;
            $data['curdate'] =Carbon::now()->format('Y-m-d');
            $data["firmEstdDate"] = $request->firmEstdDate;
            $data["tobacco_status"] =  $request->tocStatus;
            $data['noticeDate'] =  $request->noticeDate??null;
            $data["licenseFor"] = $request->licenseFor;
            $data["nature_of_business"] = $mNatureOfBussiness; 
            $data = $this->cltCharge($data);
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

    public function isvalidateSaf(Request $request)
    {
        $user = Auth()->user();
        $ulb_id = $user->ulb_id;
        if ($request->getMethod() == "POST") 
        {
            $rules=[
                "safNo"=>"required|string",
            ];
            $validator = Validator::make($request->all(), $rules, ); 
            if ($validator->fails()) {                        
                return responseMsg(false, $validator->errors(),$request->all());
            } 
            $data = array();
            $inputs = $request->all();
            $saf_no = $inputs['safNo']??null;
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

    public function isvalidateHolding(Request $request)
    {
        $user = Auth()->user();
        $user_id = $user->id;
        $ulb_id = $user->ulb_id;
        if ($request->getMethod() == "POST") 
        {
            $rules=[
                "holdingNo"=>"required|string",
            ];
            $validator = Validator::make($request->all(), $rules, ); 
            if ($validator->fails()) {                        
                return responseMsg(false, $validator->errors(),$request->all());
            } 

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
            $message["licenceNo.required"] = "Licence No Required";
            $rules["applicationType"] = "required:int";
            $message["applicationType.required"] = "Application Type Id Is Required";
            
            $validator = Validator::make($request->all(), $rules, $message);
            if ($validator->fails()) {
                return responseMsg(false, $validator->errors(),$request->all());
            }
            $application_type_id = $request->applicationType ;
            if(!in_array($application_type_id,[1,2,3,4]))
            {
                throw new Exception("Invalid Application Type Supplied");
            }
            elseif($application_type_id==1)
            {
                throw new Exception("You Can Not Apply New Licence"); 
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
                    ->where('active_licences.update_status',0)
                    ->first();
           if(!$data)
           {
                throw new Exception("No Data Found");
           }
        //    elseif($application_type_id==3 && $data->application_type_id != 4)
        //    {
        //         throw new Exception("Please Apply Surrender Before Amendment");
        //    }
           elseif($data->valid_upto > $nextMonth && $application_type_id!=4)
           {
                throw new Exception("Licence Valid Upto ".$data->valid_upto);
           } 
           elseif($data->pending_status!=5)
           {
                throw new Exception("Application Already Applied. Please Track  ".$data->application_no);
           }
           if($application_type_id==4 && $data->valid_upto < Carbon::now()->format('Y-m-d')) 
           {
                throw new Exception("You Can Not Apply Surrender. Application No: ".$data->application_no." Of Licence No: ".$data->license_no." Expired On ".$data->valid_upto.".");
           }          
           return responseMsg(true,"",remove_null($data));
        }
        catch(Exception $e)
        {
            return responseMsg(false,$e->getMessage(),$request->all());
        }
        
    }
    public function readApplication(Request $request)
    {
        try{
            $user = Auth()->user();
            $ulbId = $user->ulb_id;            
            $inputs = $request->all();
            $rules =[
                "entityValue"=>"required",
                "entityName"=>"required",
            ];
            $validator = Validator::make($request->all(), $rules, );
            if ($validator->fails()) 
            {
                return responseMsg(false, $validator->errors(),$request->all());
            }   
            // DB::enableQueryLog();          
            $licence = ActiveLicence::select("active_licences.id",
                                            "active_licences.application_no",
                                            "active_licences.provisional_license_no",
                                            "active_licences.license_no",
                                            "active_licences.firm_name",
                                            "active_licences.apply_date",
                                            "active_licences.apply_from",
                                            "active_licences.valid_upto",
                                            "owner.owner_name",
                                            "owner.guardian_name",
                                            "owner.mobile_no",
                                            "owner.email_id",
                                            )                        
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
                        ->where("active_licences.ulb_id",$ulbId);
            if(isset($inputs['entityValue']) && trim($inputs['entityValue']) && isset($inputs['entityName']) && trim($inputs['entityName']))
            {
                $key = trim($inputs['entityValue']);
                $column = strtoupper(trim($inputs['entityName']));
                $licence = $licence->where(function ($query) use ($key, $column) {
                    if($column == "FIRM")
                    {
                        $query->orwhere('active_licences.firm_name', 'ILIKE', '%' . $key . '%');
                    }
                    elseif($column == "APPLICATION")
                    {
                        $query->orwhere('active_licences.application_no', 'ILIKE', '%' . $key . '%');
                    }
                    elseif($column == "LICENSE")
                    {
                        $query->orwhere('active_licences.license_no', 'ILIKE', '%' . $key . '%');
                    }
                    elseif($column == "PROVISIONAL")
                    {
                        $query->orwhere('active_licences.provisional_license_no', 'ILIKE', '%' . $key . '%');
                    }
                    elseif($column == "OWNER")
                    {
                        $query->orwhere('owner.owner_name', 'ILIKE', '%' . $key . '%');
                    }
                    elseif($column == "GUARDIAN")
                    {
                        $query->orwhere('owner.guardian_name', 'ILIKE', '%' . $key . '%');
                    }
                    elseif($column == "MOBILE")
                    {
                        $query->orwhere('owner.mobile_no', 'ILIKE', '%' . $key . '%');
                    }
                    else
                    {
                        $query->orwhere('active_licences.application_no', 'ILIKE', '%' . $key . '%');
                    }
                        
                });
            }
            $licence = $licence
                      ->orderBy("active_licences.id","DESC")
                      ->limit(10)
                      ->get();            
            if($licence->isEmpty())
            {  
                throw new Exception("Application Not Found");
            }
            $data = [             
                "licence"=>$licence,
            ] ;           
            return responseMsg(true, "", $data);

        }
        catch(Exception $e)
        {
            return responseMsg(false, $e->getMessage(), $request->all());
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
            $mUserType = $this->_parent->userType();
            $ward_permission = $this->_parent->WardPermission($user_id);           
            $role = $this->_parent->getUserRoll($user_id,$ulb_id,$workflowId->wf_master_id); 
            if (!$role) 
            {
                throw new Exception("You Are Not Authorized For This Action");
            } 
            if($role->is_initiator )    //|| in_array(strtoupper($apply_from),["JSK","SUPER ADMIN","ADMIN","TL","PMU","PM"])
            {
                $ward_permission = $this->_modelWard->getAllWard($ulb_id)->map(function($val){
                    $val->ward_no = $val->ward_name;
                    return $val;
                });
                $ward_permission = adjToArray($ward_permission);
                $joins = "leftjoin";
            }
            else
            {
                $joins = "join";
            }

            $ward_ids = array_map(function ($val) {
                return $val['id'];
            }, $ward_permission);

            $role_id = $role->role_id;   
            $inputs = $request->all();  
            // DB::enableQueryLog();          
            $licence = ActiveLicence::select("active_licences.id",
                                            "active_licences.application_no",
                                            "active_licences.provisional_license_no",
                                            "active_licences.license_no",
                                            "active_licences.document_upload_status",
                                            "active_licences.payment_status",
                                            "active_licences.firm_name",
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
                            ->where("trade_level_pendings.verification_status",0);
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
            $data = [
                "wardList"=>$ward_permission,                
                "licence"=>$licence,
            ] ;           
            return responseMsg(true, "", $data);
            
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
            $apply_from = $this->_parent->userType();
            $ward_permission = $this->_parent->WardPermission($user_id);
            $role = $this->_parent->getUserRoll($user_id,$ulb_id,$workflowId->wf_master_id);           
            if (!$role) 
            {
                throw new Exception("You Are Not Authorized");
            }
            if($role->is_initiator || in_array(strtoupper($apply_from),["JSK","SUPER ADMIN","ADMIN","TL","PMU","PM"]))
            {
                $joins = "leftjoin";
                $ward_permission = $this->_modelWard->getAllWard($ulb_id)->map(function($val){
                    $val->ward_no = $val->ward_name;
                    return $val;
                });
                $ward_permission = adjToArray($ward_permission);
            }
            else
            {
                $joins = "join";
            }
            $role_id = $role->role_id;

            $ward_ids = array_map(function ($val) {
                return $val['id'];
            }, $ward_permission);
            $inputs = $request->all();
            // DB::enableQueryLog();
            $licence = ActiveLicence::select("active_licences.id",
                                            "active_licences.application_no",
                                            "active_licences.provisional_license_no",
                                            "active_licences.license_no",
                                            "active_licences.firm_name",
                                            "active_licences.apply_date",
                                            "active_licences.apply_from",
                                            "owner.owner_name",
                                            "owner.guardian_name",
                                            "owner.mobile_no",
                                            "owner.email_id"
                                            )
                        ->$joins("trade_level_pendings",function($join) use($role_id){
                            $join->on("trade_level_pendings.licence_id","active_licences.id")
                            ->where("trade_level_pendings.sender_user_type_id",$role_id)
                            ->where("trade_level_pendings.status",1)
                            ->where("trade_level_pendings.verification_status",0);
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
            if(!$role->is_initiator)
            {
                $licence = $licence->whereIn('active_licences.pending_status',[2]);
            }
            else
            {
                $licence = $licence->whereIn('active_licences.pending_status',[2]);
            }
            $licence = $licence
                        ->whereIn('active_licences.ward_mstr_id', $ward_ids)
                        ->get();
                        // dd(DB::getQueryLog());
            $data = [
                "wardList"=>$ward_permission,                
                "licence"=>$licence,
            ] ; 
            return responseMsg(true, "", $data);
            
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
            $role = $this->_parent->getUserRoll($user_id,$ulb_id,$workflowId->wf_master_id);  
            $init_finish = $this->_parent->iniatorFinisher($user_id,$ulb_id,$refWorkflowId);         
            if (!$role) 
            {
                throw new Exception("You Are Not Authorized");
            }
            $role_id = $role->role_id;
            $apply_from = $this->_parent->userType();            
            $rules = [
                // "receiverId" => "required|int",
                "btn" => "required|in:btc,forward,backward",
                "licenceId" => "required|int",
                "comment" => "required|min:10|regex:$regex",
            ];
            $message = [
                "btn.in"=>"Button Value must be In BTC,FORWARD,BACKWARD",
                "comment.required" => "Comment Is Required",
                "comment.min" => "Comment Length can't be less than 10 charecters",
            ];
            $validator = Validator::make($request->all(), $rules, $message);
            if ($validator->fails()) {
                return responseMsg(false, $validator->errors(), $request->all());
            }
            if($role->is_initiator && in_array($request->btn,['btc','backward']))
            {
               throw new Exception("Initator Can Not send Back The Application");
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
                throw new Exception("You are not authorised for this action");
            }
            elseif(!$role->is_initiator && ! $level_data)
            {
                throw new Exception("Data Not Found On Level. Please Contact Admin!!!...");
            }  
            elseif(isset($level_data->receiver_user_type_id) && $level_data->receiver_user_type_id != $role->role_id)
            {
                throw new Exception("You Have Already Taken The Action On This Application");
            }           
            if(!$init_finish)
            {
                throw new Exception("Full Work Flow Not Desigen Properly. Please Contact Admin !!!...");
            }
            elseif(!$init_finish["initiator"])
            {
                throw new Exception("Initiar Not Available. Please Contact Admin !!!...");
            }
            elseif(!$init_finish["finisher"])
            {
                throw new Exception("Finisher Not Available. Please Contact Admin !!!...");
            }
            
            // dd($role);
            if($request->btn=="forward" && !$role->is_finisher && !$role->is_initiator)
            {
                $sms ="Application Forwarded To ".$role->forword_name;
                $receiver_user_type_id = $role->forward_role_id;
            }
            elseif($request->btn=="backward" && !$role->is_initiator)
            {
                $sms ="Application Forwarded To ".$role->backword_name;
                $receiver_user_type_id = $role->backward_role_id;
                $licence_pending = $init_finish["initiator"]['id']==$role->backward_role_id ? 3 : $licence_pending;
            }
            elseif($request->btn=="btc" && !$role->is_initiator)
            {
                $licence_pending = 3;
                $sms ="Application Forwarded To ".$init_finish["initiator"]['role_name'];
                $receiver_user_type_id = $init_finish["initiator"]['id'];
            } 
            elseif($request->btn=="forward" && !$role->is_initiator && $level_data)
            {
                $sms ="Application Forwarded ";
                $receiver_user_type_id = $level_data->sender_user_type_id;
            }
            elseif($request->btn=="forward" && $role->is_initiator && !$level_data)
            {
                $licence_pending = 2;
                $sms ="Application Forwarded To ".$role->forword_name;
                $receiver_user_type_id = $role->forward_role_id;

            } 
            elseif($request->btn=="forward" && $role->is_initiator && $level_data)
            {
                $licence_pending = 2;
                $sms ="Application Forwarded To ";
                $receiver_user_type_id = $level_data->sender_user_type_id;

            }
            if($request->btn=="forward" && $role->is_initiator)
            {
                $doc = (array) null;
                $owneres = $this->getOwnereDtlByLId($licenc_data->id);
                $documentsList = $this->getDocumentTypeList($licenc_data);  
                if($licenc_data->payment_status!=1)
                {
                    $doc[]="Payment is Not Clear";
                }               
                foreach($documentsList as $val)
                {   
                    $data["documentsList"][$val->doc_for] = $this->getDocumentList($val->doc_for,$licenc_data->application_type_id,$val->show);
                    $data["documentsList"][$val->doc_for]["is_mandatory"] = $val->is_mandatory;
                }
                if($licenc_data->application_type_id==1)
                {                
                    $data["documentsList"]["Identity Proof"] = $this->getDocumentList("Identity Proof",$licenc_data->application_type_id,0);
                    $data["documentsList"]["Identity Proof"]["is_mandatory"] = 1;
                }               
                foreach($data["documentsList"] as $key => $val)
                {
                    if($key == "Identity Proof")
                    {
                        continue;
                    }
                    $data["documentsList"][$key]["doc"] = $this->check_doc_exist($licenc_data->id,$key);
                    if(!isset($data["documentsList"][$key]["doc"]["document_path"]) && $data["documentsList"][$key]["is_mandatory"])
                    {
                        $doc[]=$key." Not Uploaded";

                    }
                } 
                if($licenc_data->application_type_id==1)
                {
                    foreach($owneres as $key=>$val)
                    {
                        $owneres[$key]["Identity Proof"] = $this->check_doc_exist_owner($licenc_data->id,$val->id);
                        if(!isset($owneres[$key]["Identity Proof"]["document_path"]) && $data["documentsList"]["Identity Proof"]["is_mandatory"])
                        {
                            $doc[]="Identity Proof Of ".$val->owner_name." Not Uploaded";
        
                        }
                    }         

                }
                // if($doc)
                // {   $err = "";
                //     foreach($doc as $val)
                //     {
                //         $err.="<li>$val</li>";
                //     }                
                //     throw new Exception($err);
                // }
            }           
            if($request->btn=="forward" && in_array(strtoupper($apply_from),["DA"]))
            {
                $docs = $this->getLicenceDocuments($request->licenceId);
                if(!$docs)
                {
                    throw new Exception("No Anny Document Found");
                }
                $docs = adjToArray($docs);
                $test = array_filter($docs,function($val){
                     if($val["verify_status"]!=1)
                     {
                        return True;
                     }
                });
                if($test)
                {
                    throw new Exception("All Document Are Not Verified");
                }

                
            }
            
            if(!$role->is_finisher && !$receiver_user_type_id)  
            {
                throw new Exception("Next Role Not Found !!!....");
            }

            DB::beginTransaction();
            if($level_data)
            {
                
                $level_data->verification_status = 1;
                $level_data->receiver_user_id =$user_id;
                $level_data->remarks =$request->comment;
                $level_data->forward_date =Carbon::now()->format('Y-m-d');
                $level_data->forward_time =Carbon::now()->format('H:s:i');
                $level_data->save();
            }
            if(!$role->is_finisher || in_array($request->btn,["backward","btc"]))
            {                
                $level_insert = new TradeLevelPending;
                $level_insert->licence_id = $licenc_data->id;
                $level_insert->sender_user_type_id = $role_id;
                $level_insert->receiver_user_type_id = $receiver_user_type_id;
                $level_insert->sender_user_id = $user_id;
                $level_insert->save();
                $licenc_data->current_user_id = $receiver_user_type_id;
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
                    $ward_no = UlbWardMaster::select("ward_name")
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
                    $licenc_data->current_user_id = $receiver_user_type_id;
                    $sms.=" Licence No ".$license_no;
            }
            if($request->btn=="forward" && $role->is_initiator)
            {
                $licenc_data->document_upload_status = 1;
            }
            if($request->btn=="forward" && in_array(strtoupper($apply_from),["DA"]))
            {   
                $nowdate = Carbon::now()->format('Y-m-d');            
                $licenc_data->doc_verify_date = $nowdate;
                $licenc_data->doc_verify_emp_details_id = $user_id;
            }
            $licenc_data->pending_status = $licence_pending;            
            $licenc_data->save();            
            DB::commit();
            return responseMsg(true, $sms, "");

        }
        catch(Exception $e)
        { 
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }
    public function provisionalCertificate($id) # unauthorised  function
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
    public function licenceCertificate($id) # unauthorised  function
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
    public function addDenail(Request $request)
    {
        $user = Auth()->user();
        $userId = $user->id;
        $ulbId = $user->ulb_id;
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
            $refWorkflowId = Config::get('workflow-constants.TRADE_WORKFLOW_ID');
            $workflowId = WfWorkflow::where('wf_master_id', $refWorkflowId)
                ->where('ulb_id', $ulbId)
                ->first();
            if (!$workflowId) 
            {
                throw new Exception("Workflow Not Available");
            }
            $role = $this->_parent->getUserRoll($userId,$ulbId,$workflowId->wf_master_id); 
            if (!$role) 
            {
                throw new Exception("You Are Not Authorized");
            } 
            $role_id = $role->role_id;  
            $userType = $this->_parent->userType();
            if(!in_array(strtoupper($userType),["TC","UTC"]))
            {
                throw new Exception("You Are Not Authorize For Apply Denial");
            }
            if($request->getMethod()=='GET')
            {
                $data['wardList'] = $this->_parent->WardPermission($userId);
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
                $rules["pinCode"]="required|digits:6";
                $rules["mobileNo"]="digits:10";
                $rules["comment"]="required|regex:$regex|min:10";
                $rules["document"]="required|mimes:pdf,jpg,jpeg,png|max:2048";
                $validator = Validator::make($request->all(), $rules, $message);
                if ($validator->fails()) {
                    return responseMsg(false, $validator->errors(),$request->all());
                }
                DB::beginTransaction();
                $denialConsumer = new TradeDenialConsumerDtl;
                $denialConsumer->firm_name  =$request->firmName;
                $denialConsumer->applicant_name =$request->ownerName;
                $denialConsumer->ward_id    =$request->wardNo;      
                $denialConsumer->ulb_id     =$ulbId;                  
                $denialConsumer->holding_no =$request->holdingNo;
                $denialConsumer->address    =$request->address;
                $denialConsumer->landmark   =$request->landmark;
                $denialConsumer->city       =$request->city;
                $denialConsumer->pincode    =$request->pinCode;
                $denialConsumer->license_no =$request->licenceNo??null;
                $denialConsumer->ip_address =$request->ip();
                $getloc = json_decode(file_get_contents("http://ipinfo.io/"));
                $coordinates = explode(",", $getloc->loc);
                $denialConsumer->latitude   = $coordinates[0]; // latitude
                $denialConsumer->longitude  = $coordinates[1]; // longitude
                if($request->mobileNo)
                {
                    $denialConsumer->mobileno = $request->mobileNo;
                }
                $denialConsumer->remarks = $request->comment;
                $denialConsumer->emp_details_id = $userId;
                $denialConsumer->save();
                $denial_id = $denialConsumer->id;
                
                if($denial_id)
                {   
                    $file = $request->file("document");
                    $file_ext = $data["exten"] = $file->getClientOriginalExtension();
                    $fileName = "denial_image/$denial_id.$file_ext";
                    $filePath = $this->uplodeFile($file,$fileName);
                    $data["filePath"] =  $filePath;
                    $data["file_url"]=config('file.url');
                    $data["upload_url"] = storage_path('app/public/' . $filePath);
                    
                    $denialConsumer->file_name = $filePath ;
                    $denialConsumer->save();
                    
                    $tradeMail = new TradeDenialMailDtl;
                    $tradeMail->denial_consumer_id = $denial_id;
                    $tradeMail->sender_id = $userId;
                    $tradeMail->sender_user_type_id = $role_id;
                    $tradeMail->receiver_user_type_id = 10; 
                    $tradeMail->remarks     = $request->comment;
                    $tradeMail->save();
                
                }
                DB::commit();

                return  responseMsg(true,"Denail Form Submitted Succesfully!",$data);
            }
            
        }
        catch(Exception $e)
        {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }

    /**
     * Only for EO (Exicutive Officer)
     */
    public function denialInbox(Request $request)
	{
        try
        {
            $data =(array)null;
            $user = Auth()->user();
            $user_id = $user->id;
            $ulb_id = $user->ulb_id;
            $workflow_id = Config::get('workflow-constants.TRADE_WORKFLOW_ID');
            $role_id = $this->_parent->getUserRoll($user_id, $ulb_id,$workflow_id)->role_id??-1;
            $mUserType = $this->_parent->userType();
            // dd($role_id);
            if(!in_array($role_id,[11]))
            {
                throw new Exception("You Are Not Authorized");
            }
            $nowdate = Carbon::now()->format('Y-m-d'); 
            $timstamp = Carbon::now()->format('Y-m-d H:i:s'); 

            $wardList = $this->_parent->WardPermission($user_id);
            $data['wardList'] = $wardList;
            $mailStatus = 1; 
            $ward_ids = array_map(function ($val) {
                return $val['id'];
            },$wardList);
            $inputs = $request->all(); 
            $denila_consumer = TradeDenialConsumerDtl::select("trade_denial_consumer_dtls.*",
                                    DB::raw("ulb_ward_masters.ward_name as ward_no ,
                                    trade_denial_mail_dtls.id as denil_mail_id
                                    ")
                                )
                                ->join("trade_denial_mail_dtls",function($join){
                                    $join->on("trade_denial_mail_dtls.denial_consumer_id","trade_denial_consumer_dtls.id")
                                    ->where("trade_denial_mail_dtls.status",1);
                                })
                                ->join("ulb_ward_masters","ulb_ward_masters.id","trade_denial_consumer_dtls.ward_id");
             
            if(isset($inputs['wardNo']) && trim($inputs['wardNo']) && $inputs['wardNo']!="ALL")
            {
                $ward_ids = $inputs["wardNo"];
            }
            if(isset($inputs['key']) && trim($inputs['key']))
            {
                $key = trim($inputs['key']);
                $denila_consumer = $denila_consumer->where(function ($query) use ($key) {
                    $query->orwhere('trade_denial_consumer_dtls.holding_no', 'ILIKE', '%' . $key . '%')
                        ->orwhere('trade_denial_consumer_dtls.firm_name', 'ILIKE', '%' . $key . '%')
                        ->orwhere("trade_denial_consumer_dtls.license_no", 'ILIKE', '%' . $key . '%')                                           
                        ->orwhere('trade_denial_consumer_dtls.applicant_name', 'ILIKE', '%' . $key . '%')
                        ->orwhere('trade_denial_consumer_dtls.mobile_no', 'ILIKE', '%' . $key . '%');
                });
            }
            if(isset($inputs['formDate']) && isset($inputs['toDate']) && trim($inputs['formDate']) && $inputs['toDate'])
            {
                $denila_consumer = $denila_consumer
                            ->whereBetween('trade_denial_consumer_dtls.created_on::date',[$inputs['formDate'],$inputs['formDate']]); 
            }
            $denila_consumer = $denila_consumer
                        ->whereIn("trade_denial_consumer_dtls.ward_id",$ward_ids)
                        ->where("trade_denial_consumer_dtls.ulb_id",$ulb_id)
                        ->where("trade_denial_consumer_dtls.status",1)
                        ->orderBy("trade_denial_consumer_dtls.created_on","DESC")
                        ->get();
            $data['denila_consumer'] =$denila_consumer;
            return responseMsg(false,"", remove_null($data));
        }
        catch(Exception $e)
        {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
        
	}
    /**
     * Apply Denail View Data And (Approve Or Reject) By EO
     * | @var data local data storage
     * |+ @var user  login user DATA 
     * |+ @var user_id login user ID
     * |+ @var ulb_id login user ULBID
     * |+ @var workflow_id owrflow id 19 for trade **Config::get('workflow-constants.TRADE_WORKFLOW_ID')
     * |+ @var role_id login user ROLEID **this->_parent->getUserRoll($user_id, $ulb_id,$workflow_id)->role_id??-1
     * | @var apply_from login user sort role name **$this->_parent->userType()
     * |
     * |+ @var denial_details  apply denial detail **this->getDenialDetailsByID($id,$ulb_id)
     * |+ @var denialID =  denial_details->id
     * |     
     */
    public function denialView($id,$mailID,Request $request)
    {
        try{
            $data =(array)null;
            $user = Auth()->user();
            $user_id = $user->id;
            $ulb_id = $user->ulb_id;
            $workflow_id = Config::get('workflow-constants.TRADE_WORKFLOW_ID');
            $role_id = $this->_parent->getUserRoll($user_id, $ulb_id,$workflow_id)->role_id??-1;
            $mUserType = $this->_parent->userType();

            $denial_details  = $this->getDenialDetailsByID($id,$ulb_id);
            $denialID =  $denial_details->id;
            if($denial_details->status ==5)
            {
                throw new Exception("Notice No Already Generated ".$denial_details->notice_no);
            }
            elseif($denial_details->status==4)
            {
                throw new Exception("Denial Request Rejected");
            }
            $denial_details->file_name = !empty(trim($denial_details->file_name))?storage_path('app/public/' . $denial_details->file_name):null;
            if($request->getMethod()=='GET')
            {
                $data["denial_details"] = $denial_details;
                return responseMsg(true, "",remove_null($data));
            }
            elseif($request->getMethod()=='POST')
            {
                $notic = new TradeDenialNotice;
                $denial_consumer = TradeDenialConsumerDtl::find($denialID);
                $denail_mail = TradeDenialMailDtl::where("denial_consumer_id",$denialID)->first();

                $nowdate = Carbon::now()->format('Y-m-d'); 
                $timstamp = Carbon::now()->format('Y-m-d H:i:s');                
                $regex = '/^[a-zA-Z1-9][a-zA-Z1-9\.\s]+$/';
                $rules = [];
                $message = [];
                $rules["btn"]="required|in:approve,reject";
                $message["btn.in"] = "btn Value In approve,reject";
                $rules["comment"]="required|min:10|regex:$regex";
                $validator = Validator::make($request->all(), $rules, $message);
                if ($validator->fails()) {
                    return responseMsg(false, $validator->errors(),$request->all());
                }
                if($mUserType!="EO")
                {
                    throw new Exception("You Are Not Authorize For Approve Or Reject Denial Detail");
                }
                DB::beginTransaction();
                # Approve Application
                $res = [];
                if($request->btn=="approve")
                {  
                    $denial_consumer->status = 5;
                    $denial_consumer->save();

                    $denail_mail->status = 5;
                    $denail_mail->emp_details_id = $user_id;
                    $denail_mail->forward_date = $nowdate;
                    $denail_mail->forward_time = Carbon::now()->format("H:i:s");
                    $denail_mail->save();

                    $notic->denial_id = $denialID;
                    $notic->emp_details_id = $user_id;
                    $notic->remarks = $request->comment;
                    $notic->save();

                    $insertID = $notic->id;
                    $noticeNO = "NOT/".date('dmy').$denialID.$insertID ;
                    $notic->notice_no = $noticeNO;
                    $notic->save();
                    $res["noticeNo"] = $noticeNO;
                    $res["sms"] = "Notice No Successfuly Generated";
                       
                }
                if($request->btn=='btn_upload') 
                {
                      
                }
                if($request->btn=="reject")
                {
                   
                    $denial_consumer->status = 4;
                    $denial_consumer->save();

                    $denail_mail->status = 4;
                    $denail_mail->emp_details_id = $user_id;
                    $denail_mail->forward_date = $nowdate;
                    $denail_mail->forward_time = Carbon::now()->format("H:i:s");
                    $denail_mail->save();

                    $notic->denial_id = $denialID;
                    $notic->emp_details_id = $user_id;
                    $notic->remarks = $request->comment;
                    $notic->status = 4;
                    $notic->save();
                    $res["noticeNo"] = "";
                    $res["sms"] = "Denail Apply Rejected";

                }
                DB::commit();                
                return responseMsg(true, $res["sms"],remove_null($res["noticeNo"]));
            }
            
        }
        catch(Exception $e)
        {
            return responseMsg(false, $e->getMessage(), $request->all());
        }

    }   

    #------------------- Reports function ------------------

    public function reports(Request $request)
    {
        try{
            $rules["alias"]="required|min:3|max:3";
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) 
            {
                return responseMsg(false, $validator->errors(),$request->all());
            }

        }
        catch(Exception $e)
        {
            return responseMsg(false, $e->getMessage(), $request->all());
        }

    }
    #------------------- End Reports Function --------------
    

    #---------- core function for trade Application--------

    /**
     * |----------------------------------------------------
     * |    APN     |      2A      |     0000001            |
     * |  const(3)  | ward no(2)   | unique No id(7)        |
     * |____________________________________________________|
     * 
     */
    public function createApplicationNo($wardNo,$licenceId)
    {
        return "APN".str_pad($wardNo, 2, '0', STR_PAD_LEFT).str_pad($licenceId, 7, '0', STR_PAD_LEFT);
    }

    /**
     * |----------------------------------------------------------------------------------
     * |     RMC                  |    2A         | 01152022           |      1           |
     * | @var shortUlbName(3)     |  ward no(2)   | Month Date Year(8) |  unique No id    |
     * |___________________________________________________________________________________
     */
    public function createProvisinalNo($shortUlbName,$wardNo,$licenceId)
    {
        return $shortUlbName . $wardNo . date('mdy') . $licenceId;
    }

    /**
     * |-----------------------------------------------------------------------------------
     * |  TRANML      |    14        |  1234         | 2022       | 01        |  53        |
     * |    (3)       |   date('d')  | transactionId | date('Y')  | date('m') | date('s')  |
     * |____________________________________________________________________________________
     */
    public function createTransactionNo($transactionId)
    {
        return "TRANML" . date('d') . $transactionId . date('Y') . date('m') . date('s');
    }
    public function cltCharge(array $args)
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
           
            if ($vMonths > 0 && strtotime($data['firm_date']) < strtotime($data['curdate'])) 
            {
                $denial_amount_month = 100 + (($vMonths) * 20);                
            }
            # In case of ammendment no denial amount
            if ($data['application_type_id'] == 3)
            {
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
            return $response;
        }
    }
    public function readNotisDtl($licence_id)
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
    public function getDenialFirmDetails($ulb_id,$notice_no)//for apply application
    {
        try{
            $data = TradeDenialConsumerDtl::select("trade_denial_consumer_dtls.*",
                        DB::raw("trade_denial_notices.notice_no,
                                trade_denial_notices.created_on::date AS noticedate,
                                trade_denial_notices.id as dnialid")
                    )
                    ->join("trade_denial_notices","trade_denial_notices.denial_id","=","trade_denial_consumer_dtls.id")
                    ->where("trade_denial_notices.notice_no",$notice_no)
                    // ->where("trade_denial_notices.created_on","<",$firm_date)
                    ->where("trade_denial_consumer_dtls.status","=", 5)
                    ->where("trade_denial_consumer_dtls.ulb_id",$ulb_id)
                    ->where("trade_denial_notices.status","=", 1)
                    ->first();                   
            return $data;
        }
        catch (Exception $e)
        {
            echo $e->getMessage();
        }
          
    }
    function getDenialAmountTrade($notice_date=null,$current_date=null)
    {
        $notice_date=$notice_date?Carbon::createFromFormat("Y-m-d",$notice_date)->format("Y-m-d"):Carbon::now()->format('Y-m-d');
        $current_date=$current_date?Carbon::createFromFormat("Y-m-d",$current_date)->format("Y-m-d"):Carbon::now()->format('Y-m-d');
        
        $datediff = strtotime($current_date)-strtotime($notice_date); //days difference in second
        $totalDays =   abs(ceil($datediff / (60 * 60 * 24))); // total no. of days
        $denialAmount=100+(($totalDays)*10);
    
        return $denialAmount;
    }    
    public function getCategoryList()
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
    public function getOwnershipTypeList()
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
    public function geItemsList($all=false)
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
    public function transferExpire(int $licenceId,$new_licence_id):int
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
                $expireLicence->update_status           = $new_licence_id??null;
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
    public function propertyDetailsfortradebyHoldingNo(string $holdingNo,int $ulb_id):array
    {
        // DB::enableQueryLog();
        $property = PropProperty::select("*")
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
                        ->where("new_holding_no","ILIKE",$holdingNo)
                        ->where("ulb_id",$ulb_id)
                        ->first();
                        // dd(DB::getQueryLog());
        if($property)
        {
            return ["status"=>true,'property'=>adjToArray($property)];

        }
        return ["status"=>false,'property'=>''];
    }
    public function getSafDtlBySafno(string $safNo,int $ulb_id):array
    {
        $saf = ActiveSaf::select("*")
                ->where('status',1)
                ->where('saf_no',$safNo)
                ->where('ulb_id',$ulb_id)
                ->first();
        if($saf->id)
        {
            $owneres = ActiveSafsOwnerDtl::select("*")
                        ->where("saf_dtl_id",$saf->id)
                        ->where('status',1)
                        ->get();
            return ["status"=>true,'saf'=>adjToArray($saf),'owneres'=>adjToArray($owneres)];

        }
        return ["status"=>false,'property'=>'','owneres'=>''];
    }
    public function updateStatusFine($denial_id,$denialAmount,$applyid,$status = 2)
    {
        $tradeNotice = TradeDenialNotice::where("id",$denial_id)
                        ->orderBy("id","DESC")
                        ->first();
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
                            "trade_param_ownership_types.ownership_type",
                    DB::raw("ulb_ward_masters.ward_name AS ward_no, new_ward.ward_name as new_ward_no")
                    )
                ->join("ulb_ward_masters",function($join){
                    $join->on("ulb_ward_masters.id","=","active_licences.ward_mstr_id");                                
                })
                ->join("ulb_ward_masters AS new_ward",function($join){
                    $join->on("new_ward.id","=","active_licences.new_ward_mstr_id");                                
                })
                ->join("trade_param_application_types","trade_param_application_types.id","active_licences.application_type_id")
                ->leftjoin("trade_param_category_types","trade_param_category_types.id","active_licences.category_type_id")
                ->leftjoin("trade_param_firm_types","trade_param_firm_types.id","active_licences.firm_type_id")    
                ->leftjoin("trade_param_ownership_types","trade_param_ownership_types.id","active_licences.ownership_type_id")            
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
    public function readTranDtl($id)
    {
        try{
            $transection   = TradeTransaction::select("*")
                            ->where("related_id",$id)
                            ->whereIn("status",[1,2])
                            ->get();
            return $transection;
        }
        catch(Exception $e)
        {
            echo $e->getMessage();
        }
        
    }
    public function getTimelin($id)
    {
        try{
           
            $time_line =  TradeLevelPending::select(
                        "remaks_for.remarks",
                        "trade_level_pendings.forward_date",
                        "trade_level_pendings.forward_time",
                        "trade_level_pendings.receiver_user_type_id",
                        "role_name",
                        DB::raw("trade_level_pendings.created_at as receiving_date")
                    )
                    ->leftjoin(DB::raw("(SELECT receiver_user_type_id::bigint, licence_id::bigint, remarks
                                        FROM trade_level_pendings 
                                        WHERE licence_id = $id
                                    )remaks_for"
                                ),function($join){
                                $join->on("remaks_for.receiver_user_type_id","trade_level_pendings.sender_user_type_id");
                                // ->where("remaks_for.licence_id","trade_level_pendings.licence_id");
                                }
                    )
                    ->leftjoin('wf_roles', "wf_roles.id", "trade_level_pendings.receiver_user_type_id")
                    ->where('trade_level_pendings.licence_id', $id)     
                    ->whereIn('trade_level_pendings.status',[1,2])                 
                    ->groupBy('trade_level_pendings.receiver_user_type_id',
                            'remaks_for.remarks',
                            'trade_level_pendings.forward_date',
                            'trade_level_pendings.forward_time','wf_roles.role_name',
                            'trade_level_pendings.created_at'
                    )
                    ->orderBy('trade_level_pendings.created_at', 'desc')
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
           
            $time_line =  TradeLicenceDocument::select(
                        "trade_licence_documents.doc_for",
                        "trade_licence_documents.document_path",
                        "trade_licence_documents.remarks",
                        "trade_licence_documents.verify_status"
                    )
                    ->where('trade_licence_documents.licence_id', $id)
                    ->where('trade_licence_documents.status', 1)                    
                    ->orderBy('trade_licence_documents.id', 'desc')
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
        $filePath = $file->storeAs('uploads/Trade', $custumFileName, 'public');
        return  $filePath;
    }
    public function getDenialDetailsByID($id,$ulb_id)
    {
        try{
            $data = TradeDenialConsumerDtl::select("trade_denial_consumer_dtls.*",
                        DB::raw("ulb_ward_masters.ward_name as ward_no,
                        trade_denial_notices.id as notice_id,trade_denial_notices.notice_no,
                        trade_denial_notices.remarks as notice_remarks,trade_denial_notices.fine_amount,
                        trade_denial_notices.status as notice_status,trade_denial_notices.apply_id as notice_apply_id,
                        trade_denial_notices.created_on as notice_created_on,
                        trade_denial_mail_dtls.id as denial_mail_id,trade_denial_mail_dtls.sender_id as denial_mail_sender_id,
                        trade_denial_mail_dtls.sender_user_type_id as denial_mail_sender_user_type_id,
                        trade_denial_mail_dtls.receiver_user_type_id as denil_mail_receiver_user_type_id,
                        trade_denial_mail_dtls.remarks as denial_mail_remarks,trade_denial_mail_dtls.created_on as denial_mail_created_on,
                        trade_denial_mail_dtls.forward_date as denial_mail_forward_date,trade_denial_mail_dtls.forward_time as denial_mail_forward_time,
                        trade_denial_mail_dtls.emp_details_id as demail_mail_emp_details_id,trade_denial_mail_dtls.status as denial_mail_status
                        ")
                    )
                    ->join("ulb_ward_masters",function($join){
                        $join->on("ulb_ward_masters.id","trade_denial_consumer_dtls.ward_id");
                    })                    
                    ->leftjoin("trade_denial_notices",function($join){
                        $join->on("trade_denial_notices.denial_id","trade_denial_consumer_dtls.id");
                    })
                    ->join("trade_denial_mail_dtls",function($join){
                        $join->on("trade_denial_mail_dtls.denial_consumer_id","trade_denial_consumer_dtls.id");
                    })
                    ->where("trade_denial_consumer_dtls.id",$id)
                    ->where("trade_denial_consumer_dtls.ulb_id",$ulb_id)
                    ->first();
            return $data;
        }
        catch(Exception $e)
        {
            echo $e->getMessage();
        }
    }
    public function getDocumentTypeList(ActiveLicence $application)
    {
        try
        {
            $show='1';
            if($application->application_type_id==1)
            {
                if($application->ownership_type_id==1)
                {
                    $show .=','.'2';  
                }
                else
                {
                    $show .=','.'3';  
                }

                if($application->firm_type_id==2)
                {
                    $show .=','.'4';  
                }
                elseif($application->firm_type_id ==3 || $application->firm_type_id==4)
                {
                    $show .=','.'5';  
                }
                if($application->category_type_id==2)
                {
                    $show .=','.'6';  
                }
            }
            $show = explode(",",$show);
            $data = TradeParamDocumentType::select("doc_for","is_mandatory","show")
                    ->where("application_type_id",$application->application_type_id)
                    ->where("status",1)
                    ->whereIn("show",$show)
                    ->groupBy("doc_for","is_mandatory","show")
                    ->get();
            return $data;
        }
        catch(Exception $e)
        {
            return $e->getMessage();   
        }
    }
    public function getDocumentList($doc_for,$application_type_id ,$show)
    {
        try{
            $data = TradeParamDocumentType::select("id","doc_name")
                                ->where("status",1)
                                ->where("doc_for",$doc_for)
                                ->where("application_type_id",$application_type_id )
                                ->where("show",$show)
                                ->get();
            return $data;
        }
        catch(Exception $e)
        {
            return $e->getMessage();   
        }
    }
    public function check_doc_exist($licenceId,$doc_for,$doc_mstr_id=null,$woner_id=null)
    {
        try{
            
            $doc = TradeLicenceDocument::select("id","doc_for","verify_status","document_path","document_id")
                       ->where('licence_id',$licenceId)
                       ->where('doc_for',$doc_for);
                       if($doc_mstr_id)
                       {
                            $doc =$doc->where('document_id',$doc_mstr_id);
                       }                       
                       if($woner_id)
                       {
                            $doc =$doc->where('licence_owner_dtl_id', $woner_id);
                       }                       
                $doc =$doc->where('status',1)
                       ->orderBy('id','DESC')
                       ->first();                     
            return $doc;
          
       }
       catch(Exception $e)
       {
          echo $e->getMessage();   
       }
    }

    public function check_doc_exist_owner($licenceId,$owner_id,$document_id=null)
    {
        try{
            // DB::enableQueryLog();
            $doc = TradeLicenceDocument::select("id","doc_for","verify_status","document_path","document_id")
                           ->where('licence_id',$licenceId)
                           ->where('licence_owner_dtl_id',$owner_id);
                           if($document_id!==null)
                            { 
                                $document_id = (int)$document_id;
                                $doc = $doc->where('document_id',$document_id);
                            }
                            else
                            {
                                $doc = $doc->where("document_id","<>",0);                     

                            }
                $doc =$doc->where('status',1)
                        ->orderBy('id','DESC')
                        ->first();                        
        //    print_var(DB::getQueryLog());                    
            return $doc;
        }
        catch(Exception $e)
        {
            return $e->getMessage();   
        }
    }
   
    #-------------------- End core function of core function --------------
    /** Incomplite code */
    public function updateBasicDtl(Request $request)
    {
        $user = Auth()->user();
        $user_id = $user->id;
        $ulb_id = $user->ulb_id;
        $redis = new Redis;
        $user_data = json_decode($redis::get('user:' . $user_id), true);
        $roll_id =  $user_data['role_id']??($this->getUserRoll($user_id,'Trade','Trade')->role_id??-1);
        $rules = [];
        $message = [];
        try{
            if($roll_id==-1)
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
    public function trackApplication(Request $request)
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
            $mUserType = $this->_parent->userType();
            $ward_permission = $this->_modelWard->getAllWard($ulb_id)->map(function($val){
                $val->ward_no = $val->ward_name;
                return $val;
            });
            $ward_permission = adjToArray($ward_permission);

            $ward_ids = array_map(function ($val) {
                return $val['id'];
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
            $licence = $licence
                        ->whereIn('active_licences.ward_mstr_id', $ward_ids)
                        ->get();
            return responseMsg(true, "", $licence);
            
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), $request->all());
        } 
    }
    public function ApplicationLog(Request $request)
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
            $mUserType = $this->_parent->userType();
            $ward_permission = $this->_modelWard->getAllWard($ulb_id)->map(function($val){
                $val->ward_no = $val->ward_name;
                return $val;
            });
            $ward_permission = adjToArray($ward_permission);

            $ward_ids = array_map(function ($val) {
                return $val['id'];
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
            $licence = $licence
                        ->whereIn('active_licences.ward_mstr_id', $ward_ids)
                        ->get();
            return responseMsg(true, "", $licence);
            
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }
    /** Incomplite End code */
}