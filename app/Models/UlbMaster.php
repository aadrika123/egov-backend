<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

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
        $ulb = DB::table('ulb_masters as u')
            ->select('u.*', 'd.district_name', 's.name as state_name')
            ->join('district_masters as d', 'd.district_code', '=', 'u.district_code')
            ->join('m_states as s', 's.id', '=', 'u.state_id')
            ->where('u.id', $ulbId)
            ->first();
        if (collect($ulb)->isEmpty())
            throw new Exception("Ulb Not Found");
        return [
            "ulb_name" => $ulb->ulb_name,
            "district" => $ulb->district_name,
            "state" => $ulb->state_name,
            "address" => $ulb->address,
            "mobile_no" => $ulb->mobile_no,
            "website" => $ulb->current_website,
            "email" => $ulb->email,
            "state_logo" => $docBaseUrl . "/" . $ulb->logo,
            "ulb_logo" => $docBaseUrl . "/" . $ulb->logo,
            "ulb_parent_website" => $ulb->parent_website,
            "shortName" => $ulb->short_name,
            "tollFreeNo" => $ulb->toll_free_no,
            "hindiName" => $ulb->hindi_name,
            "currentWebsite" => $ulb->current_website,
            "parentWebsite" => $ulb->parent_website,
        ];
    }
}
