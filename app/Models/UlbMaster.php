<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Config;

class UlbMaster extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * | Get Ulbs by district code
     */
    public function getUlbsByDistrictCode($districtCode)
    {
        return UlbMaster::where('district_code', $districtCode)
            ->get();
    }

    /**
     * | Get Ulb Details
     */
    public function getUlbDetails($ulbId): array
    {
        $docBaseUrl = Config::get('app.url');
        $ulb = UlbMaster::where('id', $ulbId)
            ->first();
        if (collect($ulb)->isEmpty())
            throw new Exception("Ulb Not Found");
        return [
            "ulbName" => $ulb->ulb_name,
            "logo" => $docBaseUrl . "/" . $ulb->logo,
            "shortName" => $ulb->short_name,
            "tollFreeNo" => $ulb->toll_free_no,
            "hindiName" => $ulb->hindi_name,
            "currentWebsite" => $ulb->current_website,
            "parentWebsite" => $ulb->parent_website,
        ];
    }
}
