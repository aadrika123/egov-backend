<?php

namespace App\Models\Trade;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradeLicence extends Model
{
    use HasFactory;

    public function owneres()
    {
        return $this->hasMany(TradeOwner::class,'temp_id',"id");
    }

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
}
