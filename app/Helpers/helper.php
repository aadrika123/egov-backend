<?php

namespace App\Helpers;

use App\Models\Param;

/**
 * Our Helper Functions
 */

class helper
{

    // Autogenerating Renewal IDs for all Modules 
    public function getNewRenewalID($pre)
    {
        $x = Param::where('id', '1')->first();

        if ($pre == 'SF') {
            $str = $x->self_ad_prefix;
            $counter = $x->self_ad_counter;
            $str = $x->self_ad_prefix;
            $counter = $x->self_ad_counter;
            $x->self_ad_counter = $counter + 1;
        } elseif ($pre == 'VH') {
            $str = $x->vehicle_prefix;
            $counter = $x->vehicle_counter;
            $str = $x->vehicle_prefix;
            $counter = $x->vehicle_counter;
            $x->vehicle_counter = $counter + 1;
        } elseif ($pre == 'PL') {
            $str = $x->land_prefix;
            $counter = $x->land_counter;
            $str = $x->land_prefix;
            $counter = $x->land_counter;
            $x->land_counter = $counter + 1;
        } elseif ($pre == 'BQ') {
            $str = $x->banquet_prefix;
            $counter = $x->BanquetCounter;
            $str = $x->banquet_prefix;
            $counter = $x->BanquetCounter;
            $x->BanquetCounter = $counter + 1;
        } elseif ($pre == 'LD') {
            $str = $x->lodge_prefix;
            $counter = $x->LodgeCounter;
            $str = $x->lodge_prefix;
            $counter = $x->LodgeCounter;
            $x->LodgeCounter = $counter + 1;
        } elseif ($pre == 'DH') {
            $str = $x->dharmasala_prefix;
            $counter = $x->dharmasala_counter;
            $str = $x->dharmasala_prefix;
            $counter = $x->dharmasala_counter;
            $x->dharmasala_counter = $counter + 1;
        } elseif ($pre == 'AG') {
            $str = $x->agency_prefix;
            $counter = $x->AgencyCounter;
            $str = $x->agency_prefix;
            $counter = $x->AgencyCounter;
            $x->AgencyCounter = $counter + 1;
        } elseif ($pre == 'HRD') {
            $str = $x->hoarding_prefix;
            $counter = $x->hoarding_counter;
            $str = $x->hoarding_prefix;
            $counter = $x->hoarding_counter;
            $x->hoarding_counter = $counter + 1;
        }

        $month = date("Ym");
        $m = substr($month, 2);
        $strToSave = $str . $m . str_pad($counter, 5, "0", STR_PAD_LEFT);
        $x->save();
        return $strToSave;
    }
}
