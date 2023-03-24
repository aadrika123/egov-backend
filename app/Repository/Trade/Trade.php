<?php

namespace App\Repository\Trade;

use Illuminate\Support\Facades\Storage;
use App\EloquentModels\Common\ModelWard;
use App\Models\CustomDetail;
use App\Models\Payment\TempTransaction;
use App\Models\Property\ActiveSafsOwnerDtl;
use App\Models\Property\ActiveSaf;
use App\Models\Property\PropActiveSaf;
use App\Models\Property\PropActiveSafsOwner;
use App\Models\Property\PropOwner;
use App\Models\Property\PropProperty;
use App\Models\Trade\TradeLicence;
use App\Models\Trade\TradeDocument;
use App\Models\Trade\ActiveTradeDocument;
use App\Models\Trade\ActiveTradeLicence;
use App\Models\Trade\ActiveTradeNoticeConsumerDtl;
use App\Models\Trade\ActiveTradeOwner;
use App\Models\Trade\RejectedTradeLicence;
use App\Models\Trade\RejectedTradeOwner;
use App\Models\Trade\RejectedTradeDocument;
use App\Models\Trade\TradeChequeDtl;
use App\Models\Trade\TradeFineRebete;
use App\Models\Trade\TradeNoticeConsumerDtl;
use App\Models\Trade\TradeOwner;
use App\Models\Trade\TradeParamApplicationType;
use App\Models\Trade\TradeParamCategoryType;
use App\Models\Trade\TradeParamDocumentType;
use App\Models\Trade\TradeParamFirmType;
use App\Models\Trade\TradeParamItemType;
use App\Models\Trade\TradeParamLicenceRate;
use App\Models\Trade\TradeParamOwnershipType;
use App\Models\Trade\TradeRenewal;
use App\Models\Trade\TradeTransaction;
use App\Models\UlbMaster;
use App\Models\UlbWardMaster;
use App\Models\Workflows\WfActiveDocument;
use App\Models\Workflows\WfWorkflow;
use App\Models\WorkflowTrack;
use App\Models\Workflows\WfRole;
use App\Repository\Common\CommonFunction;
use App\Repository\WorkflowMaster\Concrete\WorkflowMap;
use Illuminate\Http\Request;

use App\Traits\Auth;
use App\Traits\Property\WardPermission;
use App\Traits\Payment\Razorpay;
use App\Traits\Trade\TradeTrait;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;

class Trade implements ITrade
{
    use Auth;               // Trait Used added by sandeep bara date 17-09-2022
    use WardPermission;
    use Razorpay;
    use TradeTrait;

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
    
    protected $_wardNo;
    protected $_licenceId;
    protected $_shortUlbName;
    protected $_MODEL_WARD;
    protected $_COMMON_FUNCTION;
    protected $_WARD_NO;
    protected $_LICENCE_ID;
    protected $_SHORT_ULB_NAME;
    protected $_WF_MASTER_Id;
    protected $_WF_NOTICE_MASTER_Id;
    protected $_MODULE_ID;
    protected $_REF_TABLE;
    protected $_TRADE_CONSTAINT;

    public function __construct()
    {
        $this->_MODEL_WARD = new ModelWard();
        $this->_COMMON_FUNCTION = new CommonFunction();
        $this->_WARD_NO = NULL;
        $this->_LICENCE_ID = NULL;
        $this->_SHORT_ULB_NAME = NULL;
        $this->_WF_MASTER_Id = Config::get('workflow-constants.TRADE_MASTER_ID');
        $this->_WF_NOTICE_MASTER_Id = Config::get('workflow-constants.TRADE_NOTICE_ID');
        $this->_MODULE_ID = Config::get('module-constants.TRADE_MODULE_ID');
        $this->_TRADE_CONSTAINT = Config::get("TradeConstant");
        $this->_REF_TABLE = $this->_TRADE_CONSTAINT["TRADE_REF_TABLE"];
    }
    # Serial No : 01
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
     * | @var refWorkflowId      = $this->_WF_MASTER_Id
     * | @var refWorkflows       = $this->_COMMON_FUNCTION->iniatorFinisher(refUserId,refUlbId,refWorkflowId)
     * | @var redis              = new Redis
     * | @var mUserData          = json_decode(redis::get('user:' . refUserId), true)
     * | @var mUserType          = $this->_COMMON_FUNCTION->userType()    | loging user Role Name
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
     * |    this->getItemsList(true)                               |    read Trade Item List without tomacco item
     * |    this->getLicenceById(request->id)                           |    only for applicationType(2,3,4)  |  read Old Licence Dtl
     * |    ActiveLicenceOwner::owneresByLId($request->id)                       |    only for applicationType(2,3,4)  |  read Old Licence Owner Dtl
     * |    TradeParamItemType::itemsById(refOldLicece->nature_of_bussiness)|    only for applicationType(2,3,4)  |  read Old Licence Trade Items
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
        try {
            #------------------------ Declaration-----------------------           
            $refUser            = Auth()->user();
            $refUserId          = $refUser->id;
            $refUlbId           = $refUser->ulb_id ?? $request->ulbId;
            $refUlbDtl          = UlbMaster::find($refUlbId);
            $refUlbName         = explode(' ', $refUlbDtl->ulb_name);
            $refNoticeDetails   = null;
            $refWorkflowId      = $this->_WF_MASTER_Id ;
            $refWfWorkflow     = WfWorkflow::where('wf_master_id', $refWorkflowId)
                ->where('ulb_id', $refUlbId)
                ->first();
            if (!$refWfWorkflow) {
                throw new Exception("Workflow Not Available");
            }
            $refWorkflows       = $this->_COMMON_FUNCTION->iniatorFinisher($refUserId, $refUlbId, $refWorkflowId);
            $mUserType          = $this->_COMMON_FUNCTION->userType($refWorkflowId);
            $mShortUlbName      = "";
            $mApplicationTypeId = $this->_TRADE_CONSTAINT["APPLICATION-TYPE"][$request->applicationType];
            $mNowdate           = Carbon::now()->format('Y-m-d');
            $mNoticeDate        = null;
            $mProprtyId         = null;
            $mnaturOfBusiness   = null;
            $mOldLicenceId      = null;
            $data               = array();
            foreach ($refUlbName as $mval) {
                $mShortUlbName .= $mval[0];
            }
            #------------------------End Declaration-----------------------
            if (in_array(strtoupper($mUserType), ["ONLINE", "JSK", "SUPER ADMIN", "TL"])) 
            {
                $data['wardList'] = $this->_MODEL_WARD->getAllWard($refUlbId)->map(function ($val) {
                    $val->ward_no = $val->ward_name;
                    return $val;
                });
                $data['wardList'] = objToArray($data['wardList']);
            } 
            else 
            {
                $data['wardList'] = $this->_COMMON_FUNCTION->WardPermission($refUserId);
            }
            if ($request->getMethod() == "POST") 
            {
                if ($request->firmDetails['holdingNo']) 
                {
                    $property = $this->propertyDetailsfortradebyHoldingNo($request->firmDetails['holdingNo'], $refUlbId);
                    if ($property['status'])
                        $mProprtyId = $property['property']['id'];
                    else
                        throw new Exception("Property Details Not Found");
                }
                if ($mApplicationTypeId == 1) 
                {
                    $mnaturOfBusiness = array_map(function ($val) {
                        return $val['id'];
                    }, $request->firmDetails['natureOfBusiness']);
                    $mnaturOfBusiness = implode(',', $mnaturOfBusiness);
                }
                if ($mApplicationTypeId != 1) 
                {
                    $mOldLicenceId = $request->licenseId;
                    $nextMonth = Carbon::now()->addMonths(1)->format('Y-m-d');
                    $refOldLicece = TradeLicence::find($mOldLicenceId);
                    if (!$refOldLicece) 
                    {
                        throw new Exception("Old Licence Not Found");
                    }
                    if (!$refOldLicece->is_active) 
                    {
                        $newLicense = ActiveTradeLicence::where("license_no", $refOldLicece->license_no)
                            ->orderBy("id")
                            ->first();
                        throw new Exception("Application Aready Apply Please Track  " . $newLicense->application_no);
                    }
                    if ($refOldLicece->valid_upto > $nextMonth && !in_array($mApplicationTypeId,[3,4])) {
                        throw new Exception("Licence Valice Upto " . $refOldLicece->valid_upto);
                    }
                    if($refOldLicece->valid_upto < (Carbon::now()->format('Y-m-d')) && in_array($mApplicationTypeId,[3,4]))
                    {
                        throw new Exception("Licence Was Expired Please Renewal First" );
                    }
                    if ($refOldLicece->pending_status != 5) 
                    {
                        throw new Exception("Application Aready Apply Please Track  " . $refOldLicece->application_no);
                    }
                    if(in_array($mApplicationTypeId,[3,4]) && $refOldLicece->valid_upto<Carbon::now()->format('Y-m-d'))
                    {
                        throw new Exception("Application was Expired.You Can't Apply ".$request->applicationType.". Please Renew First.");
                    }

                    $mnaturOfBusiness = $refOldLicece->nature_of_bussiness;
                    $wardId = $refOldLicece->ward_mstr_id;
                    $mWardNo = array_filter($data['wardList'], function ($val) use ($wardId) {
                        return $val['id'] == $wardId;
                    });
                    $mWardNo = array_values($mWardNo)[0]['ward_no'] ?? "";
                    $refOldowners = TradeOwner::where('temp_id', $mOldLicenceId)
                        ->get();
                }

                DB::beginTransaction();
                $licence = new ActiveTradeLicence();
                $licence->application_type_id = $mApplicationTypeId;
                $licence->ulb_id              = $refUlbId;
                $licence->trade_id            = $mOldLicenceId;
                $licence->property_id         = $mProprtyId;
                $licence->user_id             = $refUserId;
                $licence->application_date    = $mNowdate;
                $licence->apply_from          = $mUserType;
                $licence->current_role        = $refWorkflows['initiator']['id'];
                $licence->initiator_role      = $refWorkflows['initiator']['id'];
                $licence->finisher_role       = $refWorkflows['finisher']['id'];
                $licence->workflow_id         = $refWorkflowId;

                if (strtoupper($mUserType) == "ONLINE") {
                    $licence->citizen_id      = $refUserId;
                }
                #----------------Crate Application--------------------
                if (in_array($mApplicationTypeId, ["2", "4"])) # code for Renewal,Surender respectively
                {
                    $licence->licence_for_years   = $mApplicationTypeId == 2 ? $request->licenseDetails['licenseFor'] : $refOldLicece->licence_for_years;
                    $this->renewalAndSurenderLicense($licence, $refOldLicece, $request);
                    $licence->valid_from    = $refOldLicece->valid_upto;
                    $licence->save();
                    $licenceId = $licence->id;
                    foreach ($refOldowners as $owners) {
                        $owner = new ActiveTradeOwner();
                        $owner->temp_id      = $licenceId;
                        $this->transerOldOwneres($owner, $owners);
                        $owner->user_id  = $refUserId;
                        $owner->save();
                    }
                } elseif ($mApplicationTypeId == 3) # code for Amendment
                {
                    $this->amedmentLicense($licence, $refOldLicece, $request);
                    $licence->valid_from    = date('Y-m-d');
                    $licence->save();
                    $licenceId = $licence->id;
                    foreach ($refOldowners as $owners) {
                        $owner = new ActiveTradeOwner();
                        $owner->temp_id      = $licenceId;
                        $this->transerOldOwneres($owner, $owners);
                        $owner->user_id  = $refUserId;
                        $owner->save();
                    }
                    foreach ($request->ownerDetails as $owners) {
                        $owner = new ActiveTradeOwner();
                        $owner->temp_id      = $licenceId;
                        $this->addNewOwners($owner, $owners);
                        $owner->user_id  = $refUserId;
                        $owner->save();
                    }
                } elseif ($mApplicationTypeId == 1) # code for New License
                {
                    $wardId = $request->firmDetails['wardNo'];
                    $mWardNo = array_filter($data['wardList'], function ($val) use ($wardId) {
                        return $val['id'] == $wardId;
                    });
                    $mWardNo = array_values($mWardNo)[0]['ward_no'] ?? "";
                    $this->newLicense($licence, $request);
                    $licence->valid_from    = $licence->application_date;
                    $licence->save();
                    $licenceId = $licence->id;
                    foreach ($request->ownerDetails as $owners) {
                        $owner = new ActiveTradeOwner();
                        $owner->temp_id      = $licenceId;
                        $this->addNewOwners($owner, $owners);
                        $owner->user_id      = $refUserId;
                        $owner->save();
                    }
                }
                $licence->nature_of_bussiness = $mnaturOfBusiness;
                $mAppNo = $this->createApplicationNo($mWardNo, $licenceId);
                $licence->application_no = $mAppNo;
                $licence->update();
                #----------------End Crate Application--------------------
                #---------------- transaction of payment-------------------------------
                if ($mApplicationTypeId == 1 && $request->initialBusinessDetails['applyWith'] == 1) {
                    $noticeNo = trim($request->initialBusinessDetails['noticeNo']);
                    $firm_date = $request->firmDetails['firmEstdDate'];
                    $refNoticeDetails = $this->getDenialFirmDetails($refUlbId, strtoupper(trim($noticeNo)));
                    if ($refNoticeDetails) {                       
                        $refDenialId = $refNoticeDetails->dnialid;
                        $licence->dnial_id = $refDenialId;
                        $licence->update();
                        $mNoticeDate = date("Y-m-d", strtotime($refNoticeDetails['created_on'])); //notice date  
                        if ($firm_date < $mNoticeDate) {
                            throw new Exception("Firm Establishment Date Can Not Be Greater Than Notice Date ");
                        }
                    }
                }
                if (in_array(strtoupper($mUserType), ["JSK", "UTC", "TC", "SUPER ADMIN", "TL"]) && $mApplicationTypeId != 4) {
                    $myRequest = new \Illuminate\Http\Request();
                    $myRequest->setMethod('POST');
                    $myRequest->request->add(['paymentMode' => $request->licenseDetails['paymentMode']]);
                    $myRequest->request->add(['licenceId'   => $licence->id]);
                    $myRequest->request->add(['licenseFor' => $licence->licence_for_years]);
                    $myRequest->request->add(['totalCharge' => $request->licenseDetails["totalCharge"]]);
                    if ($request->licenseDetails['paymentMode'] != "CASH") {
                        $myRequest->request->add(['chequeNo' => $request->licenseDetails["chequeNo"]]);
                        $myRequest->request->add(['chequeDate' => $request->licenseDetails["chequeDate"]]);
                        $myRequest->request->add(['bankName' => $request->licenseDetails["bankName"]]);
                        $myRequest->request->add(['branchName' => $request->licenseDetails["branchName"]]);
                    }
                    $temp = $this->paymentCounter($myRequest);
                    if (!$temp->original["status"]) {
                        throw new Exception($temp->original["message"]);
                    }
                    $res['transactionId']   = $temp->original["data"]["transactionId"];
                    $res['paymentReceipt']   = $temp->original["data"]["paymentReceipt"];
                } elseif ($refNoticeDetails) {
                    $licence->denial_id = $refDenialId;
                    $licence->update();
                    $this->updateStatusFine($refDenialId, 0, $licenceId, 1); //update status and fineAmount                     
                }
                #---------------- End transaction of payment----------------------------
                if ($mApplicationTypeId == 4) {
                    $mProvno                         = $this->createProvisinalNo($mShortUlbName, $mWardNo, $licenceId);
                    $licence->provisional_license_no = $mProvno;
                    $licence->payment_status         = 1;
                    $licence->update();
                }
                #frize Priviuse License
                if ($mApplicationTypeId != 1 && !$this->transferExpire($mOldLicenceId)) {
                    throw new Exception("Some Error Ocures!....");
                }
                DB::commit();
                $res['applicationNo'] = $mAppNo;
                $res['applyLicenseId'] = $licenceId;
                return responseMsg(true, $mAppNo, $res);
            }
        } catch (Exception $e) {
            DB::rollBack();
            // dd($e->getMessage(),$e->getFile(),$e->getLine());          
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }

    # Serial No : 01.01
    public function newLicense($refActiveLicense, $request)
    { 
        $refActiveLicense->firm_type_id        = $request->initialBusinessDetails['firmType'];
        $refActiveLicense->firm_description    = $request->initialBusinessDetails['otherFirmType'] ?? null;
        $refActiveLicense->category_type_id    = $request->initialBusinessDetails['categoryTypeId'] ?? null;
        $refActiveLicense->ownership_type_id   = $request->initialBusinessDetails['ownershipType'];
        $refActiveLicense->ward_id        = $request->firmDetails['wardNo'];
        $refActiveLicense->new_ward_id    = $request->firmDetails['newWardNo'];
        $refActiveLicense->holding_no          = $request->firmDetails['holdingNo'];
        $refActiveLicense->firm_name           = $request->firmDetails['firmName'];
        $refActiveLicense->premises_owner_name = $request->firmDetails['premisesOwner'] ?? null;
        $refActiveLicense->brief_firm_desc     = $request->firmDetails['businessDescription'];
        $refActiveLicense->area_in_sqft        = $request->firmDetails['areaSqft'];

        $refActiveLicense->k_no                = $request->firmDetails['kNo'] ?? null;
        $refActiveLicense->bind_book_no        = $request->firmDetails['bindBookNo'] ?? null;
        $refActiveLicense->account_no          = $request->firmDetails['accountNo'] ?? null;
        $refActiveLicense->pan_no              = $request->firmDetails['panNo'] ?? null;
        $refActiveLicense->tin_no              = $request->firmDetails['tinNo'] ?? null;
        $refActiveLicense->salestax_no         = $request->firmDetails['salestaxNo'] ?? null;
        $refActiveLicense->establishment_date  = $request->firmDetails['firmEstdDate'];

        $refActiveLicense->licence_for_years   = $request->licenseDetails['licenseFor'];
        $refActiveLicense->address             = $request->firmDetails['businessAddress'];
        $refActiveLicense->landmark            = $request->firmDetails['landmark'] ?? null;
        $refActiveLicense->pin_code            = $request->firmDetails['pincode'] ?? null;
        $refActiveLicense->street_name         = $request->firmDetails['streetName'] ?? null;
        $refActiveLicense->property_type       = "Property";
        $refActiveLicense->is_tobacco      = $request->firmDetails['tocStatus'];
    }
    # Serial No : 01.02
    public function amedmentLicense($refActiveLicense, $refOldLicece, $request)
    {
        $refActiveLicense->parent_ids          = trim(($refActiveLicense->trade_id . "," . $refOldLicece->parent_ids), ',');
        $refActiveLicense->parent_ids          = trim(',', ($refOldLicece->parent_ids . "," . $refActiveLicense->trade_id));
        $refActiveLicense->firm_type_id        = $request->initialBusinessDetails['firmType'];
        $refActiveLicense->firm_description    = $request->initialBusinessDetails['otherFirmType'] ?? null;
        $refActiveLicense->category_type_id    = $refOldLicece->category_type_id;
        $refActiveLicense->ownership_type_id   = $request->initialBusinessDetails['ownershipType']; //$refOldLicece->ownership_type_id;
        $refActiveLicense->ward_id             = $refOldLicece->ward_id;
        $refActiveLicense->new_ward_id         = $refOldLicece->new_ward_id;
        $refActiveLicense->holding_no          = $request->firmDetails['holdingNo'];
        $refActiveLicense->nature_of_bussiness = $refOldLicece->nature_of_bussiness;
        $refActiveLicense->firm_name           = $refOldLicece->firm_name;
        $refActiveLicense->premises_owner_name = $refOldLicece->premises_owner_name;
        $refActiveLicense->brief_firm_desc     = $request->firmDetails['businessDescription']; //$refOldLicece->brife_desp_firm;
        $refActiveLicense->area_in_sqft        = $request->firmDetails['areaSqft']; //$refOldLicece->area_in_sqft;

        $refActiveLicense->k_no                = $refOldLicece->k_no;
        $refActiveLicense->bind_book_no        = $refOldLicece->bind_book_no;
        $refActiveLicense->account_no          = $refOldLicece->account_no;
        $refActiveLicense->pan_no              = $refOldLicece->pan_no;
        $refActiveLicense->tin_no              = $refOldLicece->tin_no;
        $refActiveLicense->salestax_no         = $refOldLicece->salestax_no;
        $refActiveLicense->establishment_date  = $refOldLicece->establishment_date;

        $refActiveLicense->licence_for_years   = $request->licenseDetails['licenseFor'];
        $refActiveLicense->address             = $refOldLicece->address;
        $refActiveLicense->landmark            = $refOldLicece->landmark;
        $refActiveLicense->pin_code            = $refOldLicece->pin_code;
        $refActiveLicense->street_name         = $refOldLicece->street_name;
        $refActiveLicense->property_type       = $refOldLicece->property_type;
        $refActiveLicense->valid_from          = $refOldLicece->valid_upto;
        $refActiveLicense->license_no          = $refOldLicece->license_no;
        $refActiveLicense->is_tobacco          = $refOldLicece->is_tobacco;
    }

    # Serial No : 01.03
    public function renewalAndSurenderLicense($refActiveLicense, $refOldLicece, $request)
    {
        $refActiveLicense->parent_ids          = trim(($refActiveLicense->trade_id . "," . $refOldLicece->parent_ids), ',');

        $refActiveLicense->firm_type_id        = $refOldLicece->firm_type_id;
        $refActiveLicense->firm_description    = $refOldLicece->firm_description;
        $refActiveLicense->category_type_id    = $refOldLicece->category_type_id;
        $refActiveLicense->ownership_type_id   = $refOldLicece->ownership_type_id;
        $refActiveLicense->ward_id             = $refOldLicece->ward_id;
        $refActiveLicense->new_ward_id         = $refOldLicece->new_ward_id;
        $refActiveLicense->holding_no          = $request->firmDetails['holdingNo'];
        $refActiveLicense->nature_of_bussiness = $refOldLicece->nature_of_bussiness;
        $refActiveLicense->firm_name           = $refOldLicece->firm_name;
        $refActiveLicense->premises_owner_name = $refOldLicece->premises_owner_name;
        $refActiveLicense->brief_firm_desc     = $refOldLicece->brief_firm_desc;
        $refActiveLicense->area_in_sqft        = $refOldLicece->area_in_sqft;

        $refActiveLicense->k_no                = $refOldLicece->k_no;
        $refActiveLicense->bind_book_no        = $refOldLicece->bind_book_no;
        $refActiveLicense->account_no          = $refOldLicece->account_no;
        $refActiveLicense->pan_no              = $refOldLicece->pan_no;
        $refActiveLicense->tin_no              = $refOldLicece->tin_no;
        $refActiveLicense->salestax_no         = $refOldLicece->salestax_no;
        $refActiveLicense->establishment_date  = $refOldLicece->establishment_date;
        $refActiveLicense->address             = $refOldLicece->address;
        $refActiveLicense->landmark            = $refOldLicece->landmark;
        $refActiveLicense->pin_code            = $refOldLicece->pin_code;
        $refActiveLicense->street_name         = $refOldLicece->street_name;
        $refActiveLicense->property_type       = $refOldLicece->property_type;
        $refActiveLicense->valid_from          = $refOldLicece->valid_upto;
        $refActiveLicense->license_no          = $refOldLicece->license_no;
        $refActiveLicense->is_tobacco          = $refOldLicece->is_tobacco;
    }

    # Serial No : 01.04
    public function addNewOwners($refOwner, $owners)
    {
        $refOwner->owner_name      = $owners['businessOwnerName'];
        $refOwner->guardian_name   = $owners['guardianName'] ?? null;
        $refOwner->address         = $owners['address'] ?? null;
        $refOwner->mobile_no          = $owners['mobileNo'];
        $refOwner->city            = $owners['city'] ?? null;
        $refOwner->district        = $owners['district'] ?? null;
        $refOwner->state           = $owners['state'] ?? null;
        $refOwner->email_id         = $owners['email'] ?? null;
    }

    # Serial No : 01.05
    public function transerOldOwneres($refOwner, $owners)
    {
        $refOwner->owner_name      = $owners->owner_name;
        $refOwner->guardian_name   = $owners->guardian_name;
        $refOwner->address         = $owners->address;
        $refOwner->mobile_no          = $owners->mobile;
        $refOwner->city            = $owners->city;
        $refOwner->district        = $owners->district;
        $refOwner->state           = $owners->state;
        $refOwner->email_id         = $owners->emailid;
    }

    # Serial No : 01.06
    public function transferExpire(int $licenceId)
    {
        try {
            $licence = TradeLicence::find($licenceId);
            if ($licence->id) {
                $licence->is_active = FALSE;
                $licence->update();
                return $licence->id;
            }
        } catch (Exception $e) {
            echo $e->getMessage();
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
     * | @var refWorkflowId  = $this->_WF_MASTER_Id
     * | @var refWorkflows   = $this->_COMMON_FUNCTION->iniatorFinisher(refUserId,refUlbId,refWorkflowId)
     * | @var refNoticeDetails= null
     * | @var refDenialId    = null
     * | @var refUlbDtl      = UlbMaster::find(refUlbId)
     * | @var refUlbName     = explode(' ',refUlbDtl->ulb_name)
     * | @var refLecenceData  = model object(active_licences)
     * | @var licenceId   = request->licenceId
     * | @var refLevelData = TradeLevelPending::getLevelData(licenceId)
     * |   
     * | @var mUserData      = $this->_COMMON_FUNCTION->getUserRoll(refUserId, refUlbId,refWorkflowId)
     * | @var mUserType      = $this->_COMMON_FUNCTION->userType()
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
     * |  mUserData      = $this->_COMMON_FUNCTION->getUserRoll(refUserId, refUlbId,refWorkflowId)
     * |  mUserType      = $this->_COMMON_FUNCTION->userType(refWorkflowId)
     * |  refLevelData   = TradeLevelPending::getLevelData(licenceId)
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
     * | @var transactionType = $this->_TRADE_CONSTAINT['APPLICATION-TYPE-ID][refLecenceData->application_type_id]    | NEW LICENSE, RENEWAL, AMENDMENT
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
        try {
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id;
            $refUlbId       = $refUser->ulb_id;
            $refWorkflowId  = $this->_WF_MASTER_Id ;
            $refWorkflows   = $this->_COMMON_FUNCTION->iniatorFinisher($refUserId, $refUlbId, $refWorkflowId);
            $refNoticeDetails = null;
            $refDenialId    = null;
            $refUlbDtl      = UlbMaster::find($refUlbId);
            $refUlbName     = explode(' ', $refUlbDtl->ulb_name);

            $mUserData      = $this->_COMMON_FUNCTION->getUserRoll($refUserId, $refUlbId, $refWorkflowId);
            $mUserType      = $this->_COMMON_FUNCTION->userType($refWorkflowId);
            $mNowDate       = Carbon::now()->format('Y-m-d');
            $mTimstamp      = Carbon::now()->format('Y-m-d H:i:s');
            $mDenialAmount  = 0;
            $mPaymentStatus = 1;
            $mNoticeDate    = null;
            $mShortUlbName  = "";
            $mWardNo        = "";
            foreach ($refUlbName as $val) {
                $mShortUlbName .= $val[0];
            }

            #-----------valication-------------------                            
            if (!in_array($mUserType, ["JSK", "UTC", "TC", "SUPER ADMIN", "TL"])) {
                throw new Exception("You Are Not Authorized For Payment Cut");
            }
            $refLecenceData = ActiveTradeLicence::find($request->licenceId);
            $licenceId = $request->licenceId;
            $refLevelData = $this->getWorkflowTrack($licenceId); //TradeLevelPending::getLevelData($licenceId);
            if (!$refLecenceData) {
                throw new Exception("Licence Data Not Found !!!!!");
            } elseif ($refLecenceData->application_type_id == 4) {
                throw new Exception("Surender Application Not Pay Anny Amount");
            } elseif (in_array($refLecenceData->payment_status, [1, 2])) {
                throw new Exception("Payment Already Done Of This Application");
            }
            if ($refLecenceData->tobacco_status == 1 && $request->licenseFor > 1) {
                throw new Exception("Tobaco Application Not Take Licence More Than One Year");
            }
            if ($refNoticeDetails = $this->readNotisDtl($refLecenceData->denial_id)) {
                $refDenialId = $refNoticeDetails->dnialid;
                $mNoticeDate = date("Y-m-d", strtotime($refNoticeDetails['created_on'])); //notice date 
            }

            $ward_no = UlbWardMaster::select("ward_name")
                ->where("id", $refLecenceData->ward_id)
                ->first();
            $mWardNo = $ward_no['ward_name'];

            #-----------End valication-------------------

            #-------------Calculation-----------------------------                
            $args['areaSqft']            = (float)$refLecenceData->area_in_sqft;
            $args['application_type_id'] = $refLecenceData->application_type_id;
            $args['firmEstdDate'] = !empty(trim($refLecenceData->valid_from)) ? $refLecenceData->valid_from : $refLecenceData->apply_date;
            if ($refLecenceData->application_type_id == 1) {
                $args['firmEstdDate'] = $refLecenceData->establishment_date;
            }
            $args['tobacco_status']      = $refLecenceData->is_tobacco;
            $args['licenseFor']          = $request->licenseFor;
            $args['nature_of_business']  = $refLecenceData->nature_of_bussiness;
            $args['noticeDate']          = $mNoticeDate;
            $chargeData = $this->cltCharge($args);

            if ($chargeData['response'] == false || $chargeData['total_charge'] != $request->totalCharge) {
                throw new Exception("Payble Amount Missmatch!!!");
            }

            $transactionType = $this->_TRADE_CONSTAINT['APPLICATION-TYPE-BY-ID'][$refLecenceData->application_type_id];
            $rate_id = $chargeData["rate_id"];
            $totalCharge = $chargeData['total_charge'];
            $mDenialAmount = $chargeData['notice_amount'];
            #-------------End Calculation-----------------------------
            #-------- Transection -------------------
            DB::beginTransaction();
            $Tradetransaction = new TradeTransaction;
            $Tradetransaction->temp_id          = $licenceId;
            $Tradetransaction->ward_id          = $refLecenceData->ward_id;
            $Tradetransaction->tran_type        = $transactionType;
            $Tradetransaction->tran_date        = $mNowDate;
            $Tradetransaction->payment_mode     = $request->paymentMode;
            $Tradetransaction->rate_id          = $rate_id;
            $Tradetransaction->paid_amount      = $totalCharge;
            $Tradetransaction->penalty          = $chargeData['penalty'] + $mDenialAmount + $chargeData['arear_amount'];
            if ($request->paymentMode != 'CASH') 
            {
                $Tradetransaction->status = 2;
            }
            $Tradetransaction->emp_dtl_id       = $refUserId;
            $Tradetransaction->created_at       = $mTimstamp;
            $Tradetransaction->ip_address       = '';
            $Tradetransaction->ulb_id           = $refUlbId;
            $Tradetransaction->save();
            $transaction_id                     = $Tradetransaction->id;
            $Tradetransaction->tran_no   = $this->createTransactionNo($transaction_id); //"TRANML" . date('d') . $transaction_id . date('Y') . date('m') . date('s');
            $Tradetransaction->update();

            $TradeFineRebet = new TradeFineRebete;
            $TradeFineRebet->tran_id  = $transaction_id;
            $TradeFineRebet->type      = 'Delay Apply License';
            $TradeFineRebet->amount         = $chargeData['penalty'];
            $TradeFineRebet->created_at    = $mTimstamp;
            $TradeFineRebet->save();

            $mDenialAmount = $mDenialAmount + $chargeData['arear_amount'];
            if ($mDenialAmount > 0) {
                $TradeFineRebet2 = new TradeFineRebete;
                $TradeFineRebet2->tran_id = $transaction_id;
                $TradeFineRebet2->type      = 'Denial Apply';
                $TradeFineRebet2->amount         = $mDenialAmount;
                $TradeFineRebet2->created_at     = $mTimstamp;
                $TradeFineRebet2->save();
            }

            if ($request->paymentMode != 'CASH') {
                $tradeChq = new TradeChequeDtl;
                $tradeChq->tran_id = $transaction_id;
                $tradeChq->temp_id = $licenceId;
                $tradeChq->cheque_no      = $request->chequeNo;
                $tradeChq->cheque_date    = $request->chequeDate;
                $tradeChq->bank_name      = $request->bankName;
                $tradeChq->branch_name    = $request->branchName;
                $tradeChq->emp_dtl_id     = $refUserId;
                $tradeChq->created_at     = $mTimstamp;
                $tradeChq->save();
                $mPaymentStatus = 2;
            }
            if ($mPaymentStatus == 1 && $refLecenceData->document_upload_status == 1 && $refLecenceData->pending_status == 0 && !$refLevelData) {
                $refLecenceData->current_role = $refWorkflows['initiator']['forward_id'];
                $refLecenceData->pending_status  = 2;
                $args["sender_role_id"] = $refWorkflows['initiator']['id'];
                $args["receiver_role_id"] = $refWorkflows['initiator']['forward_id'];
                $args["citizen_id"] = $refUserId;;
                $args["ref_table_dot_id"] = "active_trade_licences";
                $args["ref_table_id_value"] = $licenceId;
                $args["workflow_id"] = $refWorkflowId;
                $args["module_id"] = $this->_MODULE_ID;

                $tem =  $this->insertWorkflowTrack($args);
            }

            if(!$refLecenceData->provisional_license_no)
            {
                $provNo = $this->createProvisinalNo($mShortUlbName, $mWardNo, $licenceId);
                $refLecenceData->provisional_license_no = $provNo;
            }
            $refLecenceData->payment_status         = $mPaymentStatus;
            $refLecenceData->save();

            if ($refNoticeDetails) {
                $refLecenceData->denial_id = $refDenialId;
                $refLecenceData->update();
                $this->updateStatusFine($refDenialId, $chargeData['notice_amount'], $licenceId, 1); //update status and fineAmount                     
            }
            $this->postTempTransection($Tradetransaction,$refLecenceData,$mWardNo);            
            DB::commit();
            #----------End transaction------------------------
            #----------Response------------------------------
            $res['transactionId'] = $transaction_id;
            $res['paymentReceipt'] = config('app.url') . "/api/trade/application/payment-receipt/" . $licenceId . "/" . $transaction_id;
            return responseMsg(true, "", $res);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }
    public function postTempTransection(TradeTransaction $refTransection,ActiveTradeLicence $refApplication,$mWardNo=null)
    {
        $module_id = $this->_MODULE_ID;
        $mTempTransaction = new TempTransaction();
        $tranReqs = [
            'transaction_id' => $refTransection->id,
            'application_id' => $refTransection->temp_id,
            'module_id' => $module_id,
            'workflow_id' => $refApplication->workflow_id,
            'transaction_no' => $refTransection->tran_no,
            'application_no' => $refApplication->application_no,
            'amount' => $refTransection->paid_amount,
            'payment_mode' => $refTransection->payment_mode,            
            'tran_date' => $refTransection->tran_date,
            'user_id' => $refTransection->emp_dtl_id,
            'ulb_id' => $refTransection->ulb_id,
            'cheque_dd_no' => null,
            'bank_name' => null,
            "ward_no"=>$mWardNo,
        ];
        if ($refTransection->payment_mode != 'CASH') 
        {
            $mChequeDtl = TradeChequeDtl::select("*")
                        ->where("tran_id",$refTransection->id)
                        ->orderBy("id","DESC")
                        ->first();

            $tranReqs ['cheque_dd_no'] = $mChequeDtl->cheque_no??null;
            $tranReqs ['bank_name'] = $mChequeDtl->bank_name??null;
           
        }
        $mTempTransaction->tempTransaction($tranReqs);
        $sms = trade(["ammount"=>$refTransection->paid_amount,"application_no"=>$refApplication->application_no,"ref_no"=>$refTransection->tran_no],"Payment done");
        
        if($sms["status"])
        {
            $owners = $this->getAllOwnereDtlByLId($refApplication->id);
            foreach($owners as $val)
            {
                $respons=send_sms($val["mobile_no"],$sms["sms"],$sms["temp_id"]);
            }

        }

    }
    # Serial No : 02
    public function updateLicenseBo(Request $request)
    {
        try {
            $refUser            = Auth()->user();
            $refUserId          = $refUser->id;
            $refUlbId           = $refUser->ulb_id;
            $refWorkflowId = $this->_WF_MASTER_Id ;
            $mUserType          = $this->_COMMON_FUNCTION->userType($refWorkflowId);
            if (in_array(strtoupper($mUserType), ["SUPER ADMIN", "BO"])) {
                $data['wardList'] = $this->_MODEL_WARD->getAllWard($refUlbId)->map(function ($val) {
                    $val->ward_no = $val->ward_name;
                    return $val;
                });
                $data['wardList'] = objToArray($data['wardList']);
            } else {
                $data['wardList'] = $this->_COMMON_FUNCTION->WardPermission($refUserId);
            }
            $mLicenceId         = $request->initialBusinessDetails['id'];
            $refOldLicece       = $this->getActiveLicenseById($mLicenceId);
            if (!$refOldLicece) {
                throw new Exception("No Licence Found");
            }
            $refOldOwneres = ActiveTradeOwner::owneresByLId($mLicenceId);
            $mnaturOfBusiness = !empty(trim($refOldLicece->nature_of_bussiness))?TradeParamItemType::itemsById($refOldLicece->nature_of_bussiness):[];
            $natur = array();
            foreach ($mnaturOfBusiness as $val) {
                $natur[] = [
                    "id" => $val->id,
                    "trade_item" => "(" . $val->trade_code . ") " . $val->trade_item
                ];
            }
            $refOldLicece->nature_of_bussiness = $natur;

            $data["licenceDtl"]         =  $refOldLicece;
            $data["ownerDtl"]           = $refOldOwneres;
            $data['userType']           = $mUserType;
            $data["firmTypeList"]       = TradeParamFirmType::List();
            $data["ownershipTypeList"]  = TradeParamOwnershipType::List();
            $data["categoryTypeList"]   = TradeParamCategoryType::List();
            $data["natureOfBusiness"]   = TradeParamItemType::List(true);
            return responseMsg(true, "", remove_null($data));
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }
    public function updateBasicDtl(Request $request)
    {
        $user       = Auth()->user();
        $refUserId  = $user->id;
        $refUlbId   = $user->ulb_id;
        $redis      = new Redis;
        $mUserData  = json_decode($redis::get('user:' . $refUserId), true);
        $refWorkflowId = $this->_WF_MASTER_Id ;
        $rollId     =  $mUserData['role_id'] ?? ($this->_COMMON_FUNCTION->getUserRoll($refUserId, $refUlbId, $refWorkflowId)->role_id ?? -1);

        $mUserType = $this->_COMMON_FUNCTION->userType($refWorkflowId);
        $mProprtyId = null;
        $rules = [];
        $message = [];

        $mRegex = '/^[a-zA-Z1-9][a-zA-Z1-9\.\, \s]+$/';
        $mFramNameRegex = '/^[a-zA-Z1-9][a-zA-Z1-9\.&\s]+$/';
        try {
            if ($rollId == -1 || (!in_array($mUserType, ['BO', 'SUPER ADMIN']))) 
            {
                throw new Exception("You Are Not Authorized");
            }
            $mLicenceId         = $request->initialBusinessDetails['id'];
            $refOldLicece       = ActiveTradeLicence::find($mLicenceId);
            if (!$refOldLicece) 
            {
                throw new Exception("No Licence Found");
            } 
            if ($refOldLicece->payment_status == 0) 
            {
                $rules["firmDetails.areaSqft"] = "required|numeric";
                $rules["firmDetails.firmEstdDate"] = "required|date";
                $rules["firmDetails.natureOfBusiness"] = "required|array";
                $rules["firmDetails.natureOfBusiness.*.id"] = "required|digits_between:1,9223372036854775807";
                $rules["firmDetails.tocStatus"] = "required|bool";
            }
            $rules["firmDetails.businessAddress"] = "required|regex:$mRegex";
            $rules["firmDetails.businessAddress"] = "required|regex:$mRegex";
            $rules["firmDetails.businessDescription"] = "required|regex:$mRegex";
            $rules["firmDetails.firmName"] = "required|regex:$mFramNameRegex";
            $rules["firmDetails.premisesOwner"] = "required|regex:$mRegex";
            $rules["firmDetails.newWardNo"] = "required|digits_between:1,9223372036854775807";
            $rules["firmDetails.wardNo"] = "required|digits_between:1,9223372036854775807";
            $rules["firmDetails.pincode"] = "required|digits:6|regex:/[0-9]{6}/|nullable";

            $rules["firmDetails.landmark"] = "regex:$mRegex|nullable";
            $rules["firmDetails.kNo"] = "digits|regex:/[0-9]{10}/";
            $rules["firmDetails.bindBookNo"] = "regex:$mRegex";
            $rules["firmDetails.accountNo"] = "regex:$mRegex";

            $rules["initialBusinessDetails.firmType"] = "required|digits_between:1,9223372036854775807";
            $rules["initialBusinessDetails.categoryTypeId"] = "required|digits_between:1,9223372036854775807";
            if (isset($request->initialBusinessDetails['firmType']) && $request->initialBusinessDetails['firmType'] == 5) {
                $rules["initialBusinessDetails.otherFirmType"] = "required|regex:$mRegex";
            }
            $rules["initialBusinessDetails.ownershipType"] = "required|digits_between:1,9223372036854775807";

            $rules["ownerDetails"] = "required|array";
            $rules["ownerDetails.*.id"] = "nullable|digits_between:1,9223372036854775807";
            $rules["ownerDetails.*.businessOwnerName"] = "required|regex:/^([a-zA-Z]+)(\s[a-zA-Z0-9]+)*$/";
            $rules["ownerDetails.*.guardianName"] = "regex:/^([a-zA-Z]+)(\s[a-zA-Z0-9]+)*$/|nullable";
            $rules["ownerDetails.*.mobileNo"] = "required|digits:10|regex:/[0-9]{10}/";
            $rules["ownerDetails.*.email"] = "email|nullable";

            $validator = Validator::make($request->all(), $rules, $message);
            if ($validator->fails()) {
                return responseMsg(false, $validator->errors(), $request->all());
            }
            $mnaturOfBusiness = array_map(function ($val) {
                return $val['id'];
            }, $request->firmDetails['natureOfBusiness']);
            $mnaturOfBusiness = implode(',', $mnaturOfBusiness);
            if ($request->firmDetails['holdingNo']) 
            {
                $property = $this->propertyDetailsfortradebyHoldingNo($request->firmDetails['holdingNo'], $refUlbId);
                if ($property['status'])
                    $mProprtyId = $property['property']['id'];
                else
                    throw new Exception("Property Details Not Found");
            }
            DB::beginTransaction();

            if ($refOldLicece->payment_status == 0) 
            {
                $refOldLicece->area_in_sqft        = $request->firmDetails['areaSqft'];
                $refOldLicece->establishment_date  = $request->firmDetails['firmEstdDate'];
                $refOldLicece->nature_of_bussiness = $mnaturOfBusiness;
                $refOldLicece->is_tobacco      = $request->firmDetails['tocStatus'];
                if ($refOldLicece->is_tobacco) 
                {
                    $refOldLicece->licence_for_years   = 1;
                    $refOldLicece->nature_of_bussiness = 187;
                }
            }
            $refOldLicece->firm_type_id        = $request->initialBusinessDetails['firmType'];
            $refOldLicece->firm_description    = $request->initialBusinessDetails['otherFirmType'] ?? null;
            $refOldLicece->category_type_id    = $request->initialBusinessDetails['categoryTypeId'] ?? null;
            $refOldLicece->ownership_type_id   = $request->initialBusinessDetails['ownershipType'];
            $refOldLicece->ward_id             = $request->firmDetails['wardNo'];
            $refOldLicece->new_ward_id         = $request->firmDetails['newWardNo'];

            $refOldLicece->property_id         = $mProprtyId;
            $refOldLicece->holding_no          = $request->firmDetails['holdingNo'];
            $refOldLicece->firm_name           = $request->firmDetails['firmName'];
            $refOldLicece->premises_owner_name = $request->firmDetails['premisesOwner'] ?? null;
            $refOldLicece->brief_firm_desc     = $request->firmDetails['businessDescription'];

            $refOldLicece->k_no                = $request->firmDetails['kNo'] ?? null;
            $refOldLicece->bind_book_no        = $request->firmDetails['bindBookNo'] ?? null;
            $refOldLicece->account_no          = $request->firmDetails['accountNo'] ?? null;
            $refOldLicece->pan_no              = $request->firmDetails['panNo'] ?? null;
            $refOldLicece->tin_no              = $request->firmDetails['tinNo'] ?? null;
            $refOldLicece->salestax_no         = $request->firmDetails['salestaxNo'] ?? null;
            $refOldLicece->address             = $request->firmDetails['businessAddress'];
            $refOldLicece->landmark            = $request->firmDetails['landmark'] ?? null;
            $refOldLicece->pin_code            = $request->firmDetails['pincode'] ?? null;
            $refOldLicece->street_name         = $request->firmDetails['streetName'] ?? null;
            $refOldLicece->update();
            foreach ($request->ownerDetails as $owner) {
                if (isset($owner['id']) && trim($owner['id'])) {
                    $refOldOwner = ActiveTradeOwner::find($owner['id']);
                    if (!$refOldOwner || $refOldOwner->temp_id != $mLicenceId) {
                        throw new Exception("Invalid Owner Id Supply!!!");
                    }
                    $refOldOwner->owner_name       = $owner['businessOwnerName'];
                    $refOldOwner->guardian_name    = $owner['guardianName'] ?? null;
                    $refOldOwner->address          = $owner['address'] ?? null;
                    $refOldOwner->mobile_no           = $owner['mobileNo'];
                    $refOldOwner->city             = $owner['city'] ?? null;
                    $refOldOwner->district         = $owner['district'] ?? null;
                    $refOldOwner->state            = $owner['state'] ?? null;
                    $refOldOwner->email_id          = $owner['email'] ?? null;
                    $refOldOwner->update();
                } elseif (!$refOldLicece->is_doc_verified) {
                    $newOwner = new ActiveTradeOwner();
                    $newOwner->temp_id      = $mLicenceId;
                    $newOwner->owner_name      = $owner['businessOwnerName'];
                    $newOwner->guardian_name   = $owner['guardianName'] ?? null;
                    $newOwner->address         = $owner['address'] ?? null;
                    $newOwner->mobile_no       = $owner['mobileNo'];
                    $newOwner->city            = $owner['city'] ?? null;
                    $newOwner->district        = $owner['district'] ?? null;
                    $newOwner->state           = $owner['state'] ?? null;
                    $newOwner->email_id        = $owner['email'] ?? null;
                    $newOwner->user_id         = $refUserId;
                    $newOwner->save();
                } else {
                    throw new Exception("You Can Not Update Owner Document is VeriFy On " . $refOldLicece->doc_verify_date . " !!!");
                }
            }
            DB::commit();
            return responseMsg(true, "Application Update SuccessFully!", "");
        } catch (Exception $e) {            
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }

    # Serial No : 04
    public function readPaymentReceipt($id, $transectionId) # unauthorised  function
    {
        try {
            $application = ActiveTradeLicence::select(
                "application_no",
                "provisional_license_no",
                "license_no",
                "firm_name",
                "holding_no",
                "address",
                "owner.owner_name",
                "owner.guardian_name",
                "owner.mobile",
                DB::raw("ulb_ward_masters.ward_name AS ward_no, 
                                            ulb_masters.id as ulb_id, ulb_masters.ulb_name,ulb_masters.ulb_type
                                            ")
            )
                ->join("ulb_masters", "ulb_masters.id", "active_trade_licences.ulb_id")
                ->join("ulb_ward_masters", function ($join) {
                    $join->on("ulb_ward_masters.id", "=", "active_trade_licences.ward_id");
                })
                ->leftjoin(DB::raw("(SELECT STRING_AGG(owner_name,',') as owner_name,
                                                STRING_AGG(guardian_name,',') as guardian_name,
                                                STRING_AGG(mobile_no::text,',') as mobile,
                                                temp_id
                                            FROM active_trade_owners 
                                            WHERE temp_id = $id
                                                AND is_active  = TRUE
                                            GROUP BY temp_id
                                            ) owner"), function ($join) {
                    $join->on("owner.temp_id", "=", "active_trade_licences.id");
                })
                ->where('active_trade_licences.id', $id)
                ->first();
            if (!$application) {
                $application = TradeLicence::select(
                    "application_no",
                    "provisional_license_no",
                    "license_no",
                    "firm_name",
                    "holding_no",
                    "address",
                    "owner.owner_name",
                    "owner.guardian_name",
                    "owner.mobile",
                    DB::raw("ulb_ward_masters.ward_name AS ward_no, 
                                        ulb_masters.id as ulb_id, ulb_masters.ulb_name,ulb_masters.ulb_type
                                        ")
                )
                    ->join("ulb_masters", "ulb_masters.id", "trade_licences.ulb_id")
                    ->join("ulb_ward_masters", function ($join) {
                        $join->on("ulb_ward_masters.id", "=", "trade_licences.ward_id");
                    })
                    ->leftjoin(DB::raw("(SELECT STRING_AGG(owner_name,',') as owner_name,
                                                STRING_AGG(guardian_name,',') as guardian_name,
                                                STRING_AGG(mobile_no,',') as mobile,
                                                temp_id
                                            FROM trade_owners 
                                            WHERE temp_id = $id
                                                AND is_active = TRUE
                                            GROUP BY temp_id
                                            ) owner"), function ($join) {
                        $join->on("owner.temp_id", "=", "trade_licences.id");
                    })
                    ->where('trade_licences.id', $id)
                    ->first();
            }
            if (!$application) {
                $application = RejectedTradeLicence::select(
                    "application_no",
                    "provisional_license_no",
                    "license_no",
                    "firm_name",
                    "holding_no",
                    "address",
                    "owner.owner_name",
                    "owner.guardian_name",
                    "owner.mobile",
                    DB::raw("ulb_ward_masters.ward_name AS ward_no, 
                                        ulb_masters.id as ulb_id, ulb_masters.ulb_name,ulb_masters.ulb_type
                                        ")
                )
                    ->join("ulb_masters", "ulb_masters.id", "rejected_trade_licences.ulb_id")
                    ->join("ulb_ward_masters", function ($join) {
                        $join->on("ulb_ward_masters.id", "=", "rejected_trade_licences.ward_id");
                    })
                    ->leftjoin(DB::raw("(SELECT STRING_AGG(owner_name,',') as owner_name,
                                                STRING_AGG(guardian_name,',') as guardian_name,
                                                STRING_AGG(mobile_no,',') as mobile,
                                                temp_id
                                            FROM rejected_trade_owners 
                                            WHERE temp_id = $id
                                                AND is_active = TRUE
                                            GROUP BY temp_id
                                            ) owner"), function ($join) {
                        $join->on("owner.temp_id", "=", "rejected_trade_licences.id");
                    })
                    ->where('rejected_trade_licences.id', $id)
                    ->first();
            }
            if (!$application) {
                $application = TradeRenewal::select(
                    "application_no",
                    "provisional_license_no",
                    "license_no",
                    "firm_name",
                    "holding_no",
                    "address",
                    "owner.owner_name",
                    "owner.guardian_name",
                    "owner.mobile",
                    DB::raw("ulb_ward_masters.ward_name AS ward_no, 
                                        ulb_masters.id as ulb_id, ulb_masters.ulb_name,ulb_masters.ulb_type
                                        ")
                )
                    ->join("ulb_masters", "ulb_masters.id", "trade_renewals.ulb_id")
                    ->join("ulb_ward_masters", function ($join) {
                        $join->on("ulb_ward_masters.id", "=", "trade_renewals.ward_id");
                    })
                    ->leftjoin(DB::raw("(SELECT STRING_AGG(owner_name,',') as owner_name,
                                                STRING_AGG(guardian_name,',') as guardian_name,
                                                STRING_AGG(mobile_no,',') as mobile,
                                                temp_id
                                            FROM trade_owners 
                                            WHERE temp_id = $id
                                                AND is_active = TRUE
                                            GROUP BY temp_id
                                            ) owner"), function ($join) {
                        $join->on("owner.temp_id", "=", "trade_renewals.id");
                    })
                    ->where('trade_renewals.id', $id)
                    ->first();
            }
            if (!$application) {
                throw new Exception("Application Not Found");
            }
            $transaction = TradeTransaction::select(
                "tran_no",
                "tran_type",
                "tran_date",
                "payment_mode",
                "paid_amount",
                "penalty",
                "trade_cheque_dtls.cheque_no",
                "trade_cheque_dtls.cheque_date",
                "trade_cheque_dtls.bank_name",
                "trade_cheque_dtls.branch_name"
            )
                ->leftjoin("trade_cheque_dtls", "trade_cheque_dtls.tran_id", "trade_transactions.id")
                ->where("trade_transactions.id", $transectionId)
                ->where("trade_transactions.temp_id", $id)
                ->whereIn("trade_transactions.status", [1, 2])
                ->first();
            if (!$transaction) {
                throw new Exception("Transaction Not Faound");
            }
            $penalty = TradeFineRebete::select("type", "amount")
                ->where('tran_id', $transectionId)
                ->where("status", 1)
                ->orderBy("id")
                ->get();
            $pen = 0;
            $delay_fee = 0;
            $denial_fee = 0;
            foreach ($penalty as $val) {
                if (strtoupper($val->type) == strtoupper("Delay Apply License")) {
                    $delay_fee = $val->amount;
                } elseif (strtoupper($val->type) == strtoupper("Denial Apply")) {
                    $denial_fee = $val->amount;
                }
                $pen += $val->amount;
            }
            $transaction->rate = number_format(($transaction->paid_amount - $pen), 2);
            $transaction->delay_fee = $delay_fee;
            $transaction->denial_fee = $denial_fee;
            $transaction->paid_amount_in_words = getIndianCurrency($transaction->paid_amount);
            $data = [
                "application" => $application,
                "transaction" => $transaction,
                "penalty"    => $penalty
            ];
            $data['paymentReceipt'] = config('app.url') . "/api/trade/paymentReceipt/" . $id . "/" . $transectionId;
            $data = remove_null($data);
            return responseMsg(true, "", $data);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), '');
        }
    }

    #serial 
    public function getDocList(Request $request)
    {
        try {

            $refUser = Auth()->user();
            $refUserId = $refUser->id;
            $refUlbId = $refUser->ulb_id;
            $refLicence = null;
            $refOwneres = (array)null;
            $mUploadDocument = (array)null;
            $mDocumentsList  = (array)null;
            $requiedDocs =   (array) null;
            $ownersDoc = (array) null;
            $testOwnersDoc = (array) null;
            $mItemName = "";
            $mCods = "";
            $mWfActiveDocument = new WfActiveDocument();

            $licenceId = $request->applicationId;
            if (!$licenceId) {
                throw new Exception("Licence Id Required");
            }
            $refLicence = $this->getActiveLicenseById($licenceId);
            if (!$refLicence) {
                throw new Exception("Data Not Found");
            } elseif ($refLicence->doc_verify_date) {
                throw new Exception("Document Verified You Can Not Upload Documents");
            }
            if ($refLicence->nature_of_bussiness) {
                $items = TradeParamItemType::itemsById($refLicence->nature_of_bussiness);
                foreach ($items as $val) {
                    $mItemName .= $val->trade_item . ",";
                    $mCods .= $val->trade_code . ",";
                }
                $mItemName = trim($mItemName, ',');
                $mCods = trim($mCods, ',');
            }
            $refLicence->items = $mItemName;
            $refLicence->items_code = $mCods;
            $refOwneres = ActiveTradeOwner::owneresByLId($licenceId);
            $mUploadDocument = $this->getLicenceDocuments($licenceId)->map(function ($val) {
                if (isset($val["document_path"])) {
                    $path = $this->readDocumentPath($val["document_path"]);
                    $val["document_path"] = !empty(trim($val["document_path"])) ? $path : null;
                }
                return $val;
            });

            $mDocumentsList = $this->getDocumentTypeList($refLicence);
            foreach ($mDocumentsList as $val) {
                $doc = (array) null;
                $doc['docName'] = $val->doc_for;
                $doc['isMadatory'] = $val->is_mandatory;
                $doc['docVal'] = $this->getDocumentList($val->doc_for, $refLicence->application_type_id, $val->show);
                array_push($requiedDocs, $doc);
            }
            
            // return   collect($requiedDocs)->first()['docVal'];
            if ($refLicence->application_type_id == 1) {
                $doc = (array) null;
                $doc['docName'] = "Identity Proof";
                $doc['isMadatory'] = 1;
                $doc['docVal'] = $this->getDocumentList("Identity Proof", $refLicence->application_type_id, 0);
            }
            
            foreach ($requiedDocs as $key => $val) {
                if ($val['docName'] == "Identity Proof") {
                    continue;
                }
                
                $docForId = collect($val['docVal'])->map(function ($value, $key) {
                    return $value['id'];
                });
                $requiedDocs[$key]['uploadDoc'] = $mWfActiveDocument->getTradeAppByAppNoDocId($refLicence->id,$refLicence->ulb_id, [$val['docName']]);
               
                if (isset($requiedDocs[$key]['uploadDoc']->doc_path)) {
                    $path = $this->readDocumentPath($requiedDocs[$key]['uploadDoc']->doc_path);
                    $requiedDocs[$key]['uploadDoc']->doc_path = !empty(trim($requiedDocs[$key]['uploadDoc']->doc_path)) ? $path : null;
                }
            }
            if ($refLicence->application_type_id == 1) {
                foreach ($refOwneres as $key => $val) {
                    $doc = (array) null;
                    $testOwnersDoc[$key] = (array) null;
                    $doc["ownerId"] = $val->id;
                    $doc["ownerName"] = $val->owner_name;
                    $doc["docName"]   = "Identity Proof";
                    $doc['isMadatory'] = 1;
                    $doc['docVal'] = $this->getDocumentList("Identity Proof", $refLicence->application_type_id, 0);
                    
                    $ownerdocForId = collect($doc['docVal'])->map(function ($value, $key) {
                        return $value['id'];
                    });
                    $doc['uploadDoc'] = $mWfActiveDocument->getTradeAppByAppNoDocId($refLicence->id,$refLicence->ulb_id,  [$doc["docName"]],$val->id);
                    
                    if (isset($doc['uploadDoc']->doc_path)) {
                        $path = $this->readDocumentPath($doc['uploadDoc']->doc_path);
                        $doc['uploadDoc']->doc_path = !empty(trim($doc['uploadDoc']->doc_path)) ? $path : null;
                    }
                    array_push($ownersDoc, $doc);
                    array_push($testOwnersDoc[$key], $doc);
                    $doc2 = (array) null;
                    $doc2["ownerId"] = $val->id;
                    $doc2["ownerName"] = $val->owner_name;
                    $doc2["docName"]   = "Owner Image";
                    $doc2['isMadatory'] = 0;
                    $doc2['docVal'] = $this->getDocumentList("Owner Image", $refLicence->application_type_id, 0);
                    

                    $refdocumentId = collect($doc2['docVal'])->map(function ($value, $key) {
                        return $value['id'];
                    });
                    $doc2['uploadDoc'] = $mWfActiveDocument->getTradeAppByAppNoDocId($refLicence->id, $refLicence->ulb_id,[$doc2["docName"]],$val->id);
                    if (isset($doc2['uploadDoc']->doc_path)) {
                        $path = $this->readDocumentPath($doc2['uploadDoc']->doc_path);
                        $doc2['uploadDoc']->doc_path = !empty(trim($doc2['uploadDoc']->doc_path)) ? $path : null;
                    }
                    array_push($ownersDoc, $doc2);
                    array_push($testOwnersDoc[$key], $doc2);
                }
            }
            $data["documentsList"]  = $requiedDocs;
            $data["ownersDocList"]  = $testOwnersDoc;

            return responseMsg(true, "ABC Ok", remove_null($data));
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), '');
        }
    }


    # Serial No : 05
    public function documentUpload(Request $request)
    {
        $refUser = Auth()->user();
        $refUserId = $refUser->id;
        $refUlbId = $refUser->ulb_id;
        $refLicence = null;
        $refOwneres = (array)null;
        $mUploadDocument = (array)null;
        $mDocumentsList  = (array)null;
        $requiedDocs =   (array) null;
        $ownersDoc = (array) null;
        $testOwnersDoc = (array) null;
        $mItemName = "";
        $mCods = "";
        try {
            $licenceId = $request->id;
            if (!$licenceId) {
                throw new Exception("Licence Id Required");
            }
            $refLicence = $this->getActiveLicenseById($licenceId);
            if (!$refLicence) {
                throw new Exception("Data Not Found");
            } elseif ($refLicence->doc_verify_date) {
                throw new Exception("Document Verified You Can Not Upload Documents");
            }
            if ($refLicence->nature_of_bussiness) {
                $items = TradeParamItemType::itemsById($refLicence->nature_of_bussiness);
                foreach ($items as $val) {
                    $mItemName .= $val->trade_item . ",";
                    $mCods .= $val->trade_code . ",";
                }
                $mItemName = trim($mItemName, ',');
                $mCods = trim($mCods, ',');
            }
            $refLicence->items = $mItemName;
            $refLicence->items_code = $mCods;
            $refOwneres = ActiveTradeOwner::owneresByLId($licenceId);
            $mUploadDocument = $this->getLicenceDocuments($licenceId)->map(function ($val) {
                if (isset($val["document_path"])) {
                    $path = $this->readDocumentPath($val["document_path"]);
                    $val["document_path"] = !empty(trim($val["document_path"])) ? $path : null;
                }
                return $val;
            });

            $mDocumentsList = $this->getDocumentTypeList($refLicence);
            foreach ($mDocumentsList as $val) {
                $doc = (array) null;
                $doc['docName'] = $val->doc_for;
                $doc['isMadatory'] = $val->is_mandatory;
                $doc['docVal'] = $this->getDocumentList($val->doc_for, $refLicence->application_type_id, $val->show);
                array_push($requiedDocs, $doc);
            }
            if ($refLicence->application_type_id == 1) {
                $doc = (array) null;
                $doc['docName'] = "Identity Proof";
                $doc['isMadatory'] = 1;
                $doc['docVal'] = $this->getDocumentList("Identity Proof", $refLicence->application_type_id, 0);
            }
            foreach ($requiedDocs as $key => $val) {
                if ($val['docName'] == "Identity Proof") {
                    continue;
                }
                $doc = (array) null;
                $doc = $this->check_doc_exist($licenceId, $val['docName']);
                if (isset($doc["document_path"])) {
                    $path = $this->readDocumentPath($doc["document_path"]);
                    $doc["document_path"] = !empty(trim($doc["document_path"])) ? $path : null;
                }
                $requiedDocs[$key]['uploadDoc'] = $doc;
            }

            if ($refLicence->application_type_id == 1) {
                foreach ($refOwneres as $key => $val) {
                    $doc = (array) null;
                    $testOwnersDoc[$key] = (array) null;
                    $doc["ownerId"] = $val->id;
                    $doc["ownerName"] = $val->owner_name;
                    $doc["docName"]   = "Identity Proof";
                    $doc['isMadatory'] = 1;
                    $doc['docVal'] = $this->getDocumentList("Identity Proof", $refLicence->application_type_id, 0);
                    $refOwneres[$key]["Identity Proof"] = $this->check_doc_exist_owner($licenceId, $val->id);
                    $doc['uploadDoc'] = $refOwneres[$key]["Identity Proof"];
                    if (isset($refOwneres[$key]["Identity Proof"]["document_path"])) {
                        $path = $this->readDocumentPath($refOwneres[$key]["Identity Proof"]["document_path"]);
                        $refOwneres[$key]["Identity Proof"]["document_path"] = !empty(trim($refOwneres[$key]["Identity Proof"]["document_path"])) ? $path : null;
                        $doc['uploadDoc']["document_path"] = $path;
                    }
                    array_push($ownersDoc, $doc);
                    array_push($testOwnersDoc[$key], $doc);
                    $doc2 = (array) null;
                    $doc2["ownerId"] = $val->id;
                    $doc2["ownerName"] = $val->owner_name;
                    $doc2["docName"]   = "image";
                    $doc2['isMadatory'] = 0;
                    $doc2['docVal'][] = ["id" => 0, "doc_name" => "Photo"];
                    $refOwneres[$key]["image"] = $this->check_doc_exist_owner($licenceId, $val->id, 0);
                    $doc2['uploadDoc'] = $refOwneres[$key]["image"];
                    if (isset($refOwneres[$key]["image"]["document_path"])) {
                        $path = $this->readDocumentPath($refOwneres[$key]["image"]["document_path"]);
                        $refOwneres[$key]["image"]["document_path"] = !empty(trim($refOwneres[$key]["image"]["document_path"])) ? $path : null;
                        $doc2['uploadDoc']["document_path"] = $path;
                    }
                    array_push($ownersDoc, $doc2);
                    array_push($testOwnersDoc[$key], $doc2);
                }
            }
            $data["documentsList"]  = $requiedDocs;
            $data["ownersDocList"]  = $testOwnersDoc;
            $data["licence"] = $refLicence;
            $data["owneres"] = $refOwneres;
            $data["uploadDocument"] = $mUploadDocument;
            if ($request->getMethod() == "GET") {
                return responseMsg(true, "", $data);
            }
            if ($request->getMethod() == "POST") {
                DB::beginTransaction();
                $rules = [];
                $message = [];
                $sms = "";
                $cnt = $request->btn_doc_path;
                $doc_for = "doc_path_for$cnt";
                $doc_mstr_id = "doc_path_mstr_id$cnt";
                $owners = objToArray($refOwneres);
                $show = "";
                $ids = [0];
                if (in_array($request->$doc_for, objToArray(collect($mDocumentsList)->pluck("doc_for")))) {
                    $type = ($mDocumentsList->filter(function ($val) use ($request, $doc_for) {
                        return $val->doc_for == $request->$doc_for;
                    }));
                    $show = (collect($type)->pluck("show"))[0];
                    $ids =  (objToArray($this->getDocumentList($request->$doc_for, $refLicence->application_type_id, $show)->pluck("id")));
                } elseif (isset($request->$doc_for) && $request->$doc_for == "Identity Proof" && in_array($request->$doc_for, objToArray(collect($ownersDoc)->pluck("docName")))) {
                    $ids = (objToArray(collect($this->getDocumentList("Identity Proof", $refLicence->application_type_id, 0))->pluck("id")));
                }

                # Upload Document 
                if (isset($request->btn_doc_path) && isset($request->$doc_for) && in_array($request->$doc_for, objToArray(collect($mDocumentsList)->pluck("doc_for")))) {
                    $cnt = $request->btn_doc_path;
                    $rules = [
                        'doc_path' . $cnt => 'required|max:30720|mimes:pdf,jpg,jpeg,png',
                        'doc_path_mstr_id' . $cnt . '' => 'required|int',
                        'doc_path_for' . $cnt => "required|string",
                    ];
                    $validator = Validator::make($request->all(), $rules, $message);
                    if ($validator->fails()) {
                        return responseMsg(false, $validator->errors(), $request->all());
                    }
                    $file = $request->file('doc_path' . $cnt);
                    $doc_for = "doc_path_for$cnt";
                    $doc_mstr_id = "doc_path_mstr_id$cnt";
                    if ($file->IsValid() && in_array($request->$doc_mstr_id, $ids)) {
                        if ($app_doc_dtl_id = $this->check_doc_exist($licenceId, $request->$doc_for)) {
                            if ($app_doc_dtl_id->verify_status == 0) {
                                $delete_path = storage_path('app/public/' . $app_doc_dtl_id['document_path']);
                                if (file_exists($delete_path)) {
                                    unlink($delete_path);
                                }
                                $newFileName = $app_doc_dtl_id['id'];

                                $file_ext = $data["exten"] = $file->getClientOriginalExtension();
                                $fileName = "licence_doc/$newFileName.$file_ext";
                                $filePath = $this->uplodeFile($file, $fileName);
                                $app_doc_dtl_id->document_path =  $filePath;
                                $app_doc_dtl_id->document_id =  $request->$doc_mstr_id;
                                $app_doc_dtl_id->update();
                            } else {
                                $app_doc_dtl_id->status =  0;
                                $app_doc_dtl_id->update();

                                $licencedocs = new ActiveTradeDocument;
                                $licencedocs->temp_id    = $licenceId;
                                $licencedocs->doc_type_code   = $request->$doc_for;
                                $licencedocs->document_id     = $request->$doc_mstr_id;
                                $licencedocs->user_id   = $refUserId;

                                $licencedocs->save();
                                $newFileName = $licencedocs->id;

                                $file_ext = $data["exten"] = $file->getClientOriginalExtension();
                                $fileName = "licence_doc/$newFileName.$file_ext";
                                $filePath = $this->uplodeFile($file, $fileName);
                                $licencedocs->document_path =  $filePath;
                                $licencedocs->update();
                            }
                            $sms = $app_doc_dtl_id->doc_for . " Update Successfully";
                        } else {
                            $licencedocs = new ActiveTradeDocument;
                            $licencedocs->licence_id = $licenceId;
                            $licencedocs->doc_for    = $request->$doc_for;
                            $licencedocs->document_id = $request->$doc_mstr_id;
                            $licencedocs->emp_details_id = $refUserId;

                            $licencedocs->save();
                            $newFileName = $licencedocs->id;

                            $file_ext = $data["exten"] = $file->getClientOriginalExtension();
                            $fileName = "licence_doc/$newFileName.$file_ext";
                            $filePath = $this->uplodeFile($file, $fileName);
                            $licencedocs->document_path =  $filePath;
                            $licencedocs->update();
                            $sms = $licencedocs->doc_for . " Upload Successfully";
                        }
                    } else {
                        return responseMsg(false, "something errors in Document Uploades", $request->all());
                    }
                }
                # Upload Owner Document Id Proof
                elseif (isset($request->btn_doc_path) && isset($request->$doc_for) && $request->$doc_for == "Identity Proof") {
                    $cnt = $request->btn_doc_path;
                    $rules = [
                        'doc_path' . $cnt => 'required|max:30720|mimes:pdf,jpg,jpeg,png',
                        'doc_path_mstr_id' . $cnt . '' => 'required|int',
                        'doc_path_for' . $cnt => "required|string",
                        "owner_id" => "required|digits_between:1,9223372036854775807",
                    ];

                    $validator = Validator::make($request->all(), $rules, $message);
                    if ($validator->fails()) {
                        return responseMsg(false, $validator->errors(), $request->all());
                    }
                    $req_owner_id = $request->owner_id;
                    $woner_id = array_filter($owners, function ($val) use ($req_owner_id) {
                        return $val['id'] == $req_owner_id;
                    });
                    $woner_id = array_values($woner_id)[0] ?? [];
                    if (!$woner_id) {
                        throw new Exception("Invalide Owner Id given!!!");
                    }
                    $file = $request->file('doc_path' . $cnt);
                    $doc_mstr_id = "doc_path_mstr_id$cnt";
                    $doc_for = "doc_path_for$cnt";
                    if ($file->IsValid() && in_array($request->$doc_mstr_id, $ids)) {
                        if ($app_doc_dtl_id = $this->check_doc_exist_owner($licenceId, $request->owner_id)) {
                            if ($app_doc_dtl_id->verify_status == 0) {

                                $delete_path = storage_path('app/public/' . $app_doc_dtl_id['document_path']);
                                if (file_exists($delete_path)) {
                                    unlink($delete_path);
                                }

                                $newFileName = $app_doc_dtl_id['id'];

                                $file_ext = $file->getClientOriginalExtension();
                                $fileName = "licence_doc/$newFileName.$file_ext";
                                $filePath = $this->uplodeFile($file, $fileName);
                                $app_doc_dtl_id->document_path =  $filePath;
                                $app_doc_dtl_id->document_id =  $request->$doc_mstr_id;
                                $app_doc_dtl_id->update();
                            } else {
                                $app_doc_dtl_id->status =  0;
                                $app_doc_dtl_id->update();

                                $licencedocs = new ActiveTradeDocument;
                                $licencedocs->licence_id = $licenceId;
                                $licencedocs->doc_for    = $request->$doc_for;
                                $licencedocs->licence_owner_dtl_id = $request->owner_id;
                                $licencedocs->document_id = $request->$doc_mstr_id;
                                $licencedocs->emp_details_id = $refUserId;

                                $licencedocs->save();
                                $newFileName = $licencedocs->id;

                                $file_ext = $data["exten"] = $file->getClientOriginalExtension();
                                $fileName = "licence_doc/$newFileName.$file_ext";
                                $filePath = $this->uplodeFile($file, $fileName);
                                $licencedocs->document_path =  $filePath;
                                $licencedocs->update();
                            }
                            $sms = "Id Proof of " . $woner_id["owner_name"] . " Update Successfully";
                        } else {
                            $licencedocs = new ActiveTradeDocument;
                            $licencedocs->licence_id = $licenceId;
                            $licencedocs->doc_for    = $request->$doc_for;
                            $licencedocs->licence_owner_dtl_id = $request->owner_id;
                            $licencedocs->document_id = $request->$doc_mstr_id;
                            $licencedocs->emp_details_id = $refUserId;

                            $licencedocs->save();
                            $newFileName = $licencedocs->id;

                            $file_ext = $data["exten"] = $file->getClientOriginalExtension();
                            $fileName = "licence_doc/$newFileName.$file_ext";
                            $filePath = $this->uplodeFile($file, $fileName);
                            $licencedocs->document_path =  $filePath;
                            $licencedocs->update();
                            $sms = "Id Proof of " . $woner_id["owner_name"] . " Upload Successfully";
                        }
                    } else {
                        return responseMsg(false, "something errors in Document Uploades", $request->all());
                    }
                }
                # owner image upload hear 
                elseif (isset($request->btn_doc_path) && isset($request->$doc_for) && $request->$doc_for == "image") {
                    $cnt = $request->btn_doc_path;
                    $rules = [
                        'doc_path' . $cnt => 'required|max:30720|mimes:pdf,jpg,jpeg,png',
                        'doc_path_mstr_id' . $cnt . '' => 'required|int',
                        'doc_path_for' . $cnt => "required|string",
                        "owner_id" => "required|digits_between:1,9223372036854775807",
                    ];
                    $validator = Validator::make($request->all(), $rules, $message);
                    if ($validator->fails()) {
                        return responseMsg(false, $validator->errors(), $request->all());
                    }
                    $req_owner_id = $request->owner_id;
                    $woner_id = array_filter($owners, function ($val) use ($req_owner_id) {
                        return $val['id'] == $req_owner_id;
                    });
                    $woner_id = array_values($woner_id)[0] ?? [];
                    if (!$woner_id) {
                        throw new Exception("Invalide Owner Id given!!!");
                    }
                    $file = $request->file('doc_path' . $cnt);
                    $doc_for = "doc_path_for$cnt";
                    if ($file->IsValid() && in_array($request->$doc_mstr_id, $ids)) {
                        if ($app_doc_dtl_id = $this->check_doc_exist_owner($licenceId, $request->owner_id, 0)) {
                            if ($app_doc_dtl_id->verify_status == 0) {

                                $delete_path = storage_path('app/public/' . $app_doc_dtl_id['document_path']);
                                if (file_exists($delete_path)) {
                                    unlink($delete_path);
                                }

                                $newFileName = $app_doc_dtl_id['id'];
                                $file_ext = $data["exten"] = $file->getClientOriginalExtension();
                                $fileName = "licence_doc/$newFileName.$file_ext";
                                $filePath = $this->uplodeFile($file, $fileName);
                                $app_doc_dtl_id->document_path =  $filePath;
                                $app_doc_dtl_id->document_id =  0;
                                $app_doc_dtl_id->update();
                            } else {
                                $app_doc_dtl_id->status =  0;
                                $app_doc_dtl_id->update();

                                $licencedocs = new ActiveTradeDocument;
                                $licencedocs->licence_id = $licenceId;
                                $licencedocs->doc_for    = $request->$doc_for;
                                $licencedocs->licence_owner_dtl_id = $request->owner_id;
                                $licencedocs->document_id = 0;
                                $licencedocs->emp_details_id = $refUserId;

                                $licencedocs->save();
                                $newFileName = $licencedocs->id;

                                $file_ext = $file->getClientOriginalExtension();
                                $fileName = "licence_doc/$newFileName.$file_ext";
                                $filePath = $this->uplodeFile($file, $fileName);
                                $licencedocs->document_path =  $filePath;
                                $licencedocs->update();
                            }
                            $sms = "Photo of " . $woner_id["owner_name"] . " Update Successfully";
                        } else {
                            $licencedocs = new ActiveTradeDocument;
                            $licencedocs->licence_id = $licenceId;
                            $licencedocs->doc_for    = $request->$doc_for;
                            $licencedocs->licence_owner_dtl_id = $request->owner_id;
                            $licencedocs->document_id = 0;
                            $licencedocs->emp_details_id = $refUserId;

                            $licencedocs->save();
                            $newFileName = $licencedocs->id;

                            $file_ext = $file->getClientOriginalExtension();
                            $fileName = "licence_doc/$newFileName.$file_ext";
                            $filePath = $this->uplodeFile($file, $fileName);
                            $licencedocs->document_path =  $filePath;
                            $licencedocs->update();
                            $sms = "Photo of " . $woner_id["owner_name"] . " Upload Successfully";
                        }
                    } else {
                        return responseMsg(false, "something errors in Document Uploades", $request->all());
                    }
                } else {
                    throw new Exception("Invalid Document type Passe");
                }
                DB::commit();
                $data = (array)null;
                $mUploadDocument = $this->getLicenceDocuments($licenceId)->map(function ($val) {
                    if (isset($val["document_path"])) {
                        $path = $this->readDocumentPath($val["document_path"]);
                        $val["document_path"] = !empty(trim($val["document_path"])) ? $path : null;
                    }
                    return $val;
                });
                $data["uploadDocument"] = $mUploadDocument;
                return responseMsg(true, $sms, $data);
            }
        } catch (Exception $e) {

            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }

    # Serial No : 06
    public function getUploadDocuments(Request $request)
    {
        try {
            $licenceId = $request->id;
            if (!$licenceId) {
                throw new Exception("Licence Id Required");
            }
            $refLicence = $this->getAllLicenceById($licenceId);
            if (!$refLicence) {
                throw new Exception("Data Not Found");
            }
            $tbl = $refLicence->tbl == "active_trade_licences" ? "active_" : ($refLicence->tbl == "rejected_trade_licences" ? "rejected_" : "");
            $mUploadDocument = $this->getLicenceDocuments($licenceId, $tbl)->map(function ($val) {
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

    # Serial No : 07
    public function documentVirify(Request $request)
    {
        $user = Auth()->user();
        $user_id = $user->id;
        $ulb_id = $user->ulb_id;
        $workflow_id = $this->_WF_MASTER_Id ;
        $workflows = $this->_COMMON_FUNCTION->iniatorFinisher($user_id, $ulb_id, $workflow_id);
        $rolles = $this->_COMMON_FUNCTION->getUserRoll($user_id, $ulb_id, $workflow_id);
        $roll_id =  $rolles->role_id ?? null;
        $mUserType = $this->_COMMON_FUNCTION->userType($workflow_id);
        // dd($this->_COMMON_FUNCTION->getUserRoll($user_id, $ulb_id, $workflow_id));
        $licenceId = $request->licenceId;
        $rules = [];
        $message = [];
        try {
            if (!$roll_id || !$rolles->can_verify_document) {
                throw new Exception("You are Not Authorized For Document Verify");
            }
            if (!$licenceId) {
                throw new Exception("Data Not Found");
            }
            $licence = $this->getActiveLicenseById($licenceId);
            if (!$licence) {
                throw new Exception("Data Not Found2");
            }
            $item_name = "";
            $cods = "";
            if ($licence->nature_of_bussiness) {
                $items = TradeParamItemType::itemsById($licence->nature_of_bussiness);
                foreach ($items as $val) {
                    $item_name .= $val->trade_item . ",";
                    $cods .= $val->trade_code . ",";
                }
                $item_name = trim($item_name, ',');
                $cods = trim($cods, ',');
            }
            $licence->items = $item_name;
            $licence->items_code = $cods;
            $owneres = $this->getAllOwnereDtlByLId($licenceId);
            $mUploadDocument = $this->getLicenceDocuments($licenceId)->map(function ($val) {
                if (isset($val["document_path"])) {
                    $path = $this->readDocumentPath($val["document_path"]);
                    $val["document_path"] = !empty(trim($val["document_path"])) ? $path : null;
                }
                return $val;
            });
            foreach ($owneres as $key => $val) {
                $owneres[$key]["Identity Proof"] = $this->check_doc_exist_owner($licenceId, $val->id);
                if (isset($refOwneres[$key]["Identity Proof"]["document_path"])) {
                    $path = $this->readDocumentPath($owneres[$key]["Identity Proof"]["document_path"]);
                    // $refOwneres[$key]["Identity Proof"]["document_path"] = !empty(trim($refOwneres[$key]["Identity Proof"]["document_path"]))?storage_path('app/public/' . $refOwneres[$key]["Identity Proof"]["document_path"]):null;
                    $owneres[$key]["Identity Proof"]["document_path"] = !empty(trim($owneres[$key]["Identity Proof"]["document_path"])) ? $path : null;
                }
                $owneres[$key]["image"] = $this->check_doc_exist_owner($licenceId, $val->id, 0);
                if (isset($owneres[$key]["image"]["document_path"])) {
                    $path = $this->readDocumentPath($owneres[$key]["image"]["document_path"]);
                    $owneres[$key]["image"]["document_path"] = !empty(trim($owneres[$key]["image"]["document_path"])) ? $path : null;
                }
            }
            $data["uploadDocs"] = $mUploadDocument;
            $data["licence"] = $licence;
            $data["owneres"] = $owneres;
            if ($request->getMethod() == "GET") 
            {
                return responseMsg(true, "", remove_null($data));
            } 
            elseif ($request->getMethod() == "POST") 
            {
                $rules = [];
                $message = [];
                $nowdate = Carbon::now()->format('Y-m-d');
                $timstamp = Carbon::now()->format('Y-m-d H:i:s');
                $regex = '/^[a-zA-Z1-9][a-zA-Z1-9\.\s]+$/';
                $rules = [
                    'btn' => 'required|in:verify,reject',
                    'id' => 'required',
                ];
                $status = 1;
                if ($request->btn == "reject") {
                    $status = 2;
                    $rules["comment"] = "required|regex:$regex|min:10";
                }
                $validator = Validator::make($request->all(), $rules, $message);
                if ($validator->fails()) 
                {
                    return responseMsg(false, $validator->errors(), $request->all());
                }
                $level_data = $this->getWorkflowTrack($request->licenceId); //TradeLevelPending::getLevelData($licenceId);
                if (!$level_data || $level_data->receiver_role_id != $roll_id) {
                    throw new Exception("You Are Not Authorized For This Action");
                }
                DB::beginTransaction();
                $tradeDoc = ActiveTradeDocument::find($request->id);
                $tradeDoc->verify_status = $status;
                $tradeDoc->remarks = ($status == 2 ? $request->comment : null);
                $tradeDoc->verified_by_emp_id = $user_id;
                $tradeDoc->lvl_pending_id = $level_data->id;
                $tradeDoc->update();
                DB::commit();
                $sms = $tradeDoc->doc_for . " " . strtoupper($request->btn);
                return responseMsg(true, $sms, "");
            }
        } 
        catch (Exception $e) 
        {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }

    # Serial No : 08 
    /**
     * | Get License All Dtl
     * |-------------------------------------------------------------------------
     * | @var mUserType      = $this->_COMMON_FUNCTION->userType() | login user Role Name
     * | @var refApplication = this->getLicenceById(id)  | read application dtl
     * | @var items          = TradeParamItemType::itemsById(refApplication->nature_of_bussiness) | read trade licence Items
     * | @var refOwnerDtl    = ActiveLicenceOwner::owneresByLId(id)  | read owner dtl
     * | @var refTransactionDtl  = TradeTransaction::listByLicId(id)    | read Transaction Dtl
     * | @var refTimeLine    = this->getTimelin(id)      | read Level remarks
     * | @var refUploadDocuments = this->getLicenceDocuments(id)    | read upload Documents
     */
    public function readLicenceDtl($request)
    {

        try {
            $mWorkflowTracks = new WorkflowTrack();
            $mCustomDetails = new CustomDetail();
            $forwardBackward = new WorkflowMap;
            $id = $request->applicationId;
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id;
            $refUlbId       = $refUser->ulb_id;
            $refWorkflowId  = $this->_WF_MASTER_Id ;
            $mRefTable = $this->_REF_TABLE;

            $init_finish = $this->_COMMON_FUNCTION->iniatorFinisher($refUserId, $refUlbId, $refWorkflowId);
            $finisher = $init_finish['finisher'];
            $finisher['short_user_name'] = $this->_TRADE_CONSTAINT["USER-TYPE-SHORT-NAME"][strtoupper($init_finish['finisher']['role_name'])];
            $mUserType      = $this->_COMMON_FUNCTION->userType($refWorkflowId);
            $refApplication = $this->getAllLicenceById($id);
            $mStatus = $this->applicationStatus($id);
            $mItemName      = "";
            $mCods          = "";
            if (trim($refApplication->nature_of_bussiness)) {
                $items = TradeParamItemType::itemsById($refApplication->nature_of_bussiness);
                foreach ($items as $val) {
                    $mItemName  .= $val->trade_item . ",";
                    $mCods      .= $val->trade_code . ",";
                }
                $mItemName = trim($mItemName, ',');
                $mCods = trim($mCods, ',');
            }
            $tbl = $refApplication->tbl == "active_trade_licences" ? "active_" : ($refApplication->tbl == "rejected_trade_licences" ? "rejected_" : "");
            $refApplication->items      = $mItemName;
            $refApplication->items_code = $mCods;
            $refOwnerDtl                = $this->getAllOwnereDtlByLId($id);
            $refTransactionDtl          = TradeTransaction::listByLicId($id);
            $refTimeLine                = $this->getTimelin($id);
            // $refUploadDocuments         = $this->getLicenceDocuments($id,$tbl)->map(function($val){
            //                                     $val->document_path = !empty(trim($val->document_path))? $this->readDocumentPath($val->document_path):"";
            //                                     return $val;
            //                                 });
            // $pendingAt  = $init_finish['initiator']['id'];
            // $mlevelData = $this->getWorkflowTrack($id);//TradeLevelPending::getLevelData($id);
            // if($mlevelData)
            // {
            //     $pendingAt = $mlevelData->receiver_user_type_id;                
            // }
            $mworkflowRoles = $this->_COMMON_FUNCTION->getWorkFlowAllRoles($refUserId, $refUlbId, $refWorkflowId, true);
            $mileSton = $this->_COMMON_FUNCTION->sortsWorkflowRols($mworkflowRoles);


            $licenseDetail =  $refApplication;
            $ownerDetails  = $refOwnerDtl;
            $transactionDtl = $refTransactionDtl;
            $data['pendingStatus']  = $mStatus;
            $data['remarks']        = $refTimeLine;
            $data["userType"]       = $mUserType;
            $data["roles"]          = $mileSton;

            // $data['documents']      = $refUploadDocuments;   
            // $data["pendingAt"]      = $pendingAt;
            // $data["levelData"]      = $mlevelData;
            // $data['finisher']       = $finisher;

            //=========================================================================================
            // return $data['licenceDtl'];
            $newData = array();
            $fullDetailsData = array();
            $basicDetails = $this->generateBasicDetails($licenseDetail);      // Trait function to get Basic Details
            $basicElement = [
                'headerTitle' => "Basic Details",
                "data" => $basicDetails
            ];

            // Property Details and address
            // $propertyDetails = $this->generatePropertyDetails($licenseDetail);   // Trait function to get Property Details
            // $propertyElement = [
            //     'headerTitle' => "Property Details & Address",
            //     'data' => $propertyDetails
            // ];
            $paymentDetail = sizeOf($transactionDtl) > 0 ? $this->generatepaymentDetails($transactionDtl) : (array) null;      // Trait function to get payment Details
            $paymentElement = [
                'headerTitle' => "Transaction Details",
                'tableHead' => ["#", "Payment For", "Tran No", "Payment Mode", "Date"],
                'tableData' => $paymentDetail,
                // "data" => $paymentDetail
            ];

            $ownerDetails = $this->generateOwnerDetails($ownerDetails);
            $ownerElement = [
                'headerTitle' => 'Owner Details',
                'tableHead' => ["#", "Owner Name", "Gender", "DOB", "Guardian Name", "Relation", "Mobile No", "Aadhar", "PAN", "Email","Address"],
                'tableData' => $ownerDetails
            ];

            $cardDetails = $this->generateCardDetails($licenseDetail, $ownerDetails);
            $cardElement = [
                'headerTitle' => "About Trade",
                'data' => $cardDetails
            ];

            $fullDetailsData['application_no'] = $licenseDetail->application_no;
            $fullDetailsData['apply_date'] = $licenseDetail->application_date;
            $fullDetailsData['fullDetailsData']['dataArray'] = new Collection([$basicElement]);
            $fullDetailsData['fullDetailsData']['tableArray'] = new Collection([$ownerElement,$paymentElement]);
            $fullDetailsData['fullDetailsData']['cardArray'] = $cardElement;

            $metaReqs['customFor'] = 'Trade';
            $metaReqs['wfRoleId'] = $licenseDetail->current_role;
            $metaReqs['workflowId'] = $licenseDetail->workflow_id;
            $metaReqs['lastRoleId'] = $licenseDetail->last_role_id;
            // dd($mRefTable);
            $levelComment = $mWorkflowTracks->getTracksByRefId($mRefTable, $licenseDetail->id);
            $fullDetailsData['levelComment'] = $levelComment;

            $citizenComment = $mWorkflowTracks->getCitizenTracks($mRefTable, $licenseDetail->id, $licenseDetail->user_id);
            $fullDetailsData['citizenComment'] = $citizenComment;

            $request->request->add($metaReqs);
            $forwardBackward = $forwardBackward->getRoleDetails($request);
            $fullDetailsData['roleDetails'] = collect($forwardBackward)['original']['data'];

            $fullDetailsData['timelineData'] = collect($request);

            $custom = $mCustomDetails->getCustomDetails($request);
            $fullDetailsData['departmentalPost'] = collect($custom)['original']['data'];

            return responseMsgs(true, 'Data Fetched', remove_null($fullDetailsData), "010104", "1.0", "303ms", "POST", $request->deviceId);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), '');
        }
    }

    # Serial No : 09 
    /**
     * | Get Notice Data
     */
    public function readDenialdtlbyNoticno(Request $request)
    {
        $data = (array)null;
        $refUser = Auth()->user();
        $refUlbId = $refUser->ulb_id??$request->ulbId;
        $mNoticeNo = null;
        $mNowDate = Carbon::now()->format('Y-m-d'); // todays date
        try {
            $rules = [
                "noticeNo" => "required|string",
                "ulbId"=>$refUlbId?"nullable":"required",
            ];
            $validator = Validator::make($request->all(), $rules,);
            if ($validator->fails()) {
                return responseMsg(false, $validator->errors(), $request->all());
            }
            $mNoticeNo = $request->noticeNo;

            $refDenialDetails = $this->getDenialFirmDetails($refUlbId, strtoupper(trim($mNoticeNo)));
            if ($refDenialDetails && $refDenialDetails->is_active) {
                $notice_date = Carbon::parse($refDenialDetails->noticedate)->format('Y-m-d'); //notice date
                $denialAmount = $this->getDenialAmountTrade($notice_date, $mNowDate);
                $data['denialDetails'] = $refDenialDetails;
                $data['denialAmount'] = $denialAmount;
                return responseMsg(true, "", $data);
            } else {
                $response = "no Data";
                return responseMsg(false, $response, $request->all());
            }
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }

    # Serial No : 10 
    /**
     * | @var data = this->cltCharge(data) | get the calculated Charge
     */
    public function getPaybleAmount(Request $request)
    {
        try {
            $mNoticeDate = null;
            $data['application_type_id'] =$this->_TRADE_CONSTAINT["APPLICATION-TYPE"][$request->applicationType];
            if (!$data['application_type_id']) {
                throw new Exception("Invalide Application Type");
            }
            $mNatureOfBussiness = array_map(function ($val) {
                return $val['id'];
            }, $request->natureOfBusiness);

            $mNatureOfBussiness = implode(',', $mNatureOfBussiness);
            if ($request->licenceId && ($refLecenceData = ActiveTradeLicence::find($request->licenceId))
                    &&($refNoticeDetails = $this->readNotisDtl($refLecenceData->denial_id))) 
            {
                $refDenialId = $refNoticeDetails->dnialid;
                $mNoticeDate = date("Y-m-d", strtotime($refNoticeDetails['created_on'])); //notice date 
            }

            $data["areaSqft"]       = $request->areaSqft;
            $data['curdate']        = Carbon::now()->format('Y-m-d');
            $data["firmEstdDate"]   = $request->firmEstdDate;
            $data["tobacco_status"] = $request->tocStatus;
            $data['noticeDate']     = $request->noticeDate ? $request->noticeDate :$mNoticeDate;
            $data["licenseFor"]     = $request->licenseFor;
            $data["apply_licence_id"]  = $request->licenceId??null;
            $data["nature_of_business"] = $mNatureOfBussiness;

            $data = $this->cltCharge($data);
            if ($data['response'])
                return responseMsg(true, "", $data);
            else
                throw new Exception("some Errors on Calculation");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }

    # Serial No : 11 
    public function isvalidateSaf(Request $request)
    {
        $ferUser = Auth()->user();
        $ferUlbId = $ferUser->ulb_id ?? $request->ulbId;
        if ($request->getMethod() == "POST") {
            $refWorkflowId      = $this->_WF_MASTER_Id ;
            $mUserType          = $this->_COMMON_FUNCTION->userType($refWorkflowId);
            $rules = [
                "safNo" => "required|string",
            ];
            if ($mUserType == "ONLINE") {
                $rules["ulbId"] = "required|digits_between:1,92";
            }
            $validator = Validator::make($request->all(), $rules,);
            if ($validator->fails()) {
                return responseMsg(false, $validator->errors(), $request->all());
            }
            $inputs = $request->all();
            $saf_no = $inputs['safNo'] ?? null;
            $safdet = $this->getSafDtlBySafno($saf_no, $ferUlbId);
            if ($safdet['status']) {
                $response = ['response' => true, $safdet];
            } else {
                $response = ['response' => false];
            }
        } else {
            $response = ['response' => false];
        }
        return json_encode($response);
    }

    # Serial No : 12 
    public function isvalidateHolding(Request $request)
    {
        $refUser = Auth()->user();
        $refUserId = $refUser->id;
        $refUlbId = $refUser->ulb_id ?? $request->ulbId;
        if ($request->getMethod() == "POST") {
            $refWorkflowId      = $this->_WF_MASTER_Id ;
            $mUserType          = $this->_COMMON_FUNCTION->userType($refWorkflowId);
            $rules = [
                "holdingNo" => "required|string",
            ];
            if ($mUserType == "Online") {
                $rules["ulbId"] = "required|digits_between:1,92";
            }
            $validator = Validator::make($request->all(), $rules,);
            if ($validator->fails()) {
                return responseMsg(false, $validator->errors(), $request->all());
            }
            $inputs = $request->all();

            $propdet = $this->propertyDetailsfortradebyHoldingNo($inputs['holdingNo'], $refUlbId);
            if ($propdet['status']) {
                $response = ['status' => true, "data" => ["property" => $propdet['property']], "message" => ""];
            } else {
                $response = ['status' => false, "data" => '', "message" => 'No Property Found'];
            }
        } else {
            $response = ['status' => false, "data" => '', "message" => 'Onlly Post Allowed'];
        }
        return responseMsg($response['status'], $response["message"], remove_null($response["data"]));
    }

    # Serial No : 13 
    /**
     * | Validate The Licence No Befor Apply(reniwal/surrend/amendment)
        query cost(***)
     * |----------------------------------------------------------------
     * |-------------------Request--------------------------------------
     * |    1. licenceNo
     * |-----------------------------------------------------------------
     * | @var refUser    = Auth()->user()
     * | @var refUserId  = refUser->id
     * | @var refUlbId   = refUser->ulb_id
     * | @var mNextMonth = Carbon::now()->addMonths(1)->format('Y-m-d')
     * | @var mApplicationTypeId = request->applicationType
     * | @var mLicenceNo = $request->licenceNo
     * | @var data       std class object 
     * |------------------------- validation ----------------------------
     * | case 1) empty(data)                                                    -> data not Existing on given license No
     * |      2) (data->valid_upto > mNextMonth && mApplicationTypeId!=4 )      -> Applicant Can Apply  reniwal/amendment Only whene Existing License Validation Remains 1 Months
     * |      3) (data->pending_status!=5)                                      -> Current Application Not Approved And It Is On Owrkflow
     * |      4) (mApplicationTypeId==4 && data->valid_upto < Carbon::now()->format('Y-m-d'))  -> Applicant Can Apply surrend When Current Application Is Valide 
     * |           
     */
    public function searchLicenceByNo(Request $request) // reniwal/surrend/amendment
    {
        try {
            $refUser    = Auth()->user();
            $refUlbId   = $refUser->ulb_id ?? $request->ulbId;
            $mNextMonth = Carbon::now()->addMonths(1)->format('Y-m-d');
            $refWorkflowId      = $this->_WF_MASTER_Id ;
            $mUserType          = $this->_COMMON_FUNCTION->userType($refWorkflowId);
            if (in_array(strtoupper($mUserType), ["ONLINE"])) {
                $rules["ulbId"]     = "required|digits_between:1,92";
            }

            $rules["licenceNo"]     = "required";
            $message["licenceNo.required"] = "Licence No Required";
            $rules["applicationType"] = "required:int";
            $message["applicationType.required"] = "Application Type Id Is Required";

            $validator = Validator::make($request->all(), $rules, $message);
            if ($validator->fails()) {
                return responseMsg(false, $validator->errors(), $request->all());
            }

            $mApplicationTypeId = $request->applicationType;    //if application type is for new, renewal, amendment or surrender btw 1,2,3,4
            $mLicenceNo = $request->licenceNo;
            if (!in_array($mApplicationTypeId, [1, 2, 3, 4])) {
                throw new Exception("Invalid Application Type Supplied");
            } elseif ($mApplicationTypeId == 1) {
                throw new Exception("You Can Not Apply New Licence");
            }
            DB::enableQueryLog();

            $data = TradeLicence::select(
                "trade_licences.*",
                "owner.*",
                DB::raw("ulb_ward_masters.ward_name as ward_no,'trade_licences' AS tbl")
            )
                ->join("ulb_ward_masters", "ulb_ward_masters.id", "=", "trade_licences.ward_id")
                ->leftjoin(
                    DB::raw("(SELECT temp_id,
                                    string_agg(owner_name,',') as owner_name,
                                    string_agg(guardian_name,',') as guardian_name,
                                    string_agg(mobile_no,',') as mobile
                                    FROM trade_owners
                                    WHERE is_active =true
                                    GROUP BY temp_id
                                    ) owner
                                    "),
                    function ($join) {
                        $join->on("owner.temp_id", "=",  "trade_licences.id");
                    }
                )
                ->where('trade_licences.is_active', TRUE)
                ->where('trade_licences.license_no', $mLicenceNo)
                ->where("trade_licences.ulb_id", $refUlbId)
                ->first();

            if (!$data) {
                $data = ActiveTradeLicence::select(
                    "active_trade_licences.*",
                    "owner.*",
                    DB::raw("ulb_ward_masters.ward_name as ward_no,'active_trade_licences' AS tbl")
                )
                    ->join("ulb_ward_masters", "ulb_ward_masters.id", "=", "active_trade_licences.ward_id")
                    ->leftjoin(
                        DB::raw("(SELECT temp_id,
                                string_agg(owner_name,',') as owner_name,
                                string_agg(guardian_name,',') as guardian_name,
                                string_agg(mobile_no,',') as mobile
                                FROM active_trade_owners
                                WHERE is_active =true
                                GROUP BY temp_id
                                ) owner
                                "),
                        function ($join) {
                            $join->on("owner.temp_id", "=",  "active_trade_licences.id");
                        }
                    )
                    ->where('active_trade_licences.is_active', TRUE)
                    ->where('active_trade_licences.license_no', $mLicenceNo)
                    ->where("active_trade_licences.ulb_id", $refUlbId)
                    ->first();
            }
            if (!$data) {
                $data = RejectedTradeLicence::select(
                    "rejected_trade_licences.*",
                    "owner.*",
                    DB::raw("ulb_ward_masters.ward_name as ward_no,'rejected_trade_licences' AS tbl")
                )
                    ->join("ulb_ward_masters", "ulb_ward_masters.id", "=", "rejected_trade_licences.ward_id")
                    ->leftjoin(
                        DB::raw("(SELECT temp_id,
                                string_agg(owner_name,',') as owner_name,
                                string_agg(guardian_name,',') as guardian_name,
                                string_agg(mobile_no,',') as mobile
                                FROM rejected_trade_owners
                                WHERE is_active =true
                                GROUP BY temp_id
                                ) owner
                                "),
                        function ($join) {
                            $join->on("owner.temp_id", "=",  "rejected_trade_licences.id");
                        }
                    )
                    ->where('rejected_trade_licences.is_active', TRUE)
                    ->where('rejected_trade_licences.license_no', $mLicenceNo)
                    ->where("rejected_trade_licences.ulb_id", $refUlbId)

                    ->first();
            }
            if (!$data) 
            {
                throw new Exception("No Data Found");
            } 
            elseif ($data->valid_upto > $mNextMonth && !in_array($mApplicationTypeId,[4,3]) && $data->tbl == "trade_licences") 
            {
                throw new Exception("Licence Valid Upto " . $data->valid_upto);
            } 
            elseif ($data->tbl == "active_trade_licences") 
            {
                throw new Exception("Application Already Applied. Please Track  " . $data->application_no);
            }
            if ($mApplicationTypeId == 4 && $data->valid_upto < Carbon::now()->format('Y-m-d') && $data->tbl == "trade_licences") 
            {
                throw new Exception("You Can Not Apply Surrender. Application No: " . $data->application_no . " Of Licence No: " . $data->license_no . " Expired On " . $data->valid_upto . ".");
            }
            if ($mApplicationTypeId == 3 && $data->valid_upto < Carbon::now()->format('Y-m-d') && $data->tbl == "trade_licences") 
            {
                throw new Exception("You Can Not Apply Amendment. Application No: " . $data->application_no . " Of Licence No: " . $data->license_no . " Expired On " . $data->valid_upto . ".");
            }
            return responseMsg(true, "", remove_null($data));
        } 
        catch (Exception $e) 
        {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }

    # Serial No : 14
    /**
     * | Get 10 Application List Only
         query cost(**)
     * |----------------------------------------------------------------------------
     * |---------------------------Request------------------------------------------
     * |    1. entityValue
     * |    2. entityName
     * |
     * |----------------------------------------------------------------------------
     * | @var refUser   = Auth()->user()
     * | @var refUlbId  = refUser->ulb_id
     * | @var mInputs   = request->all() 
     */
    public function readApplication(Request $request)
    {
        try {
            $refUser    = Auth()->user();
            $refUlbId   = $refUser->ulb_id;
            $mInputs    = $request->all();DB::enableQueryLog();
            $licence = ActiveTradeLicence::select(
                "active_trade_licences.id",
                "active_trade_licences.application_no",
                "active_trade_licences.provisional_license_no",
                "active_trade_licences.license_no",
                "active_trade_licences.firm_name",
                "active_trade_licences.application_date",
                "active_trade_licences.apply_from",
                "active_trade_licences.valid_upto",                
                "owner.owner_name",
                "owner.guardian_name",
                "owner.mobile_no",
                "owner.email_id",
                DB::raw("'pending' as type"),
            )
            ->leftjoin(DB::raw("(select STRING_AGG(owner_name,',') AS owner_name,
                                        STRING_AGG(guardian_name,',') AS guardian_name,
                                        STRING_AGG(mobile_no::TEXT,',') AS mobile_no,
                                        STRING_AGG(email_id,',') AS email_id,
                                        temp_id
                                    FROM active_trade_owners 
                                    WHERE is_active  =TRUE
                                    GROUP BY temp_id
                                    )owner"), function ($join) {
                $join->on("owner.temp_id", "active_trade_licences.id");
            });
            // ->where("active_trade_licences.status",1) 

            $aropved = TradeLicence::select(
                "trade_licences.id",
                "trade_licences.application_no",
                "trade_licences.provisional_license_no",
                "trade_licences.license_no",
                "trade_licences.firm_name",
                "trade_licences.application_date",
                "trade_licences.apply_from",
                "trade_licences.valid_upto",
                "owner.owner_name",
                "owner.guardian_name",
                "owner.mobile_no",
                "owner.email_id",
                DB::raw("'Approved' as type"),
            )
            ->leftjoin(DB::raw("(select STRING_AGG(owner_name,',') AS owner_name,
                                        STRING_AGG(guardian_name,',') AS guardian_name,
                                        STRING_AGG(mobile_no::TEXT,',') AS mobile_no,
                                        STRING_AGG(email_id,',') AS email_id,
                                        temp_id
                                    FROM trade_owners 
                                    WHERE is_active  =TRUE
                                    GROUP BY temp_id
                                    )owner"), function ($join) {
                $join->on("owner.temp_id", "trade_licences.id");
            });

            $old = tradeRenewal::select(
                "trade_renewals.id",
                "trade_renewals.application_no",
                "trade_renewals.provisional_license_no",
                "trade_renewals.license_no",
                "trade_renewals.firm_name",
                "trade_renewals.application_date",
                "trade_renewals.apply_from",
                "trade_renewals.valid_upto",
                "owner.owner_name",
                "owner.guardian_name",
                "owner.mobile_no",
                "owner.email_id",
                DB::raw("'Old' as type"),
            )
            ->leftjoin(DB::raw("(select STRING_AGG(owner_name,',') AS owner_name,
                                        STRING_AGG(guardian_name,',') AS guardian_name,
                                        STRING_AGG(mobile_no::TEXT,',') AS mobile_no,
                                        STRING_AGG(email_id,',') AS email_id,
                                        temp_id
                                    FROM trade_owners 
                                    WHERE is_active  =TRUE
                                    GROUP BY temp_id
                                    )owner"), function ($join) {
                $join->on("owner.temp_id", "trade_renewals.id");
            });

                                   
            $licence = $licence->where("active_trade_licences.ulb_id", $refUlbId);
            $aropved = $aropved->where("trade_licences.ulb_id", $refUlbId);
            $old = $old->where("trade_renewals.ulb_id", $refUlbId);

            if (isset($mInputs['entityValue']) && trim($mInputs['entityValue']) && isset($mInputs['entityName']) && trim($mInputs['entityName'])) 
            {
                $key = trim($mInputs['entityValue']);
                $column = strtoupper(trim($mInputs['entityName']));
                $licence = $licence->where(function ($query) use ($key, $column) 
                {
                    if ($column == "FIRM") 
                    {
                        $query->orwhere('active_trade_licences.firm_name', 'ILIKE', '%' . $key . '%');
                    } 
                    elseif ($column == "APPLICATION") 
                    {
                        $query->orwhere('active_trade_licences.application_no', 'ILIKE', '%' . $key . '%');
                    } 
                    elseif ($column == "LICENSE") 
                    {
                        $query->orwhere('active_trade_licences.license_no', 'ILIKE', '%' . $key . '%');
                    } 
                    elseif ($column == "PROVISIONAL") 
                    {
                        $query->orwhere('active_trade_licences.provisional_license_no', 'ILIKE', '%' . $key . '%');
                    } 
                    elseif ($column == "OWNER") 
                    {
                        $query->orwhere('owner.owner_name', 'ILIKE', '%' . $key . '%');
                    } 
                    elseif ($column == "GUARDIAN") 
                    {
                        $query->orwhere('owner.guardian_name', 'ILIKE', '%' . $key . '%');
                    } 
                    elseif ($column == "MOBILE") 
                    {
                        $query->orwhere('owner.mobile_no', 'ILIKE', '%' . $key . '%');
                    } 
                    else 
                    {
                        $query->orwhere('active_trade_licences.application_no', 'ILIKE', '%' . $key . '%');
                    }
                });

                $aropved = $aropved->where(function ($query) use ($key, $column) 
                {
                    if ($column == "FIRM") 
                    {
                        $query->orwhere('trade_licences.firm_name', 'ILIKE', '%' . $key . '%');
                    } 
                    elseif ($column == "APPLICATION") 
                    {
                        $query->orwhere('trade_licences.application_no', 'ILIKE', '%' . $key . '%');
                    } 
                    elseif ($column == "LICENSE") 
                    {
                        $query->orwhere('trade_licences.license_no', 'ILIKE', '%' . $key . '%');
                    } 
                    elseif ($column == "PROVISIONAL") 
                    {
                        $query->orwhere('trade_licences.provisional_license_no', 'ILIKE', '%' . $key . '%');
                    } 
                    elseif ($column == "OWNER") 
                    {
                        $query->orwhere('owner.owner_name', 'ILIKE', '%' . $key . '%');
                    } 
                    elseif ($column == "GUARDIAN") 
                    {
                        $query->orwhere('owner.guardian_name', 'ILIKE', '%' . $key . '%');
                    } 
                    elseif ($column == "MOBILE") 
                    {
                        $query->orwhere('owner.mobile_no', 'ILIKE', '%' . $key . '%');
                    } 
                    else 
                    {
                        $query->orwhere('trade_licences.application_no', 'ILIKE', '%' . $key . '%');
                    }
                });

                $old = $old->where(function ($query) use ($key, $column) 
                {
                    if ($column == "FIRM") 
                    {
                        $query->orwhere('trade_renewals.firm_name', 'ILIKE', '%' . $key . '%');
                    } 
                    elseif ($column == "APPLICATION") 
                    {
                        $query->orwhere('trade_renewals.application_no', 'ILIKE', '%' . $key . '%');
                    } 
                    elseif ($column == "LICENSE") 
                    {
                        $query->orwhere('trade_renewals.license_no', 'ILIKE', '%' . $key . '%');
                    } 
                    elseif ($column == "PROVISIONAL") 
                    {
                        $query->orwhere('trade_renewals.provisional_license_no', 'ILIKE', '%' . $key . '%');
                    } 
                    elseif ($column == "OWNER") 
                    {
                        $query->orwhere('owner.owner_name', 'ILIKE', '%' . $key . '%');
                    } 
                    elseif ($column == "GUARDIAN") 
                    {
                        $query->orwhere('owner.guardian_name', 'ILIKE', '%' . $key . '%');
                    } 
                    elseif ($column == "MOBILE") 
                    {
                        $query->orwhere('owner.mobile_no', 'ILIKE', '%' . $key . '%');
                    } 
                    else 
                    {
                        $query->orwhere('trade_renewals.application_no', 'ILIKE', '%' . $key . '%');
                    }
                });
            }
            $licence = $licence->union($aropved)->union($old)
                ->orderBy("id", "DESC")
                ->limit(10)
                ->get();
            // dd(DB::getQueryLog());
            if ($licence->isEmpty()) {
                throw new Exception("Application Not Found");
            }
            $data = [
                "licence" => $licence,
            ];
            return responseMsg(true, "", $data);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }

    # Serial No : 15
    public function postEscalate(Request $request)
    {
        try {
            $userId = auth()->user()->id;
            // Validation Rule
            $rules = [
                "escalateStatus" => "required|int",
                "applicationId" => "required",
            ];
            // Validation Message
            $message = [
                "escalateStatus.required" => "Escalate Status Is Required",
                // "id.required" => "Application Id Is Required",
            ];
            $validator = Validator::make($request->all(), $rules, $message);
            if ($validator->fails()) {
                return responseMsg(false, $validator->errors(), $request->all());
            }
            DB::beginTransaction();
            $licenceId = $request->applicationId;
            $data = ActiveTradeLicence::find($licenceId);
            $data->is_escalate = $request->escalateStatus;
            $data->escalate_by = $userId;
            $data->save();
            DB::commit();
            return responseMsg(true, $request->escalateStatus == 1 ? 'Application is Escalated' : "Application is removed from Escalated", '');
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }

    # Serial No : 16
    /**
     * | Trade module WorkFlow Inbox 
     * |
     * |----------------------------------------------------------------------------------
     * |----------------Request-----------------------------------------------------------
     * |    1.  key     -> optinal
     * |    2.  wardNo  -> optinal
     * |    3.  formDate-> optinal
     * |    4.  toDate  -> optinal 
     * |
     * |----------------------------------------------------------------------------------
     * | @var refUser        = Auth()->user()
     * | @var refUserId      = refUser->id
     * | @var refUlbId       = refUser->ulb_id
     * | @var refWorkflowId  = $this->_WF_MASTER_Id
     * | @var refWorkflowMstrId = WfWorkflow  | (model)
     * |
     * | @var mUserType       = $this->_COMMON_FUNCTION->userType()
     * | @var mWardPermission = $this->_COMMON_FUNCTION->WardPermission(refUserId)
     * | @var mRole           = $this->_COMMON_FUNCTION->getUserRoll(refUserId, refUlbId, refWorkflowMstrId->wf_master_id)
     * | @var mJoins          = ""
     * | @var mRoleId         = mRole->role_id
     * | @var mWardIds                      | permited ward ids
     */
    public function inbox(Request $request)
    {
        try {
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id;
            $refUlbId       = $refUser->ulb_id;
            $refWorkflowId  = $this->_WF_MASTER_Id ;
            $refWfWorkflow     = WfWorkflow::where('wf_master_id', $refWorkflowId)
                ->where('ulb_id', $refUlbId)
                ->first();
            if (!$refWfWorkflow) {
                throw new Exception("Workflow Not Available");
            }
            $mUserType = $this->_COMMON_FUNCTION->userType($refWorkflowId);
            $mWardPermission = $this->_COMMON_FUNCTION->WardPermission($refUserId);
            $mRole = $this->_COMMON_FUNCTION->getUserRoll($refUserId, $refUlbId, $refWorkflowId);
            // dd($refUserId, $refUlbId, $refWorkflowId);
            $mJoins = "";
            if (!$mRole) {
                throw new Exception("You Are Not Authorized For This Action");
            }
            if ($mRole->is_initiator)    //|| in_array(strtoupper($mUserType),["JSK","SUPER ADMIN","ADMIN","TL","PMU","PM"])
            {
                $mWardPermission = $this->_MODEL_WARD->getAllWard($refUlbId)->map(function ($val) {
                    $val->ward_no = $val->ward_name;
                    return $val;
                });
                $mWardPermission = objToArray($mWardPermission);
                $mJoins = "leftjoin";
            } 
            else
            {
                $mJoins = "join";
            }

            $mWardIds = array_map(function ($val) {
                return $val['id'];
            }, $mWardPermission);

            $mRoleId = $mRole->role_id;
            $inputs = $request->all();
            // DB::enableQueryLog();          
            $licence = ActiveTradeLicence::select(
                    "active_trade_licences.id",
                    "active_trade_licences.application_no",
                    "active_trade_licences.provisional_license_no",
                    "active_trade_licences.license_no",
                    "active_trade_licences.document_upload_status",
                    "active_trade_licences.payment_status",
                    "active_trade_licences.firm_name",
                    "active_trade_licences.application_date",
                    "active_trade_licences.apply_from",
                    "owner.owner_name",
                    "owner.guardian_name",
                    "owner.mobile_no",
                    "owner.email_id",
                    "trade_param_application_types.application_type",
                    // DB::raw("workflow_tracks.id AS level_id")
                )
                ->JOIN("trade_param_application_types","trade_param_application_types.id","active_trade_licences.application_type_id")
                ->join(DB::raw("(select STRING_AGG(owner_name,',') AS owner_name,
                                            STRING_AGG(guardian_name,',') AS guardian_name,
                                            STRING_AGG(mobile_no::TEXT,',') AS mobile_no,
                                            STRING_AGG(email_id,',') AS email_id,
                                            temp_id
                                        FROM active_trade_owners 
                                        WHERE is_active = TRUE
                                        GROUP BY temp_id
                                        )owner"), function ($join) {
                    $join->on("owner.temp_id", "active_trade_licences.id");
                })
                ->where("active_trade_licences.is_parked", FALSE)
                ->where("active_trade_licences.payment_status", 1)
                ->where("active_trade_licences.current_role", $mRoleId)
                ->where("active_trade_licences.ulb_id", $refUlbId);
            if (isset($inputs['key']) && trim($inputs['key'])) {
                $key = trim($inputs['key']);
                $licence = $licence->where(function ($query) use ($key) {
                    $query->orwhere('active_trade_licences.holding_no', 'ILIKE', '%' . $key . '%')
                        ->orwhere('active_trade_licences.application_no', 'ILIKE', '%' . $key . '%')
                        ->orwhere("active_trade_licences.license_no", 'ILIKE', '%' . $key . '%')
                        ->orwhere("active_trade_licences.provisional_license_no", 'ILIKE', '%' . $key . '%')
                        ->orwhere('owner.owner_name', 'ILIKE', '%' . $key . '%')
                        ->orwhere('owner.guardian_name', 'ILIKE', '%' . $key . '%')
                        ->orwhere('owner.mobile_no', 'ILIKE', '%' . $key . '%');
                });
            }
            if (isset($inputs['wardNo']) && trim($inputs['wardNo']) && $inputs['wardNo'] != "ALL") {
                $mWardIds = $inputs['wardNo'];
            }
            if (isset($inputs['formDate']) && isset($inputs['toDate']) && trim($inputs['formDate']) && $inputs['toDate']) {
                $licence = $licence
                    ->whereBetween('active_trade_licences.application_date', [$inputs['formDate'], $inputs['formDate']]);
            }
            $licence = $licence
                ->whereIn('active_trade_licences.ward_id', $mWardIds)
                // ->limit(100)
                ->get();
            // dd($licence);            
            return responseMsg(true, "", $licence);
        } catch (Exception $e) {
            dd($e->getMessage(),$e->getLine(),$e->getFile());
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }

    # Serial No : 17
    public function outbox(Request $request)
    {
        try {
            $user = Auth()->user();
            $user_id = $user->id;
            $ulb_id = $user->ulb_id;
            $refWorkflowId = $this->_WF_MASTER_Id ;
            $refWfWorkflow     = WfWorkflow::where('wf_master_id', $refWorkflowId)
                ->where('ulb_id', $ulb_id)
                ->first();
            if (!$refWfWorkflow) {
                throw new Exception("Workflow Not Available");
            }
            $mUserType = $this->_COMMON_FUNCTION->userType($refWorkflowId);
            $ward_permission = $this->_COMMON_FUNCTION->WardPermission($user_id);
            $role = $this->_COMMON_FUNCTION->getUserRoll($user_id, $ulb_id, $refWorkflowId);
            if (!$role) {
                throw new Exception("You Are Not Authorized");
            }
            if ($role->is_initiator || in_array(strtoupper($mUserType), ["JSK", "SUPER ADMIN", "ADMIN", "TL", "PMU", "PM"])) {
               
                $ward_permission = $this->_MODEL_WARD->getAllWard($ulb_id)->map(function ($val) {
                    $val->ward_no = $val->ward_name;
                    return $val;
                });
                $ward_permission = objToArray($ward_permission);
            } 
            $role_id = $role->role_id;

            $ward_ids = array_map(function ($val) {
                return $val['id'];
            }, $ward_permission);
            $inputs = $request->all();
            // DB::enableQueryLog();
            $licence = ActiveTradeLicence::select(
                    "active_trade_licences.id",
                    "active_trade_licences.application_no",
                    "active_trade_licences.provisional_license_no",
                    "active_trade_licences.license_no",
                    "active_trade_licences.firm_name",
                    "active_trade_licences.application_date",
                    "active_trade_licences.apply_from",
                    "owner.owner_name",
                    "owner.guardian_name",
                    "owner.mobile_no",
                    "owner.email_id",
                    "trade_param_application_types.application_type",
                )
                ->JOIN("trade_param_application_types","trade_param_application_types.id","active_trade_licences.application_type_id")
                ->join(DB::raw("(select STRING_AGG(owner_name,',') AS owner_name,
                                            STRING_AGG(guardian_name,',') AS guardian_name,
                                            STRING_AGG(mobile_no::TEXT,',') AS mobile_no,
                                            STRING_AGG(email_id,',') AS email_id,
                                            temp_id
                                        FROM active_trade_owners  
                                        WHERE is_active =TRUE
                                        GROUP BY temp_id
                                        )owner"), function ($join) {
                    $join->on("owner.temp_id", "active_trade_licences.id");
                })
                ->where("active_trade_licences.is_parked", FALSE)
                ->where("active_trade_licences.current_role", "<>", $role_id)
                ->where("active_trade_licences.ulb_id", $ulb_id);

            if (isset($inputs['key']) && trim($inputs['key'])) {
                $key = trim($inputs['key']);
                $licence = $licence->where(function ($query) use ($key) {
                    $query->orwhere('active_trade_licences.holding_no', 'ILIKE', '%' . $key . '%')
                        ->orwhere('active_trade_licences.application_no', 'ILIKE', '%' . $key . '%')
                        ->orwhere("active_trade_licences.license_no", 'ILIKE', '%' . $key . '%')
                        ->orwhere("active_trade_licences.provisional_license_no", 'ILIKE', '%' . $key . '%')
                        ->orwhere('owner.owner_name', 'ILIKE', '%' . $key . '%')
                        ->orwhere('owner.guardian_name', 'ILIKE', '%' . $key . '%')
                        ->orwhere('owner.mobile_no', 'ILIKE', '%' . $key . '%');
                });
            }

            if (isset($inputs['wardNo']) && trim($inputs['wardNo']) && $inputs['wardNo'] != "ALL") {
                $ward_ids = $inputs['wardNo'];
            }
            if (isset($inputs['formDate']) && isset($inputs['toDate']) && trim($inputs['formDate']) && $inputs['toDate']) {
                $licence = $licence
                    ->whereBetween('active_trade_licences.application_date', [$inputs['formDate'], $inputs['formDate']]);
            }
            $licence = $licence
                ->whereIn('active_trade_licences.ward_id', $ward_ids)
                ->get();
            return responseMsg(true, "", $licence);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }

    public function specialInbox(Request $request)
    {
        try {
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id;
            $refUlbId       = $refUser->ulb_id;
            $refWorkflowId  = $this->_WF_MASTER_Id ;
            $refWfWorkflow     = WfWorkflow::where('wf_master_id', $refWorkflowId)
                ->where('ulb_id', $refUlbId)
                ->first();
            if (!$refWfWorkflow) {
                throw new Exception("Workflow Not Available");
            }
            $mWardPermission = $this->_COMMON_FUNCTION->WardPermission($refUserId);
            $inputs = $request->all();
            $licence = ActiveTradeLicence::select(
                    "active_trade_licences.id",
                    "active_trade_licences.application_no",
                    "active_trade_licences.provisional_license_no",
                    "active_trade_licences.license_no",
                    "active_trade_licences.document_upload_status",
                    "active_trade_licences.payment_status",
                    "active_trade_licences.firm_name",
                    "active_trade_licences.application_date",
                    "active_trade_licences.apply_from",
                    "owner.owner_name",
                    "owner.guardian_name",
                    "owner.mobile_no",
                    "owner.email_id",
                    "trade_param_application_types.application_type",
                )
                ->JOIN("trade_param_application_types","trade_param_application_types.id","active_trade_licences.application_type_id")
                ->join(DB::raw("(select STRING_AGG(owner_name,',') AS owner_name,
                                            STRING_AGG(guardian_name,',') AS guardian_name,
                                            STRING_AGG(mobile_no::TEXT,',') AS mobile_no,
                                            STRING_AGG(email_id,',') AS email_id,
                                            temp_id
                                        FROM active_trade_owners 
                                        WHERE is_active = TRUE
                                        GROUP BY temp_id
                                        )owner"), function ($join) {
                    $join->on("owner.temp_id", "active_trade_licences.id");
                })
                ->where("active_trade_licences.is_escalate", TRUE)
                ->where("active_trade_licences.ulb_id", $refUlbId);
            if (isset($inputs['key']) && trim($inputs['key'])) {
                $key = trim($inputs['key']);
                $licence = $licence->where(function ($query) use ($key) {
                    $query->orwhere('active_trade_licences.holding_no', 'ILIKE', '%' . $key . '%')
                        ->orwhere('active_trade_licences.application_no', 'ILIKE', '%' . $key . '%')
                        ->orwhere("active_trade_licences.license_no", 'ILIKE', '%' . $key . '%')
                        ->orwhere("active_trade_licences.provisional_license_no", 'ILIKE', '%' . $key . '%')
                        ->orwhere('owner.owner_name', 'ILIKE', '%' . $key . '%')
                        ->orwhere('owner.guardian_name', 'ILIKE', '%' . $key . '%')
                        ->orwhere('owner.mobile_no', 'ILIKE', '%' . $key . '%');
                });
            }
            if (isset($inputs['wardNo']) && trim($inputs['wardNo']) && $inputs['wardNo'] != "ALL") {
                $mWardIds = $inputs['wardNo'];
                $licence = $licence->where('active_trade_licences.ward_id', $mWardIds);
            }
            if (isset($inputs['formDate']) && isset($inputs['toDate']) && trim($inputs['formDate']) && $inputs['toDate']) {
                $licence = $licence
                    ->whereBetween('active_trade_licences.application_date', [$inputs['formDate'], $inputs['formDate']]);
            }
            $licence = $licence
                ->get();
            return responseMsg(true, "", $licence);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }
    public function btcInbox(Request $request)
    {
        try {
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id;
            $refUlbId       = $refUser->ulb_id;
            $refWorkflowId  = $this->_WF_MASTER_Id ;
            $refWfWorkflow     = WfWorkflow::where('wf_master_id', $refWorkflowId)
                ->where('ulb_id', $refUlbId)
                ->first();
            if (!$refWfWorkflow) {
                throw new Exception("Workflow Not Available");
            }
            $mWardPermission = $this->_COMMON_FUNCTION->WardPermission($refUserId);
            $mRole = $this->_COMMON_FUNCTION->getUserRoll($refUserId, $refUlbId, $refWorkflowId);

            if (!$mRole->is_initiator) {
                throw new Exception("You Are Not Authorized For This Action");
            }
            if ($mRole->is_initiator) {
                $mWardPermission = $this->_MODEL_WARD->getAllWard($refUlbId)->map(function ($val) {
                    $val->ward_no = $val->ward_name;
                    return $val;
                });
                $mWardPermission = objToArray($mWardPermission);
            }

            $mWardIds = array_map(function ($val) {
                return $val['id'];
            }, $mWardPermission);

            $mRoleId = $mRole->role_id;
            $inputs = $request->all();
            // DB::enableQueryLog();          
            $licence = ActiveTradeLicence::select(
                    "active_trade_licences.id",
                    "active_trade_licences.application_no",
                    "active_trade_licences.provisional_license_no",
                    "active_trade_licences.license_no",
                    "active_trade_licences.document_upload_status",
                    "active_trade_licences.payment_status",
                    "active_trade_licences.firm_name",
                    "active_trade_licences.application_date",
                    "active_trade_licences.apply_from",
                    "owner.owner_name",
                    "owner.guardian_name",
                    "owner.mobile_no",
                    "owner.email_id",
                    "trade_param_application_types.application_type",
                )
                ->JOIN("trade_param_application_types","trade_param_application_types.id","active_trade_licences.application_type_id")
                ->join(DB::raw("(select STRING_AGG(owner_name,',') AS owner_name,
                                            STRING_AGG(guardian_name,',') AS guardian_name,
                                            STRING_AGG(mobile_no::TEXT,',') AS mobile_no,
                                            STRING_AGG(email_id,',') AS email_id,
                                            temp_id
                                        FROM active_trade_owners 
                                        WHERE is_active = TRUE
                                        GROUP BY temp_id
                                        )owner"), function ($join) {
                    $join->on("owner.temp_id", "active_trade_licences.id");
                })
                ->where("active_trade_licences.is_parked", TRUE)
                ->where("active_trade_licences.ulb_id", $refUlbId);
            if (isset($inputs['key']) && trim($inputs['key'])) {
                $key = trim($inputs['key']);
                $licence = $licence->where(function ($query) use ($key) {
                    $query->orwhere('active_trade_licences.holding_no', 'ILIKE', '%' . $key . '%')
                        ->orwhere('active_trade_licences.application_no', 'ILIKE', '%' . $key . '%')
                        ->orwhere("active_trade_licences.license_no", 'ILIKE', '%' . $key . '%')
                        ->orwhere("active_trade_licences.provisional_license_no", 'ILIKE', '%' . $key . '%')
                        ->orwhere('owner.owner_name', 'ILIKE', '%' . $key . '%')
                        ->orwhere('owner.guardian_name', 'ILIKE', '%' . $key . '%')
                        ->orwhere('owner.mobile_no', 'ILIKE', '%' . $key . '%');
                });
            }
            if (isset($inputs['wardNo']) && trim($inputs['wardNo']) && $inputs['wardNo'] != "ALL") {
                $mWardIds = $inputs['wardNo'];
            }
            if (isset($inputs['formDate']) && isset($inputs['toDate']) && trim($inputs['formDate']) && $inputs['toDate']) {
                $licence = $licence
                    ->whereBetween('active_trade_licences.application_date', [$inputs['formDate'], $inputs['formDate']]);
            }
            $licence = $licence
                ->whereIn('active_trade_licences.ward_id', $mWardIds)
                ->get();
            return responseMsg(true, "", $licence);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }
    

    # Serial No : 18
    public function postNextLevel(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'senderRoleId' => 'required|integer',
            'receiverRoleId' => 'required|integer',
            'comment' => 'required',
        ]);

        try {
            // SAF Application Update Current Role Updation
            DB::beginTransaction();
            $licence = ActiveTradeLicence::find($request->id);
            $licence->current_role = $request->receiverRoleId;
            $licence->save();


            $metaReqs['moduleId'] = $this->_MODULE_ID;
            $metaReqs['workflowId'] = $licence->workflow_id;
            $metaReqs['refTableDotId'] = 'active_trade_licences.id';
            $metaReqs['refTableIdValue'] = $request->id;
            $request->request->add($metaReqs);

            $track = new WorkflowTrack();
            $track->saveTrack($request);

            DB::commit();
            return responseMsgs(true, "Successfully Forwarded The Application!!", "", "010109", "1.0", "286ms", "POST", $request->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }



    # Serial No : 19
    public function provisionalCertificate($id) # unauthorised  function
    {
        try {

            $data = (array)null;
            $data['provisionalCertificate'] = config('app.url') . "/api/trade/provisional-certificate/" . $id;
            $application = ActiveTradeLicence::select(
                "active_trade_licences.id",
                "active_trade_licences.application_date",
                "active_trade_licences.establishment_date",
                "application_no",
                "provisional_license_no",
                "license_no",
                "firm_name",
                "holding_no",
                "address",
                "payment_status",
                "owner.owner_name",
                "owner.guardian_name",
                "owner.mobile",
                DB::raw("ulb_ward_masters.ward_name AS ward_no, 
                                        ulb_masters.id as ulb_id, ulb_masters.ulb_name,ulb_masters.ulb_type
                                        ")
            )
                ->join("ulb_masters", "ulb_masters.id", "active_trade_licences.ulb_id")
                ->join("ulb_ward_masters", function ($join) {
                    $join->on("ulb_ward_masters.id", "=", "active_trade_licences.ward_id");
                })
                ->join(DB::raw("(SELECT STRING_AGG(owner_name,',') as owner_name,
                                            STRING_AGG(guardian_name,',') as guardian_name,
                                            STRING_AGG(mobile_no::text,',') as mobile,
                                            temp_id
                                        FROM active_trade_owners 
                                        WHERE temp_id = $id
                                            AND is_active =TRUE
                                        GROUP BY temp_id
                                        ) owner"), function ($join) {
                    $join->on("owner.temp_id", "=", "active_trade_licences.id");
                })
                ->where('active_trade_licences.id', $id)
                ->first();
            if (!$application) {
                $application = TradeLicence::select(
                    "trade_licences.id",
                    "trade_licences.application_date",
                    "trade_licences.establishment_date",
                    "application_no",
                    "provisional_license_no",
                    "license_no",
                    "firm_name",
                    "holding_no",
                    "address",
                    "payment_status",
                    "owner.owner_name",
                    "owner.guardian_name",
                    "owner.mobile",
                    DB::raw("ulb_ward_masters.ward_name AS ward_no, 
                                    ulb_masters.id as ulb_id, ulb_masters.ulb_name,ulb_masters.ulb_type
                                    ")
                )
                    ->join("ulb_masters", "ulb_masters.id", "trade_licences.ulb_id")
                    ->join("ulb_ward_masters", function ($join) {
                        $join->on("ulb_ward_masters.id", "=", "trade_licences.ward_id");
                    })
                    ->join(DB::raw("(SELECT STRING_AGG(owner_name,',') as owner_name,
                                            STRING_AGG(guardian_name,',') as guardian_name,
                                            STRING_AGG(mobile_no,',') as mobile,
                                            temp_id
                                        FROM trade_owners 
                                        WHERE temp_id = $id
                                            AND is_active =TRUE
                                        GROUP BY temp_id
                                        ) owner"), function ($join) {
                        $join->on("owner.temp_id", "=", "trade_licences.id");
                    })
                    ->where('trade_licences.id', $id)
                    ->first();
            }
            if (!$application) {
                $application = RejectedTradeLicence::select(
                    "rejected_trade_licences.id",
                    "rejected_trade_licences.application_date",
                    "rejected_trade_licences.establishment_date",
                    "application_no",
                    "provisional_license_no",
                    "license_no",
                    "firm_name",
                    "holding_no",
                    "address",
                    "payment_status",
                    "owner.owner_name",
                    "owner.guardian_name",
                    "owner.mobile",
                    DB::raw("ulb_ward_masters.ward_name AS ward_no, 
                                    ulb_masters.id as ulb_id, ulb_masters.ulb_name,ulb_masters.ulb_type
                                    ")
                )
                    ->join("ulb_masters", "ulb_masters.id", "rejected_trade_licences.ulb_id")
                    ->join("ulb_ward_masters", function ($join) {
                        $join->on("ulb_ward_masters.id", "=", "rejected_trade_licences.ward_id");
                    })
                    ->join(DB::raw("(SELECT STRING_AGG(owner_name,',') as owner_name,
                                            STRING_AGG(guardian_name,',') as guardian_name,
                                            STRING_AGG(mobile_no,',') as mobile,
                                            temp_id
                                        FROM rejected_trade_owners 
                                        WHERE temp_id = $id
                                            AND is_active =TRUE
                                        GROUP BY temp_id
                                        ) owner"), function ($join) {
                        $join->on("owner.temp_id", "=", "rejected_trade_licences.id");
                    })
                    ->where('rejected_trade_licences.id', $id)
                    ->first();
            }
            if (!$application) {
                $application = TradeRenewal::select(
                    "trade_renewals.id",
                    "trade_renewals.application_date",
                    "trade_renewals.establishment_date",
                    "application_no",
                    "provisional_license_no",
                    "license_no",
                    "firm_name",
                    "holding_no",
                    "address",
                    "payment_status",
                    "owner.owner_name",
                    "owner.guardian_name",
                    "owner.mobile",
                    DB::raw("ulb_ward_masters.ward_name AS ward_no, 
                                    ulb_masters.id as ulb_id, ulb_masters.ulb_name,ulb_masters.ulb_type
                                    ")
                )
                    ->join("ulb_masters", "ulb_masters.id", "trade_renewals.ulb_id")
                    ->join("ulb_ward_masters", function ($join) {
                        $join->on("ulb_ward_masters.id", "=", "trade_renewals.ward_id");
                    })
                    ->join(DB::raw("(SELECT STRING_AGG(owner_name,',') as owner_name,
                                            STRING_AGG(guardian_name,',') as guardian_name,
                                            STRING_AGG(mobile_no,',') as mobile,
                                            temp_id
                                        FROM trade_owners 
                                        WHERE temp_id = $id
                                            AND is_active =TRUE
                                        GROUP BY temp_id
                                        ) owner"), function ($join) {
                        $join->on("owner.temp_id", "=", "trade_renewals.id");
                    })
                    ->where('trade_renewals.id', $id)
                    ->first();
            }
            if (!$application) {
                throw new Exception("Application Not Found");
            }
            if ($application->payment_status == 0) {
                throw new Exception("Please Payment Of This Application");
            }
            $vUpto = $application->apply_date;
            $application->valid_upto = date('Y-m-d', strtotime(date("$vUpto", mktime(time())) . " + 20 day"));
            $transaction = TradeTransaction::select(
                "trade_transactions.id",
                "tran_no",
                "tran_type",
                "tran_date",
                "payment_mode",
                "paid_amount",
                "penalty",
                "trade_cheque_dtls.cheque_no",
                "trade_cheque_dtls.cheque_date",
                "trade_cheque_dtls.bank_name",
                "trade_cheque_dtls.branch_name"
            )
                ->leftjoin("trade_cheque_dtls", "trade_cheque_dtls.tran_id", "trade_transactions.id")
                ->where("trade_transactions.temp_id", $id)
                ->whereIn("trade_transactions.status", [1, 2])
                ->first();
            if (!$transaction) {
                throw new Exception("Transaction Not Faound");
            }
            $penalty = TradeFineRebete::select("type", "amount")
                ->where('tran_id', $transaction->id)
                ->where("status", 1)
                ->orderBy("id")
                ->get();
            $pen = 0;
            foreach ($penalty as $val) {
                $pen += $val->amount;
            }
            $transaction->rate = $transaction->paid_amount - $pen;
            $data["application"] = $application;
            $data["transaction"] = $transaction;
            $data["penalty"]    = $penalty;

            $data = remove_null($data);
            return  responseMsg(true, "", $data);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), $id);
        }
    }

    # Serial No : 20
    public function licenceCertificate($id) # unauthorised  function
    {
        try {

            $data = (array)null;
            $data['licenceCertificate'] = config('app.url') . "/api/trade/license-certificate/" . $id;
            $application = TradeLicence::select(
                "trade_licences.id",
                "trade_licences.application_date",
                "trade_licences.establishment_date",
                "application_no",
                "provisional_license_no",
                "license_no",
                "firm_name",
                "holding_no",
                "address",
                "license_date",
                "valid_from",
                "valid_upto",
                "licence_for_years",
                "establishment_date",
                "nature_of_bussiness",
                "firm_description",
                "pending_status",
                "owner.owner_name",
                "owner.guardian_name",
                "owner.mobile",
                DB::raw("ulb_ward_masters.ward_name AS ward_no, 
                                        ulb_masters.id as ulb_id, ulb_masters.ulb_name,ulb_masters.ulb_type
                                        ")
            )
                ->join("ulb_masters", "ulb_masters.id", "trade_licences.ulb_id")
                ->join("ulb_ward_masters", function ($join) {
                    $join->on("ulb_ward_masters.id", "=", "trade_licences.ward_id");
                })
                ->leftjoin(DB::raw("(SELECT STRING_AGG(owner_name,',') as owner_name,
                                            STRING_AGG(guardian_name,',') as guardian_name,
                                            STRING_AGG(mobile_no::text,',') as mobile,
                                            temp_id
                                        FROM trade_owners 
                                        WHERE temp_id = $id
                                            AND is_active =TRUE
                                        GROUP BY temp_id
                                        ) owner"), function ($join) {
                    $join->on("owner.temp_id", "=", "trade_licences.id");
                })
                ->where('trade_licences.id', $id)
                ->first();
            if (!$application) {
                $application = TradeRenewal::select(
                    "trade_renewals.id",
                    "trade_renewals.establishment_date",
                    "application_no",
                    "provisional_license_no",
                    "license_no",
                    "firm_name",
                    "holding_no",
                    "address",
                    "application_date",
                    "license_date",
                    "valid_from",
                    "valid_upto",
                    "licence_for_years",
                    "establishment_date",
                    "nature_of_bussiness",
                    "firm_description",
                    "pending_status",
                    "owner.owner_name",
                    "owner.guardian_name",
                    "owner.mobile",
                    DB::raw("ulb_ward_masters.ward_name AS ward_no, 
                                    ulb_masters.id as ulb_id, ulb_masters.ulb_name,ulb_masters.ulb_type
                                    ")
                )
                    ->join("ulb_masters", "ulb_masters.id", "trade_renewals.ulb_id")
                    ->join("ulb_ward_masters", function ($join) {
                        $join->on("ulb_ward_masters.id", "=", "trade_renewals.ward_id");
                    })
                    ->leftjoin(DB::raw("(SELECT STRING_AGG(owner_name,',') as owner_name,
                                            STRING_AGG(guardian_name,',') as guardian_name,
                                            STRING_AGG(mobile_no::text,',') as mobile,
                                            temp_id
                                        FROM trade_owners 
                                        WHERE temp_id = $id
                                            AND is_active =TRUE
                                        GROUP BY temp_id
                                        ) owner"), function ($join) {
                        $join->on("owner.temp_id", "=", "trade_renewals.id");
                    })
                    ->where('trade_renewals.id', $id)
                    ->first();
                if (!$application) {
                    throw new Exception("Application Not Found");
                }
            }
            if ($application->pending_status != 5) {
                throw new Exception("Application Not Approved");
            }
            $item_name = "";
            $cods = "";
            if ($application->nature_of_bussiness) {
                $items = TradeParamItemType::itemsById($application->nature_of_bussiness);
                foreach ($items as $val) {
                    $item_name .= $val->trade_item . ",";
                    $cods .= $val->trade_code . ",";
                }
                $item_name = trim($item_name, ',');
                $cods = trim($cods, ',');
            }
            $application->items = $item_name;
            $application->items_code = $cods;
            $data["application"] = $application;
            $data = remove_null($data);
            return  responseMsg(true, "", $data);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), $id);
        }
    }

    # Serial No : 21
    public function addDenail(Request $request)
    {
        $user = Auth()->user();
        $userId = $user->id;
        $ulbId = $user->ulb_id;
        try {
            $data = array();
            $refWorkflowId = $this->_WF_NOTICE_MASTER_Id;            

            $role = $this->_COMMON_FUNCTION->getUserRoll($userId, $ulbId, $refWorkflowId);
            $role_id = $role->role_id;
            $mForwardRoleId = $role->forward_role_id;
            if ($request->getMethod() == 'POST') {
                DB::beginTransaction();
                $denialConsumer = new ActiveTradeNoticeConsumerDtl;
                $denialConsumer->firm_name  = $request->firmName;
                $denialConsumer->owner_name = $request->ownerName;
                $denialConsumer->ward_id    = $request->wardNo;
                $denialConsumer->ulb_id     = $ulbId;
                $denialConsumer->holding_no = $request->holdingNo;
                $denialConsumer->address    = $request->address;
                $denialConsumer->landmark   = $request->landmark;
                $denialConsumer->city       = $request->city;
                $denialConsumer->pin_code    = $request->pinCode;
                $denialConsumer->license_no = $request->licenceNo ?? null;
                $denialConsumer->ip_address = $request->ip();
                $getloc = json_decode(file_get_contents("http://ipinfo.io/"));
                $coordinates = explode(",", $getloc->loc);
                $denialConsumer->latitude   = $coordinates[0]; // latitude
                $denialConsumer->longitude  = $coordinates[1]; // longitude
                if ($request->mobileNo) {
                    $denialConsumer->mobileno = $request->mobileNo;
                }
                $denialConsumer->remarks = $request->comment;
                $denialConsumer->user_id = $userId;
                $denialConsumer->save();
                $denial_id = $denialConsumer->id;

                if ($denial_id) {
                    $file = $request->file("document");
                    $file_ext =  $file->getClientOriginalExtension();
                    $fileName = "denial_image/$denial_id.$file_ext";
                    $filePath = $this->uplodeFile($file, $fileName);
                    $denialConsumer->document_path = $filePath;
                    $denialConsumer->update();

                    // $workflowTrack = new WorkflowTrack;
                    // $workflowTrack->workflow_id     =   $this->_WF_MASTER_Id;
                    // $workflowTrack->citizen_id      =   $userId;
                    // $workflowTrack->module_id       =   $this->_MODULE_ID;
                    // $workflowTrack->ref_table_dot_id = "active_trade_notice_consumer_dtls";
                    // $workflowTrack->ref_table_id_value = $denial_id;
                    // $workflowTrack->message         =   $request->comment;
                    // $workflowTrack->commented_by    =   $role_id;
                    // $workflowTrack->track_date      =   Carbon::now()->format('Y-m-d H:i:s');    
                    // $workflowTrack->save();

                }
                DB::commit();

                return  responseMsg(true, "Denail Form Submitted Succesfully!", $data);
            }
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }

    # Serial No : 22
    public function addIndependentComment(Request $request)
    {
        try {
            $mRegex     = '/^[a-zA-Z1-9][a-zA-Z1-9\. \s]+$/';
            $userId = auth()->user()->id;
            $ulbId  = auth()->user()->ulb_id;
            $role_id = 0;
            $refWorkflowId = $this->_WF_MASTER_Id ;
            $role = $this->_COMMON_FUNCTION->getUserRoll($userId, $ulbId, $refWorkflowId);
            if ($role) {
                $role_id = $role->role_id;
            }
            $rules["comment"] = "required|min:10|regex:$mRegex";
            $rules["id"] = "required||digits_between:1,9223372036854775807";
            $validator = Validator::make($request->all(), $rules,);
            if ($validator->fails()) {
                return responseMsg(false, $validator->errors(), $request->all());
            }

            $refLicense = ActiveTradeLicence::find($request->applicationId);
            if (!$refLicense) {
                throw new Exception("Comments for invalide application !....");
            }

            // Save On Workflow Track
            DB::beginTransaction();
            $metaReqs['moduleId'] = $this->_MODULE_ID;
            $metaReqs['workflowId'] = $refLicense->workflow_id;
            $metaReqs['refTableDotId'] = 'active_trade_licences';
            $metaReqs['refTableIdValue'] = $refLicense->id;
            $metaReqs['senderRoleId'] = $role_id;            
            $metaReqs['citizenId']=$userId;
            $metaReqs['ulb_id']=$refLicense->ulb_id;
            $request->request->add($metaReqs);

            $track = new WorkflowTrack();
            $track->saveTrack($request);
            DB::commit();
            return responseMsg(true, "You Have Commented Successfully!!", ['Comment' => $request->comment]);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    # Serial No : 23
    public function readIndipendentComment(Request $request)
    {
        try {
            $rules["licenceId"] = "required|digits_between:1,9223372036854775807";
            $validator = Validator::make($request->all(), $rules,);
            if ($validator->fails()) {
                return responseMsg(false, $validator->errors(), $request->all());
            }
            $comments = $this->getIndipendentComment($request->licenceId);
            return $comments;
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }
    # Serial No : 23.01
    public function getIndipendentComment($licenceId)
    {
        try {
            $data = WorkflowTrack::select(
                "workflow_tracks.*",
                "users.user_name",
                DB::raw("CASE WHEN wf_roles.id ISNULL THEN 'Citizen' ELSE wf_roles.role_name END AS role_name")
            )
                ->join("users", "users.id", "workflow_tracks.citizen_id")
                ->leftjoin("wf_roles", "wf_roles.id", "workflow_tracks.commented_by")
                ->where("workflow_tracks.ref_table_dot_id", "active_licences")
                ->where("workflow_tracks.ref_table_id_value", $licenceId)
                ->where("workflow_tracks.module_id", $this->_MODULE_ID)
                ->orderBy("workflow_tracks.track_date")
                ->get();
            return $data;
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Only for EO (Exicutive Officer)
     */
    # Serial No : 24
    public function denialInbox(Request $request)
    {
        try {
            $data = (array)null;
            $user = Auth()->user();
            $user_id = $user->id;
            $ulb_id = $user->ulb_id;
            $workflow_id = $this->_WF_NOTICE_MASTER_Id;
            $role = $this->_COMMON_FUNCTION->getUserRoll($user_id, $ulb_id, $workflow_id);
            $role_id = $role->role_id ?? -1;
            $mUserType = $this->_COMMON_FUNCTION->userType($workflow_id);
            if (!$role  || !in_array($role_id, [10])) {
                throw new Exception("You Are Not Authorized");
            }
            $nowdate = Carbon::now()->format('Y-m-d');
            $timstamp = Carbon::now()->format('Y-m-d H:i:s');

            $wardList = $this->_COMMON_FUNCTION->WardPermission($user_id);
            $data['wardList'] = $wardList;
            $mailStatus = 1;
            $ward_ids = array_map(function ($val) {
                return $val['id'];
            }, $wardList);
            $inputs = $request->all();
            $denila_consumer = ActiveTradeNoticeConsumerDtl::select(
                "active_trade_notice_consumer_dtls.*",
                DB::raw("ulb_ward_masters.ward_name as ward_no ,
                                    ")
            )
                ->join("ulb_ward_masters", "ulb_ward_masters.id", "active_trade_notice_consumer_dtls.ward_id");

            if (isset($inputs['wardNo']) && trim($inputs['wardNo']) && $inputs['wardNo'] != "ALL") {
                $ward_ids = $inputs["wardNo"];
            }
            if (isset($inputs['key']) && trim($inputs['key'])) {
                $key = trim($inputs['key']);
                $denila_consumer = $denila_consumer->where(function ($query) use ($key) {
                    $query->orwhere('active_trade_notice_consumer_dtls.holding_no', 'ILIKE', '%' . $key . '%')
                        ->orwhere('active_trade_notice_consumer_dtls.firm_name', 'ILIKE', '%' . $key . '%')
                        ->orwhere("active_trade_notice_consumer_dtls.license_no", 'ILIKE', '%' . $key . '%')
                        ->orwhere('active_trade_notice_consumer_dtls.applicant_name', 'ILIKE', '%' . $key . '%')
                        ->orwhere('active_trade_notice_consumer_dtls.mobile_no', 'ILIKE', '%' . $key . '%');
                });
            }
            if (isset($inputs['formDate']) && isset($inputs['toDate']) && trim($inputs['formDate']) && $inputs['toDate']) {
                $denila_consumer = $denila_consumer
                    ->whereBetween('active_trade_notice_consumer_dtls.created_on::date', [$inputs['formDate'], $inputs['formDate']]);
            }
            $denila_consumer = $denila_consumer
                ->whereIn("active_trade_notice_consumer_dtls.ward_id", $ward_ids)
                ->where("active_trade_notice_consumer_dtls.ulb_id", $ulb_id)
                ->where("active_trade_notice_consumer_dtls.current_role", $role_id)
                ->where("active_trade_notice_consumer_dtls.workflow_id", $workflow_id)
                ->where("active_trade_notice_consumer_dtls.status", 1)
                ->orderBy("active_trade_notice_consumer_dtls.created_on", "DESC")
                ->get();
            $data['denila_consumer'] = $denila_consumer->map(function ($val) {
                if (isset($val["document_path"])) {
                    $path = $this->readDocumentPath($val["document_path"]);
                    $val["document_path"] = !empty(trim($val["document_path"])) ? $path : null;
                }
                return $val;
            });
            return responseMsg(false, "", remove_null($data));
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }
    # Serial No : 25
    /**
     * Apply Denail View Data And (Approve Or Reject) By EO
     * | @var data local data storage
     * |+ @var user  login user DATA 
     * |+ @var user_id login user ID
     * |+ @var ulb_id login user ULBID
     * |+ @var workflow_id owrflow id 19 for trade **$this->_WF_MASTER_Id
     * |+ @var role_id login user ROLEID **$this->_COMMON_FUNCTION->getUserRoll($user_id, $ulb_id,$workflow_id)->role_id??-1
     * | @var mUserType login user sort role name **$this->_COMMON_FUNCTION->userType(workflow_id)
     * |
     * |+ @var denial_details  apply denial detail **this->getDenialDetailsByID($id,$ulb_id)
     * |+ @var denialID =  denial_details->id
     * |     
     */
    public function denialView($id, $mailID, Request $request)
    {
        try {
            $data = (array)null;
            $user = Auth()->user();
            $user_id = $user->id;
            $ulb_id = $user->ulb_id;
            $workflow_id = $this->_WF_NOTICE_MASTER_Id;
            $role_id = $this->_COMMON_FUNCTION->getUserRoll($user_id, $ulb_id, $workflow_id)->role_id ?? -1;
            $mUserType = $this->_COMMON_FUNCTION->userType($workflow_id);

            $denial_details  = $this->getDenialDetailsByID($id, $ulb_id);
            $denialID =  $denial_details->id;
            if ($denial_details->status == 5) {
                throw new Exception("Notice No Already Generated " . $denial_details->notice_no);
            } elseif ($denial_details->status == 4) {
                throw new Exception("Denial Request Rejected");
            }
            $denial_details->file_name = !empty(trim($denial_details->file_name)) ? $this->readDocumentPath($denial_details->file_name) : null;
            if ($request->getMethod() == 'GET') {
                $data["denial_details"] = $denial_details;
                return responseMsg(true, "", remove_null($data));
            } elseif ($request->getMethod() == 'POST') {
                $denial_consumer = ActiveTradeNoticeConsumerDtl::find($denialID);

                $nowdate = Carbon::now()->format('Y-m-d');
                $timstamp = Carbon::now()->format('Y-m-d H:i:s');
                $regex = '/^[a-zA-Z1-9][a-zA-Z1-9\.\s]+$/';
                $rules = [];
                $message = [];
                $rules["btn"] = "required|in:approve,reject";
                $message["btn.in"] = "btn Value In approve,reject";
                $rules["comment"] = "required|min:10|regex:$regex";
                $validator = Validator::make($request->all(), $rules, $message);
                if ($validator->fails()) {
                    return responseMsg(false, $validator->errors(), $request->all());
                }
                if ($mUserType != "EO") {
                    throw new Exception("You Are Not Authorize For Approve Or Reject Denial Detail");
                }
                DB::beginTransaction();
                # Approve Application
                $res = [];
                if ($request->btn == "approve") {
                    $denial_consumer->status = 5;
                    $denial_consumer->notice_date  = $nowdate;
                    $noticeNO = "NOT/" . date('dmy') . $denialID;
                    $denial_consumer->notice_no = $noticeNO;
                    $denial_consumer->update();
                    $res["noticeNo"] = $noticeNO;
                    $res["sms"] = "Notice No Successfuly Generated";
                }
                if ($request->btn == 'btn_upload') {
                }
                if ($request->btn == "reject") {
                    $denial_consumer->status = 4;
                    $denial_consumer->update();
                    $res["noticeNo"] = "";
                    $res["sms"] = "Denail Apply Rejected";
                }
                DB::commit();
                return responseMsg(true, $res["sms"], remove_null($res["noticeNo"]));
            }
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }

    # Serial No : 26
    public function approvedApplication(Request $request)
    {
        try {
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id;
            $refUlbId       = $refUser->ulb_id;
            $refWorkflowId      = $this->_WF_MASTER_Id ;
            $mUserType          = $this->_COMMON_FUNCTION->userType($refWorkflowId);
            if (in_array(strtoupper($mUserType), ["ONLINE", "JSK", "BO", "SUPER ADMIN", "TL"])) {
                $mWardPermission = $this->_MODEL_WARD->getAllWard($refUlbId)->map(function ($val) {
                    $val->ward_no = $val->ward_name;
                    return $val;
                });
                $mWardPermission = objToArray($mWardPermission);
            } else 
            {
                $mWardPermission = $this->_COMMON_FUNCTION->WardPermission($refUserId);
            }

            $fromDate = $uptoDate = Carbon::now()->format("Y-m-d");
            
            $key = null;
            $wardNo = null;
            if($request->fromDate)
            {
                $fromDate = $request->fromDate;
            }
            if($request->uptoDate)
            {
                $uptoDate = $request->uptoDate;
            }
            if(in_array(strtoupper($mUserType),["ONLINE"]))
            {
                $fromDate = $uptoDate=null;
            }
            if($request->key)
            {
                $key = trim($request->key);
            }
            if($request->wardNo)
            {
                $wardNo = $request->wardNo;
            }
            $licence = TradeLicence::select(
                    "trade_licences.id",
                    "trade_licences.application_no",
                    "trade_licences.provisional_license_no",
                    "trade_licences.license_no",
                    "trade_licences.document_upload_status",
                    "trade_licences.payment_status",
                    "trade_licences.firm_name",
                    "trade_licences.application_date",
                    "trade_licences.apply_from",
                    "owner.owner_name",
                    "owner.guardian_name",
                    "owner.mobile_no",
                    "owner.email_id",
                )
                ->join(DB::raw("(select STRING_AGG(owner_name,',') AS owner_name,
                                STRING_AGG(guardian_name,',') AS guardian_name,
                                STRING_AGG(mobile_no::TEXT,',') AS mobile_no,
                                STRING_AGG(email_id,',') AS email_id,
                                temp_id
                                FROM trade_owners 
                                WHERE is_active =TRUE
                                GROUP BY temp_id
                                )owner"), function ($join) {
                    $join->on("owner.temp_id", "trade_licences.id");
                })
                ->where("trade_licences.is_active", TRUE)
                ->where(DB::RAW("trade_licences.current_role"),"=", DB::RAW("trade_licences.finisher_role"))
                ->where("trade_licences.ulb_id", $refUlbId)
                ->whereBetween('trade_licences.application_date', [$fromDate,$uptoDate]);
            if ($key) 
            {
                $licence = $licence->where(function ($query) use ($key) {
                    $query->orwhere('trade_licences.holding_no', 'ILIKE', '%' . $key . '%')
                        ->orwhere('trade_licences.application_no', 'ILIKE', '%' . $key . '%')
                        ->orwhere("trade_licences.license_no", 'ILIKE', '%' . $key . '%')
                        ->orwhere("trade_licences.provisional_license_no", 'ILIKE', '%' . $key . '%')
                        ->orwhere('owner.owner_name', 'ILIKE', '%' . $key . '%')
                        ->orwhere('owner.guardian_name', 'ILIKE', '%' . $key . '%')
                        ->orwhere('owner.mobile_no', 'ILIKE', '%' . $key . '%');
                });
            }
            if ($wardNo && $wardNo!= "ALL") 
            {
                $mWardIds = $wardNo;
                $licence = $licence
                    ->whereIn('trade_licences.ward_id', $mWardIds);
            }
            
            if (in_array(strtoupper($mUserType), ["ONLINE"])) 
            {
                $licence = $licence
                    ->where("citizen_id", $refUserId);
            }
            $licence = $licence
                ->get();
            $data = [
                "wardList" => $mWardPermission,
                "licence" => $licence,
            ];
            return responseMsg(true, "", $data);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }
    


    #---------- core function for trade Application--------

    /**
     * |----------------------------------------------------
     * |    APN     |      2A      |     0000001            |
     * |  const(3)  | ward no(2)   | unique No id(7)        |
     * |____________________________________________________|
     * 
     */
    public function createApplicationNo($wardNo, $licenceId)
    {
        return "APN" . str_pad($wardNo, 2, '0', STR_PAD_LEFT) . str_pad($licenceId, 7, '0', STR_PAD_LEFT);
    }

    /**
     * |----------------------------------------------------------------------------------
     * |     RMC                  |    2A         | 01152022           |      1           |
     * | @var shortUlbName(3)     |  ward no(2)   | Month Date Year(8) |  unique No id    |
     * |___________________________________________________________________________________
     */
    public function createProvisinalNo($shortUlbName, $wardNo, $licenceId)
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
        $response = ['response' => false];
        try {
            $data = array();
            $inputs = $args;
            $data['area_in_sqft'] = (float)$inputs['areaSqft'];
            $data['application_type_id'] = $inputs['application_type_id'];
            $data['firm_date'] = $inputs['firmEstdDate'];
            $data['firm_date'] = date('Y-m-d', strtotime($data['firm_date']));

            $data['tobacco_status'] = $inputs['tobacco_status'] == True ? 1 : 0;
            $data['timeforlicense'] = $inputs['licenseFor'];
            $data['curdate'] = $inputs['curdate'] ?? date("Y-m-d");

            $denial_amount_month = 0;
            $count = $this->getrate($data);
            $rate = $count->rate * $data['timeforlicense'];
            $notice_amount = 0;
            if (isset($inputs['noticeDate']) && $inputs['noticeDate']) {
                $notice_amount = $this->getDenialAmountTrade($inputs['noticeDate']);
            }
            $pre_app_amount = 0;
            if (isset($data['application_type_id']) && in_array($data['application_type_id'], [1, 2])) {
                $nob = array();
                $data['nature_of_business'] = null;
                if (isset($inputs['nature_of_business']))
                    $nob = explode(',', $inputs['nature_of_business']);
                if (sizeof($nob) == 1) {
                    $data['nature_of_business'] = $nob[0];
                }

                $temp = $data['firm_date'];
                $temp2 = $data['firm_date'];
                if ($data['nature_of_business'] == 198 && strtotime($temp) <= strtotime('2021-10-30')) {
                    $temp = '2021-10-30';
                    $temp2 = $temp;
                } elseif ($data['nature_of_business'] != 198 && strtotime($temp) <= strtotime('2020-01-01')) {
                    $temp = '2020-01-01';
                }
                $data['firm_date'] = $temp;
                $diff_year = date_diff(date_create($temp2), date_create($data['curdate']))->format('%R%y');
                $pre_app_amount = ($diff_year > 0 ? $diff_year : 0) * $count->rate;
            }

            $vDiff = abs(strtotime($data['curdate']) - strtotime($data['firm_date'])); // here abs in case theres a mix in the dates
            $vMonths = ceil($vDiff / (30 * 60 * 60 * 24)); // number of seconds in a month of 30 days

            if ($vMonths > 0 && strtotime($data['firm_date']) < strtotime($data['curdate'])) {
                $denial_amount_month = 100 + (($vMonths) * 20);
            }
            # In case of ammendment no denial amount
            if ($data['application_type_id'] == 3) {
                $denial_amount_month = 0;
            }
            $total_denial_amount = $denial_amount_month + $rate + $pre_app_amount + $notice_amount;

            # Check If Any cheque bounce charges
            if (isset($inputs['apply_licence_id'], $inputs['apply_licence_id'])) {
                $penalty = $this->getChequeBouncePenalty($inputs['apply_licence_id']);
                $denial_amount_month += $penalty;
                $total_denial_amount += $penalty;
            }

            if ($count) {
                $response = ['response' => true, 'rate' => $rate, 'penalty' => $denial_amount_month, 'total_charge' => $total_denial_amount, 'rate_id' => $count['id'], 'arear_amount' => $pre_app_amount, "notice_amount" => $notice_amount];
            } else {
                $response = ['response' => false];
            }
            return $response;
        } catch (Exception $e) {
            return $response;
        }
    }
    public function readNotisDtl($id)
    {
        try {
            $data = TradeNoticeConsumerDtl::select(
                "*",
                DB::raw("trade_notice_consumer_dtls.notice_date::date AS noticedate")
            )
                ->where("id", $id)
                ->first();
            return $data;
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
    public function getDenialFirmDetails($ulb_id, $notice_no) //for apply application
    {
        try {
            DB::enableQueryLog();
            $data = TradeNoticeConsumerDtl::select(
                "trade_notice_consumer_dtls.*",
                DB::raw("trade_notice_consumer_dtls.notice_no,
                                trade_notice_consumer_dtls.notice_date::date AS noticedate,
                                trade_notice_consumer_dtls.id as dnialid")
            )
                ->where("trade_notice_consumer_dtls.notice_no", $notice_no)
                // ->where("trade_denial_notices.created_on","<",$firm_date)
                ->where("trade_notice_consumer_dtls.status", "=", 5)
                ->where("trade_notice_consumer_dtls.ulb_id", $ulb_id)
                ->first();
                // dd(DB::getQueryLog());
            return $data;
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
    public function getDenialAmountTrade($notice_date = null, $current_date = null)
    {
        $notice_date = $notice_date ? Carbon::createFromFormat("Y-m-d", $notice_date)->format("Y-m-d") : Carbon::now()->format('Y-m-d');
        $current_date = $current_date ? Carbon::createFromFormat("Y-m-d", $current_date)->format("Y-m-d") : Carbon::now()->format('Y-m-d');

        $datediff = strtotime($current_date) - strtotime($notice_date); //days difference in second
        $totalDays =   abs(ceil($datediff / (60 * 60 * 24))); // total no. of days
        $denialAmount = 100 + (($totalDays) * 10);

        return $denialAmount;
    }
    public function getAllApplicationType()
    {
        try {
            $data = TradeParamApplicationType::select("id", "application_type")
                ->where('status', '1')
                ->get();
            return $data;
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
    public function getrate(array $input) //stdcl object array
    {
        try {
            $builder = TradeParamLicenceRate::select('id', 'rate')
                ->where('application_type_id', $input['application_type_id'])
                ->where('range_from', '<=', ceil($input['area_in_sqft']))
                ->where('range_to', '>=', ceil($input['area_in_sqft']))
                ->where('effective_date', '<', $input['curdate'])
                ->where('status', 1)
                ->where('tobacco_status', $input['tobacco_status'])
                ->orderBy('effective_date', 'Desc')
                ->first();
            return $builder;
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
    public function getChequeBouncePenalty(int $apply_licence_id): float
    {
        try {

            $result = TradeChequeDtl::select(DB::raw("coalesce(sum(amount), 0) as penalty"))
                ->where("temp_id", $apply_licence_id)
                ->where("status", 3)
                ->first();
            return $result->penalty;
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
    public function propertyDetailsfortradebyHoldingNo(string $holdingNo, int $ulb_id): array
    {
        // DB::enableQueryLog();
        $property = PropProperty::select("*")
            ->leftjoin(
                DB::raw("(SELECT STRING_AGG(owner_name,',') as owner_name ,property_id
                                        FROM Prop_OwnerS 
                                        WHERE status = 1
                                        GROUP BY property_id
                                        ) owners
                                        "),
                function ($join) {
                    $join->on("owners.property_id", "=", "prop_properties.id");
                }
            )
            ->where("status", 1)
            ->where("new_holding_no", "<>", "")
            ->where("new_holding_no", "ILIKE", $holdingNo)
            ->where("ulb_id", $ulb_id)
            ->first();
        // dd(DB::getQueryLog());
        if ($property) {
            return ["status" => true, 'property' => objToArray($property)];
        }
        return ["status" => false, 'property' => ''];
    }
    public function getSafDtlBySafno(string $safNo, int $ulb_id): array
    {
        $saf = PropActiveSaf::select("*")
            ->where('status', 1)
            ->where('saf_no', $safNo)
            ->where('ulb_id', $ulb_id)
            ->first();
        if ($saf->id) {
            $owneres = PropActiveSafsOwner::select("*")
                ->where("saf_dtl_id", $saf->id)
                ->where('status', 1)
                ->get();
            return ["status" => true, 'saf' => objToArray($saf), 'owneres' => objToArray($owneres)];
        }
        return ["status" => false, 'property' => '', 'owneres' => ''];
    }
    public function updateStatusFine($denial_id, $denialAmount, $applyid, $status = 2)
    {
        $tradeNotice = TradeNoticeConsumerDtl::where("id", $denial_id)
            ->orderBy("id", "DESC")
            ->first();
        $tradeNotice->fine_amount  =  $denialAmount;
        $tradeNotice->status =  $status;
        if($applyid)
        {
            // $tradeNotice->is_active =  false;
        }
        $tradeNotice->update();
    }
    public function getLicenceById($id)
    {
        try {
            $application = TradeLicence::select(
                "trade_licences.*",
                "trade_param_application_types.application_type",
                "trade_param_category_types.category_type",
                "trade_param_firm_types.firm_type",
                "trade_param_ownership_types.ownership_type",
                DB::raw("ulb_ward_masters.ward_name AS ward_no, new_ward.ward_name as new_ward_no")
            )
                ->leftjoin("ulb_ward_masters", function ($join) {
                    $join->on("ulb_ward_masters.id", "=", "trade_licences.ward_id");
                })
                ->leftjoin("ulb_ward_masters AS new_ward", function ($join) {
                    $join->on("new_ward.id", "=", "trade_licences.new_ward_id");
                })
                ->join("trade_param_application_types", "trade_param_application_types.id", "trade_licences.application_type_id")
                ->leftjoin("trade_param_category_types", "trade_param_category_types.id", "trade_licences.category_type_id")
                ->leftjoin("trade_param_firm_types", "trade_param_firm_types.id", "trade_licences.firm_type_id")
                ->leftjoin("trade_param_ownership_types", "trade_param_ownership_types.id", "trade_licences.ownership_type_id")
                ->where('trade_licences.id', $id)
                ->first();
            return $application;
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
    public function getActiveLicenseById($id)
    {
        try {
            $application = ActiveTradeLicence::select(
                "active_trade_licences.*",
                "trade_param_application_types.application_type",
                "trade_param_category_types.category_type",
                "trade_param_firm_types.firm_type",
                "trade_param_ownership_types.ownership_type",
                DB::raw("ulb_ward_masters.ward_name AS ward_no, new_ward.ward_name as new_ward_no")
            )
                ->leftjoin("ulb_ward_masters", function ($join) {
                    $join->on("ulb_ward_masters.id", "=", "active_trade_licences.ward_id");
                })
                ->leftjoin("ulb_ward_masters AS new_ward", function ($join) {
                    $join->on("new_ward.id", "=", "active_trade_licences.new_ward_id");
                })
                ->join("trade_param_application_types", "trade_param_application_types.id", "active_trade_licences.application_type_id")
                ->leftjoin("trade_param_category_types", "trade_param_category_types.id", "active_trade_licences.category_type_id")
                ->leftjoin("trade_param_firm_types", "trade_param_firm_types.id", "active_trade_licences.firm_type_id")
                ->leftjoin("trade_param_ownership_types", "trade_param_ownership_types.id", "active_trade_licences.ownership_type_id")
                ->where('active_trade_licences.id', $id)
                ->first();
            return $application;
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
    public function getAllLicenceById($id)
    {
        try {
            $test = TradeLicence::select("id")->find($id);
            $table = "trade_licences";
            $application = TradeLicence::select(
                "trade_licences.*",
                "trade_param_application_types.application_type",
                "trade_param_category_types.category_type",
                "trade_param_firm_types.firm_type",
                "trade_param_ownership_types.ownership_type",
                DB::raw("ulb_ward_masters.ward_name AS ward_no, new_ward.ward_name as new_ward_no,ulb_masters.ulb_name, '$table' AS tbl")
            );
            if (!$test) {
                $test = RejectedTradeLicence::select("id")->find($id);                
                $table = "rejected_trade_licences";
                $application = RejectedTradeLicence::select(
                    "rejected_trade_licences.*",
                    "trade_param_application_types.application_type",
                    "trade_param_category_types.category_type",
                    "trade_param_firm_types.firm_type",
                    "trade_param_ownership_types.ownership_type",
                    DB::raw("ulb_ward_masters.ward_name AS ward_no, new_ward.ward_name as new_ward_no,ulb_masters.ulb_name,'$table' AS tbl")
                );
            }
            if (!$test) {                
                $table = "active_trade_licences";
                $application = ActiveTradeLicence::select(
                    "active_trade_licences.*",
                    "trade_param_application_types.application_type",
                    "trade_param_category_types.category_type",
                    "trade_param_firm_types.firm_type",
                    "trade_param_ownership_types.ownership_type",
                    DB::raw("ulb_ward_masters.ward_name AS ward_no, 
                    new_ward.ward_name as new_ward_no,ulb_masters.ulb_name,'$table' AS tbl")
                );
            }
            
            $application = $application
                ->leftjoin("ulb_ward_masters", function ($join) use ($table) {
                    $join->on("ulb_ward_masters.id", "=", $table . ".ward_id");
                })
                ->leftjoin("ulb_ward_masters AS new_ward", function ($join) use ($table) {
                    $join->on("new_ward.id", "=", $table . ".new_ward_id");
                })
                ->join("ulb_masters", "ulb_masters.id", $table . ".ulb_id")
                ->join("trade_param_application_types", "trade_param_application_types.id", $table . ".application_type_id")
                ->leftjoin("trade_param_category_types", "trade_param_category_types.id", $table . ".category_type_id")
                ->leftjoin("trade_param_firm_types", "trade_param_firm_types.id", $table . ".firm_type_id")
                ->leftjoin("trade_param_ownership_types", "trade_param_ownership_types.id", $table . ".ownership_type_id")
                ->where($table . '.id', $id)
                ->first();
            return $application;
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    public function getAllOwnereDtlByLId($id)
    {
        try {
            $ownerDtl   = ActiveTradeOwner::select("*")
                ->where("temp_id", $id)
                ->where("is_active", 1)
                ->get();
            if (sizeOf($ownerDtl) < 1) {
                $ownerDtl   = RejectedTradeOwner::select("*")
                    ->where("temp_id", $id)
                    ->where("is_active", 1)
                    ->get();
            }
            if (sizeOf($ownerDtl) < 1) {
                $ownerDtl   = TradeOwner::select("*")
                    ->where("temp_id", $id)
                    ->where("is_active", 1)
                    ->get();
            }
            return $ownerDtl;
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
    public function getTimelin($id)
    {
        try {
            //    DB::enableQueryLog();
            $time_line =  workflowTrack::select(
                "workflow_tracks.message",
                "workflow_tracks.forward_date",
                "workflow_tracks.forward_time",
                "workflow_tracks.receiver_role_id",
                "role_name",
                DB::raw("workflow_tracks.created_at as receiving_date")
            )
                ->leftjoin('wf_roles', "wf_roles.id", "workflow_tracks.receiver_role_id")
                ->where('workflow_tracks.ref_table_id_value', $id)
                ->where('workflow_tracks.ref_table_dot_id', "active_trade_licences")
                ->whereNotNull('workflow_tracks.sender_role_id')
                ->where('workflow_tracks.status', true)
                ->groupBy(
                    'workflow_tracks.receiver_role_id',
                    'workflow_tracks.message',
                    'workflow_tracks.forward_date',
                    'workflow_tracks.forward_time',
                    'wf_roles.role_name',
                    'workflow_tracks.created_at'
                )
                ->orderBy('workflow_tracks.created_at', 'desc')
                ->get();
            // dd(DB::getQueryLog());
            return $time_line;
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
    public function getLicenceDocuments($id, $tbl = "active_")
    {
        try {
            $doc =  TradeDocument::select(
                $tbl . "trade_documents.*",
                DB::raw("CASE WHEN " . $tbl . "trade_owners.id" . " NOTNULL 
                                    THEN CONCAT(" . $tbl . "trade_owners.owner_name,'( '," . $tbl . "trade_documents.doc_type_code,' )')
                                    ELSE " . $tbl . "trade_documents.doc_type_code END doc_type_code")
            );
            if ($tbl = "active_") {
                $doc =  ActiveTradeDocument::select(
                    $tbl . "trade_documents.*",
                    DB::raw("CASE WHEN " . $tbl . "trade_owners.id" . " NOTNULL 
                                        THEN CONCAT(" . $tbl . "trade_owners.owner_name,'( '," . $tbl . "trade_documents.doc_type_code,' )')
                                        ELSE " . $tbl . "trade_documents.doc_type_code END doc_type_code")
                );
            }
            if ($tbl = "rejected_") {
                $doc =  RejectedTradeDocument::select(
                    $tbl . "trade_documents.*",
                    DB::raw("CASE WHEN " . $tbl . "trade_owners.id" . " NOTNULL 
                                        THEN CONCAT(" . $tbl . "trade_owners.owner_name,'( '," . $tbl . "trade_documents.doc_type_code,' )')
                                        ELSE " . $tbl . "trade_documents.doc_type_code END doc_type_code")
                );
            }
            $doc =  $doc->leftjoin($tbl . "trade_owners", function ($join) use ($tbl) {
                $join->on($tbl . "trade_owners.id", $tbl . "trade_documents.temp_owner_id");
            })
                ->where($tbl . "trade_documents.temp_id", $id)
                ->orderBy($tbl . "trade_documents.id", 'desc')
                ->get();
            return $doc;
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
    public function searchLicence(string $licence_no, $ulb_id)
    {
        try {
            $data = TradeLicence::select("*")
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
                        $join->on("owner.licence_id", "=",  "active_licences.id");
                    }
                )
                ->where('is_active', true)
                ->where("ulb_id", $ulb_id)
                ->where('license_no', $licence_no)
                ->first();
            return responseMsg(true, "", remove_null($data));
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), $licence_no);
        }
    }

    public function uplodeFile($file, $custumFileName)
    {
        $filePath = $file->storeAs('uploads/Trade', $custumFileName, 'public');
        return  $filePath;
    }
    public function getDenialDetailsByID($id, $ulb_id)
    {
        try {
            $data = TradeNoticeConsumerDtl::select(
                "trade_notice_consumer_dtls.*",
                DB::raw("ulb_ward_masters.ward_name as ward_no
                        ")
            )
                ->join("ulb_ward_masters", function ($join) {
                    $join->on("ulb_ward_masters.id", "trade_denial_consumer_dtls.ward_id");
                })
                ->where("trade_notice_consumer_dtls.id", $id)
                ->where("trade_notice_consumer_dtls.ulb_id", $ulb_id)
                ->first();
            return $data;
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
    public function getDocumentTypeList($refLicense)
    {
        try {
            $show = '1';
            if ($refLicense->application_type_id == 1) {
                if ($refLicense->ownership_type_id == 1) {
                    $show .= ',' . '2';
                } else {
                    $show .= ',' . '3';
                }

                if ($refLicense->firm_type_id == 2) {
                    $show .= ',' . '4';
                } elseif ($refLicense->firm_type_id == 3 || $refLicense->firm_type_id == 4) {
                    $show .= ',' . '5';
                }
                if ($refLicense->category_type_id == 2) {
                    $show .= ',' . '6';
                }
            }
            $show = explode(",", $show);
            // DB::enableQueryLog();
            $data = TradeParamDocumentType::select("doc_for", "is_mandatory", "show")
                ->where("application_type_id", $refLicense->application_type_id)
                ->where("status", 1)
                ->whereIn("show", $show)
                ->groupBy("doc_for", "is_mandatory", "show");
            if (strtoupper($refLicense->apply_from) == "ONLINE") {
                $data = $data->where("doc_for", "<>", "Application Form");
            }
            $data =    $data->get();
            // dd(DB::getQueryLog());
            return $data;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
    public function getDocumentList($doc_for, $application_type_id, $show)
    {
        try {
            $data = TradeParamDocumentType::select("id", "doc_name")
                ->where("status", 1)
                ->where("doc_for", $doc_for)
                ->where("application_type_id", $application_type_id)
                ->where("show", $show)
                ->get();
            return $data;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
    public function check_doc_exist($licenceId, $doc_for, $doc_mstr_id = null, $woner_id = null)
    {
        try {

            $doc = ActiveTradeDocument::select("id", "doc_type_code", "is_verified", "remarks", "document_id")
                ->where('temp_id', $licenceId)
                ->where('doc_type_code', $doc_for);
            if ($doc_mstr_id) {
                $doc = $doc->where('document_id', $doc_mstr_id);
            }
            if ($woner_id) {
                $doc = $doc->where('temp_owner_id', $woner_id);
            }
            $doc = $doc->orderBy('id', 'DESC')
                ->first();
            return $doc;
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    public function check_doc_exist_owner($licenceId, $owner_id, $document_id = null, $doc_type_code = null)
    {
        try {
            // DB::enableQueryLog();
            $doc = ActiveTradeDocument::select("id", "doc_type_code", "is_verified", "remarks",  "document_id")
                ->where('temp_id', $licenceId)
                ->where('temp_owner_id', $owner_id);
            if ($doc_type_code) {
                $doc = $doc->where('doc_type_code', $doc_type_code);
            }
            if ($document_id !== null) {
                $document_id = (int)$document_id;
                $doc = $doc->where('document_id', $document_id);
            } else {
                $doc = $doc->where("document_id", "<>", 0);
            }
            $doc = $doc->orderBy('id', 'DESC')
                ->first();
            //    print_var(DB::getQueryLog());                    
            return $doc;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function readDocumentPath($path)
    {
        $path = (config('app.url') . '/api/getImageLink?path=' . $path);
        return $path;
    }
    public function applicationStatus($licenceId)
    {
        $refUser        = Auth()->user();
        $refUserId      = $refUser->id??0;
        $refUlbId       = $refUser->ulb_id ?? 0;
        $refWorkflowId  = $this->_WF_MASTER_Id ;
        $mUserType      = $this->_COMMON_FUNCTION->userType($refWorkflowId, $refUlbId);
        $application = TradeLicence::find($licenceId);
        if (!$application) 
        {
            $application = ActiveTradeLicence::find($licenceId);
        }
        if (!$application) 
        {
            $application = RejectedTradeLicence::find($licenceId);
        }
        $status = "";
        if ($application->pending_status == 5) 
        {
            $status = "License Created Successfully";
            if($application->valid_upto < Carbon::now()->format("Y-m-d"))
            {
                $status = "License Expired On ".$application->valid_upto;
            }
        } 
        elseif ($application->is_parked) 
        {
            $rols  = WfRole::find($application->current_role);
            $status = "Application back to citizen by " . $rols->role_name;
        } 
        elseif ($application->pending_status!=0 && (($application->current_role != $application->finisher_role) || ($application->current_role == $application->finisher_role))) 
        {
            $rols  = WfRole::find($application->current_role);
            $status = "Application pending at " . $rols->role_name;
        } 
        elseif (!$application->is_active) 
        {
            $status = "Application rejected ";
        } 
        elseif(strtoupper($mUserType)=="ONLINE" && $application->citizen_id == $refUserId && $application->payment_status == 0 )
        {
            $request = new Request(["applicationId"=>$licenceId,"ulb_id"=>$refUlbId,"user_id"=>$refUserId]);
            $doc_status = $this->checkWorckFlowForwardBackord($request);
            if($doc_status && $application->payment_status==0)
            {
                $status = "All Required Documents Are Uploaded But Payment is Pending ";
            }
            elseif($doc_status && $application->payment_status==1)
            {
                $status = "Pending At Counter";
            }
            elseif(!$doc_status && $application->payment_status==1)
            {
                $status = "Payment is Done But Document Not Uploaded";
            }
            elseif(!$doc_status && $application->payment_status==0)
            {
                $status = "Payment is Pending And Document Not Uploaded";
            }
        }
        elseif ($application->payment_status == 0 && $application->document_upload_status == 0) 
        {
            $status = "Payment is pending and document not uploaded ";
        } 
        elseif ($application->payment_status == 1 && $application->document_upload_status == 0) 
        {
            $status = "Payment is done but document not uploaded ";
        }
        elseif ($application->payment_status == 0 && $application->document_upload_status == 1) 
        {
            $status = "Payment is pending but document is uploaded ";
        } 
        elseif ($application->payment_status == 1 && $application->document_upload_status == 1) 
        {
            $status = "Payment is done and document is uploaded ";
        } 
        elseif ($application->payment_status == 2 && $application->document_upload_status == 1) 
        {
            $status = "Document is uploaded but Payment is not clear";
        }
        elseif ($application->payment_status == 2 && $application->document_upload_status == 0) 
        {
            $status = "Payment is not clear and document not uploaded ";
        }
        else 
        {
            $status = "Applilcation Not Appoved";
        }

        
        return $status;
    }

    public function getWorkflowTrack($licenseId)
    {
        try {
            $data = WorkflowTrack::select("*")
                ->where("ref_table_dot_id", "active_trade_licences")
                ->where("ref_table_id_value", "$licenseId")
                ->where("status", 1)
                ->whereNotNull("sender_role_id")
                ->where("verification_status", 0)
                ->orderBy("id", "DESC")
                ->first();
            return $data;
        } catch (Exception $e) {
            return [];
        }
    }
    public function insertWorkflowTrack(array $arg)
    {
        try {
            $WorkflowTrack = new WorkflowTrack;
            $WorkflowTrack->workflow_id      = $arg["workflow_id"] ?? null;
            $WorkflowTrack->citizen_id       = $arg["citizen_id"] ?? null;
            $WorkflowTrack->ref_table_dot_id = $arg["ref_table_dot_id"] ?? null;
            $WorkflowTrack->ref_table_id_value = $arg["ref_table_id_value"] ?? null;
            $WorkflowTrack->message         = $arg["message"] ?? null;
            $WorkflowTrack->track_date      = $arg["track_date"] ?? null;
            $WorkflowTrack->forward_date    = $arg["forward_date"] ?? null;
            $WorkflowTrack->module_id       = $arg["module_id"] ?? null;
            $WorkflowTrack->user_id         = $arg["user_id"] ?? null;
            $WorkflowTrack->sender_role_id  = $arg["sender_role_id"] ?? null;
            $WorkflowTrack->receiver_role_id = $arg["receiver_role_id"] ?? null;
            $WorkflowTrack->verification_status = $arg["verification_status"] ?? 0;
            $WorkflowTrack->forward_time    = $arg["forward_time"] ?? null;
            $WorkflowTrack->save();
            $i = $WorkflowTrack->id;
            return $i;
        } catch (Exception $e) {
        }
    }
    
    public function checkWorckFlowForwardBackord(Request $request)
    {
        $user = Auth()->user();
        $user_id = $user->id??$request->user_id;
        $ulb_id = $user->ulb_id ?? $request->ulb_id;
        $refWorkflowId = $this->_WF_MASTER_Id ;
        $allRolse = collect($this->_COMMON_FUNCTION->getAllRoles($user_id,$ulb_id,$refWorkflowId,0,true));
        $init_finish = $this->_COMMON_FUNCTION->iniatorFinisher($user_id,$ulb_id,$refWorkflowId);
        $mUserType      = $this->_COMMON_FUNCTION->userType($refWorkflowId,$ulb_id);
        $fromRole =[];
        if(!empty($allRolse))
        {
            $fromRole = array_values(objToArray($allRolse->where("id",$request->senderRoleId)))[0]??[];       

        }
        if(strtoupper($mUserType)=="ONLINE" || ($fromRole["can_upload_document"]??false) ||  ($fromRole["can_verify_document"] ??false))
        {
            $documents = $this->getLicenseDocLists($request);
            if(!$documents->original["status"])
            {
                return false;
            }
            $applicationDoc = $documents->original["data"]["listDocs"];
            $ownerDoc = $documents->original["data"]["ownerDocs"];
            $appMandetoryDoc = $applicationDoc->whereIn("docType",["R","OR"]);
            $appUploadedDoc = $applicationDoc->whereNotNull("uploadedDoc");
            $appUploadedDocVerified = collect();
            $appUploadedDoc->map(function($val) use($appUploadedDocVerified){   
                $appUploadedDocVerified->push(["is_docVerify"=>(!empty($val["uploadedDoc"]) ?  (((collect($val["uploadedDoc"])->all())["verifyStatus"] ) ? true : false ) :true)]);             
                
            });
            $is_appUploadedDocVerified = $appUploadedDocVerified->where("is_docVerify",false);            
            $is_appMandUploadedDoc  = $appMandetoryDoc->whereNull("uploadedDoc");
            $Wdocuments = collect();
            $ownerDoc->map(function($val) use($Wdocuments){                
                $ownerId = $val["ownerDetails"]["ownerId"]??"";           
                $val["documents"]->map(function($val1)use($Wdocuments,$ownerId){
                    $val1["ownerId"] = $ownerId;
                    $val1["is_uploded"] = (in_array($val1["docType"],["R","OR"]))  ? ((!empty($val1["uploadedDoc"])) ? true : false ) :true;
                    $val1["is_docVerify"] = !empty($val1["uploadedDoc"]) ?  (((collect($val1["uploadedDoc"])->all())["verifyStatus"] ) ? true : false ) :true;
                    $Wdocuments->push($val1);
                });
            });
            $ownerMandetoryDoc = $Wdocuments->whereIn("docType",["R","OR"]);            
            $is_ownerUploadedDoc = $Wdocuments->where("is_uploded",false);
            $is_ownerDocVerify = $Wdocuments->where("is_docVerify",false);
            
            if(($fromRole["can_upload_document"]??false) || strtoupper($mUserType)=="ONLINE")
            {
                return (empty($is_ownerUploadedDoc->all()) && empty($is_appMandUploadedDoc->all()));
            }
            if($fromRole["can_verify_document"]??false)
            {                
                return (empty($is_ownerDocVerify->all()) && empty($is_appUploadedDocVerified->all()));
            }
        }
        return true;

    }
    #-------------------- End core function of core function --------------

    public function getLicenseDocLists(Request $request)
    {
        $request->validate([
            'applicationId' => 'required|numeric'
        ]);
        try{
            $refApplication = ActiveTradeLicence::find($request->applicationId);
            if (!$refApplication)
            {
                throw new Exception("Application Not Found for this id");
            }
            $refOwners = ActiveTradeOwner::owneresByLId($request->applicationId);
            $DocsType['listDocs'] = $this->getApplTypeDocList($refApplication); 
            $DocsType['ownerDocs'] = collect($refOwners)->map(function ($owner) use ($refApplication) {
                return $this->getOwnerDocLists($owner, $refApplication);
            }); 
            $status =  $this->check($DocsType);
            $DocsType['docUploadStatus'] = ($status["docUploadStatus"]??false)?1:0;
            $DocsType['docVerifyStatus'] = ($status["docVerifyStatus"] ??false)?1:0;
            return responseMsgs(true, "Documents Fetched", $DocsType, "010203", "1.0", "", 'POST', "");
        }
        catch(Exception $e)
        {
            return responseMsgs(false, $e->getMessage(), "", "010203", "1.0", "", 'POST', "");
        }
    }

    public function check($documentsList)
    {
            $applicationDoc = $documentsList["listDocs"];
            $ownerDoc = $documentsList["ownerDocs"];
            $appMandetoryDoc = $applicationDoc->whereIn("docType",["R","OR"]);
            $appUploadedDoc = $applicationDoc->whereNotNull("uploadedDoc");
            $appUploadedDocVerified = collect();
            $appUploadedDoc->map(function($val) use($appUploadedDocVerified){   
                $appUploadedDocVerified->push(["is_docVerify"=>(!empty($val["uploadedDoc"]) ?  (((collect($val["uploadedDoc"])->all())["verifyStatus"] !=0 ) ? true : false ) :true)]);             
                
            });
            $is_appUploadedDocVerified = $appUploadedDocVerified->where("is_docVerify",false);            
            $is_appMandUploadedDoc  = $appMandetoryDoc->whereNull("uploadedDoc");
            $Wdocuments = collect();
            $ownerDoc->map(function($val) use($Wdocuments){                
                $ownerId = $val["ownerDetails"]["ownerId"]??"";           
                $val["documents"]->map(function($val1)use($Wdocuments,$ownerId){
                    $val1["ownerId"] = $ownerId;
                    $val1["is_uploded"] = (in_array($val1["docType"],["R","OR"]))  ? ((!empty($val1["uploadedDoc"])) ? true : false ) :true;
                    $val1["is_docVerify"] = !empty($val1["uploadedDoc"]) ?  (((collect($val1["uploadedDoc"])->all())["verifyStatus"] !=0 ) ? true : false ) :true;
                    $Wdocuments->push($val1);
                });
            });
            $ownerMandetoryDoc = $Wdocuments->whereIn("docType",["R","OR"]);            
            $is_ownerUploadedDoc = $Wdocuments->where("is_uploded",false);
            $is_ownerDocVerify = $Wdocuments->where("is_docVerify",false);
            $data =[
                "docUploadStatus"=>0,
                "docVerifyStatus"=>0,
            ];
            $data["docUploadStatus"] = (empty($is_ownerUploadedDoc->all()) && empty($is_appMandUploadedDoc->all()));
            $data["docVerifyStatus"] =  (empty($is_ownerDocVerify->all()) && empty($is_appUploadedDocVerified->all()));
            return($data);
    }

}
