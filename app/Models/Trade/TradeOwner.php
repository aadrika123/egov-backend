<?php

namespace App\Models\Trade;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TradeOwner extends TradeParamModel
{
    use HasFactory;
    public $timestamps = false;
    protected $connection;
    public function __construct($DB=null)
    {
        parent::__construct($DB);
    }

    public function application()
    {
        return $this->belongsTo(TradeLicence::class,'temp_id',"id");
    }

    public function renewalApplication()
    {
        return $this->belongsTo(TradeRenewal::class,'temp_id',"id");
    }

    public function docDtl()
    {
        return $this->hasManyThrough(WfActiveDocument::class,TradeLicence::class,'id',"active_id","temp_id","id")
                ->whereColumn("wf_active_documents.workflow_id","trade_licences.workflow_id")
                ->where("wf_active_documents.owner_dtl_id",$this->id)
                ->where("wf_active_documents.status",1);
    }

    public function renewalDocDtl()
    {
        return $this->hasManyThrough(WfActiveDocument::class,TradeRenewal::class,'id',"active_id","temp_id","id")
                ->whereColumn("wf_active_documents.workflow_id","trade_renewals.workflow_id")
                ->where("wf_active_documents.owner_dtl_id",$this->id)
                ->where("wf_active_documents.status",1);
    }

    public static function owneresByLId($licenseId)
    {
        return self::select("*")
            ->where("temp_id", $licenseId)
            ->where("is_active", True)
            ->get();
    }

    public function getFirstOwner($licenseId)
    {
        return self::select('owner_name', 'mobile_no')
            ->where('temp_id', $licenseId)
            ->where('is_active', true)
            ->first();
    }

    /* 
    | Get owner details by filter conditions
    | @param array $filterConditions
    | created by alok
    */
    public function getOwnerDetails($filterConditions)
    {
        $query = self::select(
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

            DB::raw('trade_licences.address as firm_address'),
            DB::raw("CASE WHEN trade_owners.is_active = 'true' THEN 'Active' ELSE 'Inactive' END AS status")
        )
        ->join('trade_licences', 'trade_licences.trade_id', '=', 'trade_owners.id')
        ->leftJoin('ulb_masters', 'ulb_masters.id', '=', 'trade_licences.ulb_id');
        
        // Apply filter conditions
        foreach ($filterConditions as $condition) {
            $query->orWhere($condition[0], $condition[1], $condition[2]); 
        }
    
        $query->where('trade_licences.ulb_id', '=', 2);
        $query->where('trade_owners.is_active', '=', true);
    
        return $query->groupBy(
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
            'trade_licences.address'
        )->get();
    }
}
