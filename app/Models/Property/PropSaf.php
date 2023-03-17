<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropSaf extends Model
{
    use HasFactory;

    /**
     * | Get citizen safs
     */
    public function getCitizenSafs($citizenId, $ulbId)
    {
        return PropSaf::select('id', 'saf_no', 'citizen_id')
            ->where('citizen_id', $citizenId)
            ->where('ulb_id', $ulbId)
            ->get();
    }


    /**
     * | 
     */
    public function getpropLatLongDetails($wardId)
    {
        return PropSaf::select(
            'prop_safs.id as saf_id',
            'prop_saf_geotag_uploads.id as geo_id',
            'prop_safs.holding_no',
            'prop_safs.prop_address',
            'prop_saf_geotag_uploads.latitude',
            'prop_saf_geotag_uploads.longitude'
        )
            ->leftjoin('prop_saf_geotag_uploads', 'prop_saf_geotag_uploads.saf_id', '=', 'prop_safs.id')
            ->where('prop_safs.ward_mstr_id', $wardId)
            ->orderByDesc('prop_safs.id')
            ->get();
    }
}
