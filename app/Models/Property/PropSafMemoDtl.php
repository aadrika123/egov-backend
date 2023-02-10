<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropSafMemoDtl extends Model
{
    use HasFactory;
    protected $guarded = [];

    /**
     * | Post SAF Memo Dtls
     */
    public function postSafMemoDtls($req)
    {
        $metaReqs = [
            'saf_id' => $req->saf_id,
            'from_qtr' => $req->qtr,
            'from_fyear' => $req->fyear,
            'arv' => $req->arv,
            'quarterly_tax' => $req->amount,
            'user_id' => authUser()->id,
            'memo_no' => $req->sam_no,
            'memo_type' => $req->memo_type,
            'holding_no' => $req->holding_no,
            'prop_id' => $req->prop_id ?? null,
            'ward_mstr_id' => $req->ward_id,
        ];
        PropSafMemoDtl::create($metaReqs);
    }
}
