<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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

    /**
     * | Get memo list by Safid
     */
    public function memoLists($safId)
    {
        return DB::table('prop_saf_memo_dtls as m')
            ->select(
                'm.id',
                'm.saf_id',
                'm.from_qtr',
                'm.from_fyear',
                'm.arv',
                'm.quarterly_tax',
                'm.user_id',
                'm.memo_no',
                'm.memo_type',
                'm.holding_no',
                'm.prop_id',
                DB::raw("(to_char(m.created_at::timestamp,'yyyy-mm-dd HH:MI')) as memo_date"),
                'u.user_name as generated_by'
            )
            ->where('saf_id', $safId)
            ->join('users as u', 'u.id', '=', 'm.user_id')
            ->where('status', 1)
            ->get();
    }
}
