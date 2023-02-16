<?php

namespace App\Models\Property;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PropTransaction extends Model
{
    use HasFactory;
    protected $guarded = [];

    /***
     * | @param id 
     * | @param key saf id or property id
     */
    public function getPropTransactions($id, $key)
    {
        return PropTransaction::where("$key", $id)
            ->get();
    }

    /**
     * | Get PropTran By tranno property id
     */
    public function getPropByTranPropId($tranNo)
    {
        return PropTransaction::where('tran_no', $tranNo)
            ->firstOrFail();
    }

    // getPropTrans as trait function on current object
    public function getPropTransTrait()
    {
        return DB::table('prop_transactions')
            ->select('prop_transactions.*', 'a.saf_no', 'p.holding_no')
            ->leftJoin('prop_active_safs as a', 'a.id', '=', 'prop_transactions.saf_id')
            ->leftJoin('prop_properties as p', 'p.id', '=', 'prop_transactions.property_id')
            ->where('prop_transactions.status', 1);
    }

    // Get Property Transaction by User Id
    public function getPropTransByUserId($userId)
    {
        return $this->getPropTransTrait()
            ->where('prop_transactions.user_id', $userId)
            ->orderByDesc('prop_transactions.id')
            ->get();
    }

    // Get Property Transaction by SAF Id
    public function getPropTransBySafId($safId)
    {
        return $this->getPropTransTrait()
            ->where('prop_transactions.saf_id', $safId)
            ->orderByDesc('prop_transactions.id')
            ->first();
    }

    // Get Property Transaction by Property ID
    public function getPropTransByPropId($propId)
    {
        return $this->getPropTransTrait()
            ->where('prop_transactions.property_id', $propId)
            ->orderByDesc('prop_transactions.id')
            ->first();
    }

    // Save Property Transactions
    public function store($req)
    {
        $tranDate = Carbon::now()->format('Y-m-d');
        $metaReqs = [
            'saf_id' => $req->id,
            'amount' => $req->amount,
            'tran_date' => $tranDate,
            'tran_no' => $req->transactionNo,
            'payment_mode' => $req->paymentMode,
            'user_id' => $req->userId,
        ];
        return PropTransaction::insertGetId($metaReqs);
    }

    public function getAllData()
    {
        return PropTransaction::select('*')
            ->where('payment_mode', '!=', 'ONLINE')
            ->get();
    }

    public function postPropTransactions($req, $demands)
    {
        $propTrans = new PropTransaction();
        $propTrans->property_id = $req['id'];
        $propTrans->amount = $req['amount'];
        $propTrans->tran_date = $req['todayDate'];
        $propTrans->tran_no = $req['tranNo'];
        $propTrans->payment_mode = $req['paymentMode'];
        $propTrans->user_id = $req['userId'];
        $propTrans->ulb_id = $req['ulbId'];
        $propTrans->from_fyear = collect($demands)->last()['fyear'];
        $propTrans->to_fyear = collect($demands)->first()['fyear'];
        $propTrans->from_qtr = collect($demands)->last()['qtr'];
        $propTrans->to_qtr = collect($demands)->first()['qtr'];
        $propTrans->demand_amt = collect($demands)->sum('balance');
        $propTrans->save();

        return [
            'id' => $propTrans->id
        ];
    }


    /**
     * | public function Get Transaction Full Details by TranNo
     */
    public function getPropTransFullDtlsByTranNo($tranNo)
    {
        return DB::table('prop_transactions as t')
            ->select(
                't.*',
                'd.prop_demand_id',
                'd.total_demand',
                'pd.arv',
                'pd.qtr',
                'pd.holding_tax',
                'pd.water_tax',
                'pd.education_cess',
                'pd.health_cess',
                'pd.latrine_tax',
                'pd.additional_tax',
                'pd.amount',
                'pd.balance',
                'pd.fyear',
                'pd.due_date'
            )
            ->join('prop_tran_dtls as d', 'd.tran_id', '=', 't.id')
            ->join('prop_demands as pd', 'pd.id', '=', 'd.prop_demand_id')
            ->where('t.tran_no', $tranNo)
            ->where('pd.status', 1)
            ->orderBy('pd.due_date')
            ->get();
    }

    /**
     * | Cheque Dtl And Transaction Dtl
     */
    public function chequeTranDtl($ulbId)
    {
        return PropTransaction::select(
            'prop_cheque_dtls.*',
            'tran_date',
            'tran_no',
            'payment_mode',
            'amount',
            "cheque_date",
            "bank_name",
            "branch_name",
            "bounce_status",
            "cheque_no",
            "clear_bounce_date",
            // "user_name"
        )
            ->leftjoin('prop_cheque_dtls', 'prop_cheque_dtls.transaction_id', 'prop_transactions.id')
            // ->join('users', 'users.id', 'prop_cheque_dtls.user_id')
            ->where('payment_mode', 'DD')
            ->orWhere('payment_mode', 'CHEQUE')
            ->where('prop_transactions.ulb_id', $ulbId);
    }
}
