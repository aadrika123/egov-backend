<?php

namespace App\Models\Payment;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RevDailycollection extends Model
{
    use HasFactory;

    protected $fillable = ["tran_no", "user_id", "deposit_date", "ulb_id", "demand_date", "tc_id"];

    /**
     * |
     */
    public function store($req)
    {
        $req = $req->toarray();
        $revDailyCollections =  RevDailycollection::create($req);
        return $revDailyCollections->id;
    }

    /**
     * | collection Dtl
     */
    public function collectionDetails($ulbId)
    {
        return RevDailycollection::select(
            'rev_dailycollections.id',
            'u.user_name as tc_name',
            'u.mobile as tc_mobile',
            'uu.user_name as verifier_name',
            'uu.mobile as verifier_mobile',
            'tran_no',
            'deposit_amount as amount',
            'module_id',
            'deposit_date as tran_date',
            'deposit_mode as payment_mode',
            'cheq_dd_no as cheque_dd_no',
            'bank_name',
            'application_no'
        )
            ->join('rev_dailycollectiondetails as rdc', 'rdc.collection_id', 'rev_dailycollections.id')
            ->join('users as u', 'u.id', 'rev_dailycollections.tc_id')
            ->join('users as uu', 'uu.id', 'rev_dailycollections.user_id')
            ->where('rev_dailycollections.ulb_id', $ulbId);
    }
}
