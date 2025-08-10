<?php

namespace App\Models\Trade;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TradeLicence extends TradeParamModel
{
    use HasFactory;
    protected $connection;
    public function __construct($DB = null)
    {
        parent::__construct($DB);
    }

    # one to one
    public function cotegoryType()
    {
        return $this->hasOne(TradeParamCategoryType::class, 'id', "category_type_id");
    }

    public function FirmType()
    {
        return $this->hasOne(TradeParamFirmType::class, 'id', "firm_type_id");
    }

    public function applicationType()
    {
        return $this->hasOne(TradeParamApplicationType::class, 'id', "application_type_id");
    }

    public function ownershipeType()
    {
        return $this->hasOne(TradeParamOwnershipType::class, 'id', "ownership_type_id");
    }

    public function noticeDtl()
    {
        return $this->hasOne(TradeNoticeConsumerDtl::class, 'id', "denial_id");
    }
    # end one to one
    # one to many
    public function owneres()
    {
        return $this->hasMany(TradeOwner::class, 'temp_id', "id");
    }
    // public function itemType()
    // {
    //     return $this->hasMany(TradeParamItemType::class,'id',"nature_of_bussiness");
    // }

    public function transactionDtl()
    {
        return $this->hasMany(TradeTransaction::class, 'temp_id', "id")->whereNotIn("status", [0, 3]);
    }

    public function chequenDtl()
    {
        return $this->hasMany(TradeChequeDtl::class, 'temp_id', "id");
    }

    public function docDtl()
    {
        return $this->hasMany(WfActiveDocument::class, 'active_id', "id")
            ->where("wf_active_documents.workflow_id", $this->workflow_id)
            ->where("wf_active_documents.status", 1);
    }

    public function razorPayRequest()
    {
        return $this->hasMany(TradeRazorPayRequest::class, 'temp_id', "id");
    }

    public function razorPayResonse()
    {
        return $this->hasMany(TradeRazorPayResponse::class, 'temp_id', "id");
    }
    # end one to many
    # one to many through
    public function tranChequenDtl()
    {
        return $this->hasManyThrough(TradeChequeDtl::class, TradeTransaction::class, 'temp_id', "tran_id", "id");
    }

    public function fineRebateDtl()
    {
        return $this->hasManyThrough(TradeFineRebete::class, TradeTransaction::class, 'temp_id', "tran_id", "id")
            ->where("trade_fine_rebetes.status", 1);
    }

    # end one to many through

    public function getTradeIdByLicenseNo($licenseNo)
    {
        return TradeLicence::select('id')
            ->where('license_no', $licenseNo)
            ->first();
    }

    public function getTradeDtlsByLicenseNo($licenseNo)
    {
        return TradeLicence::where('license_no', $licenseNo)
            ->select(
                'id',
                'premises_owner_name',
                'address',
                'establishment_date',
                'application_no',
                'provisional_license_no',
                'application_date',
                'license_no',
                'license_date',
                'valid_from',
                'valid_upto',
                'firm_name'
            )
            ->firstOrFail();
    }

    /* 
     | Get owner details by filter conditions
     | @param array $ulbId
     | created by alok
     */
    public function getOwnerDetails($parameter, $key)
    {
        return self::select(
            'trade_owners.id',
            'trade_licences.trade_id',
            'trade_owners.owner_name',
            'trade_owners.guardian_name',
            'trade_owners.address',
            'trade_owners.mobile_no',
            'trade_licences.holding_no',
            'trade_licences.application_no',
            'trade_licences.license_no',
            'trade_licences.firm_name',
            'trade_licences.ulb_id',
            'ulb_masters.ulb_name',
            'trade_licences.address as firm_address',
            DB::raw("CASE WHEN trade_owners.is_active = 'true' THEN 'Active' ELSE 'Inactive' END AS status")
        )
            ->join('trade_owners', 'trade_owners.temp_id', '=', 'trade_licences.id')
            ->leftJoin('ulb_masters', 'ulb_masters.id', '=', 'trade_licences.ulb_id')
            ->where($key, $parameter);
    }
    public function getTradeHolding($refrenceNo)
    {
        return TradeLicence::select('id', 'license_no')
            ->where('holding_no', $refrenceNo)
            ->first();
    }
}
