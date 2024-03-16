<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MPropMultiFactor extends Model
{
    use HasFactory;

    /**
     * | Get Multi Factors by usage type
     */
    public function getMultiFactorsByUsageType($usageTypeId)
    {
        return MPropMultiFactor::where('usage_type_id', $usageTypeId)
            ->where('status', 1)
            ->get();
    }

    /**
     * | Get All Multi Factors
     */
    public function multiFactorsLists()
    {
        return MPropMultiFactor::where('status', 1)
            ->get();
    }

     //written by prity pandey

     public function getById($req)
     {
         $list = MPropMultiFactor::select(
             'id',
             'usage_type_id',
             'multi_factor',
             'effective_date',
             'com_apt_main',
             'res_apt_other',
             'com_apt_other',
             'res_pakka_main',
             'com_pakka_main',
             'res_pakka_other',
             'com_pakka_other',
             'res_kuccha_main',
             'com_kuccha_main',
             'res_kuccha_other',
             'com_kuccha_other'
 
 
         )
             ->where('id', $req->id)
             ->first();
         return $list;
     }
 
 
     public function listMPropMultiFactor()
     {
         $list = MPropMultiFactor::select(
             'id',
             'ulb_id',
             'ward_no',
             'res_apt_main',
             'com_apt_main',
             'res_apt_other',
             'com_apt_other',
             'res_pakka_main',
             'com_pakka_main',
             'res_pakka_other',
             'com_pakka_other',
             'res_kuccha_main',
             'com_kuccha_main',
             'res_kuccha_other',
             'com_kuccha_other'
         )
             ->orderBy('id', 'asc')
             ->get();
         return $list;
     }
}
