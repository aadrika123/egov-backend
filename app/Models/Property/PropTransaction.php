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

}
