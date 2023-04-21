<?php

namespace App\Models\Trade;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradeLicence extends Model
{
    use HasFactory;

    public function getTradeIdByLicenseNo($licenseNo)
    {
        return TradeLicence::select('id')
            ->where('license_no', $licenseNo)
            ->firstOrFail();
    }
}
