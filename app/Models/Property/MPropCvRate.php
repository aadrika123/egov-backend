<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MPropCvRate extends Model
{
    use HasFactory;
    //written by prity pandey

    /**
     * | Get MPropCvRate By ID
     */
    public function getById($req)
    {
        $list = MPropCvRate::select(
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
            ->where('id', $req->id)
            ->first();
        return $list;
    }

    /**
     * | Get MPropCvRate By ULB ID
     */
    public function listMPropCvRate()
    {
        $list = MPropCvRate::select(
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
